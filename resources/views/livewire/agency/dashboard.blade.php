<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">مرحباً بك في {{ $this->agencyInfo->name }}</h1>
        <p class="text-gray-600">
            @switch($this->dashboardType)
                @case('comprehensive')
                    لوحة التحكم الشاملة - عرض جميع الإحصائيات
                    @break
                @case('roles-focused')
                    لوحة التحكم - إدارة الأدوار
                    @break
                @case('users-focused')
                    لوحة التحكم - إدارة المستخدمين
                    @break
                @case('permissions-focused')
                    لوحة التحكم - إدارة الصلاحيات
                    @break
                @default
                    لوحة التحكم المبسطة
            @endswitch
        </p>
    </div>

    @switch($this->dashboardType)
        @case('comprehensive')
            <!-- لوحة التحكم الشاملة لأدمن الوكالة -->
            @include('livewire.agency.dashboard.comprehensive')
            @break
            
        @case('roles-focused')
            <!-- لوحة التحكم تركز على الأدوار -->
            @include('livewire.agency.dashboard.roles-focused')
            @break
            
        @case('users-focused')
            <!-- لوحة التحكم تركز على المستخدمين -->
            @include('livewire.agency.dashboard.users-focused')
            @break
            
        @case('permissions-focused')
            <!-- لوحة التحكم تركز على الصلاحيات -->
            @include('livewire.agency.dashboard.permissions-focused')
            @break
            
        @default
            <!-- لوحة التحكم المبسطة -->
            @include('livewire.agency.dashboard.simple')
    @endswitch
</div> 