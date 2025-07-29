@php
    use App\Services\ThemeService;
    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);
    
    // استخراج قيم RGB للألوان
    $primary100 = $colors['primary-100'];
    $primary500 = $colors['primary-500'];
    $primary600 = $colors['primary-600'];
    
    // تحضير ألوان متدرجة من الأغمق للأفتح بناءً على لون الثيم
    $letterColors = [
        "rgb($primary600)", // الأغمق
        "rgb($primary500)",
        "rgba($primary500, 0.9)",
        "rgba($primary500, 0.8)",
        "rgba($primary500, 0.7)",
        "rgba($primary500, 0.6)",
        "rgba($primary500, 0.5)",
        "rgb($primary100)"  // الأفتح
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <title>نظام إدارة وكالات السفر</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            background-color: #f5f5f5;
            font-family: 'Tajawal', 'Arial', sans-serif;
            overflow: hidden;
            direction: rtl;
        }
        
        .main-container {
            display: flex;
            width: 100%;
            height: 100%;
        }
        
        .letter-container {
            display: flex;
            width: 50%;
            height: 100%;
            align-items: center;
            justify-content: flex-start;
        }
        
        .letter {
            font-size: 8rem;
            font-weight: bold;
            color: white;
            opacity: 0;
            width: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.8s, transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: translateX(100px);
            height: 100%;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        /* ألوان الأحرف بناءً على الثيم */
        .letter:nth-child(1) { background-color: {{ $letterColors[0] }}; } /* T */
        .letter:nth-child(2) { background-color: {{ $letterColors[1] }}; } /* R */
        .letter:nth-child(3) { background-color: {{ $letterColors[2] }}; } /* A */
        .letter:nth-child(4) { background-color: {{ $letterColors[3] }}; } /* V */
        .letter:nth-child(5) { background-color: {{ $letterColors[4] }}; } /* E */
        .letter:nth-child(6) { background-color: {{ $letterColors[5] }}; } /* L */
        .letter:nth-child(7) { background-color: {{ $letterColors[6] }}; } /* - */
        .letter:nth-child(8) { background-color: {{ $letterColors[7] }}; } /* X */
        
        .content-container {
            width: 50%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 1s ease, transform 1s ease;
        }
        
        .welcome-text {
            margin-bottom: 2rem;
        }

        .welcome-heading {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 0.5rem;
            font-weight: 300;
            letter-spacing: 1px;
            opacity: 0;
            transform: translateY(20px);
        }

        .title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            line-height: 1.2;
            opacity: 0;
            transform: translateY(30px);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .highlight-text {
            color: #333;
            position: relative;
            display: inline-block;
        }

        .highlight-text::after {
            content: '';
            position: absolute;
            bottom: 10px;
            left: 0;
            width: 100%;
            height: 12px;
            background: rgba({{ $primary500 }}, 0.2);
            z-index: -1;
            border-radius: 3px;
        }

        .tagline {
            font-size: 1.4rem;
            color: #555;
            font-weight: 300;
            margin-top: 0.5rem;
            opacity: 0;
            transform: translateY(20px);
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .gradient-text {
            background: linear-gradient(to right, rgb({{ $primary500 }}), rgb({{ $primary600 }}));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .description {
            font-size: 1.2rem;
            color: #555;
            line-height: 1.9;
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .text-feature {
            font-weight: 700;
            color: rgb({{ $primary600 }});
        }
        
        .text-underline {
            position: relative;
        }
        
        .text-underline::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, rgba({{ $primary500 }}, 0.8), transparent);
        }
        
        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s;
        }
        
        .feature-item:hover {
            transform: translateX(-10px);
        }
        
        .feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: rgba({{ $primary500 }}, 0.1);
        }
        
        .feature-text {
            color: #555;
        }
        
        .cta-button {
            padding: 0.8rem 1.5rem;
            background: linear-gradient(to right, rgb({{ $primary500 }}), rgb({{ $primary600 }}));
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            align-self: flex-start;
            margin-top: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba({{ $primary500 }}, 0.3);
        }
        
        .trust-badges {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .trust-badge {
            padding: 0.5rem 1rem;
            background: #f0fdf4;
            border-radius: 0.5rem;
            color: rgb({{ $primary500 }});
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .letter-container, .content-container {
                width: 100%;
            }
            
            .letter-container {
                height: 15vh;
                order: -1;
                justify-content: center;
            }
            
            .letter {
                font-size: 3rem;
                width: 60px;
            }
            
            .content-container {
                padding: 1.5rem;
            }
            
            .title {
                font-size: 2rem;
            }
            
            .welcome-heading {
                font-size: 1.2rem;
            }
            
            .tagline {
                font-size: 1.1rem;
            }
            
            .description {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content-container">
            <div class="welcome-text">
                <div class="welcome-heading">مرحباً بكم في</div>
                <h1 class="title">
                    <span class="gradient-text">نظام إدارة</span>
                    <span class="highlight-text">وكالات السفر</span>
                </h1>
                <div class="tagline">الحل الأمثل لإدارة أعمالك السياحية</div>
            </div>
            
            <p class="description">
                منصة متكاملة <span class="text-feature">ذكية</span> و<span class="text-feature">احترافية</span> لإدارة الحجوزات والعملاء والرحلات السياحية<br>
                تم تصميم النظام ليساعدك على <span class="text-underline">تطوير أعمالك</span> و<span class="text-underline">زيادة أرباحك</span> بأحدث التقنيات
            </p>
            
            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgb({{ $primary500 }})" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <span class="feature-text">إدارة شاملة للحجوزات والعملاء</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgb({{ $primary500 }})" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <span class="feature-text">تقارير وإحصائيات مفصلة</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgb({{ $primary500 }})" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <span class="feature-text">واجهة سهلة الاستخدام ومتجاوبة</span>
                </div>
            </div>
            
            <a href="/login" class="cta-button">ابدأ الآن</a>
            
            <div class="trust-badges">
                <div class="trust-badge">⭐ نظام آمن</div>
                <div class="trust-badge">🛡️ حماية بيانات</div>
            </div>
        </div>
        <div class="letter-container">
            <!-- ترتيب الأحرف من اليمين لليسار -->
            <div class="letter">X</div>
            <div class="letter">-</div>
            <div class="letter">L</div>
            <div class="letter">E</div>
            <div class="letter">V</div>
            <div class="letter">A</div>
            <div class="letter">R</div>
            <div class="letter">T</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const letters = document.querySelectorAll('.letter');
            const content = document.querySelector('.content-container');
            const welcomeHeading = document.querySelector('.welcome-heading');
            const title = document.querySelector('.title');
            const tagline = document.querySelector('.tagline');
            const description = document.querySelector('.description');
            const delayBetweenLetters = 300;
            
            // إظهار المحتوى أولاً
            setTimeout(() => {
                content.style.opacity = '1';
                content.style.transform = 'translateY(0)';
                
                // تأثيرات النصوص
                welcomeHeading.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                welcomeHeading.style.opacity = '1';
                welcomeHeading.style.transform = 'translateY(0)';
                
                setTimeout(() => {
                    title.style.transition = 'opacity 0.8s ease 0.2s, transform 0.8s ease 0.2s';
                    title.style.opacity = '1';
                    title.style.transform = 'translateY(0)';
                    
                    setTimeout(() => {
                        tagline.style.transition = 'opacity 0.8s ease 0.3s, transform 0.8s ease 0.3s';
                        tagline.style.opacity = '1';
                        tagline.style.transform = 'translateY(0)';
                        
                        setTimeout(() => {
                            description.style.transition = 'opacity 1s ease 0.4s, transform 1s ease 0.4s';
                            description.style.opacity = '1';
                            description.style.transform = 'translateY(0)';
                        }, 200);
                    }, 200);
                }, 200);
            }, 500);
            
            // ظهور الأحرف بالتتابع من اليمين إلى اليسار
            letters.forEach((letter, index) => {
                setTimeout(() => {
                    letter.style.opacity = '1';
                    letter.style.transform = 'translateX(0)';
                    
                    // تأثير خاص لكل حرف عند ظهوره
                    letter.animate([
                        { transform: 'translateX(0) scale(1)' },
                        { transform: 'translateX(10px) scale(1.05)' },
                        { transform: 'translateX(0) scale(1)' }
                    ], {
                        duration: 400,
                        iterations: 1
                    });
                }, 800 + ((letters.length - 1 - index) * delayBetweenLetters));
            });
        });
    </script>
</body>
</html>