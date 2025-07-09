<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-black mb-2">مرحباً بك في {{ $this->agencyInfo->name }}</h1>
        <p class="text-gray-900">
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
                @case('service-types-focused')
                    لوحة تحكم إدارة الخدمات
                    @break
                @case('sales-focused')
                    لوحة تحكم إدارة المبيعات
                    @break
                @case('hr-focused')
                    لوحة تحكم إدارة الموارد البشرية
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
        
        @case('service-types-focused')
            <!-- لوحة تحكم إدارة الخدمات -->
            @include('livewire.agency.dashboard.service-types-focused')
            @break
        
        @case('sales-focused')
            <!-- لوحة تحكم إدارة المبيعات -->
            @include('livewire.agency.dashboard.sales-focused')
            @break
        
        @case('hr-focused')
            <!-- لوحة تحكم إدارة الموارد البشرية -->
            @include('livewire.agency.dashboard.hr-focused')
            @break
        
        @default
            <!-- لوحة التحكم المبسطة -->
            @include('livewire.agency.dashboard.simple')
    @endswitch
</div> 