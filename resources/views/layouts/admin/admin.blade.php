<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('layouts.admin.head')
</head>
<body class="bg-dashboard min-h-screen font-app" x-data="{ mobileSidebarOpen: false }">
<div>
    @include('layouts.admin.navbar')        {{-- الشريط العلوي --}}
    @include('layouts.admin.mobile-sidebar'){{-- القائمة الجانبية للموبايل --}}

    {{-- المحتوى الرئيسي --}}
    <div class="flex-1 w-full px-0 py-8">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-emerald-100 p-8 w-full">
           @yield('content')
        </div>
    </div>

    @include('layouts.admin.scripts')
</div>
</body>
</html>
