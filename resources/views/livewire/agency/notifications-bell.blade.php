@can('notifications.view')
@php
    $isBranch = auth()->user()->agency->parent_id != null;
@endphp
<div class="relative group" wire:poll.2s>
    <button type="button"
        class="flex items-center justify-center w-10 h-10 rounded-full transition-colors duration-200
               bg-[rgb(var(--primary-100))] hover:bg-[rgb(var(--primary-200))] focus:outline-none relative">
        <i class="fas fa-bell text-lg text-white"></i>
        @if($notifications->count() > 0)
            <span class="absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center text-xs font-bold text-white bg-red-600 rounded-full border-2 border-[rgb(var(--primary-100))] z-10">
                {{ $notifications->count() }}
            </span>
        @endif
    </button>
    <div class="absolute left-0 top-full mt-2 min-w-[200px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
        @forelse($notifications as $notification)
            @if($isBranch)
                <div class="block px-4 py-2 text-sm border-b last:border-0 text-gray-400 cursor-not-allowed" title="لا يمكنك فتح هذا الإشعار من الفرع">
                    {{ $notification->data['message'] ?? 'إشعار جديد' }}
                </div>
            @else
                <a href="{{ route('agency.approvals.index', ['notification_id' => $notification->id]) }}"
                   class="block px-4 py-2 hover:bg-gray-100 text-sm border-b last:border-0">
                    {{ $notification->data['message'] ?? 'إشعار جديد' }}
                </a>
            @endif
        @empty
            <div class="px-4 py-2 text-gray-500">لا توجد إشعارات</div>
        @endforelse
    </div>
</div>
<script>
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            window.Livewire && window.Livewire.dispatch('refreshNotifications');
        }
    });
</script>
@endcan 