<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class ProviderDetailedInvoices extends Component
{
    use WithPagination;

    #[Url(as: 'search', history: true, keep: true)]
    public string $search = '';

    public function updatingSearch($value)
    {
        $this->resetPage();
    }

    public function showInvoiceDetails($id)
    {
        return redirect()->route('agency.provider-invoice-overview', ['provider' => $id]);
    }

    private function arNormalize(string $s): string
    {
        $tashkeel = ['َ','ً','ُ','ٌ','ِ','ٍ','ْ','ّ','ٰ'];
        $s = str_replace($tashkeel, '', $s);
        $s = str_replace(['أ','إ','آ','ٱ'], 'ا', $s);
        $s = str_replace(['ؤ'], 'و', $s);
        $s = str_replace(['ئ','ء'], 'ي', $s);
        $s = str_replace('ى', 'ي', $s);
        $s = str_replace('ة', 'ه', $s);
        $s = strtr($s, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        return trim(mb_strtolower($s));
    }

    public function render()
    {
        $term = trim((string) $this->search);

        $providers = Provider::where('agency_id', Auth::user()->agency_id)
            ->when($term !== '', function ($q) use ($term) {
                $norm = $this->arNormalize($term);
                $map = [
                    'َ' => '', 'ً' => '', 'ُ' => '', 'ٌ' => '', 'ِ' => '', 'ٍ' => '', 'ْ' => '', 'ّ' => '',
                    'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
                    'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي', 'ء' => 'ي',
                ];
                $sql = "LOWER(name)";
                foreach ($map as $from => $to) {
                    $from = addslashes($from);
                    $to   = addslashes($to);
                    $sql  = "REPLACE($sql, '$from', '$to')";
                }
                $q->whereRaw("$sql LIKE ?", ['%'.$norm.'%']);
            })
            ->with(['sales.service']) // نجلب خدمات المزوّد عبر السيلز
            ->orderByDesc('id')
            ->paginate(10);

        // كوّن نص “نوع الخدمة” لكل مزوّد لعرضه في العمود
        $providers->getCollection()->transform(function ($p) {
            $p->service_labels = $p->sales
                ->pluck('service.label')
                ->filter()
                ->unique()
                ->implode('، ');
            return $p;
        });

        return view('livewire.agency.provider-detailed-invoices', [
            'providers' => $providers,
        ]);
    }
}
