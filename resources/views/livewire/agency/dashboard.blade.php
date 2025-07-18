<div>
    @switch($this->dashboardType)
        @case('comprehensive')
            @include('livewire.agency.dashboard.comprehensive')
            @break
        @case('roles-focused')
            @include('livewire.agency.dashboard.roles-focused')
            @break
        @case('users-focused')
            @include('livewire.agency.dashboard.users-focused')
            @break
        @case('permissions-focused')
            @include('livewire.agency.dashboard.permissions-focused')
            @break
        @case('service-types-focused')
            @include('livewire.agency.dashboard.service-types-focused')
            @break
        @case('sales-focused')
            @include('livewire.agency.dashboard.sales-focused')
            @break
        @case('hr-focused')
            @include('livewire.agency.dashboard.hr-focused')
            @break
        @default
            @include('livewire.agency.dashboard.simple')
    @endswitch
</div> 