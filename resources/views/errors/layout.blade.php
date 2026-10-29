<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'حدث خطأ') | مزاد سوريا</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fdf2f4',
                            500: '#8b1d30',
                            600: '#751727',
                            700: '#5e0f1c',
                        }
                    },
                    fontFamily: {
                        sans: ['Tajawal', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8fafc;
        }
        .mzad-gradient {
            background: linear-gradient(135deg, #8b1d30 0%, #5e0f1c 100%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between text-slate-800 antialiased">

    <!-- Header Minimal -->
    <header class="bg-white border-b border-slate-200 py-3 px-4 shadow-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <div class="w-10 h-10 rounded-xl mzad-gradient flex items-center justify-center text-white shadow-sm">
                    <i class="fa-solid fa-gavel text-amber-400"></i>
                </div>
                <div>
                    <span class="text-xl font-black text-slate-900 group-hover:text-brand-500 transition">مزاد <span class="text-brand-500">سوريا</span></span>
                </div>
            </a>

            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" class="text-xs font-bold text-slate-600 hover:text-brand-500 px-3 py-1.5 rounded-lg border border-slate-200 transition">
                    <i class="fa-solid fa-house ml-1"></i> الرئيسية
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center p-4">
        <div class="max-w-xl w-full bg-white rounded-3xl p-8 md:p-10 shadow-xl border border-slate-200/80 text-center relative overflow-hidden">
            <div class="absolute -top-16 -right-16 w-40 h-40 bg-brand-500/5 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-40 h-40 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

            @yield('content')

            <!-- Support & Contact Notice (كما طلب المستخدم: يرجى الاتصال بادارة النظام) -->
            <div class="mt-8 pt-6 border-t border-slate-100 bg-slate-50/70 -mx-8 -mb-8 p-6 rounded-b-3xl text-xs text-slate-500">
                <p class="font-bold text-slate-700 mb-1.5 flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-headset text-brand-500"></i>
                    <span>يرجى الاتصال بإدارة النظام إذا استمر هذا الخطأ:</span>
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4 mt-2">
                    <a href="mailto:support@mazad.sy" class="text-brand-500 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-envelope"></i> support@mazad.sy
                    </a>
                    <span class="text-slate-300">•</span>
                    <a href="https://wa.me/963944112233" target="_blank" class="text-emerald-600 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-brands fa-whatsapp"></i> واتساب الدعم الفني
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Minimal -->
    <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
        © {{ date('Y') }} منصة مزاد سوريا - جميع الحقوق محفوظة
    </footer>

</body>
</html>

