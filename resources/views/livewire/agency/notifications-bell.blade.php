<div>
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
                    <span
                        class="absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center text-xs font-bold text-white bg-red-600 rounded-full border-2 border-[rgb(var(--primary-100))] z-10">
                        {{ $notifications->count() }}
                    </span>
                @endif
            </button>

            <div
                class="absolute left-0 top-full mt-2 min-w-[260px] bg-white rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">

                @forelse($notifications as $notification)
                    @php
                        $msg = $notification->data['message'] ?? ($notification->data['title'] ?? 'إشعار جديد');
                    @endphp

                    @if($isBranch)
                        <div class="px-4 py-2 text-sm border-b last:border-0 text-gray-400 cursor-not-allowed"
                             title="لا يمكنك فتح هذا الإشعار من الفرع">
                            {{ $msg }}
                        </div>
                    @else
                        <button
                            type="button"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="w-full text-right px-4 py-2 hover:bg-gray-100 text-sm border-b last:border-0">
                            {{ $msg }}
                        </button>
                    @endif
                @empty
                    <div class="px-4 py-2 text-gray-500">لا توجد إشعارات</div>
                @endforelse
            </div>
        </div>
<script>
    (function () {
        const ensureToaster = () => {
            let el = document.getElementById('lw-toaster');
            if (!el) {
                el = document.createElement('div');
                el.id = 'lw-toaster';
                el.style.position = 'fixed';
                el.style.bottom = '16px';
                el.style.left = '16px';
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
            setTimeout(() => {
                item.style.transition = 'opacity .3s';
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 300);
            }, 3500);
        };

        // ✅ استمع لأحداث المتصفح الصادرة من $this->dispatch()
        window.addEventListener('new-notification-toast', (e) => {
            showToast(e.detail?.message);
        });

        window.addEventListener('redirect-to-url', (e) => {
            const url = e.detail?.url;
            if (url) window.location.href = url;
        });

        // حدّث الجرس عند العودة للتبويب
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && window.Livewire) {
                Livewire.dispatch('refreshNotifications');
            }
        });
    })();
</script>

    @endcan
</div>
