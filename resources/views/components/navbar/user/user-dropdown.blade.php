@php
    $user = Auth::user();
    $displayName = $name ?? ($user ? $user->name : 'User Name');
    $displayRole = $role ?? ($user ? $user->getRoleNames()->first() : 'الدور غير محدد');
@endphp
<div class="relative group-user-dropdown" tabindex="0">
    <button
        class="flex items-center justify-center h-10 w-10 rounded-full border-2 border-theme bg-white/10 focus:outline-none focus:ring-2 focus-ring-theme">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24">
            <circle cx="12" cy="8" r="4" fill="#28A745" />
            <rect x="5" y="15" width="14" height="6" rx="3" fill="#3CCFCF" />
            <circle cx="12" cy="8" r="3.2" fill="#fff" />
        </svg>
    </button>
    <div class="user-dropdown-menu left-auto right-0 mt-2 bg-white/40 backdrop-blur-sm rounded-xl shadow-lg border border-gray-100"
    style="min-width: 180px; margin-right: -8rem;">
            <div class="px-4 py-3 border-b border-gray-100">
            <div class="font-bold text-gray-800 text-base">{{ $displayName }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $displayRole ?? 'الدور غير محدد' }}</div>
        </div>
        <form method="POST" action="{{ $logoutRoute ?? route('logout') }}" class="m-0 p-0">
            @csrf
            <button type="submit"
                class="w-full text-right px-4 py-2 text-red-600 hover:bg-red-50 font-semibold transition rounded-b-xl">
                تسجيل الخروج
            </button>
        </form>
    </div>
</div> 