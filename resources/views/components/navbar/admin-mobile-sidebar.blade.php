@php
    $user = Auth::user();
    $agencyName = $user && $user->agency ? $user->agency->name : 'Travel-X';
@endphp

<div class="fixed inset-0 z-50 flex lg:hidden">
    <!-- خلفية شفافة -->
    <div class="fixed inset-0 bg-black bg-opacity-40" @click="mobileSidebarOpen = false"></div>
    <!-- القائمة الجانبية -->
    <aside class="relative w-72 max-w-full bg-white shadow-xl h-full flex flex-col rtl:border-l rtl:border-gray-200 ltr:border-r ltr:border-gray-200">
        <!-- رأس القائمة -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo-travelx.png') }}" alt="TRAVEL-X Logo" class="h-10 w-auto object-contain">
                <div class="font-bold text-lg text-gray-800">{{ $agencyName }}</div>
            </div>
            <button @click="mobileSidebarOpen = false" class="text-gray-500 hover:text-red-500 focus:outline-none">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <!-- روابط -->
        <nav class="flex-1 flex flex-col gap-1 px-4 py-4">
            <a href="{{ route('admin.dashboard') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fa-solid fa-home"></i>
                <span>لوحة التحكم</span>
            </a>
            <a href="{{ route('admin.agencies') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fa-solid fa-building"></i>
                <span>إدارة الوكالات</span>
            </a>
            <a href="{{ route('admin.add-agency') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fa-solid fa-folder-plus"></i>
                <span>إضافة وكالة</span>
            </a>
            <a href="{{ route('admin.dynamic-lists') }}" @click="mobileSidebarOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-800 hover:bg-gray-100">
                <i class="fa-solid fa-bars"></i>
                <span>القوائم</span>
            </a>
            <div class="border-t my-2"></div>
            <!-- إعدادات الثيم -->
            <div class="mb-2">
                <div class="flex items-center gap-3 px-3 py-2">
                    <i class="fas fa-palette"></i>
                    <span>تغيير الثيم</span>
                </div>
                @php
                    use App\Services\ThemeService;
                    $themes = ThemeService::getThemeColors();
                @endphp
                <div class="grid grid-cols-5 gap-2 px-3 py-2">
                    @foreach ($themes as $name => $theme)
                        <button onclick="updateSystemTheme('{{ $name }}')" class="h-7 w-7 rounded-full border"
                            style="background-color: rgb({{ $theme['primary-500'] }})"></button>
                    @endforeach
                </div>
                <div class="flex items-center gap-2 px-3 py-2">
                    <input type="color" id="customHexColorMobile" class="h-7 w-7 rounded-full border cursor-pointer"
                        onchange="handleHexColorChangeMobile(this.value)" />
                    <input type="text" id="customHexInputMobile" maxlength="7" placeholder="#1abc9c"
                        class="border rounded px-2 py-1 text-xs w-20" onchange="handleHexColorChangeMobile(this.value)" />
                    <button onclick="submitHexColorMobile()"
                        class="px-2 py-1 rounded text-white text-xs transition duration-200 shadow"
                        style="background: rgb(var(--primary-500));">
                        تطبيق
                    </button>
                </div>
                <script>
                    function handleHexColorChangeMobile(val) {
                        let hex = val.trim();
                        if (hex[0] !== '#') hex = '#' + hex;
                        document.getElementById('customHexColorMobile').value = hex;
                        document.getElementById('customHexInputMobile').value = hex;
                    }
                    function submitHexColorMobile() {
                        let hex = document.getElementById('customHexInputMobile').value.trim();
                        if (hex[0] !== '#') hex = '#' + hex;
                        if (/^#([A-Fa-f0-9]{6})$/.test(hex)) {
                            updateSystemTheme(hex);
                        } else {
                            alert('يرجى إدخال كود لون HEX صالح مثل #1abc9c');
                        }
                    }
                </script>
            </div>
            <!-- إعدادات اللغة -->
            <div class="mb-2">
                <div class="flex items-center gap-3 px-3 py-2">
                    <i class="fas fa-globe"></i>
                    <span>تغيير اللغة</span>
                </div>
                <div class="flex gap-2 px-3 py-2">
                    <a href="/?lang=ar" class="px-3 py-1 rounded bg-gray-100 text-gray-800 text-xs">العربية</a>
                    <a href="/?lang=en" class="px-3 py-1 rounded bg-gray-100 text-gray-800 text-xs">English</a>
                </div>
            </div>
            <div class="border-t my-2"></div>
            <!-- تسجيل الخروج -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 w-full">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </nav>
    </aside>
</div> 