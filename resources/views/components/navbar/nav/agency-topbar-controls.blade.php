@php
    // جلب عدد الطلبات المعلقة للوكالة الرئيسية أو فروعها
    $mainAgency = Auth::user()->agency->parent_id ? Auth::user()->agency->parent : Auth::user()->agency;
    $agencyIds = $mainAgency->branches()->pluck('id')->toArray();
    $agencyIds[] = $mainAgency->id;
    $pendingApprovalsCount = \App\Models\ApprovalRequest::where('status', 'pending')->whereIn('agency_id', $agencyIds)->count();
    // جلب الإشعارات غير المقروءة للمستخدم الحالي
    $notifications = Auth::user()->unreadNotifications()->latest()->take(10)->get();
    $isMainAgency = !Auth::user()->agency->parent_id;
@endphp
<div class="flex items-center gap-2 sm:gap-4">
    <x-theme.theme-selector />
    <div class="relative group">
        <x-navbar.buttons.icon-button
            icon="fas fa-globe"
            tooltip="تغيير اللغة"
            label="تغيير اللغة"
        />
        <div class="dropdown-accounts absolute left-0 top-full mt-2 min-w-[200px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
            <x-navbar.buttons.dropdown-link
                :href="'#'"
                icon="fas fa-briefcase"
                label="العربيه"
            />
            <x-navbar.buttons.dropdown-link
                :href="'#'"
                icon="fas fa-briefcase"
                label="English"
            />
        </div>
    </div>
    {{-- زر الإشعارات مع polling تلقائي --}}
    @livewire('agency.notifications-bell')
    <!-- {{-- زر الموافقات للوكالة الرئيسية --}}
    @if(auth()->user()->hasRole('agency-admin') && $isMainAgency)
        <a href="{{ route('agency.approval-requests') }}" class="relative flex items-center gap-1 px-3 py-2 hover:bg-gray-100 rounded transition">
            <i class="fas fa-tasks text-primary-600"></i>
            <span>الموافقات</span>
            @if($pendingApprovalsCount > 0)
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {{ $pendingApprovalsCount }}
                </span>
            @endif
        </a>
    @endif -->
    <x-navbar.user.user-dropdown />
</div> 