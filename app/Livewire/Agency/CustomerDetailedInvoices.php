<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Models\Customer;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class CustomerDetailedInvoices extends Component
{
    use WithPagination;

    // ✅ Livewire v3: مزامنة مع الرابط
    #[Url(as: 'search', history: true, keep: true)]
    public string $search = '';

    // ✅ ارجع للصفحة الأولى قبل ما يتطبّق الفلتر
    public function updatingSearch($value)
    {
        $this->resetPage();
    }

    public function showInvoiceDetails($id)
    {
        return redirect()->route('agency.customer-invoice-overview', ['customer' => $id]);
    }

    private function arNormalize(string $s): string
    {
        $tashkeel = ['َ','ً','ُ','ٌ','ِ','ٍ','ْ','ّ','ٰ'];
        $s = str_replace($tashkeel, '', $s);

        $s = str_replace(['أ','إ','آ','ٱ'], 'ا', $s);
        $s = str_replace(['ؤ'], 'و', $s);
        $s = str_replace(['ئ'], 'ي', $s);
        $s = str_replace(['ء'], 'ي', $s);      // أو '' لو تفضّل حذفها تمامًا
        $s = str_replace('ى', 'ي', $s);
        $s = str_replace('ة', 'ه', $s);

        $s = strtr($s, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);

        return trim(mb_strtolower($s));
    }


    public function render()
    {
        $term = trim((string) $this->search);

        $customers = Customer::where('agency_id', Auth::user()->agency_id)
            ->when($term !== '', function ($q) use ($term) {

                // 1) طبّع نص البحث في PHP
                $norm = $this->arNormalize($term);

                // 2) ابنِ عبارة SQL لتطبيع العمود name بسلسلة REPLACE سليمة تركيبياً
                $map = [
                    'َ' => '', 'ً' => '', 'ُ' => '', 'ٌ' => '', 'ِ' => '', 'ٍ' => '', 'ْ' => '', 'ّ' => '',
                    'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
                    'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي', 'ء' => 'ي',
                ];

                $sql = "LOWER(name)";            // نبدأ بـ LOWER(name)
                foreach ($map as $from => $to) { // نبني REPLACE بشكل متداخل مضمون الأقواس
                    $from = addslashes($from);
                    $to   = addslashes($to);
                    $sql  = "REPLACE($sql, '$from', '$to')";
                }
                // الآن $sql صار مثل REPLACE(REPLACE(...LOWER(name)...), ...)

                // 3) ابحث باستخدام LIKE على النص المُطبّع
                $q->whereRaw("$sql LIKE ?", ['%' . $norm . '%']);
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.agency.customer-detailed-invoices', [
            'customers' => $customers,
        ]);
    }

}
