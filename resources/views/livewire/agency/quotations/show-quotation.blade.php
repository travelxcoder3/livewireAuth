<!-- resources/views/livewire/agency/quotations/show-quotation.blade.php -->
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Auth;

    $logoData = null;
    $mime = 'image/png';
    $currency = $sale->agency->currency ?? (Auth::user()->agency->currency ?? 'USD');

    // اختَر الوكالة من السيل إن وُجدت وإلا استخدم وكالة المستخدم
    $agency = $sale->agency ?? Auth::user()->agency;

    $stored = trim($agency->logo ?? '');
    if ($stored !== '' && Storage::disk('public')->exists($stored)) {
        $fullPath = Storage::disk('public')->path($stored);
        $logoData  = base64_encode(file_get_contents($fullPath));
        $mime      = mime_content_type($fullPath) ?: 'image/png';
    }

    // نصوص الشروط حسب اللغة
    $conditionsList = $lang === 'ar'
        ? [
            'قابل للتغيير والاسترجاع مع غرامة في جميع الحالات',
            'قابل للتغيير مع غرامة في جميع الحالات وقابل للاسترجاع قبل اللاحضور',
            'قابل للتغيير مع غرامة في جميع الحالات وغير قابل للاسترجاع',
            'قابل للتغيير والاسترجاع مع غرامة فقط قبل اللاحضور',
            'غير قابل للتغيير وغير قابل للاسترجاع في جميع الحالات',
          ]
        : [
            'Changeable and Refundable with penalty in all cases',
            'Changeable with penalty in all cases and Refundable before Noshow',
            'Changeable with penalty in all cases and non-Refundable',
            'Changeable and Refundable with penalty only before Noshow',
            'Non-Changeable and Non-Refundable in all cases',
          ];
@endphp

<div id="quotationRoot" class="pdf-root"
     lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}"
     style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.15);font-family:'Segoe UI', Tahoma, sans-serif;max-width:1100px;margin:auto;position:relative;">

  <style>
    body{font-family:'Segoe UI', Tahoma, sans-serif;margin:0;background-color:#f4f4f4;}
    table{width:100%;border-collapse:collapse;margin-top:25px;table-layout:fixed;}
    th,td{border:1px solid #ccc;padding:10px;vertical-align:top;word-wrap:break-word;}
    th{background:#eef1f5;}
    input,select,textarea{width:100%;border:1px solid #ccc;padding:6px;border-radius:4px;background:transparent;font-weight:600;box-sizing:border-box}
    textarea{min-height:80px;resize:none}
    .title{font-size:26px;margin:10px 0 0;color:#2c3e50;text-align:center}
    .header{text-align:center}
    .logo{max-width:150px}
    .info{display:flex;justify-content:space-between;margin-top:18px}
    .to-section{margin:22px 0 10px;font-size:18px;font-weight:700}
    .totals{margin-top:20px;font-size:16px;font-weight:bold}
    .signature{margin-top:32px;font-weight:bold;text-align:{{ $lang==='ar'?'right':'left' }}}
    .btn{display:inline-block;border:none;border-radius:6px;padding:8px 14px;cursor:pointer;font-weight:700}
    .btn-blue{background:#3498db;color:#fff}
    .btn-green{background:#27ae60;color:#fff}
    .btn-orange{background:#f39c12;color:#fff}
    .no-print{user-select:none}
    @media print{ .no-print{display:none !important;} }
  </style>

  <!-- زر العودة -->
<x-primary-button class="no-print absolute top-[10px] {{ $lang==='ar' ? 'right-[10px]' : 'left-[10px]' }}"
                  onclick="window.history.back()">
    {{ $lang === 'ar' ? 'عودة إلى اللوحة' : 'Back to dashboard' }}
</x-primary-button>

  <!-- تبديل اللغة عبر Livewire -->
<x-primary-button 
    class="no-print absolute top-[10px] {{ $lang==='ar' ? 'left-[10px]' : 'right-[10px]' }} text-[rgb(var(--primary-500))]"
    color="#f39c12" 
    gradient="false"
    textColor="[rgb(var(--primary-500))]"
    wire:click="setLang('{{ $lang === 'ar' ? 'en' : 'ar' }}')">
    {{ $lang === 'ar' ? 'English / الإنجليزية' : 'العربية / Arabic' }}
</x-primary-button>


  <!-- الرأس -->
 <div class="header" style="text-align:center; display:flex; flex-direction:column; align-items:center;">
    @if ($logoData)
        <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="Logo" class="logo" style="margin-bottom:10px;">
    @endif
    <div class="title">{{ $lang === 'ar' ? 'عرض السعر' : 'Quotation' }}</div>
</div>


  <!-- التاريخ والرقم -->
  <div class="info">
    <div><strong>{{ $lang==='ar'?'التاريخ:':'Date:' }}</strong> <span id="quotationDate">{{ $quotationDate }}</span></div>
    <div><strong>{{ $lang==='ar'?'رقم عرض السعر:':'Quotation No:' }}</strong> <span id="quotationNumber">{{ $quotationNumber }}</span></div>
  </div>

  <!-- إلى -->
  <div class="to-section">
    <span>{{ $lang==='ar'?'إلى:':'To:' }}</span>
    <input type="text" id="toClient" wire:model="toClient">
  </div>

  <!-- الضريبة -->
  <div class="no-print" style="display:grid;grid-template-columns:auto 1fr auto 120px;gap:10px;align-items:center">
    <label>{{ $lang==='ar'?'اسم الضريبة:':'Tax Name:' }}</label>
    <input type="text" id="taxName" wire:model="taxName">
    <label>{{ $lang==='ar'?'النسبة (%):':'Rate (%):' }}</label>
    <input type="number" id="taxRate" wire:model="taxRate" min="0" step="0.1" oninput="calculateTotal()">
  </div>

  <!-- الجدول -->
  <table id="quotationTable">
    <thead>
      <tr>
        <th>#</th>
        <th>{{ $lang==='ar'?'شركة الطيران':'Airline' }}</th>
        <th>{{ $lang==='ar'?'تفاصيل الرحلة':'Trip Details' }}</th>
        <th>{{ $lang==='ar'?'الوزن':'Weight' }}</th>
        <th>{{ $lang==='ar'?'الدرجة':'Class' }}</th>
        <th>{{ $lang==='ar'?'الشروط والأحكام':'Terms & Conditions' }}</th>
        <th>{{ $lang==='ar'?'السعر للشخص':'Price per Person' }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($services as $index => $service)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td><input type="text" wire:model="services.{{ $index }}.airline"></td>
          <td><input type="text" wire:model="services.{{ $index }}.details"></td>
          <td><input type="text" wire:model="services.{{ $index }}.weight"></td>
          <td><input type="text" wire:model="services.{{ $index }}.class"></td>
          <td>
            <select wire:model="services.{{ $index }}.conditions">
              @foreach($conditionsList as $c)
                <option value="{{ $c }}">{{ $c }}</option>
              @endforeach
            </select>
          </td>
          <td><input type="number" step="0.01" wire:model="services.{{ $index }}.price" oninput="calculateTotal()"></td>
        </tr>
      @endforeach
    </tbody>
  </table>

 <x-primary-button id="btnAddService" class="no-print mt-2"
                  color="#27ae60" gradient="false"   textColor="[rgb(var(--primary-500))]"
                  wire:click.prevent="addServiceRow">
    {{ $lang==='ar'?'إضافة خدمة':'Add Service' }}
</x-primary-button>


  <!-- الشروط -->
  <div class="terms" style="margin-top:24px">
    <strong>{{ $lang==='ar'?'الشروط:':'Terms:' }}</strong>
    <ul id="termsList" style="margin-top:10px">
     @foreach($terms as $i => $term)
      <li style="margin:6px 0">
        <input type="text" data-term-input wire:model="terms.{{ $i }}">
      </li>
    @endforeach

    </ul>
   <x-primary-button id="btnAddTerm" class="no-print"  
                  wire:click.prevent="addTerm">
    {{ $lang==='ar'?'إضافة شرط':'Add Term' }}
</x-primary-button>
  </div>

  <!-- الإجماليات -->
  <div class="totals">
    <div>{{ $lang==='ar'?'الإجمالي الفرعي:':'Subtotal:' }} {{ number_format($this->total, 2) }} {{ $currency }}</div>
    <div><span id="taxLabel">{{ $taxName }} ({{ $taxRate }}%)</span>: {{ number_format($this->taxAmount, 2) }} {{ $currency }}</div>
    <div>{{ $lang==='ar'?'الإجمالي الكلي:':'Grand Total:' }} {{ number_format($this->grandTotal, 2) }} {{ $currency }}</div>
  </div>

  <!-- الملاحظات -->
  <div class="notes" style="margin-top:20px">
    <label>{{ $lang==='ar'?'ملاحظات:':'Notes:' }}</label>
    <textarea id="quotationNotes" wire:model="notes"></textarea>
  </div>

  <!-- نموذج PDF (يأخذ القيم الحالية) -->
  <form id="quotationPdfForm" method="POST" action="{{ route('agency.quotation.pdf') }}" target="_blank" class="no-print" style="margin-top:16px">
    @csrf
    <input type="hidden" name="quotation_date"   value="{{ $quotationDate }}">
    <input type="hidden" name="quotation_number" value="{{ $quotationNumber }}">
    <input type="hidden" name="to_client"        value="{{ $toClient }}">
    <input type="hidden" name="tax_name"         value="{{ $taxName }}">
    <input type="hidden" name="tax_rate"         value="{{ $taxRate }}">
    <input type="hidden" name="notes"            value="{{ $notes }}">
    <input type="hidden" name="services_json"    value='@json($services)'>
    <input type="hidden" name="terms_json"       value='@json($terms)'>
    <input type="hidden" name="lang" id="pdfLang" value="{{ $lang }}">


   <x-primary-button type="button"
                  onclick="document.getElementById('quotationPdfForm').submit()">
    {{ $lang==='ar'?'تنزيل PDF':'Download PDF' }}
</x-primary-button>
  </form>

  <!-- التوقيع -->
  <div class="signature">
    {{ $lang==='ar'?'مع تحيات إدارة المبيعات':'Sales Department' }}
  </div>

  <!-- أزرار أخيرة -->
  <div class="footer-buttons no-print" style="margin-top:16px;display:flex;gap:10px">
   <x-primary-button id="issueBtn" color="#27ae60" gradient="false"   textColor="[rgb(var(--primary-500))]"
                  onclick="issueQuotation()">
    {{ $lang==='ar'?'إصدار عرض السعر':'Issue Quotation' }}  
</x-primary-button>
<x-primary-button id="printBtn" style="display:none"   textColor="[rgb(var(--primary-500))]"
                  onclick="window.print()">
    {{ $lang==='ar'?'طباعة':'Print' }}
</x-primary-button>
<x-primary-button color="#7f8c8d" gradient="false"   textColor="[rgb(var(--primary-500))]"
                  wire:click.prevent="resetForm">
    {{ $lang==='ar'?'جديد':'New' }}
</x-primary-button>
  </div>

  <script>
    // حساب الإجماليات (لعرض فوري فقط – القيم الفعلية تُحتسب في Livewire)
    function calculateTotal(){
      let total = 0;
      document.querySelectorAll("#quotationTable tbody tr").forEach(row=>{
        const priceInput = row.cells[6]?.querySelector('input');
        const value = parseFloat(priceInput?.value || 0);
        total += value;
      });
      const taxRate = parseFloat(document.getElementById("taxRate")?.value || 0);
      const taxName = document.getElementById("taxName")?.value || "Tax";
      const tax = total * (taxRate/100);
      const grand = total + tax;

      const elTotal = document.getElementById("totalAmount");
      const elTax   = document.getElementById("taxAmount");
      const elGrand = document.getElementById("grandTotal");
      if(elTotal) elTotal.textContent = total.toFixed(2);
      if(elTax)   elTax.textContent   = tax.toFixed(2);
      if(elGrand) elGrand.textContent = grand.toFixed(2);
      const tl = document.getElementById("taxLabel"); if(tl) tl.textContent = `${taxName} (${taxRate}%)`;
    }

    // تحويل الحقول إلى نصوص للطباعة (شكل نهائي)
   function issueQuotation(){
  const printBtn = document.getElementById("printBtn");
  if (printBtn) printBtn.style.display = "inline-block";
  const issueBtn = document.getElementById("issueBtn");
  if (issueBtn) issueBtn.style.display = "none";

  document.querySelectorAll("input, textarea, select").forEach(el => {
    // ⛔ تجاهل الحقول المخفية وأي عنصر داخل نموذج PDF
    if (el.type === "hidden" || el.closest("#quotationPdfForm")) return;

    const value = (el.tagName === "SELECT")
      ? (el.options[el.selectedIndex]?.text || "")
      : (el.value || "");

    const div = document.createElement("div");
    div.className = "print-block";
    div.style.whiteSpace = "pre-wrap";
    div.style.fontWeight = "bold";
    div.style.padding = "5px";
    div.textContent = value;

    el.parentNode.replaceChild(div, el);
  });
}


    // عند إرسال نموذج PDF: خُذ القيم الحالية من DOM (حتى بعد التعديل اليدوي)
    (function () {
      const form = document.getElementById('quotationPdfForm');
      if (!form) return;

      form.addEventListener('submit', function () {

          const currentLang = document.getElementById('quotationRoot')?.getAttribute('lang') || '{{ $lang }}';
  form.querySelector('input[name="lang"]').value = currentLang;
        form.querySelector('input[name="to_client"]').value =
          document.getElementById('toClient')?.value || '';

        form.querySelector('input[name="tax_name"]').value =
          document.getElementById('taxName')?.value || '';

        form.querySelector('input[name="tax_rate"]').value =
          document.getElementById('taxRate')?.value || 0;

        form.querySelector('input[name="quotation_date"]').value =
          document.getElementById('quotationDate')?.textContent.trim() || '';

        form.querySelector('input[name="quotation_number"]').value =
          document.getElementById('quotationNumber')?.textContent.trim() || '';

        form.querySelector('input[name="notes"]').value =
          document.getElementById('quotationNotes')?.value || '';

        const services = [];
        document.querySelectorAll('#quotationTable tbody tr').forEach(tr => {
          const tds = tr.querySelectorAll('td');
          services.push({
            airline:     tds[1]?.querySelector('input')?.value || '',
            details:     tds[2]?.querySelector('input')?.value || '',
            weight:      tds[3]?.querySelector('input')?.value || '',
            class:       tds[4]?.querySelector('input')?.value || '',
            conditions:  tds[5]?.querySelector('select')?.value || '',
            price:       parseFloat(tds[6]?.querySelector('input')?.value || 0)
          });
        });
        form.querySelector('input[name="services_json"]').value = JSON.stringify(services);

        const termInputs = document.querySelectorAll('#termsList [data-term-input]');
        const terms = Array.from(termInputs)
          .map(i => (i.value ?? '').toString().trim())
          .filter(v => v !== '');
        form.querySelector('input[name="terms_json"]').value = JSON.stringify(terms);

      });
    })();
  </script>
</div>
