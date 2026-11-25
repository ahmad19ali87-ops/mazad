<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول عبر Google | مزاد سوريا</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 28px 24px;
            max-width: 380px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .btn {
            display: inline-block;
            margin-top: 16px;
            background: #8b1d30;
            color: #fff;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 14px;
        }
        .msg { font-size: 14px; line-height: 1.7; color: #334155; }
    </style>
</head>
<body>
    <div class="card">
        <h3 style="margin-top:0;color:{{ $isSuccess ? '#059669' : '#dc2626' }}">
            {{ $isSuccess ? '✓ تم التحقق من حساب Google' : 'تعذر تسجيل الدخول' }}
        </h3>
        <p class="msg">{{ $message }}</p>
        <a id="returnBtn" class="btn" href="{{ $deepLink }}">العودة إلى تطبيق مزاد سوريا</a>
    </div>
    <script>
        (function () {
            var target = @json($deepLink);
            window.location.replace(target);
        })();
    </script>
</body>
</html>
