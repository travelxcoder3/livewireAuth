<?php
// app/Http/Controllers/Agency/Reports/CustomerAccountPdfController.php
namespace App\Http\Controllers\Agency\Reports;

use App\Http\Controllers\Controller;
use App\Livewire\Agency\Reports\CustomerAccountDetails as Cmp;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class CustomerAccountPdfController extends Controller
{
    public function __invoke($id)
    {
        // استدعاء نفس منطق Livewire لضمان تطابق الأرقام
        /** @var Cmp $cmp */
        $cmp = app(Cmp::class);
        $cmp->mount($id);

        $financials         = $cmp->calculateFinancials();
        $sortedTransactions = collect($financials['rows']);
        $customer           = $cmp->customer;
        $currency           = $financials['currency'];

        $accountNumber = Customer::where('agency_id', $customer->agency_id)
            ->orderBy('created_at')->pluck('id')->search($customer->id) + 1;

        $html = view('pdf.customer-account-details', [
            'customer'      => $customer,
            'currency'      => $currency,
            'accountNumber' => $accountNumber,
            'sortedTransactions' => $sortedTransactions,
            'activeSales'   => $financials['active_sales'],
            'netPayments'   => $financials['net_payments'],
            'netBalance'    => $financials['net_balance'],
            'availableBalanceToPayOthers' => $financials['available_balance'],
        ])->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(14, 10, 14, 10)
            ->emulateMedia('screen')
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->setOption('args', ['--no-sandbox'])
            ->pdf();

        return response()->streamDownload(
            fn() => print($pdf),
            'customer-account-details-'.$customer->id.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
