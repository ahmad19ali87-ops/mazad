@extends('layouts.app')

@section('title', $seller->name . ' | متجر وبائع معتمد في مزاد سوريا')

@section('content')

<!-- Seller Hero Header -->
<div class="bg-gradient-to-b from-slate-900 to-slate-800 text-white py-12 px-4 border-b border-slate-700">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">
        
        <!-- Seller Info -->
        <div class="flex flex-col sm:flex-row items-center text-center sm:text-right gap-5">
            <div class="w-24 h-24 rounded-3xl bg-brand-500 text-white font-black text-3xl flex items-center justify-center border-4 border-slate-700 shadow-xl">
                {{ mb_substr($seller->name, 0, 1) }}
            </div>

            <div>
                <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                    <h1 class="text-2xl font-black">{{ $seller->name }}</h1>
                    @if($seller->is_verified)
                        <span class="inline-flex items-center gap-1 bg-blue-500/20 text-blue-400 border border-blue-500/40 text-[10px] font-bold px-2 py-0.5 rounded-full" title="بائع معتمد رسمياً">
                            <i class="fa-solid fa-circle-check"></i> موثق
                        </span>
                    @endif
                </div>

                @if($seller->bio)
                    <p class="text-xs text-slate-300 max-w-lg mb-2 font-light">{{ $seller->bio }}</p>
                @endif

                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-slate-400">
                    <span><i class="fa-solid fa-location-dot text-amber-400 ml-1"></i> {{ $seller->governorate ?? 'سوريا' }}</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-box text-blue-400 ml-1"></i> {{ $seller->listings_count }} سلعة معروضة</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-users text-emerald-400 ml-1"></i> <strong class="text-white">{{ $seller->followers_count }}</strong> متابع</span>
                    <span>•</span>
                    <span><i class="fa-regular fa-calendar-check text-slate-500 ml-1"></i> عضو منذ {{ $seller->created_at->format('Y/m') }}</span>
                </div>
            </div>
        </div>

        <!-- Follow & Contact Buttons -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <!-- Follow Button -->
            <form action="{{ route('sellers.follow', $seller->id) }}" method="POST" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="w-full sm:w-auto px-5 py-3 rounded-xl font-bold text-xs transition shadow-lg flex items-center justify-center gap-2 {{ $isFollowing ? 'bg-slate-700 hover:bg-slate-600 text-white border border-slate-600' : 'bg-amber-400 hover:bg-amber-300 text-slate-950 font-black' }}">
                    <i class="fa-solid {{ $isFollowing ? 'fa-user-check text-emerald-400' : 'fa-bell' }}"></i>
                    <span>{{ $isFollowing ? 'أنت تتابع هذا البائع' : 'متابعة البائع (إشعارات السلع الجديدة)' }}</span>
                </button>
            </form>

            @if($seller->phone)
                <a href="tel:{{ $seller->phone }}" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold text-xs px-4 py-3 rounded-xl transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-phone text-amber-400"></i>
                    <span>اتصال</span>
                </a>
            @endif

            @if($seller->whatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $seller->whatsapp) }}" target="_blank" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-3 rounded-xl transition flex items-center justify-center gap-1.5">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>واتساب</span>
                </a>
            @endif
        </div>
    </div>
</div>

<!-- Seller Listings Section -->
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-black text-slate-900">سلع ومعروضات {{ $seller->name }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">جميع البضائع الحالية المعروضة للبيع</p>
        </div>
    </div>

    @if($listings->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($listings as $item)
                @include('components.listing-card', ['listing' => $item])
            @endforeach
        </div>

        <div class="mt-8">
            {{ $listings->links() }}
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto mb-4">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3 class="text-base font-black text-slate-800 mb-1">لا توجد سلع معروضة حالياً</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                لم يقم البائع بنشر أي سلع نشطة حتى الآن. اضغط على زر المتابعة أعلاه لتصلك إشعارات فور نشر سلعة جديدة.
            </p>
        </div>
    @endif
</div>

@endsection

