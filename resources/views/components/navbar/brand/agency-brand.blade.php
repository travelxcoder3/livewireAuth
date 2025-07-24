<div class="flex items-center gap-3 {{ $class ?? '' }}">
    <a href="{{ Auth::user()->hasRole('super-admin') ? route('admin.dashboard') : route('agency.dashboard') }}" class="flex items-center gap-2">
        <img src="{{ asset('images/logo-travelx.svg') }}" alt="TRAVEL-X Logo"
         class="h-10 w-auto object-contain ">
    </a>
</div>
