@extends('layouts.app')

@section('title', 'مزاد سوريا | استعراض والبحث في كافة السلع والبضائع المعروضة للبيع')

@section('content')

<!-- Header Breadcrumb & Quick Title -->
<div class="bg-white border-b border-slate-200 py-4">
    <div class="max-w-7xl mx-auto px-4 flex flex-wrap justify-between items-center gap-2">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900">سوق السلع والمعروضات</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                تصفح وبحث متقدم بين السلع الجديدة والمستعملة في كافة المحافظات
            </p>
        </div>
        <div class="text-xs text-slate-500 flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="hover:text-brand-500">الرئيسية</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">كافة السلع</span>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Sidebar Filters -->
        <div class="lg:col-span-1">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm sticky top-24">
                <div class="flex justify-between items-center pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-brand-500"></i> تصفية النتائج
                    </h3>
                    @if(request()->anyFilled(['q', 'category', 'subcategory', 'condition', 'governorate_id', 'min_price', 'max_price', 'currency']))
                        <a href="{{ route('listings.index') }}" class="text-[11px] text-red-500 hover:underline font-bold">
                            إلغاء الفلاتر
                        </a>
                    @endif
                </div>

                <form action="{{ route('listings.index') }}" method="GET" class="space-y-4">
                    <!-- Search Text -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">كلمات البحث</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو المواصفة..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <!-- Listing Type: Sale vs Wanted -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الإعلان</label>
                        <div class="grid grid-cols-3 gap-1 bg-slate-100 p-1 rounded-xl text-center text-xs font-bold">
                            <label class="cursor-pointer">
                                <input type="radio" name="listing_type" value="" {{ !request('listing_type') ? 'checked' : '' }} class="peer hidden" onchange="this.form.submit()">
                                <div class="py-1.5 rounded-lg text-slate-600 peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm transition">
                                    الكل
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="listing_type" value="sale" {{ request('listing_type') == 'sale' ? 'checked' : '' }} class="peer hidden" onchange="this.form.submit()">
                                <div class="py-1.5 rounded-lg text-slate-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-sm transition">
                                    للبيع
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="listing_type" value="wanted" {{ request('listing_type') == 'wanted' ? 'checked' : '' }} class="peer hidden" onchange="this.form.submit()">
                                <div class="py-1.5 rounded-lg text-slate-600 peer-checked:bg-amber-600 peer-checked:text-white peer-checked:shadow-sm transition">
                                    مطلوب
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Condition: New vs Used (الجديد من القديم) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">حالة السلعة</label>
                        <div class="grid grid-cols-3 gap-1 bg-slate-100 p-1 rounded-xl text-center text-xs font-bold">
                            <label class="cursor-pointer">
                                <input type="radio" name="condition" value="" {{ !request('condition') ? 'checked' : '' }} class="peer hidden" onchange="this.form.submit()">
                                <div class="py-1.5 rounded-lg text-slate-600 peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm transition">
                                    الكل
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="condition" value="new" {{ request('condition') == 'new' ? 'checked' : '' }} class="peer hidden" onchange="this.form.submit()">
                                <div class="py-1.5 rounded-lg text-slate-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-sm transition">
                                    جديد
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="condition" value="used" {{ request('condition') == 'used' ? 'checked' : '' }} class="peer hidden" onchange="this.form.submit()">
                                <div class="py-1.5 rounded-lg text-slate-600 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm transition">
                                    مستعمل
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">القسم الرئيسي</label>
                        <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer" onchange="this.form.submit()">
                            <option value="">كافة الأقسام</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                                    {{ $cat->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Governorate -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">المحافظة</label>
                        <select name="governorate_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer" onchange="this.form.submit()">
                            <option value="">كافة المحافظات السورية</option>
                            @foreach($governorates as $gov)
                                <option value="{{ $gov->id }}" {{ request('governorate_id') == $gov->id ? 'selected' : '' }}>
                                    {{ $gov->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Range & Currency -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">السعر والعملة</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="من" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="إلى" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                        </div>
                        <select name="currency" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                            <option value="">أي عملة (ل.س أو $)</option>
                            <option value="SYP" {{ request('currency') == 'SYP' ? 'selected' : '' }}>الليرة السورية (ل.س)</option>
                            <option value="USD" {{ request('currency') == 'USD' ? 'selected' : '' }}>الدولار الأمريكي ($)</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الترتيب حسب</label>
                        <select name="sort" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>الأحدث أولاً</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>الأقل سعراً</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>الأعلى سعراً</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>الأكثر مشاهدة</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs py-2.5 rounded-xl transition shadow flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-filter"></i>
                        <span>تطبيق الفلاتر</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Listings Content Area -->
        <div class="lg:col-span-3">
            <!-- Active Filters & Count Header -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap justify-between items-center gap-3 mb-6 shadow-sm">
                <div class="text-xs text-slate-600">
                    تم العثور على <strong class="text-slate-900 font-black">{{ $listings->total() }}</strong> سلعة متاحة للبيع
                </div>

                <!-- Condition Filter Pills -->
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-600">عرض:</span>
                    <a href="{{ request()->fullUrlWithQuery(['condition' => null]) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ !request('condition') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">الكل</a>
                    <a href="{{ request()->fullUrlWithQuery(['condition' => 'new']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ request('condition') == 'new' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700' }}">جديد</a>
                    <a href="{{ request()->fullUrlWithQuery(['condition' => 'used']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ request('condition') == 'used' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">مستعمل</a>
                </div>
            </div>

            <!-- Listings Grid -->
            @if($listings->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($listings as $item)
                        @include('components.listing-card', ['listing' => $item])
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $listings->links() }}
                </div>
            @else
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto mb-4">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3 class="text-base font-black text-slate-800 mb-1">لم يتم العثور على نتائج</h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                        لم نجد أي سلع تطابق معايير البحث الحالية. جرب تغيير المحافظة أو تخفيف شروط البحث.
                    </p>
                    <a href="{{ route('listings.index') }}" class="bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition shadow">
                        إعادة ضبط البحث
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

