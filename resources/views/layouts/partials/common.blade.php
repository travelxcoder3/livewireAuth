@php
    use App\Services\ThemeService;

    $themeName = Auth::user()?->agency->theme_color ?? ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors(strtolower($themeName));
@endphp

<!-- ========== متغيرات الثيم الديناميكية ========== -->
<style>
    :root {
        --primary-100: {{ $colors['primary-100'] }};
        --primary-500: {{ $colors['primary-500'] }};
        --primary-600: {{ $colors['primary-600'] }};
    }
</style>

<!-- ========== تنسيقات عامة وتفاعلية ========== -->
<style>
    /* تركيز الحقول */
    input:focus,
    select:focus,
    textarea:focus {
        border-color: rgb(var(--primary-500)) !important;
        box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2) !important;
        outline: none;
    }

    /* زر الإرسال */
    button[type="submit"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
    }

    button[type="submit"]:active {
        transform: translateY(0);
    }

    /* تصميم القائمة الجانبية */
    .nav-gradient {
        background: linear-gradient(90deg, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);
    }

    .border-theme {
        border-color: rgb(var(--primary-500));
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
        box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.08);
        z-index: 50;
    }

    .group-user-dropdown:focus-within .user-dropdown-menu,
    .group-user-dropdown:hover .user-dropdown-menu {
        display: block;
    }

    /* ========== الحقول بخصائص floating label ========== */
    .peer:placeholder-shown+label {
        top: 0.75rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .peer:not(:placeholder-shown)+label,
    .peer:focus+label {
        top: -0.5rem;
        font-size: 0.75rem;
        color: rgb(var(--primary-600));
    }

    select:required:invalid {
        color: #6b7280;
    }

    select option {
        color: #111827;
    }

    /* ========== تأثيرات واجهة الموظفين ========== */
    button[wire\:click="createEmployee"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
    }

    button[wire\:click="createEmployee"]:active {
        transform: translateY(0);
    }

    form button[type="submit"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
    }

    form button[type="submit"]:active {
        transform: translateY(0);
    }

    html, body {
        height: 100%;
    }

    @media (max-width: 1024px) {
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
    }
</style>

<!-- ========== مكتبة Chart.js ========== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ========== دوال JavaScript مشتركة ========== -->
<script>
    window.renderAgencyStatusChart = function(canvasId, active, pending, inactive) {
        function getThemeColor(variable, fallback) {
            const val = getComputedStyle(document.documentElement).getPropertyValue(variable);
            return val ? 'rgb(' + val.trim() + ')' : fallback;
        }

        const ctx = document.getElementById(canvasId)?.getContext('2d');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['نشطة', 'معلقة', 'غير نشطة'],
                datasets: [{
                    data: [active, pending, inactive],
                    backgroundColor: [
                        getThemeColor('--primary-500', '#10b981'),
                        getThemeColor('--primary-100', '#facc15'),
                        getThemeColor('--primary-600', '#f87171')
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                family: 'Tajawal, sans-serif',
                                size: 14
                            }
                        }
                    }
                }
            }
        });
    };
</script>
