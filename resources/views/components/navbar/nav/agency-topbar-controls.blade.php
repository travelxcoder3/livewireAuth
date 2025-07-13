<div class="flex items-center gap-2 sm:gap-4">
    <x-theme.theme-selector />
    <div class="relative group">
        <x-navbar.buttons.icon-button
            icon="fas fa-globe"
            tooltip="تغيير اللغة"
            label="تغيير اللغة"
        />
        <div class="dropdown-accounts absolute right-0 top-full mt-2 min-w-[200px] bg-[rgb(var(--primary-100))] rounded-xl shadow-lg py-2 z-50 hidden group-hover:block transition-opacity duration-200">
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
    <x-navbar.user.user-dropdown />
</div> 