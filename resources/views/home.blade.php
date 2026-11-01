@extends('layouts.app')

@section('title', 'مزاد سوريا | الرئيسية - سوق السلع الجديدة والمستعملة في سوريا')

@section('content')

<!-- Hero & Quick Filter Section (Mzad Qatar Inspired) -->
<section class="mzad-gradient text-white py-10 px-4 shadow-inner relative overflow-hidden">
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10 text-center">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 px-3.5 py-1.5 rounded-full text-xs font-semibold text-amber-300 mb-4">
            <i class="fa-solid fa-sparkles"></i>
            <span>المنصة السورية الأولى لمزادات وسلع البيع والشراء</span>
        </div>
        
        <h1 class="text-3xl md:text-5xl font-black mb-3 tracking-tight">
            سوقك المفتوح لكل السلع في <span class="text-amber-400">سوريا</span>
        </h1>
        <p class="text-slate-200 text-sm md:text-base max-w-2xl mx-auto mb-8 font-light">
            تصفح آلاف الإعلانات المفحوصة، ميز الجديد من المستعمل، تابع بائعيك المفضلين وتواصل فورياً مع أصحاب السلع
        </p>

        <!-- Main Search & Filtering Box -->
        <div class="max-w-4xl mx-auto bg-white rounded-2xl p-3 md:p-4 shadow-2xl text-slate-900 text-right">
            <form action="{{ route('listings.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Keyword Input -->
                <div class="relative">
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">ماذا تبحث عنه؟</label>
                    <input type="text" name="q" placeholder="اسم السلعة، الموديل..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">القسم</label>
                    <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                        <option value="">كافة الأقسام</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->slug }}">{{ $cat->name_ar }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Governorate -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">المحافظة</label>
                    <select name="governorate_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                        <option value="">كافة المحافظات السورية</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}">{{ $gov->name_ar }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Condition & Submit -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">الحالة</label>
                    <div class="flex gap-2">
                        <select name="condition" class="w-1/2 bg-slate-50 border border-slate-200 rounded-xl px-2 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                            <option value="">الكل</option>
                            <option value="new">جديد</option>
                            <option value="used">مستعمل</option>
                        </select>
                        <button type="submit" class="w-1/2 bg-brand-500 hover:bg-brand-600 text-white rounded-xl text-xs font-bold py-2 transition shadow flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>بحث</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quick Stats Pill Badges -->
        <div class="flex flex-wrap justify-center items-center gap-3 mt-6 text-xs text-white/90">
            <span class="bg-white/10 px-3 py-1 rounded-full border border-white/15">
                <i class="fa-solid fa-boxes-stacked text-amber-400 ml-1"></i> {{ number_format($counts['total']) }} إعلان
            </span>
            <span class="bg-white/10 px-3 py-1 rounded-full border border-white/15">
                <i class="fa-solid fa-sparkles text-emerald-400 ml-1"></i> {{ number_format($counts['new']) }} بضاعة جديدة
            </span>
            <span class="bg-white/10 px-3 py-1 rounded-full border border-white/15">
                <i class="fa-solid fa-clock-rotate-left text-sky-400 ml-1"></i> {{ number_format($counts['used']) }} مستعمل
            </span>
            <span class="bg-white/10 px-3 py-1 rounded-full border border-white/15">
                <i class="fa-solid fa-store text-amber-300 ml-1"></i> {{ number_format($counts['sellers']) }} بائع معتمد
            </span>
        </div>

        <!-- Featured Section Direct Links -->
        <div class="flex flex-wrap justify-center items-center gap-2.5 mt-4">
            <a href="{{ route('wanted.index') }}" class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black px-4 py-1.5 rounded-full text-xs shadow-md flex items-center gap-1.5 transition">
                <i class="fa-solid fa-hand-holding-dollar text-slate-900"></i>
                <span>قسم طلبات "مطلوب للشراء"</span>
            </a>
            <a href="{{ route('jobs.index') }}" class="bg-indigo-600/90 hover:bg-indigo-600 text-white font-bold px-4 py-1.5 rounded-full text-xs border border-indigo-400/30 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-briefcase text-indigo-300"></i>
                <span>فرص عمل وباحثون عن عمل</span>
            </a>
            <a href="{{ route('services.index') }}" class="bg-purple-600/90 hover:bg-purple-600 text-white font-bold px-4 py-1.5 rounded-full text-xs border border-purple-400/30 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-screwdriver-wrench text-purple-300"></i>
                <span>خدمات وأعمال ومهن</span>
            </a>
        </div>
    </div>
</section>

<!-- Sponsored Banners 5-Second Carousel -->
@if(isset($banners) && $banners->count() > 0)
<section class="max-w-7xl mx-auto px-4 pt-6 pb-2">
    <div x-data="{
        current: 0,
        total: {{ $banners->count() }},
        timer: null,
        startTimer() {
            this.timer = setInterval(() => {
                this.next();
            }, 5000);
        },
        stopTimer() {
            if (this.timer) clearInterval(this.timer);
        },
        next() {
            this.current = (this.current + 1) % this.total;
        },
        prev() {
            this.current = (this.current - 1 + this.total) % this.total;
        }
    }" 
    x-init="startTimer()" 
    @mouseenter="stopTimer()" 
    @mouseleave="startTimer()"
    class="relative rounded-3xl overflow-hidden shadow-md border border-slate-200/80 bg-slate-900 h-44 sm:h-60 md:h-72 select-none">
        
        @foreach($banners as $index => $banner)
            <div x-show="current === {{ $index }}" 
                 x-transition:enter="transition ease-out duration-700 transform" 
                 x-transition:enter-start="opacity-0 translate-x-8" 
                 x-transition:enter-end="opacity-100 translate-x-0" 
                 x-transition:leave="transition ease-in duration-500 transform" 
                 x-transition:leave-start="opacity-100 translate-x-0" 
                 x-transition:leave-end="opacity-0 -translate-x-8" 
                 class="absolute inset-0 w-full h-full">
                
                <a href="{{ $banner->target_url }}" target="{{ $banner->link_type === 'external' ? '_blank' : '_self' }}" class="block w-full h-full relative group">
                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent flex flex-col justify-end p-5 sm:p-7">
                        <div class="max-w-xl text-right">
                            <span class="inline-block bg-amber-400 text-slate-950 font-black text-[11px] px-3 py-1 rounded-full shadow mb-2">
                                {{ $banner->badge_text ?? 'إعلان ممول ⭐' }}
                            </span>
                            <h3 class="text-lg sm:text-2xl md:text-3xl font-black text-white leading-tight drop-shadow">
                                {{ $banner->title }}
                            </h3>
                            @if($banner->subtitle)
                                <p class="text-xs sm:text-sm text-slate-200 mt-1 line-clamp-2 drop-shadow">
                                    {{ $banner->subtitle }}
                                </p>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach

        <!-- Next / Prev Controls -->
        <button @click.prevent="prev()" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition z-20">
            <i class="fa-solid fa-chevron-right text-xs"></i>
        </button>
        <button @click.prevent="next()" class="absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition z-20">
            <i class="fa-solid fa-chevron-left text-xs"></i>
        </button>

        <!-- Dots indicators -->
        <div class="absolute bottom-3 inset-x-0 flex justify-center gap-1.5 z-20">
            @foreach($banners as $index => $banner)
                <button @click="current = {{ $index }}" 
                        :class="current === {{ $index }} ? 'w-6 bg-amber-400' : 'w-2 bg-white/50'" 
                        class="h-1.5 rounded-full transition-all duration-300"></button>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Categories Mini-cards Grid (Mzad Qatar Style) -->
<section class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-xl md:text-2xl font-black text-slate-900">تصفح حسب الأقسام</h2>
            <p class="text-xs text-slate-500 mt-1">اختر القسم لاستعراض الفروع والسلع المتاحة</p>
        </div>
        <a href="{{ route('listings.index') }}" class="text-xs font-bold text-brand-500 hover:text-brand-600 flex items-center gap-1 group">
            <span>استعراض كافة السلع</span>
            <i class="fa-solid fa-arrow-left text-[10px] group-hover:-translate-x-1 transition"></i>
        </a>
    </div>

    <!-- Category Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-8 gap-3">
        @php
            $iconMap = [
                'cars-vehicles' => 'fa-car',
                'real-estate' => 'fa-city',
                'electronics' => 'fa-mobile-screen-button',
                'furniture-decor' => 'fa-couch',
                'fashion-beauty' => 'fa-shirt',
                'services-jobs' => 'fa-screwdriver-wrench',
                'jobs-employment' => 'fa-user-check',
                'animals-pets' => 'fa-paw',
                'industrial-construction' => 'fa-trowel-bricks',
            ];
            $colorMap = [
                'cars-vehicles' => 'bg-red-50 text-red-600 border-red-100 group-hover:bg-red-600',
                'real-estate' => 'bg-blue-50 text-blue-600 border-blue-100 group-hover:bg-blue-600',
                'electronics' => 'bg-emerald-50 text-emerald-600 border-emerald-100 group-hover:bg-emerald-600',
                'furniture-decor' => 'bg-amber-50 text-amber-600 border-amber-100 group-hover:bg-amber-600',
                'fashion-beauty' => 'bg-pink-50 text-pink-600 border-pink-100 group-hover:bg-pink-600',
                'services-jobs' => 'bg-purple-50 text-purple-600 border-purple-100 group-hover:bg-purple-600',
                'jobs-employment' => 'bg-indigo-50 text-indigo-600 border-indigo-100 group-hover:bg-indigo-600',
                'animals-pets' => 'bg-teal-50 text-teal-600 border-teal-100 group-hover:bg-teal-600',
                'industrial-construction' => 'bg-orange-50 text-orange-600 border-orange-100 group-hover:bg-orange-600',
            ];
        @endphp

        @foreach($categories as $category)
            @php
                $iconClass = $iconMap[$category->slug] ?? 'fa-box';
                $themeColor = $colorMap[$category->slug] ?? 'bg-slate-50 text-slate-600 border-slate-100 group-hover:bg-brand-500';
            @endphp
            <a href="{{ route('categories.show', $category->slug) }}" 
               class="bg-white p-3 rounded-2xl border border-slate-200/80 hover:border-brand-500/40 text-center mzad-card-hover group flex flex-col items-center justify-center">
                
                <div class="w-12 h-12 rounded-xl {{ $themeColor }} group-hover:text-white flex items-center justify-center text-xl mb-2.5 transition duration-300">
                    <i class="fa-solid {{ $iconClass }}"></i>
                </div>
                
                <h3 class="text-xs font-bold text-slate-800 group-hover:text-brand-500 transition line-clamp-1 mb-1">
                    {{ $category->name_ar }}
                </h3>
                
                <span class="text-[10px] text-slate-600 font-semibold">
                    {{ $category->listings_count }} إعلان
                </span>
            </a>
        @endforeach
    </div>
</section>

<!-- Featured Listings Section -->
@if($featuredListings->count() > 0)
<section class="bg-amber-50/40 py-10 border-y border-amber-100/60">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                <h2 class="text-xl md:text-2xl font-black text-slate-900">إعلانات مميزة (VIP)</h2>
            </div>
            <a href="{{ route('listings.index', ['featured' => 1]) }}" class="text-xs font-bold text-brand-500 hover:text-brand-600">
                مشاهدة الكل <i class="fa-solid fa-arrow-left text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($featuredListings as $item)
                @include('components.listing-card', ['listing' => $item, 'isFeatured' => true])
            @endforeach
        </div>
    </div>
</section>

<!-- Sponsored Banners 5-Second Carousel -->
@if(isset($banners) && $banners->count() > 0)
<section class="max-w-7xl mx-auto px-4 pt-6 pb-2">
    <div x-data="{
        current: 0,
        total: {{ $banners->count() }},
        timer: null,
        startTimer() {
            this.timer = setInterval(() => {
                this.next();
            }, 5000);
        },
        stopTimer() {
            if (this.timer) clearInterval(this.timer);
        },
        next() {
            this.current = (this.current + 1) % this.total;
        },
        prev() {
            this.current = (this.current - 1 + this.total) % this.total;
        }
    }" 
    x-init="startTimer()" 
    @mouseenter="stopTimer()" 
    @mouseleave="startTimer()"
    class="relative rounded-3xl overflow-hidden shadow-md border border-slate-200/80 bg-slate-900 h-44 sm:h-60 md:h-72 select-none">
        
        @foreach($banners as $index => $banner)
            <div x-show="current === {{ $index }}" 
                 x-transition:enter="transition ease-out duration-700 transform" 
                 x-transition:enter-start="opacity-0 translate-x-8" 
                 x-transition:enter-end="opacity-100 translate-x-0" 
                 x-transition:leave="transition ease-in duration-500 transform" 
                 x-transition:leave-start="opacity-100 translate-x-0" 
                 x-transition:leave-end="opacity-0 -translate-x-8" 
                 class="absolute inset-0 w-full h-full">
                
                <a href="{{ $banner->target_url }}" target="{{ $banner->link_type === 'external' ? '_blank' : '_self' }}" class="block w-full h-full relative group">
                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent flex flex-col justify-end p-5 sm:p-7">
                        <div class="max-w-xl text-right">
                            <span class="inline-block bg-amber-400 text-slate-950 font-black text-[11px] px-3 py-1 rounded-full shadow mb-2">
                                {{ $banner->badge_text ?? 'إعلان ممول ⭐' }}
                            </span>
                            <h3 class="text-lg sm:text-2xl md:text-3xl font-black text-white leading-tight drop-shadow">
                                {{ $banner->title }}
                            </h3>
                            @if($banner->subtitle)
                                <p class="text-xs sm:text-sm text-slate-200 mt-1 line-clamp-2 drop-shadow">
                                    {{ $banner->subtitle }}
                                </p>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach

        <!-- Next / Prev Controls -->
        <button @click.prevent="prev()" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition z-20">
            <i class="fa-solid fa-chevron-right text-xs"></i>
        </button>
        <button @click.prevent="next()" class="absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition z-20">
            <i class="fa-solid fa-chevron-left text-xs"></i>
        </button>

        <!-- Dots indicators -->
        <div class="absolute bottom-3 inset-x-0 flex justify-center gap-1.5 z-20">
            @foreach($banners as $index => $banner)
                <button @click="current = {{ $index }}" 
                        :class="current === {{ $index }} ? 'w-6 bg-amber-400' : 'w-2 bg-white/50'" 
                        class="h-1.5 rounded-full transition-all duration-300"></button>
            @endforeach
        </div>
    </div>
</section>
@endif
@endif

<!-- Latest Listings with New/Used Filter Tabs -->
<section class="max-w-7xl mx-auto px-4 py-12">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-xl md:text-2xl font-black text-slate-900">أحدث السلع المعروضة للبيع</h2>
            <p class="text-xs text-slate-500 mt-1">تحديث لحظي لكافة السلع في السوق السوري</p>
        </div>

        <!-- Filter Pills: All / New / Used -->
        <div class="flex items-center bg-slate-200/80 p-1 rounded-xl text-xs font-bold">
            <a href="{{ route('listings.index') }}" class="px-3 py-1.5 rounded-lg {{ !request('condition') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                الكل
            </a>
            <a href="{{ route('listings.index', ['condition' => 'new']) }}" class="px-3 py-1.5 rounded-lg {{ request('condition') == 'new' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-certificate text-[10px] ml-1"></i> جديد زيرو
            </a>
            <a href="{{ route('listings.index', ['condition' => 'used']) }}" class="px-3 py-1.5 rounded-lg {{ request('condition') == 'used' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-recycle text-[10px] ml-1"></i> مستعمل
            </a>
        </div>
    </div>

    <!-- Listings Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($latestListings as $item)
            @include('components.listing-card', ['listing' => $item])
        @endforeach
    </div>

    <div class="text-center mt-10">
        <a href="{{ route('listings.index') }}" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-brand-500 text-white text-xs font-bold px-6 py-3 rounded-xl transition shadow">
            <span>استعراض المزيد من السلع والسيارات والعقارات</span>
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
        </a>
    </div>
</section>

<!-- Follow Seller Feature Highlight Banner -->
<section class="max-w-7xl mx-auto px-4 pb-12">
    <div class="bg-gradient-to-r from-slate-900 via-brand-700 to-slate-900 rounded-3xl p-6 md:p-10 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="max-w-xl text-right">
            <div class="inline-flex items-center gap-2 bg-amber-400 text-slate-950 px-3 py-1 rounded-full text-xs font-black mb-3">
                <i class="fa-solid fa-bell"></i>
                <span>ميزة جديدة وحصرية</span>
            </div>
            <h3 class="text-2xl md:text-3xl font-black mb-2">تابع البائعين لتصلك إشعارات فورية!</h3>
            <p class="text-xs md:text-sm text-slate-300 leading-relaxed font-light">
                هل تبحث عن عروض سيارات معينة أو هواتف بأسعار مناسبة؟ اضغط زر <strong>"متابعة"</strong> في صفحة أي بائع أو معرض، وسيصلك إشعار لحظي فور إضافته أي سلعة جديدة مع كامل المواصفات والصور.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <a href="{{ route('listings.create') }}" class="w-full sm:w-auto bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs px-5 py-3 rounded-xl transition shadow flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus-circle"></i>
                <span>ابدأ البيع الآن مجاناً</span>
            </a>
            <a href="{{ route('register') }}" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>تسجيل حساب مجاني</span>
            </a>
        </div>
    </div>
</section>

@endsection

