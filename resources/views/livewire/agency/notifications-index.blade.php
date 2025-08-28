<div wire:poll.3s.keep-alive>
    @php
        $isUnread = request('tab','unread') === 'unread';
    @endphp

    <style>
        .seg-btn{
            padding:.5rem .9rem;border-radius:.6rem;border:1px solid #e5e7eb;
            background:#fff;color:#111827;font-size:.875rem
        }
        .seg-btn.active{
            background: rgb(var(--primary-600)); color:#fff;
            border-color: rgb(var(--primary-600));
        }
        .btn-ghost{
            padding:.5rem .9rem;border-radius:.6rem;background:#f3f4f6;color:#111827;font-size:.875rem
        }
        .card{
            border:1px solid #e5e7eb;border-radius:.9rem;padding:1rem;
            display:flex;justify-content:space-between;gap:.75rem;align-items:center
        }
        .card.unread{ border-left: .4rem solid rgb(var(--primary-500)); }
        .link-primary{ color: rgb(var(--primary-700)); }
        .link-primary:hover{ text-decoration: underline; }
        .dot{ width:.5rem;height:.5rem;border-radius:9999px;background:rgb(var(--primary-500)); display:inline-block }
        .muted{ color:#6b7280;font-size:.875rem }
    </style>

    {{-- Tabs + actions --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('agency.notifications.index',['tab'=>'unread']) }}"
           class="seg-btn {{ $isUnread ? 'active' : '' }}">
           غير المقروءة
        </a>
        <a href="{{ route('agency.notifications.index',['tab'=>'all']) }}"
           class="seg-btn {{ !$isUnread ? 'active' : '' }}">
           الكل
        </a>

        <button wire:click="markAllAsRead" class="btn-ghost" title="تحديد الكل كمقروء">
            <i class="fas fa-check-double ml-1"></i> تحديد الكل كمقروء
        </button>
    </div>

    {{-- List --}}
    @forelse ($items as $n)
        <div class="card {{ $n->read_at ? '' : 'unread' }}">
            <div class="min-w-0">
                <div class="font-medium flex items-center gap-2">
                    @unless($n->read_at) <span class="dot"></span> @endunless
                    <span class="truncate">{{ $n->title }}</span>
                </div>

                @if ($n->body)
                    <div class="muted mt-1 truncate">{{ $n->body }}</div>
                @endif

                <div class="muted mt-1">{{ $n->created_at->diffForHumans() }}</div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                @if ($n->url)
                    <a href="{{ $n->url }}" class="link-primary flex items-center gap-1">
                        <i class="fas fa-external-link-alt"></i> فتح
                    </a>
                @endif

                @unless ($n->read_at)
                    <button wire:click="markAsRead({{ $n->id }})" class="text-gray-600 hover:underline">
                        تعليم كمقروء
                    </button>
                @endunless>
            </div>
        </div>
    @empty
        <div class="p-6 text-center text-gray-500">لا توجد إشعارات.</div>
    @endforelse

    <div class="mt-4">{{ $items->links() }}</div>
</div>
