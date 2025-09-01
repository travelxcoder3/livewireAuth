@props([
  'title'       => 'تأكيد العملية',
  'message'     => 'هل أنت متأكد؟',
  'subtitle'    => null,
  'confirmText' => 'تأكيد',
  'cancelText'  => 'إلغاء',
  'onConfirm'   => null,
  'icon'        => 'question',  // check | warn | danger | info | question
])

<div
  x-data="confirmDialog({ onConfirmMethod: @js($onConfirm), icon: @js($icon) })"
  x-cloak
  x-show="open"
  x-trap.noscroll="open"
  @keydown.escape.prevent.window="cancel()"
  @keydown.enter.prevent.window="confirm()"
  class="fixed inset-0 z-50"
  role="dialog" aria-modal="true" aria-labelledby="confirm-title"
  style="direction: rtl"
>
  <!-- الخلفية -->
  <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"
       x-show="open"
       x-transition.opacity></div>

  <!-- اللوحة -->
  <div class="fixed inset-0 flex items-center justify-center px-4"
       x-show="open"
       x-transition.opacity
       x-transition.scale.origin.center.duration.160ms>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/20 overflow-hidden">

      <!-- شريط علوي بلون الثيم الثابت -->
      <div class="h-2 w-full" style="background: rgb(var(--primary-600));"></div>

      <!-- رأس -->
      <div class="flex items-start gap-3 px-6 pt-5">
        <div class="shrink-0 grid place-items-center w-11 h-11 rounded-xl ring-1"
             style="background: rgba(var(--primary-100), .95); color: rgb(var(--primary-700)); border-color: rgba(var(--primary-100), .95);">
          <!-- أيقونات -->
          <template x-if="icon==='check'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </template>
          <template x-if="icon==='warn'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          </template>
          <template x-if="icon==='danger'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </template>
          <template x-if="icon==='info'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 110-20 10 10 0 010 20z"/></svg>
          </template>
          <template x-if="icon==='question'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 9a3 3 0 116 0c0 2-3 2-3 4m0 4h.01"/></svg>
          </template>
        </div>

        <div class="grow pb-2">
          <h3 id="confirm-title" class="text-base font-semibold text-gray-900" x-text="title"></h3>
          <p x-show="subtitle" x-text="subtitle" class="mt-1 text-xs text-gray-500"></p>
        </div>

        <button class="p-2 rounded-lg text-gray-500 hover:bg-gray-100"
                @click="cancel()" aria-label="إغلاق">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- الرسالة -->
      <div class="px-6 py-4">
        <p class="text-sm leading-6 text-gray-700" x-text="message"></p>
      </div>

      <div class="h-px bg-gray-100"></div>

      <!-- الأزرار -->
      <div class="px-6 py-4 bg-gray-50 flex items-center gap-3">
        <button
          class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-white text-sm font-medium disabled:opacity-60 transition focus:outline-none focus:ring-2 focus:ring-offset-2"
          style="background: rgb(var(--primary-600));"
          :disabled="loading"
          @click="confirm()"
          x-ref="confirmBtn">
          <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
            <path class="opacity-75" d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3"></path>
          </svg>
          <span x-text="confirmText"></span>
        </button>

        <button
          class="px-5 py-2 rounded-xl border text-sm font-medium text-gray-700 bg-white hover:bg-gray-100 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300"
          :disabled="loading"
          @click="cancel()"
          x-text="cancelText"></button>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDialog(init = {}) {
  return {
    open:false, loading:false,
    title:@js($title), message:@js($message), subtitle:@js($subtitle),
    confirmText:@js($confirmText), cancelText:@js($cancelText),
    onConfirmMethod:init.onConfirmMethod ?? null, payload:null,
    icon:init.icon ?? 'question',

    openDialog(opts={}){
      this.title       = opts.title       ?? this.title;
      this.message     = opts.message     ?? this.message;
      this.subtitle    = opts.subtitle    ?? this.subtitle;
      this.confirmText = opts.confirmText ?? this.confirmText;
      this.cancelText  = opts.cancelText  ?? this.cancelText;
      this.onConfirmMethod = opts.onConfirm ?? this.onConfirmMethod;
      this.payload     = opts.payload     ?? null;
      this.icon        = opts.icon        ?? this.icon;
      this.open = true;
      this.$nextTick(()=> this.$refs.confirmBtn?.focus());
    },

    init(){ window.addEventListener('confirm:open', e => this.openDialog(e.detail || {})); },

    async confirm(){
      if(!this.onConfirmMethod){ this.open=false; return; }
      if(this.loading) return;
      this.loading=true;
      try{ await this.$wire.call(this.onConfirmMethod, this.payload); this.open=false; }
      finally{ this.loading=false; }
    },

    cancel(){ if(!this.loading) this.open=false; }
  }
}
</script>
