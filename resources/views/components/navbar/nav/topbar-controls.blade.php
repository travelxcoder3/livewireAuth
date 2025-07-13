<div class="flex items-center gap-2 sm:gap-4">
    <x-theme-selector />
    <div class="relative group">
        <x-icon-button tooltip="تغيير اللغة" label="تغيير اللغة">
            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 0c2.21 0 4 4.03 4 9s-1.79 9-4 9-4-4.03-4-9 1.79-9 4-9z" />
            </svg>
        </x-icon-button>
        <div class="dropdown-accounts absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
            <x-dropdown-link :href="'#'" icon="fas fa-briefcase" label="العربيه" />
            <x-dropdown-link :href="'#'" icon="fas fa-briefcase" label="English" />
        </div>
    </div>
    <x-user-dropdown />
</div> 