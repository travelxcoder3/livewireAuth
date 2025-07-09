<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نظام إدارة وكالات السفر - Laravel Livewire</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 min-h-screen">
    @php
        use App\Services\ThemeService;
        $themeName = ThemeService::getSystemTheme();
        $colors = ThemeService::getCurrentThemeColors($themeName);
    @endphp

    <!-- Navigation Header -->
    <nav class="bg-white/90 backdrop-blur-md shadow-lg" style="border-bottom: 1px solid rgba({{ $colors['primary-500'] }}, 0.2);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="flex items-center">
                            <svg class="h-8 w-8 mr-2" style="color: rgb({{ $colors['primary-500'] }});" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h1 class="text-2xl font-bold" style="color: rgb({{ $colors['primary-500'] }});">نظام السفر</h1>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="/login" class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white px-6 py-2 rounded-lg font-medium transition duration-200 shadow-lg hover:shadow-xl" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                        تسجيل الدخول
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Split Layout -->
    <div class="relative min-h-screen flex items-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 w-full">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Side - Text and Login Button -->
                <div class="space-y-8">
                    <div class="space-y-6">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-800 leading-tight">
                            نظام إدارة
                            <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }})); -webkit-background-clip: text; background-clip: text;">
                                وكالات السفر
                            </span>
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 leading-relaxed">
                            منصة متكاملة لإدارة الحجوزات والعملاء والرحلات السياحية بكل سهولة وكفاءة. 
                            نظام ذكي يساعدك على تطوير أعمالك وزيادة أرباحك.
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center justify-center w-8 h-8 bg-emerald-100 rounded-full" style="background-color: rgba({{ $colors['primary-500'] }}, 0.1);">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-gray-700">إدارة شاملة للحجوزات والعملاء</span>
                        </div>
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center justify-center w-8 h-8 bg-teal-100 rounded-full" style="background-color: rgba({{ $colors['primary-500'] }}, 0.1);">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-gray-700">تقارير وإحصائيات مفصلة</span>
                        </div>
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center justify-center w-8 h-8 bg-cyan-100 rounded-full" style="background-color: rgba({{ $colors['primary-500'] }}, 0.1);">
                                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-gray-700">واجهة سهلة الاستخدام ومتجاوبة</span>
                        </div>
                    </div>

                    <div class="pt-4">
                        <a href="/login" class="inline-flex items-center bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white px-8 py-4 rounded-xl font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl transform hover:scale-105" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            تسجيل الدخول
                        </a>
                    </div>
                </div>

                <!-- Right Side - Image -->
                <div class="relative">
                    <div class="relative z-10">
                        <!-- Main Image Container -->
                        <div class="relative bg-gradient-to-br from-emerald-400 to-teal-500 rounded-3xl p-8 shadow-2xl" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-500'] }}));">
                            <div class="bg-white rounded-2xl p-6 shadow-lg">
                                <!-- Travel Agency Illustration -->
                                <div class="text-center space-y-6">
                                    <!-- Globe Icon -->
                                    <div class="mx-auto w-24 h-24 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full flex items-center justify-center shadow-lg" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Travel Elements -->
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="bg-emerald-50 rounded-lg p-3" style="background-color: rgba({{ $colors['primary-500'] }}, 0.1);">
                                            <svg class="w-8 h-8 text-emerald-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                            </svg>
                                        </div>
                                        <div class="bg-teal-50 rounded-lg p-3" style="background-color: rgba({{ $colors['primary-500'] }}, 0.1);">
                                            <svg class="w-8 h-8 text-teal-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                                        <div class="bg-cyan-50 rounded-lg p-3" style="background-color: rgba({{ $colors['primary-500'] }}, 0.1);">
                                            <svg class="w-8 h-8 text-cyan-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <!-- Stats -->
                                    <div class="grid grid-cols-2 gap-4 text-center">
                                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg p-3" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                                            <div class="text-2xl font-bold">500+</div>
                                            <div class="text-sm opacity-90">وكالة سفر</div>
                                        </div>
                                        <div class="bg-gradient-to-r from-teal-500 to-cyan-500 text-white rounded-lg p-3" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-500'] }}));">
                                            <div class="text-2xl font-bold">10K+</div>
                                            <div class="text-sm opacity-90">حجز شهرياً</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating Elements -->
                        <div class="absolute -top-4 -right-4 w-16 h-16 bg-emerald-200 rounded-full opacity-60 animate-pulse" style="background-color: rgba({{ $colors['primary-500'] }}, 0.2);"></div>
                        <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-teal-200 rounded-full opacity-60 animate-pulse delay-1000" style="background-color: rgba({{ $colors['primary-500'] }}, 0.2);"></div>
                        <div class="absolute top-1/2 -right-8 w-8 h-8 bg-cyan-200 rounded-full opacity-60 animate-pulse delay-500" style="background-color: rgba({{ $colors['primary-500'] }}, 0.2);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-16 bg-white/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">مميزات النظام</h2>
                <p class="text-lg text-gray-600">كل ما تحتاجه لإدارة وكالة سفر ناجحة</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-emerald-100 hover:shadow-2xl transition duration-300">
                    <div class="text-center mb-6">
                        <div class="mx-auto h-16 w-16 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">إدارة الحجوزات</h3>
                        <p class="text-gray-600">إدارة شاملة للحجوزات مع تتبع الحالة والتحديثات الفورية</p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-teal-100 hover:shadow-2xl transition duration-300">
                    <div class="text-center mb-6">
                        <div class="mx-auto h-16 w-16 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-500'] }}));">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">إدارة العملاء</h3>
                        <p class="text-gray-600">قاعدة بيانات شاملة للعملاء مع سجل الحجوزات والتواصل</p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-cyan-100 hover:shadow-2xl transition duration-300">
                    <div class="text-center mb-6">
                        <div class="mx-auto h-16 w-16 bg-gradient-to-r from-cyan-500 to-emerald-600 rounded-full flex items-center justify-center mb-4" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">تقارير وإحصائيات</h3>
                        <p class="text-gray-600">تقارير مفصلة وإحصائيات شاملة لتحسين الأداء والأرباح</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="py-16 bg-gradient-to-r from-emerald-500 to-teal-500" style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}), rgb({{ $colors['primary-600'] }}));">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">ابدأ في إدارة وكالة سفرك اليوم</h2>
            <p class="text-xl text-emerald-100 mb-8">انضم إلى مئات وكالات السفر التي تثق بنا لإدارة أعمالها</p>
            <a href="/login" class="bg-white text-emerald-600 px-8 py-4 rounded-xl font-bold text-lg transition duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 inline-block" style="color: rgb({{ $colors['primary-500'] }});">
                تسجيل الدخول الآن
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <svg class="h-8 w-8 text-emerald-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgb({{ $colors['primary-500'] }});">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-xl font-bold">نظام السفر</h3>
                    </div>
                    <p class="text-gray-300">منصة متكاملة لإدارة وكالات السفر بكل احترافية وكفاءة</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">الخدمات</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li>حجوزات الطيران</li>
                        <li>حجوزات الفنادق</li>
                        <li>الباقات السياحية</li>
                        <li>التأمين السياحي</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">الدعم</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li>الدعم الفني</li>
                        <li>الأسئلة الشائعة</li>
                        <li>الدليل الإرشادي</li>
                        <li>التواصل معنا</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">تواصل معنا</h4>
                    <div class="space-y-2 text-gray-300">
                        <p>البريد الإلكتروني: info@travelsystem.com</p>
                        <p>الهاتف: +967 771178499</p>
                        <div class="flex space-x-4 space-x-reverse mt-4">
                            <a href="#" class="text-emerald-400 hover:text-emerald-300" style="color: rgb({{ $colors['primary-500'] }});">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-emerald-400 hover:text-emerald-300" style="color: rgb({{ $colors['primary-500'] }});">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-emerald-400 hover:text-emerald-300" style="color: rgb({{ $colors['primary-500'] }});">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-300">
                <p>&copy; 2024 نظام إدارة وكالات السفر. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>