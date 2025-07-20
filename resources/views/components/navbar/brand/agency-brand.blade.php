<div class="flex items-center gap-3 {{ $class ?? '' }}">
    <a href="{{ route('agency.dashboard') }}" class="flex items-center gap-2">
        <img src="{{ asset('images/logo-travelx.png') }}" alt="TRAVEL-X Logo"
         class="h-15 w-auto object-contain ">
    </a>
</div>

<!-- @php
    use Illuminate\Support\Str;
    $name = Auth::user()->agency->name ?? 'Travel X';
    $logo = Auth::user()->agency->logo ?? null;
    $class = $class ?? '';
    $isUrl = $logo && (Str::startsWith($logo, ['http://', 'https://', '/']));
@endphp
<div class="flex items-center gap-3 {{ $class }}">
    @if($logo)
        <img src="{{ $isUrl ? $logo : asset('storage/' . $logo) }}"
             alt="شعار الوكالة"
             class="h-9 w-9 rounded-full object-cover shadow-md" />
    @else
        <svg class="h-9 w-9" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="8" width="28" height="20" rx="6" fill="rgb(var(--primary-500))" />
            <rect x="8" y="14" width="4" height="4" rx="1" fill="#fff" />
            <rect x="14" y="14" width="4" height="4" rx="1" fill="#fff" />
            <rect x="20" y="14" width="4" height="4" rx="1" fill="#fff" />
            <rect x="12" y="22" width="8" height="4" rx="2" fill="rgb(var(--primary-600))" />
        </svg>
    @endif

    <span class="text-white text-lg font-bold tracking-tight">
        {{ $name }}
    </span>
</div>  -->