@props(['rows', 'columns'])

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    @foreach($columns as $col)
                        <th class="px-2 py-1">{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50">
                        @foreach($columns as $col)
                          @php
                            $value = data_get($row, $col['key']);

                            $colorClass = '';
                            if (isset($col['color'])) {
                                if (is_callable($col['color'])) {
                                    $color = call_user_func($col['color'], $value);
                                    $colorClass = 'text-' . $color;
                                } else {
                                    $colorClass = 'text-' . $col['color'];
                                }
                            }
                        @endphp

                        <td class="px-2 py-1 {{ $colorClass }}">


                                @php
                                    $value = data_get($row, $col['key']);
                                @endphp
                                @if(isset($col['format']) && $col['format'] === 'money')
                                    {{ number_format($value, 2) }}
                                @elseif(isset($col['format']) && $col['format'] === 'date' && $value)
                                    {{ \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') }}
                                    @elseif(isset($col['format']) && $col['format'] === 'status')
                                @switch($value)
                                    @case('paid') مدفوع @break
                                    @case('unpaid') غير مدفوع @break
                                    @case('issued') تم الإصدار @break
                                    @case('reissued') أعيد إصداره @break
                                    @case('refunded') تم الاسترداد @break
                                    @case('canceled') ملغي @break
                                    @case('pending') قيد الانتظار @break
                                    @case('void') ملغي نهائي @break
                                    @default {{ $value }}
                                @endswitch
                                @elseif(isset($col['key']) && $col['key'] === 'actions' && isset($col['actions']) && is_array($col['actions']))
                                    <div class="flex gap-2">
                                        @foreach($col['actions'] as $action)
                                            @php
                                                $show = $action['can'] ?? true;
                                                if (isset($action['showIf']) && is_callable($action['showIf'])) {
                                                    $show = $show && call_user_func($action['showIf'], $row);
                                                }
                                                $label = $action['label'] ?? ($action['type'] ?? '');
                                                $icon = $action['icon'] ?? null;
                                                // إذا لم يتم تحديد كلاس، استخدم لون الثيم الأساسي
                                                $class = $action['class'] ?? 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]';
                                                $confirm = $action['confirm'] ?? false;
                                                $method = $action['method'] ?? ($action['type'] ?? '');
                                                $url = $action['url'] ?? null;
                                            @endphp
                                            @if($show)
                                                @php
                                                    // استخدم الرابط من row إذا كان موجودًا
                                                    $actionUrl = $url ?? (isset($action['type']) && isset($row->{$action['type'].'_url'}) ? $row->{$action['type'].'_url'} : null);
                                                @endphp
                                                @if($actionUrl)
                                                    <a href="{{ $actionUrl }}" class="font-medium text-xs {{ $class }} flex items-center gap-1">
                                                        @if($icon)
                                                            <i class="{{ $icon }}"></i>
                                                        @endif
                                                        {{ $label }}
                                                    </a>
                                                @else
                                                    <button wire:click="{{ $method }}({{ $row->id }})"
                                                        @if($confirm) onclick="return confirm('هل أنت متأكد من {{ $label }}؟')" @endif
                                                        class="font-medium text-xs {{ $class }} flex items-center gap-1">
                                                        @if($icon)
                                                            <i class="{{ $icon }}"></i>
                                                        @endif
                                                        {{ $label }}
                                                    </button>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                @php
                                    $translatedValue = $value;

                                    // ترجمة الحقول النصية
                                    if ($col['key'] === 'payment_method') {
                                        $translatedValue = match($value) {
                                            'kash' => 'كاش',
                                            'part' => 'جزئي',
                                            'all' => 'كامل جزئي',
                                            default => $value
                                        };
                                    } elseif ($col['key'] === 'payment_type') {
                                        $translatedValue = match($value) {
                                            'creamy' => 'كريمي',
                                            'kash' => 'كاش',
                                            'visa' => 'فيزا',
                                            default => $value
                                        };
                                    } elseif ($col['key'] === 'customer_via') {
                                        $translatedValue = match($value) {
                                            'whatsapp' => 'واتساب',
                                            'viber' => 'فايبر',
                                            'instagram' => 'إنستغرام',
                                            'other' => 'أخرى',
                                            default => $value
                                        };
                                    }
                                    elseif ($col['key'] === 'has_commission') {
                                            $translatedValue = $value ? 'نعم' : 'لا';
                                        };
                                @endphp
                                {{ $translatedValue !== null && $translatedValue !== '' ? $translatedValue : '-' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center py-4 text-gray-400">لا توجد بيانات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows instanceof \Illuminate\Pagination\AbstractPaginator && $rows->hasPages())
        <div class="px-4 py-2 border-t border-gray-200">
            {{ $rows->links() }}
        </div>
    @endif
</div> 