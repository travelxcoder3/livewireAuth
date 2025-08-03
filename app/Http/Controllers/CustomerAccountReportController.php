<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Collection;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\View;

class CustomerAccountReportController extends Controller
{
    public function generatePdf($id)
    {
        $customer = Customer::with('agency')->findOrFail($id);
        $sales = Sale::where('customer_id', $id)->get();
        $collections = Collection::whereHas('sale', function ($q) use ($id) {
            $q->where('customer_id', $id);
        })->get();
        $html = View::make('reports.customer-full-account-pdf', compact('customer', 'sales', 'collections'))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(10, 10, 10, 10)
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="customer-account.pdf"');
    }
}
