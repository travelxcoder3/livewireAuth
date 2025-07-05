<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة تحكم الوكالة')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        [dir="rtl"] {
            direction: rtl;
        }
    </style>
    
    @livewireStyles
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <h1 class="text-xl font-bold">لوحة تحكم الوكالة</h1>
                </div>
                
                <div class="flex items-center space-x-4 space-x-reverse">
                    <span class="text-sm">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm hover:text-blue-200">
                            <i class="fas fa-sign-out-alt ml-1"></i>
                            تسجيل الخروج
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    @php
                        $user = auth()->user();
                        if ($user->hasRole('agency-admin') || $user->hasRole('roles-manager') || $user->hasRole('users-manager') || $user->hasRole('permissions-manager')) {
                            $dashboardRoute = route('agency.dashboard');
                        } elseif ($user->can('users.view')) {
                            $dashboardRoute = route('agency.users');
                        } elseif ($user->can('roles.view')) {
                            $dashboardRoute = route('agency.roles');
                        } elseif ($user->can('permissions.view')) {
                            $dashboardRoute = route('agency.permissions');
                        } else {
                            $dashboardRoute = route('agency.dashboard');
                        }
                    @endphp
                    <a href="{{ route('agency.dashboard') }}"
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-tachometer-alt ml-2"></i>
                        لوحة التحكم
                    </a>
                    
                    @can('users.view')
                    <a href="{{ route('agency.users') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.users') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-users ml-2"></i>
                        المستخدمين
                    </a>
                    @endcan
                    
                    @can('roles.view')
                    <a href="{{ route('agency.roles') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.roles') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-user-tag ml-2"></i>
                        الأدوار
                    </a>
                    @endcan
                    
                    @can('permissions.view')
                    <a href="{{ route('agency.permissions') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.permissions') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-shield-alt ml-2"></i>
                        الصلاحيات
                    </a>
                    @endcan
                    
                    {{--
                    <a href="{{ route('agency.services') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.services') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-concierge-bell ml-2"></i>
                        الخدمات
                    </a>
                    <a href="{{ route('agency.employees') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.employees') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-user-tie ml-2"></i>
                        الموظفين
                    </a>
                    <a href="{{ route('agency.reports') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('agency.reports') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-chart-bar ml-2"></i>
                        التقارير
                    </a>
                    --}}
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>