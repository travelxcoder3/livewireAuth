<nav class="w-full flex items-center justify-between px-6 shadow-sm rounded-t-2xl nav-gradient"
     style="padding-top: 8px; padding-bottom: 8px; min-height:48px;">

    <!-- زر القائمة الجانبية للجوال -->
    <button class="lg:hidden flex items-center justify-center mr-2 text-white focus:outline-none"
            @click="mobileSidebarOpen = true">
        <i class="fas fa-bars text-2xl"></i>
    </button>

    <!-- الشعار -->
    <x-navbar.brand.agency-brand />

    <!-- روابط التنقل -->
    <div class="hidden lg:flex">
        <x-navbar.nav.admin-nav-links />
    </div>

    <!-- أدوات التحكم -->
    <div class="hidden lg:flex">
        <x-navbar.nav.topbar-controls />
    </div>
</nav>
