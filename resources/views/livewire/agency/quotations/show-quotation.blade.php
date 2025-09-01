<!-- resources/views/livewire/agency/quotations/show-quotation.blade.php -->
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Auth;

    $logoData = null;
    $mime = 'image/png';
    $agency  = Auth::user()->agency;
    $currency = $agency->currency ?? 'USD';

    $stored = trim($agency->logo ?? '');
    if ($stored !== '' && Storage::disk('public')->exists($stored)) {
        $fullPath = Storage::disk('public')->path($stored);
        $logoData  = base64_encode(file_get_contents($fullPath));
        $mime      = mime_content_type($fullPath) ?: 'image/png';
    }

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

<div id="quotationRoot" class="pdf-root" data-can-print="{{ $quotationId ? '1' : '0' }}"
     lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}"
     style="background:#fff;padding:30px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.15);font-family:'Segoe UI', Tahoma, sans-serif;max-width:1100px;margin:auto;position:relative;">

  <style>
    body{font-family:'Segoe UI', Tahoma, sans-serif;margin:0;background-color:#f4f4f4;}
    table{width:100%;border-collapse:collapse;margin-top:25px;table-layout:fixed;}
    th,td{border:1px solid #ccc;padding:10px;vertical-align:top;word-wrap:break-word;}
    th{background:#eef1f5;}
    input,select,textarea{width:100%;border:1px solid #ccc;padding:6px;border-radius:4px;background:transparent;font-route:600;box-sizing:border-box}
    textarea{min-height:80px;resize:none}
    .title{font-size:26px;margin:10px 0 0;color:#2c3e50;text-align:center}
    .header{text-align:center}
    .logo{max-width:150px}
    .info{display:flex;justify-content:space-between;margin-top:18px}
    .to-section{margin:22px 0 10px;font-size:18px;font-route:700}
    .totals{margin-top:20px;font-size:16px;font-route:bold}
    .signature{margin-top:32px;font-route:bold;text-align:{{ $lang==='ar'?'right':'left' }}}
    .btn{display:inline-block;border:none;border-radius:6px;padding:8px 14px;cursor:pointer;font-route:700}
    .btn-blue{background:#3498db;color:#fff}
    .btn-green{background:#27ae60;color:#fff}
    .btn-orange{background:#f39c12;color:#fff}
    .no-print{user-select:none}
    textarea.autogrow{
        width:100%;
        min-height:38px;
        border:1px solid #ccc;
        padding:6px;
        border-radius:4px;
        background:transparent;
        line-height:1.4;
        resize:none;
        overflow:hidden;
      }

    @media print{ .no-print{display:none !important;} }
    #print-guard{ display:none; }
      @media print{
        #quotationRoot[data-can-print="0"] *{ display:none !important; }
        #quotationRoot[data-can-print="0"] #print-guard{
          display:flex !important; align-items:center; justify-content:center;
          height:100vh; font-weight:700; font-size:18pt;
        }
      }
  </style>

  <style>
    #print-guard{ display:none; }
    @media print{
      body.block-print *{ display:none !important; }
      body.block-print #print-guard{
        display:flex !important; align-items:center; justify-content:center;
        height:100vh; font-weight:700; font-size:18pt;
      }
    }
  </style>

  <!-- تحسينات للموبايل فقط ≤640px. لا تأثير على الشاشات المتوسطة والكبيرة -->
  <style>
    @media (max-width:640px){
      .pdf-root{padding:16px}
      .pdf-root .logo{max-width:90px}
      .pdf-root .title{font-size:20px;margin-top:6px}
      .pdf-root .no-print.absolute{position:static !important; inset:auto !important; margin:6px 0}
      .pdf-root .info{flex-direction:column; gap:6px; align-items:flex-start}
      .pdf-root .to-section{font-size:14px;margin:14px 0 8px}
      .pdf-root .totals{font-size:14px}
      .pdf-root .table-wrap{margin:0 -8px; padding:0 8px; overflow-x:auto}
      .pdf-root table{font-size:12px}
      /* إخفاء أعمدة ثقيلة: Route(3) + Description(5) + Terms(6) */
      .pdf-root th:nth-child(3), .pdf-root td:nth-child(3){display:none}
      .pdf-root th:nth-child(5), .pdf-root td:nth-child(5){display:none}
      .pdf-root th:nth-child(6), .pdf-root td:nth-child(6){display:none}
      .pdf-root .tax-grid{display:grid; grid-template-columns:1fr 1fr; gap:8px; align-items:center}
    }
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
      <div><strong>{{ $lang==='ar'?'رقم عرض السعر:':'Quotation No:' }}</strong>
        <span id="quotationNumber">{{ $this->displayNumber }}</span>
      </div>
  </div>

  <!-- إلى -->
  <div class="to-section">
    <span>{{ $lang==='ar'?'إلى:':'To:' }}</span>
    <input type="text" id="toClient" wire:model="toClient">
  </div>

  <!-- الضريبة -->
  <div class="no-print tax-grid"
       style="display:grid;grid-template-columns:auto 1fr auto 120px;gap:10px;align-items:center">
    <label>{{ $lang==='ar'?'اسم الضريبة:':'Tax Name:' }}</label>
    <input type="text" id="taxName" wire:model="taxName">
    <label>{{ $lang==='ar'?'النسبة (%):':'Rate (%):' }}</label>
    <input type="number" id="taxRate" wire:model="taxRate" min="0" step="0.1" oninput="calculateTotal()">
  </div>

  <!-- الجدول -->
  <div class="table-wrap">
    <table id="quotationTable">
      <thead>
        <tr>
          <th>#</th>
          <th>{{ $lang==='ar'?'الخدمة':'Service' }}</th>
          <th>{{ $lang==='ar'?'المسار':'Route' }}</th>
          <th>{{ $lang==='ar'?'التاريخ':'Date' }}</th>
          <th>{{ $lang==='ar'?'الوصف':'Description' }}</th>
          <th>{{ $lang==='ar'?'الشروط والأحكام':'Terms & Conditions' }}</th>
          <th>{{ $lang==='ar'?'السعر':'Price' }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($services as $index => $service)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>
              <select wire:model="services.{{ $index }}.service_type_id">
                <option value="">{{ $lang==='ar'?'— اختر خدمة —':'— Select Service —' }}</option>
                @foreach($serviceOptions as $id => $name)
                  <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
              </select>
            </td>
            <td>
              <textarea class="autogrow"
                        rows="1"
                        wire:model.defer="services.{{ $index }}.route"></textarea>
            </td>
            <td><input type="date" wire:model="services.{{ $index }}.date"></td>
            <td>
              <textarea class="autogrow"
                        rows="1"
                        wire:model.defer="services.{{ $index }}.description"></textarea>
            </td>
            <td>
              <select wire:model="services.{{ $index }}.conditions">
                @foreach($conditionsList as $c)
                  <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
              </select>
            </td>
            <td><input type="number" step="0.01" wire:model="services.{{ $index }}.price"></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <x-primary-button id="btnAddService" class="no-print mt-2"
                    color="#27ae60" gradient="false" textColor="[rgb(var(--primary-500))]"
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
    <x-primary-button id="btnAddTerm" class="no-print" wire:click.prevent="addTerm">
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

  <!-- الأزرار -->
  <div class="footer-buttons no-print" style="margin-top:16px;display:flex;gap:10px">
    @if(!$quotationId)
        <x-primary-button id="saveBtn" type="button"
            x-data
            @click="window.dispatchEvent(new CustomEvent('confirm:open',{detail:{
                title:'تأكيد حفظ عرض السعر',
                message:'سيتم حفظ عرض السعر الحالي.',
                icon:'check',
                confirmText:'حفظ',
                cancelText:'إلغاء',
                onConfirm:'save',
                payload:null
            }}))">
            {{ $lang==='ar' ? 'حفظ عرض السعر' : 'Save Quotation' }}
        </x-primary-button>
    @endif

    @if($quotationId)
        <x-primary-button type="button"
            x-data
            @click="window.dispatchEvent(new CustomEvent('confirm:open',{detail:{
                title:'تأكيد تنزيل PDF',
                message:'سيتم توليد وتنزيل عرض السعر كملف PDF.',
                icon:'info',
                confirmText:'تنزيل',
                cancelText:'إلغاء',
                onConfirm:'downloadQuotationPdf',
                payload: {{ (int) $quotationId }}
            }}))">
            {{ $lang==='ar' ? 'تنزيل PDF' : 'Download PDF' }}
        </x-primary-button>

        <x-primary-button type="button"
            x-data
            @click="window.dispatchEvent(new CustomEvent('confirm:open',{detail:{
                title:'تأكيد فتح للطباعة',
                message:'سيتم فتح عرض السعر في تبويب جديد للطباعة.',
                icon:'info',
                confirmText:'فتح',
                cancelText:'إلغاء',
                onConfirm:'openQuotationPrint',
                payload: {{ (int) $quotationId }}
            }}))">
            {{ $lang==='ar' ? 'طباعة' : 'Print' }}
        </x-primary-button>
    @endif

    <x-primary-button color="#7f8c8d" gradient="false" textColor="[rgb(var(--primary-500))]" wire:click.prevent="resetForm">
        {{ $lang==='ar' ? 'جديد' : 'New' }}
    </x-primary-button>
</div>

<script>
window.addEventListener('open-url', e => { if(e?.detail?.url){ window.open(e.detail.url, '_blank'); }});
</script>


  @if(!$quotationId)
    <div id="print-guard" aria-hidden="true">
      {{ $lang==='ar' ? 'الرجاء حفظ عرض السعر أولاً قبل الطباعة.' : 'Please save the quotation before printing.' }}
    </div>
  @endif

  <script>
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

    function issueQuotation(){
      const printBtn = document.getElementById("printBtn");
      if (printBtn) printBtn.style.display = "inline-block";
      const issueBtn = document.getElementById("issueBtn");
      if (issueBtn) issueBtn.style.display = "none";

      document.querySelectorAll("input, textarea, select").forEach(el => {
        if (el.type === "hidden" || el.closest("#quotationPdfForm")) return;

        const value = (el.tagName === "SELECT")
          ? (el.options[el.selectedIndex]?.text || "")
          : (el.value || "");

        const div = document.createElement("div");
        div.className = "print-block";
        div.style.whiteSpace = "pre-wrap";
        div.style.fontroute = "bold";
        div.style.padding = "5px";
        div.textContent = value;

        el.parentNode.replaceChild(div, el);
      });
    }

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
            service_name: tds[1]?.querySelector('input')?.value || '',
            description:  tds[2]?.querySelector('input')?.value || '',
            route:        tds[3]?.querySelector('input')?.value || '',
            class:        tds[4]?.querySelector('input')?.value || '',
            conditions:   tds[5]?.querySelector('select')?.value || '',
            price:        parseFloat(tds[6]?.querySelector('input')?.value || 0)
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

    (function(){
      function block(e){
        const canPrint = document.getElementById('quotationRoot')?.dataset.canPrint === '1';
        if(!canPrint && (e.ctrlKey||e.metaKey) && (e.key==='p'||e.key==='P'||e.code==='KeyP'||e.keyCode===80)){
          e.preventDefault(); e.stopPropagation();
          alert('{{ $lang==="ar" ? "يجب حفظ عرض السعر أولاً قبل الطباعة." : "Please save the quotation before printing." }}');
          return false;
        }
      }
      window.addEventListener('keydown', block, true);
    })();

    (function(){
      function fit(el){
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
      }
      function init(){
        document.querySelectorAll('textarea.autogrow').forEach(fit);
      }
      document.addEventListener('input', e => {
        if(e.target.matches('textarea.autogrow')) fit(e.target);
      });
      document.addEventListener('DOMContentLoaded', init);
      document.addEventListener('livewire:load', () => {
        Livewire.hook('message.processed', init);
      });
    })();
  </script>
  <x-confirm-dialog />

</div>
