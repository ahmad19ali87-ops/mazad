<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'مزad سوريا | منصة المزادات وسوق بيع وشراء السلع الجديدة والمستعملة')</title>

    <!-- Google Fonts: Cairo & Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN with RTL support -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fdf4f4',
                            100: '#fbe8e8',
                            500: '#8b1d30', // Mzad Qatar style deep maroon
                            600: '#751424',
                            700: '#5e0f1c',
                            800: '#480a14',
                        },
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    },
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                        tajawal: ['Tajawal', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .mzad-gradient {
            background: linear-gradient(135deg, #8b1d30 0%, #5e0f1c 100%);
        }
        .mzad-card-hover {
            transition: all 0.25s ease-in-out;
        }
        .mzad-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -10px rgba(139, 29, 48, 0.15);
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-slate-50 antialiased">

    <!-- Top Utility Bar -->
    <div class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span><i class="fa-solid fa-clock ml-1 text-amber-400"></i> سوق وسلع كافة المحافظات السورية</span>
                <span class="hidden sm:inline-block">|</span>
                <span class="hidden sm:inline-block"><i class="fa-solid fa-shield-halved ml-1 text-emerald-400"></i> إعلانات موثوقة ومفحوصة</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('listings.index', ['condition' => 'new']) }}" class="hover:text-amber-400 transition">
                    <span class="bg-emerald-600 text-white px-2 py-0.5 rounded text-[10px] font-bold ml-1">جديد</span> جديد
                </a>
                <a href="{{ route('listings.index', ['condition' => 'used']) }}" class="hover:text-amber-400 transition">
                    <span class="bg-blue-600 text-white px-2 py-0.5 rounded text-[10px] font-bold ml-1">مستعمل</span> مستعمل
                </a>
                <a href="{{ route('wanted.index') }}" class="hover:text-amber-400 transition">
                    <span class="bg-amber-500 text-slate-950 px-2 py-0.5 rounded text-[10px] font-black ml-1">مطلوب</span> طلبات الشراء
                </a>
                <a href="{{ route('jobs.index') }}" class="hover:text-amber-400 transition hidden md:inline-block">
                    <i class="fa-solid fa-briefcase text-sky-400 ml-1"></i> وظائف وعمل
                </a>
                <a href="{{ route('services.index') }}" class="hover:text-amber-400 transition hidden md:inline-block">
                    <i class="fa-solid fa-screwdriver-wrench text-amber-400 ml-1"></i> خدمات
                </a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-amber-400 font-bold hover:underline">
                            <i class="fa-solid fa-gauge-high ml-1"></i> لوحة الإدارة
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 flex-shrink-0 group">
                <div class="w-11 h-11 rounded-xl mzad-gradient flex items-center justify-center text-white shadow-md group-hover:scale-105 transition">
                    <i class="fa-solid fa-gavel text-xl text-amber-400"></i>
                </div>
                <div>
                    <span class="text-2xl font-black tracking-tight text-slate-900 group-hover:text-brand-600 transition">مزاد <span class="text-brand-500 font-extrabold">سوريا</span></span>
                    <span class="block text-[10px] text-slate-600 font-semibold leading-none">السوق السوري المفتوح لكافة السلع</span>
                </div>
            </a>

            <!-- Search Bar -->
            <div class="flex-1 max-w-2xl hidden md:block">
                <form action="{{ route('listings.index') }}" method="GET" class="flex items-center">
                    <div class="relative w-full flex">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث عن سيارات، عقارات، هواتف، أثاث، مواصفات..." 
                               class="w-full bg-slate-100 border border-slate-300 text-slate-900 text-sm rounded-r-xl pr-10 pl-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                        <span class="absolute right-3 top-3 text-slate-600">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <select name="condition" class="bg-slate-200 border-y border-slate-300 text-xs font-semibold px-3 text-slate-700 focus:outline-none cursor-pointer">
                            <option value="">كافة الحالات</option>
                            <option value="new" {{ request('condition') == 'new' ? 'selected' : '' }}>جديد فقط</option>
                            <option value="used" {{ request('condition') == 'used' ? 'selected' : '' }}>مستعمل فقط</option>
                        </select>
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-bold px-5 py-2.5 rounded-l-xl transition flex items-center gap-1.5 flex-shrink-0">
                            <span>بحث</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Actions & User -->
            <div class="flex items-center gap-3">
                <!-- Post Ad CTA Button -->
                <a href="{{ route('listings.create') }}" class="mzad-gradient text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow hover:shadow-lg transition flex items-center gap-2 group">
                    <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-amber-400 group-hover:rotate-90 transition duration-300">
                        <i class="fa-solid fa-plus text-xs"></i>
                    </div>
                    <span>أضف إعلانك مجاناً</span>
                </a>

                @auth
                    <!-- Notifications -->
                    <a href="{{ route('notifications') }}" class="relative p-2.5 text-slate-600 hover:text-brand-500 hover:bg-slate-100 rounded-xl transition" title="الإشعارات">
                        <i class="fa-solid fa-bell text-lg"></i>
                        @php
                            $unreadCount = auth()->user()->inAppNotifications()->where('is_read', false)->count();
                        @endphp
                        @if($unreadCount > 0)
                            <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-red-600 text-white rounded-full text-[10px] font-black flex items-center justify-center">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <!-- User Menu -->
                    <div class="relative group" x-data="{ open: false }">
                        <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition border border-slate-200">
                            <div class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="text-xs font-bold text-slate-700 hidden lg:inline-block max-w-[100px] truncate">{{ auth()->user()->name }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-600"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="absolute left-0 mt-1 w-56 bg-white rounded-xl shadow-xl border border-slate-100 py-2 hidden group-hover:block hover:block z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs text-slate-600">تسجيل الدخول كـ</p>
                                <p class="text-sm font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 rounded {{ auth()->user()->role === 'seller' ? 'bg-amber-100 text-amber-800' : (auth()->user()->role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ auth()->user()->role === 'seller' ? 'بائع معتمد' : (auth()->user()->role === 'admin' ? 'مدير النظام' : 'عضو') }}
                                </span>
                            </div>
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-brand-600 hover:bg-brand-50">
                                <i class="fa-solid fa-gauge-high text-brand-500 w-4"></i> لوحة تحكم حسابي
                            </a>
                            <a href="{{ route('my-listings') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-boxes-stacked text-brand-500 w-4"></i> إعلاناتي وسلعي
                            </a>
                            <a href="{{ route('sellers.show', auth()->id()) }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-store text-blue-500 w-4"></i> صفحتي ومتجري
                            </a>
                            <a href="{{ route('notifications') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-bell text-amber-500 w-4"></i> الإشعارات والمتابعات
                            </a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 border-t border-slate-100">
                                    <i class="fa-solid fa-screwdriver-wrench w-4"></i> لوحة تحكم الإدارة
                                </a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" class="border-t border-slate-100 mt-1 pt-1">
                                @csrf
                                <button type="submit" class="w-full text-right flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50">
                                    <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="text-xs font-bold text-slate-700 hover:text-brand-500 px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                            دخول
                        </a>
                        <a href="{{ route('register') }}" class="text-xs font-bold bg-slate-100 text-slate-800 hover:bg-slate-200 px-3 py-2 rounded-lg transition border border-slate-200">
                            حساب جديد
                        </a>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div class="md:hidden px-4 pb-3">
            <form action="{{ route('listings.index') }}" method="GET" class="flex items-center">
                <div class="relative w-full flex">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث عن سيارات، عقارات، هواتف..." 
                           class="w-full bg-slate-100 border border-slate-300 text-slate-900 text-xs rounded-r-xl pr-9 pl-2 py-2 focus:outline-none">
                    <span class="absolute right-3 top-2.5 text-slate-600 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <button type="submit" class="bg-brand-500 text-white text-xs font-bold px-4 py-2 rounded-l-xl">بحث</button>
                </div>
            </form>
        </div>
    </header>

    <!-- Global Alerts -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-emerald-50 border-r-4 border-emerald-500 text-emerald-800 p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                    <p class="text-sm font-bold">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-rose-50 border-r-4 border-rose-500 text-rose-800 p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg"></i>
                    <p class="text-sm font-bold">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-blue-50 border-r-4 border-blue-500 text-blue-800 p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-blue-600 text-lg"></i>
                    <p class="text-sm font-bold">{{ session('info') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Content Block -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 mt-16 pt-12 pb-6 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-10 border-b border-slate-800 text-sm">
                <!-- Col 1: About -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg mzad-gradient flex items-center justify-center text-white">
                            <i class="fa-solid fa-gavel text-amber-400"></i>
                        </div>
                        <span class="text-lg font-black text-white">مزاد سوريا</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        المنصة الأولى المفتوحة لعرض وبيع وشراء كافة البضائع والسلع الجديدة والمستعملة في جميع المحافظات والمناطق السورية، مع ميزة متابعة البائعين وتوثيق الإعلانات.
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">تابعنا:</span>
                        <a href="#" class="w-7 h-7 rounded-full bg-slate-800 hover:bg-brand-500 text-white flex items-center justify-center transition text-xs"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-7 h-7 rounded-full bg-slate-800 hover:bg-emerald-600 text-white flex items-center justify-center transition text-xs"><i class="fa-brands fa-whatsapp"></i></a>
                        <a href="#" class="w-7 h-7 rounded-full bg-slate-800 hover:bg-sky-500 text-white flex items-center justify-center transition text-xs"><i class="fa-brands fa-telegram"></i></a>
                    </div>
                </div>

                <!-- Col 2: Categories -->
                <div>
                    <h4 class="text-white font-bold mb-3 text-sm flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-brand-500"></span> الأقسام الرئيسية
                    </h4>
                    <ul class="space-y-1.5 text-xs">
                        <li><a href="{{ route('listings.index', ['category' => 'cars-vehicles']) }}" class="hover:text-amber-400 transition">سيارات ومركبات وقطع غيار</a></li>
                        <li><a href="{{ route('listings.index', ['category' => 'real-estate']) }}" class="hover:text-amber-400 transition">عقارات وشقق وأملاك</a></li>
                        <li><a href="{{ route('listings.index', ['category' => 'electronics']) }}" class="hover:text-amber-400 transition">إلكترونيات وموبايلات وطاقة</a></li>
                        <li><a href="{{ route('listings.index', ['category' => 'furniture-decor']) }}" class="hover:text-amber-400 transition">أثاث ومفروشات منزلية</a></li>
                        <li><a href="{{ route('listings.index', ['category' => 'fashion-beauty']) }}" class="hover:text-amber-400 transition">أزياء وموضة وساعات</a></li>
                    </ul>
                </div>

                <!-- Col 3: Governorates -->
                <div>
                    <h4 class="text-white font-bold mb-3 text-sm flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> تغطية المحافظات
                    </h4>
                    <div class="grid grid-cols-2 gap-1.5 text-xs">
                        <a href="{{ route('listings.index', ['governorate_id' => 1]) }}" class="hover:text-amber-400 transition">دمشق العاصمة</a>
                        <a href="{{ route('listings.index', ['governorate_id' => 2]) }}" class="hover:text-amber-400 transition">ريف دمشق</a>
                        <a href="{{ route('listings.index', ['governorate_id' => 3]) }}" class="hover:text-amber-400 transition">حلب الشهباء</a>
                        <a href="{{ route('listings.index', ['governorate_id' => 4]) }}" class="hover:text-amber-400 transition">حمص العدية</a>
                        <a href="{{ route('listings.index', ['governorate_id' => 5]) }}" class="hover:text-amber-400 transition">اللاذقية عروس الساحل</a>
                        <a href="{{ route('listings.index', ['governorate_id' => 6]) }}" class="hover:text-amber-400 transition">طرطوس</a>
                    </div>
                </div>

                <!-- Col 4: Safety & Condition -->
                <div>
                    <h4 class="text-white font-bold mb-3 text-sm flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span> الأمان وسرعة البيع
                    </h4>
                    <p class="text-xs text-slate-400 leading-relaxed mb-3">
                        نظام مزاد سوريا يتيح لك تمييز السلع بين <strong>جديد</strong> و <strong>مستعمل</strong>، ومتابعة البائعين لتصلك إشعارات حية بالقطع الجديدة.
                    </p>
                    <a href="{{ route('listings.create') }}" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-white text-xs px-3.5 py-2 rounded-lg border border-slate-700 transition">
                        <i class="fa-solid fa-camera text-brand-500"></i> اعرض بضاعتك الآن مجاناً
                    </a>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 gap-2">
                <p>© {{ date('Y') }} منصة مزاد سوريا (Mzad Syria). جميع الحقوق محفوظة.</p>
                <p>مبني بأحدث تقنيات Laravel و Flutter وفق أعلى معايير الأمان والتوافقية.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

