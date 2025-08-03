@props([
    'title' => '',
    'icon' => 'chart-bar',
    'value' => 0,
    'color' => 'primary' // options: primary, green, blue, orange, purple, red
])

@php
    $colors = [
        'primary' => '--primary-500',
        'green' => '--green-500',
        'blue' => '--blue-500',
        'orange' => '--orange-500',
        'purple' => '--purple-500',
        'red' => '--red-500',
    ];

    $iconColors = [
        'primary' => '--primary-600',
        'green' => '--green-600',
        'blue' => '--blue-600',
        'orange' => '--orange-600',
        'purple' => '--purple-600',
        'red' => '--red-600',
    ];
@endphp

<div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200">
    <div class="flex items-start justify-between">
        <div class="p-3 rounded-lg mr-3"
             style="background-color: rgba(var({{ $colors[$color] ?? '--primary-500' }}), 0.1); color: rgb(var({{ $iconColors[$color] ?? '--primary-600' }}));">
            <i class="fas fa-{{ $icon }} text-lg"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $title }}</p>
            <p class="text-2xl font-bold mt-1" style="color: rgb(var({{ $iconColors[$color] ?? '--primary-600' }}));">
                {{ $value }}
            </p>
        </div>
    </div>
    <div class="mt-3 h-1 rounded-full overflow-hidden bg-gray-100">
        <div class="h-full transition-all duration-300"
             style="width: 100%; background-color: rgb(var({{ $colors[$color] ?? '--primary-500' }}));"></div>
    </div>
</div>
