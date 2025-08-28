@props([
  'label' => '',
  'options' => [],          // [{id:'1', label:'...'}, ...]
  'wireModel' => '',        // مصفوفة IDs في Livewire
  'placeholder' => 'اختر واحد أو أكثر',
  'errorName' => '',
  'searchable' => true,
])

<div class="relative mt-1"
     x-data="{
        open:false,
        q:'',
        // ربط مباشر
        selected: @entangle($wireModel).live,
        items: @js($options),

        init(){
          this.selected = Array.isArray(this.selected) ? this.selected.map(String) : [];
        },

        // تحديث واحد بدون طفرات متعدّدة
        toggle(id){
          id = String(id);
          const set = new Set((this.selected || []).map(String));
          if (set.has(id)) set.delete(id); else set.add(id);
          this.selected = Array.from(set);
        },

        allIds(){ return this.items.map(it => String(it.id)) },

        isAll(){
          const all = this.allIds();
          if (all.length === 0) return false;
          const sel = new Set((this.selected || []).map(String));
          return all.every(id => sel.has(id));
        },

        // تحديث دفعة واحدة لتفادي التعليق
        toggleAll(){
          const currentlyAll = this.isAll();
          this.selected = currentlyAll ? [] : this.allIds();
        },

        filtered(){
          if (!this.q) return this.items;
          const s = this.q.toLowerCase();
          return this.items.filter(it => String(it.label).toLowerCase().includes(s));
        },

        summary(){
          if (!this.selected || this.selected.length === 0) return '{{ $placeholder }}';
          const map = Object.fromEntries(this.items.map(it => [String(it.id), it.label]));
          return this.selected.map(id => map[String(id)]).filter(Boolean).join('، ');
        }
     }"
>
  <label class="block text-xs text-gray-600 mb-1">{{ $label }}</label>

  <button type="button" @click="open=!open"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs bg-white text-right focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))]">
    <span x-text="summary()"></span>
  </button>

  <div x-show="open" x-transition @click.outside="open=false"
       class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow max-h-64 overflow-y-auto">
    <div class="sticky top-0 bg-white border-b p-2 flex items-center gap-2">
      <button type="button" @click="toggleAll()"
              class="text-white text-xs px-2 py-1 rounded-md"
              style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
        <span x-text="isAll() ? 'إلغاء التحديد' : 'اختيار الكل'"></span>
      </button>
      @if($searchable)
      <input x-model="q" type="text" placeholder="ابحث..."
             class="flex-1 text-xs border rounded px-2 py-1 focus:ring-1 focus:ring-[rgb(var(--primary-500))]">
      @endif
    </div>

    <ul class="p-2 space-y-1">
      <template x-for="it in filtered()" :key="it.id">
        <li>
          <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
            <input type="checkbox"
                   :value="it.id"
                   :checked="(selected || []).map(String).includes(String(it.id))"
                   @change.prevent="toggle(it.id)"
                   class="h-4 w-4 border-2 border-gray-300 rounded appearance-none relative cursor-pointer
                          focus:ring-2 focus:ring-[rgb(var(--primary-600))] checked:bg-[rgb(var(--primary-600))] checked:border-transparent">
            <span x-text="it.label"></span>
          </label>
        </li>
      </template>
      <li x-show="filtered().length===0" class="text-xs text-gray-500 p-2">لا توجد نتائج</li>
    </ul>
  </div>

  @error($errorName ?: $wireModel)
    <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
  @enderror
</div>
