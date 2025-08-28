<div>
@can('notifications.view')
    <div class="relative group" wire:poll.2s>
        <button type="button"
            class="flex items-center justify-center w-10 h-10 rounded-full transition-colors
                   bg-[rgb(var(--primary-100))] hover:bg-[rgb(var(--primary-200))] focus:outline-none relative"
            aria-label="الإشعارات">
            <i class="fas fa-bell text-lg text-white"></i>

            @if(($unreadCount ?? 0) > 0)
                <span
                    class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 flex items-center justify-center
                           text-xs font-bold text-white bg-red-600 rounded-full border-2
                           border-[rgb(var(--primary-100))] z-10">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        {{-- القائمة --}}
        <div
            class="notif-menu absolute right-0 top-full mt-2 w-80 max-w-[90vw] bg-white rounded-xl shadow-xl
                   border border-gray-200 py-1 z-50 hidden group-hover:block overflow-y-auto overflow-x-hidden max-h-80">

            <div class="absolute -top-1 right-4 w-3 h-3 bg-white rotate-45 border-t border-r border-gray-200"></div>

            @forelse($notifications as $n)
                @php
                    $title = $n->title ?: 'إشعار جديد';
                    $body  = $n->body  ?: '';
                @endphp

                <button type="button" wire:click="open({{ $n->id }})"
                        class="notif-item {{ $n->is_read ? 'opacity-70' : '' }}">
                    <span class="inline-block mt-1 w-2 h-2 rounded-full {{ $n->is_read ? 'bg-gray-300' : 'bg-red-500' }}"></span>
                    <span class="flex-1 text-right">
                        <span class="block text-sm font-semibold text-gray-900 leading-5">{{ $title }}</span>
                        @if($body)
                            <span class="block text-xs mt-0.5 text-gray-600">{{ $body }}</span>
                        @endif
                        <span class="block text-[11px] mt-1 text-gray-500">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-3 text-sm text-gray-700">لا توجد إشعارات</div>
            @endforelse

            <div class="border-t mt-1">
                <a href="{{ route('agency.approvals.index') }}"
                   class="block px-4 py-2 text-xs text-[rgb(var(--primary-700))] hover:underline">
                    عرض كل الإشعارات
                </a>
            </div>
        </div>
    </div>

    <style>
        .notif-menu{ color:#111827;background:#fff }
        .notif-item{ display:flex;gap:.75rem;align-items:flex-start;padding:.75rem 1rem;
                     width:100%;text-align:right;border-bottom:1px solid #eee }
        .notif-item:hover{ background:#f3f4f6 }
    </style>

    <script>
        (function () {
            const ensureToaster = () => {
                let el = document.getElementById('lw-toaster');
                if (!el) {
                    el = document.createElement('div');
                    el.id = 'lw-toaster';
                    el.style.position = 'fixed';
                    el.style.bottom = '16px';
                    el.style.left   = '16px';
                    el.style.zIndex = '9999';
                    document.body.appendChild(el);
                }
                return el;
            };
            const showToast = (message) => {
                const host = ensureToaster();
                const item = document.createElement('div');
                item.textContent = message || 'لديك إشعار جديد';
                item.style.background = '#111827';
                item.style.color = '#fff';
                item.style.padding = '10px 14px';
                item.style.marginTop = '8px';
                item.style.borderRadius = '10px';
                item.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1)';
                host.appendChild(item);
                setTimeout(() => { item.style.transition = 'opacity .3s'; item.style.opacity = '0';
                    setTimeout(() => item.remove(), 300); }, 3500);
            };
            window.addEventListener('new-notification-toast',  e => showToast(e.detail?.message));
            window.addEventListener('redirect-to-url', e => { const url = e.detail?.url; if (url) location.href = url; });
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && window.Livewire) Livewire.dispatch('refreshNotifications');
            });
        })();
    </script>
@endcan
</div>
