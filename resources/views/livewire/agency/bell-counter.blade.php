<div wire:poll.5s.keep-alive class="absolute -top-1 -right-1">
    <span
        class="{{ $count > 0 ? 'inline-flex' : 'hidden' }}
               min-w-[20px] h-5 px-1 items-center justify-center
               text-xs font-bold text-white bg-red-600 rounded-full
               border-2 border-[rgb(var(--primary-100))] z-10">
        {{ $count }}
    </span>
</div>
