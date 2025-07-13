@if($show ?? true)
    @if($href && $href !== '#')
        <a href="{{ $href }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center gap-2">
            @if(trim($slot))
                {{ $slot }}
            @else
                <i class="{{ $icon }}"></i> {{ $label }}
            @endif
        </a>
    @else
        <span class="block px-4 py-2 text-gray-700 flex items-center gap-2 cursor-default hover:bg-gray-100">
            @if(trim($slot))
                {{ $slot }}
            @else
                <i class="{{ $icon }}"></i> {{ $label }}
            @endif
        </span>
    @endif
@endif 