@php $flashOk = session('ok'); @endphp

<!-- موبايل فقط ≤640px. بلا تأثير على الشاشات المتوسطة والكبيرة -->
<style>
@media (max-width:640px){
  #backups-page .bk-actions{display:grid; grid-template-columns:1fr; gap:.5rem; align-items:stretch}
  #backups-page .bk-actions form,
  #backups-page .bk-actions p{width:100%}
  #backups-page .bk-actions input[type=file]{width:100%}
  #backups-page .bk-actions .btn-full{width:100%}

  #backups-page .bk-cards{grid-template-columns:1fr !important}
  #backups-page .bk-card .meta{grid-template-columns:1fr 1fr !important}
}
</style>

<div id="backups-page" class="space-y-6">
  <h2 class="text-2xl font-bold">النسخ الاحتياطية للوكالة</h2>

  @if($flashOk)
    <x-toast :message="$flashOk" type="success" />
  @endif

  @if ($errors->any())
    <x-toast :message="$errors->first()" type="error" />
  @endif

  <!-- شريط الإجراءات -->
  <div class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-3 items-center bk-actions">
    {{-- إنشاء نسخة جديدة --}}
    <form method="POST" action="{{ route('agency.backups.store', $agencyId) }}">
      @csrf
      <x-primary-button type="submit" class="btn-full sm:w-auto">
        إنشاء نسخة الآن
      </x-primary-button>
    </form>

    {{-- استعادة من ملف مرفوع --}}
    <form id="restore-upload-form" method="POST"
          action="{{ route('agency.backups.restore', $agencyId) }}"
          enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
      @csrf
      <input id="zipFile" type="file" name="zip" accept=".zip" class="border rounded px-3 py-2" required>
      <x-primary-button id="restoreUploadBtn" type="submit"
        :gradient="false" color="#8d8a8aff" textColor="white"
        icon='<i class="fas fa-upload"></i>' class="btn-full sm:w-auto">
        إسترداد من ملف مرفوع
      </x-primary-button>
    </form>

    <p class="text-xs text-gray-500">
      يجب أن يكون اسم الملف: <span class="font-mono">agency_{{ $agencyId }}_YYYYMMDD_HHMMSS.zip</span>
    </p>
  </div>

  {{-- البطاقات --}}
  <div class="bg-white rounded-xl shadow p-4">
    <h3 class="font-semibold mb-3">النسخ المتوفرة</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 bk-cards">
      @forelse($backups as $b)
        <div class="border rounded-xl p-4 flex flex-col justify-between bk-card">
          <div class="space-y-3">
            <div>
              <div class="text-xs text-gray-500 mb-1">الملف</div>
              <div class="font-mono break-all">{{ $b->filename }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4 meta">
              <div>
                <div class="text-xs text-gray-500 mb-1">الحجم</div>
                <div>
                  @php
                    $kb = $b->size/1024;
                    echo $kb >= 1024 ? number_format($kb/1024, 2).' MB' : number_format($kb, 1).' KB';
                  @endphp
                </div>
              </div>
              <div>
                <div class="text-xs text-gray-500 mb-1">الحالة</div>
                <div>{{ $b->status }}</div>
              </div>
              <div class="col-span-2">
                <div class="text-xs text-gray-500 mb-1">التاريخ</div>
                <div>{{ \Illuminate\Support\Carbon::parse($b->created_at)->format('Y-m-d H:i') }}</div>
              </div>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <a class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"
               href="{{ route('agency.backups.download', [$agencyId, $b->filename]) }}">
              تنزيل
            </a>

            <form method="POST"
                  action="{{ route('agency.backups.restore_existing', [$agencyId, $b->filename]) }}"
                  x-data
                  @submit.prevent="$dispatch('open-restore-modal', { filename: '{{ $b->filename }}', action: $el.action })">
              @csrf
              <x-primary-button type="submit" class="px-3 py-1">
                استعادة
              </x-primary-button>
            </form>
          </div>
        </div>
      @empty
        <div class="col-span-full text-center text-gray-500 py-6">لا توجد نسخ حتى الآن</div>
      @endforelse
    </div>
  </div>

  <!-- مودال التأكيد -->
  <div x-data="{ show:false, action:'', filename:'' }"
       @open-restore-modal.window="show=true; action=$event.detail.action; filename=$event.detail.filename">
    <template x-if="show">
      <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative">
          <button @click="show=false"
                  class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

          <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
            تأكيد الاستعادة
          </h3>

          <p class="text-sm text-gray-600 mb-6 text-center">
            هل تريد الاستعادة من <span x-text="filename"></span>؟ سيتم حذف بيانات الوكالة الحالية واستبدالها.
          </p>

          <div class="flex justify-center gap-3 pt-4">
            <button type="button" @click="show=false"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
              إلغاء
            </button>
            <form :action="action" method="POST">
              @csrf
              <x-primary-button type="submit">تأكيد</x-primary-button>
            </form>
          </div>
        </div>
      </div>
    </template>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form   = document.getElementById('restore-upload-form');
  const input  = document.getElementById('zipFile');
  const btn    = document.getElementById('restoreUploadBtn');
  const agencyId = {{ $agencyId }};

  form.addEventListener('submit', (e) => {
    const f = input.files[0];
    if (!f) return;
    const ok = new RegExp(`^agency_${agencyId}_\\d{8}_\\d{6}\\.zip$`).test(f.name);
    if (!ok) {
      e.preventDefault();
      alert('الملف لا يخص هذه الوكالة. ارفع ملفًا يبدأ بـ agency_' + agencyId + '_ وبصيغة التاريخ الصحيحة.');
      return;
    }
    btn.disabled = true;
    btn.textContent = 'جارٍ الاستعادة...';
  });
});
</script>
