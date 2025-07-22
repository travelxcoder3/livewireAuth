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
                                // Original raw value
                                $value = data_get($row, $col['key']);
                                $translatedValue = $value;

                                // 1) Handle format closures first
                                if (isset($col['format']) && is_callable($col['format'])) {
                                    $translatedValue = $col['format']($value);
                                }
                                // 2) Handle built‑in format strings
                                elseif (isset($col['format'])) {
                                    switch ($col['format']) {
                                        case 'money':
                                            $translatedValue = number_format($value, 2);
                                            break;

                                        case 'date':
                                            $translatedValue = $value
                                                ? (\Illuminate\Support\Carbon::canBeCreatedFromFormat($value, 'Y-m-d')
                                                    ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d')
                                                    : '-')
                                                : '-';
                                            break;

                                        case 'status':
                                            $translatedValue = match ($value) {
                                                'paid'      => 'مدفوع',
                                                'unpaid'    => 'غير مدفوع',
                                                'issued'    => 'تم الإصدار',
                                                'reissued'  => 'أعيد إصداره',
                                                'refunded'  => 'تم الاسترداد',
                                                'canceled'  => 'ملغي',
                                                'pending'   => 'قيد الانتظار',
                                                'void'      => 'ملغي نهائي',
                                                default     => $value,
                                            };
                                            break;
                                            case 'payment_method':
                                                    $translatedValue = match ($value) {
                                                        'kash' => 'كاش',
                                                        'part' => 'جزئي',
                                                        'all' => 'كامل',
                                                        default => $value,
                                                    };
                                                    break;

                                                case 'payment_type':
                                                    $translatedValue = match ($value) {
                                                        'creamy' => 'تحصيل',
                                                        'kash' => 'كاش',
                                                        'visa' => 'فيزا',
                                                        default => $value,
                                                    };
                                                    break;

                                    }
                                }

                                // 3) Convert arrays or Collections into Arabic‑comma lists
                                if (is_array($translatedValue) || $translatedValue instanceof \Illuminate\Support\Collection) {
                                    $display = collect($translatedValue)->join('، ');
                                } else {
                                    $display = $translatedValue;
                                }

                                // 4) Determine color class (string or Closure)
                                $colorClass = '';
                                if (isset($col['color'])) {
                                    $colColor = $col['color'];
                                    $colorVal = is_callable($colColor)
                                        ? $colColor($value)
                                        : $colColor;
                                    $colorClass = "text-{$colorVal}";
                                }
                            @endphp

                            <td class="px-2 py-1 {{ $colorClass }}">
                                @if($col['key'] === 'actions' && isset($col['actions']) && is_array($col['actions']))
                                    <div class="flex gap-2">
                                        @foreach($col['actions'] as $action)
                                            @php
                                                $show = $action['can'] ?? true;
                                                if (isset($action['showIf']) && is_callable($action['showIf'])) {
                                                    $show = $show && call_user_func($action['showIf'], $row);
                                                }
                                                $label     = $action['label']  ?? ($action['type'] ?? '');
                                                $icon      = $action['icon']   ?? null;
                                                $class     = $action['class']  ?? 'text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-800))]';
                                                $confirm   = $action['confirm'] ?? false;
                                                $method    = $action['method'] ?? ($action['type'] ?? '');
                                                $url       = $action['url']    ?? null;
                                                $actionUrl = $url
                                                    ?? (isset($action['type'])
                                                        && isset($row->{$action['type'].'_url'})
                                                        ? $row->{$action['type'].'_url'}
                                                        : null);
                                            @endphp

                                            @if($show)
                                                @if($actionUrl)
                                                    <a href="{{ $actionUrl }}"
                                                       class="font-medium text-xs {{ $class }} flex items-center gap-1">
                                                        @if($icon)<i class="{{ $icon }}"></i>@endif
                                                        {{ $label }}
                                                    </a>
                                                @else
                                                    <button wire:click="{{ $method }}({{ $row->id }})"
                                                            @if($confirm) onclick="return confirm('هل أنت متأكد من {{ $label }}؟')" @endif
                                                            class="font-medium text-xs {{ $class }} flex items-center gap-1">
                                                        @if($icon)<i class="{{ $icon }}"></i>@endif
                                                        {{ $label }}
                                                    </button>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    {{ $display !== null && $display !== '' ? $display : '-' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center py-4 text-gray-400">
                            لا توجد بيانات
                        </td>
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
