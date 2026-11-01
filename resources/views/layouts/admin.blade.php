<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة تحكم إدارة مزاد سوريا')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#8b1d30',
                            600: '#751424',
                            700: '#5e0f1c',
                            800: '#480a14',
                        }
                    },
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body { font-family: 'Cairo', sans-serif; background: #0f172a; color: #f8fafc; }</style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- Top Admin Header -->
    <header class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center text-white shadow">
                    <i class="fa-solid fa-gauge-high text-amber-400"></i>
                </div>
                <div>
                    <span class="text-lg font-black tracking-tight text-white">إدارة <span class="text-amber-400">مزاد سوريا</span></span>
                    <span class="block text-[10px] text-slate-400 leading-none">Control Panel & Moderation</span>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-1 text-xs font-bold mr-6">
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-brand-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    الرئيسية
                </a>
                <a href="{{ route('admin.categories.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.categories.*') ? 'bg-brand-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    الأقسام والفروع
                </a>
                <a href="{{ route('admin.listings.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.listings.*') ? 'bg-brand-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    إدارة السلع
                </a>
                <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-brand-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    المستخدمين والبائعين
                </a>
                <a href="{{ route('admin.comments.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.comments.*') ? 'bg-brand-500 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    التعليقات
                </a>
                <a href="{{ route('admin.banners.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.banners.*') ? 'bg-brand-500 text-white' : 'text-amber-400 hover:bg-slate-800' }} flex items-center gap-1.5">
                    <i class="fa-solid fa-rectangle-ad"></i>
                    <span>البنرات والسلايدر</span>
                </a>
                <a href="{{ route('admin.security.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.security.*') ? 'bg-rose-700 text-white' : 'text-rose-400 hover:bg-slate-800' }} flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>درع الأمان والهجمات</span>
                </a>
                <a href="{{ route('admin.appearance.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.appearance.*') ? 'bg-brand-500 text-white' : 'text-amber-400 hover:bg-slate-800' }} flex items-center gap-1.5">
                    <i class="fa-solid fa-palette"></i>
                    <span>المظهر والواجهة</span>
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" target="_blank" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 border border-slate-700">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                <span>عرض الموقع</span>
            </a>
            <span class="text-xs text-slate-400 font-bold hidden sm:inline">{{ auth()->user()->name }}</span>
        </div>
    </header>

    <!-- Global Alerts in Admin -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-6 mt-4 w-full">
            <div class="bg-emerald-900/60 border border-emerald-500 text-emerald-200 p-3 rounded-xl text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-6 mt-4 w-full">
            <div class="bg-rose-900/60 border border-rose-500 text-rose-200 p-3 rounded-xl text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-400"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <main class="flex-1 max-w-7xl mx-auto px-6 py-8 w-full">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>

