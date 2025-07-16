@php
use App\Services\ThemeService;
$themes = ThemeService::getThemeColors();
@endphp

@if(Auth::user() && (Auth::user()->hasRole('agency-admin') || Auth::user()->hasRole('super-admin')))
<div class="relative group-theme-selector mr-2">
    <button class="flex items-center justify-center h-10 w-10 rounded-full bg-white/10 hover:bg-white/20 transition">
        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2m-4-4V5a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2z"/>
        </svg>
    </button>
    <div class="theme-selector-menu hidden absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
        <div class="p-2 grid grid-cols-3 gap-2">
            @foreach($themes as $name => $theme)
                @if(Auth::user()->hasRole('super-admin'))
                    <button onclick="updateSystemTheme('{{ $name }}')"
                            class="h-8 w-8 rounded-full"
                            style="background-color: rgb({{ $theme['primary-500'] }})"></button>
                @else
                    <button onclick="updateTheme('{{ $name }}')"
                            class="h-8 w-8 rounded-full"
                            style="background-color: rgb({{ $theme['primary-500'] }})"></button>
                @endif
            @endforeach
            <!-- HEX color picker -->
            <div class="col-span-3 flex items-center gap-2 mt-2">
                <input type="color" id="customHexColor" class="h-8 w-8 rounded-full border cursor-pointer" style="padding:0;" onchange="handleHexColorChange(this.value)" />
                <input type="text" id="customHexInput" maxlength="7" placeholder="#1abc9c" class="border rounded px-2 py-1 text-xs w-20" onchange="handleHexColorChange(this.value)" />
                <button onclick="submitHexColor()"
                        class="ml-2 px-2 py-1 rounded text-white text-xs transition duration-200 shadow"
                        style="background: rgb(var(--primary-500));">
                    تطبيق
                </button>
            </div>
        </div>
        <script>
        function handleHexColorChange(val) {
            let hex = val.trim();
            if(hex[0] !== '#') hex = '#' + hex;
            document.getElementById('customHexColor').value = hex;
            document.getElementById('customHexInput').value = hex;
        }
        function submitHexColor() {
            let hex = document.getElementById('customHexInput').value.trim();
            if(hex[0] !== '#') hex = '#' + hex;
            if(/^#([A-Fa-f0-9]{6})$/.test(hex)) {
                @if(Auth::user()->hasRole('super-admin'))
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
@endif