<div class="flex items-center gap-1 sm:gap-3 rtl:flex-row-reverse" x-data="{ openDropdown: '' }">

    {{-- زر الثيم --}}
    <div class="relative" @mouseenter="openDropdown = 'theme'" @mouseleave="openDropdown = ''">
        <x-navbar.buttons.icon-button
            icon="fas fa-palette"
            label="تغيير الثيم"
            tooltip="تغيير الثيم"
            :has-notification="false"
        />
        <div x-show="openDropdown === 'theme'" x-transition
            class="absolute left-0 top-full mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-100 py-2 z-50 p-2">

            @php
                use App\Services\ThemeService;
                $themes = ThemeService::getThemeColors();
            @endphp
            <div class="grid grid-cols-3 gap-2">
                @foreach ($themes as $name => $theme)
                    @if (Auth::user()->hasRole('super-admin'))
                        <button onclick="updateSystemTheme('{{ $name }}')" class="h-8 w-8 rounded-full"
                            style="background-color: rgb({{ $theme['primary-500'] }})"></button>
                    @else
                        <button onclick="updateTheme('{{ $name }}')" class="h-8 w-8 rounded-full"
                            style="background-color: rgb({{ $theme['primary-500'] }})"></button>
                    @endif
                @endforeach
            </div>

            <div class="col-span-3 flex items-center gap-2 mt-3">
                <input type="color" id="customHexColor" class="h-8 w-8 rounded-full border cursor-pointer"
                    onchange="handleHexColorChange(this.value)" />
                <input type="text" id="customHexInput" maxlength="7" placeholder="#1abc9c"
                    class="border rounded px-2 py-1 text-xs w-20" onchange="handleHexColorChange(this.value)" />
                <button onclick="submitHexColor()"
                    class="ml-2 px-2 py-1 rounded text-white text-xs transition duration-200 shadow"
                    style="background: rgb(var(--primary-500));">
                    تطبيق
                </button>
            </div>

            <script>
                function handleHexColorChange(val) {
                    let hex = val.trim();
                    if (hex[0] !== '#') hex = '#' + hex;
                    document.getElementById('customHexColor').value = hex;
                    document.getElementById('customHexInput').value = hex;
                }

                function submitHexColor() {
                    let hex = document.getElementById('customHexInput').value.trim();
                    if (hex[0] !== '#') hex = '#' + hex;
                    if (/^#([A-Fa-f0-9]{6})$/.test(hex)) {
                        @if (Auth::user()->hasRole('super-admin'))
                            updateSystemTheme(hex);
                        @else
                            updateTheme(hex);
                        @endif
                    } else {
                        alert('يرجى إدخال كود لون HEX صالح مثل #1abc9c');
                    }
                }
            </script>
        </div>
    </div>

    {{-- زر اللغة --}}
    <div class="relative" @mouseenter="openDropdown = 'lang'" @mouseleave="openDropdown = ''">
        <x-navbar.buttons.icon-button
            icon="fas fa-globe"
            label="تغيير اللغة"
            tooltip="تغيير اللغة"
            :has-notification="false"
        />
        <div x-show="openDropdown === 'lang'" x-transition
            class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-xl border border-gray-100 py-2 z-50 min-w-[160px]">
            <x-navbar.buttons.dropdown-link :href="'#'" icon="fas fa-language" label="العربية" />
            <x-navbar.buttons.dropdown-link :href="'#'" icon="fas fa-language" label="English" />
        </div>
    </div>

    {{-- زر المستخدم --}}
    <div class="relative" @mouseenter="openDropdown = 'user'" @mouseleave="openDropdown = ''">
        <x-navbar.buttons.icon-button
            icon="fas fa-user"
            label="الحساب"
            tooltip="الحساب"
            :has-notification="false"
        />
        <div x-show="openDropdown === 'user'" x-transition
            class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-xl border border-gray-100 py-2 z-50 min-w-[240px]">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="font-medium text-gray-800">{{ Auth::user()->name }}</p>
                <p class="text-sm text-gray-500">{{ Auth::user()->email }}</p>
            </div>

            @if (Route::has('profile.edit'))
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-user-cog ml-2 w-5 text-center"></i>
                    <span>إعدادات الحساب</span>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex items-center w-full px-4 py-2.5 text-sm text-red-600 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-sign-out-alt ml-2 w-5 text-center"></i>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </div>
    </div>

</div>


