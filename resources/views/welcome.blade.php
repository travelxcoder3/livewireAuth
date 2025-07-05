<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>وكالتي | إدارة وكالات السفر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
            <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 3rem 1rem 3rem;
        }
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
            letter-spacing: 1px;
        }
        .auth-links a {
            margin-left: 1.5rem;
            text-decoration: none;
            color: #3b82f6;
            font-weight: 500;
            font-size: 1rem;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 0.4rem 1.2rem;
            transition: background 0.2s, color 0.2s;
        }
        .auth-links a.signup {
            background: #3b82f6;
            color: #fff;
        }
        .auth-links a:hover {
            background: #2563eb;
            color: #fff;
        }
        .main-section {
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            padding: 0 3rem;
        }
        .main-content {
            max-width: 480px;
            margin-left: 2rem;
        }
        .main-content h1 {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 1.2rem;
            color: #22223b;
        }
        .main-content p {
            font-size: 1.1rem;
            color: #4b5563;
            margin-bottom: 2.2rem;
        }
        .main-content .get-started {
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.8rem 2.2rem;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .main-content .get-started:hover {
            background: #059669;
        }
        .illustration {
            max-width: 400px;
            width: 100%;
        }
        @media (max-width: 900px) {
            .main-section {
                flex-direction: column;
                padding: 1rem;
            }
            .main-content {
                margin-left: 0;
                margin-bottom: 2rem;
                text-align: center;
            }
        }
            </style>
    </head>
<body>
    <div class="header">
        <div class="logo">وكالتي</div>
        <div class="auth-links">
            <a href="{{ route('login') }}">تسجيل الدخول</a>
        </div>
    </div>
    <div class="main-section">
        <div class="main-content">
            <h1>كل ملفات وكالتك في مكان واحد آمن، متاح من أي مكان</h1>
            <p>
                نظام إدارة وكالات السفر يوفر لك التحكم الكامل في موظفيك وصلاحياتهم، ويساعدك على تنظيم أعمالك بسهولة وأمان. يمكنك الوصول إلى بياناتك ومشاركتها مع فريقك من أي مكان وفي أي وقت.
            </p>
            <a href="{{ route('login') }}" class="get-started">ابدأ الآن</a>
                </div>
        <div class="illustration">
            <!-- صورة توضيحية SVG بسيطة مكان الرسمة الأصلية -->
            <svg width="100%" height="260" viewBox="0 0 400 260" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="60" y="60" width="220" height="120" rx="18" fill="#e0e7ff"/>
                <rect x="100" y="100" width="140" height="60" rx="10" fill="#fff" stroke="#3b82f6" stroke-width="3"/>
                <circle cx="170" cy="130" r="18" fill="#10b981"/>
                <path d="M165 130l6 6 10-12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="250" y="80" width="60" height="40" rx="8" fill="#fbbf24"/>
                <rect x="80" y="180" width="60" height="20" rx="6" fill="#fbbf24"/>
                <ellipse cx="200" cy="240" rx="120" ry="18" fill="#e5e7eb"/>
                <rect x="300" y="120" width="20" height="60" rx="6" fill="#3b82f6"/>
                <rect x="60" y="120" width="20" height="60" rx="6" fill="#3b82f6"/>
                    </svg>
        </div>
    </div>
    </body>
</html>
