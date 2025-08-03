<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'إدارة النظام' }}</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
<x-theme.theme-provider />

<style>
        body.bg-dashboard {
            background: #e7e8fd !important;
        }

        .nav-item {
            transition: all 0.2s ease;
        }

        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-text {
            transition: opacity 0.2s ease, max-width 0.2s ease;
            opacity: 0;
            max-width: 0;
            overflow: hidden;
        }

        .nav-item.active .nav-text,
        .nav-item:hover .nav-text {
            opacity: 1;
            max-width: 100px;
        }

        .nav-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .nav-item:hover .nav-icon,
        .nav-item.active .nav-icon {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-dropdown-menu {
            display: none;
            position: absolute;
            left: 0;
            top: 110%;
            margin-top: 8px;
            min-width: 180px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            z-index: 50;
        }

        .group-user-dropdown:hover .user-dropdown-menu,
        .group-user-dropdown:focus-within .user-dropdown-menu {
            display: block;
        }
    </style>
