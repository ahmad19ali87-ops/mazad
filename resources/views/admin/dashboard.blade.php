@extends('layouts.admin')

@section('title', 'لوحة التحكم الرئيسية | إدارة مزاد سوريا')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-black text-white">لوحة المعلومات والإحصائيات</h1>
    <p class="text-xs text-slate-400 mt-1">نظرة عامة على نشاط المنصة وحركة السلع والمستخدمين</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
    <div class="bg-slate-800/80 p-4 rounded-2xl border border-slate-700/80">
        <span class="text-slate-400 text-xs font-bold block mb-1">إجمالي السلع</span>
        <span class="text-2xl font-black text-white">{{ number_format($stats['total_listings']) }}</span>
    </div>

    <div class="bg-slate-800/80 p-4 rounded-2xl border border-slate-700/80">
        <span class="text-emerald-400 text-xs font-bold block mb-1">سلع جديدة (زيرو)</span>
        <span class="text-2xl font-black text-emerald-400">{{ number_format($stats['new_condition']) }}</span>
    </div>

    <div class="bg-slate-800/80 p-4 rounded-2xl border border-slate-700/80">
        <span class="text-blue-400 text-xs font-bold block mb-1">سلع مستعملة</span>
        <span class="text-2xl font-black text-blue-400">{{ number_format($stats['used_condition']) }}</span>
    </div>

    <div class="bg-slate-800/80 p-4 rounded-2xl border border-slate-700/80">
        <span class="text-amber-400 text-xs font-bold block mb-1">إعلانات مميزة (VIP)</span>
        <span class="text-2xl font-black text-amber-400">{{ number_format($stats['featured_listings']) }}</span>
    </div>

    <div class="bg-slate-800/80 p-4 rounded-2xl border border-slate-700/80">
        <span class="text-slate-400 text-xs font-bold block mb-1">المستخدمين</span>
        <span class="text-2xl font-black text-white">{{ number_format($stats['total_users']) }}</span>
    </div>

    <div class="bg-slate-800/80 p-4 rounded-2xl border border-slate-700/80">
        <span class="text-amber-300 text-xs font-bold block mb-1">البائعين المعتمدين</span>
        <span class="text-2xl font-black text-amber-300">{{ number_format($stats['sellers_count']) }}</span>
    </div>
</div>

<!-- Recent Listings & Users Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Recent Listings (2 cols) -->
    <div class="lg:col-span-2 bg-slate-800/80 rounded-2xl border border-slate-700/80 p-5">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-700">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-brand-500"></i> أحدث السلع المضافة
            </h3>
            <a href="{{ route('admin.listings.index') }}" class="text-xs text-amber-400 hover:underline">عرض الكل</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="text-slate-400 font-bold border-b border-slate-700/50">
                    <tr>
                        <th class="pb-2">السلعة</th>
                        <th class="pb-2">الحالة</th>
                        <th class="pb-2">السعر</th>
                        <th class="pb-2">البائع</th>
                        <th class="pb-2 text-center">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/40">
                    @foreach($recentListings as $listing)
                        <tr class="hover:bg-slate-700/30">
                            <td class="py-2.5">
                                <span class="font-bold text-white block line-clamp-1">{{ $listing->title }}</span>
                                <span class="text-[10px] text-slate-400">{{ $listing->governorate->name_ar ?? '' }}</span>
                            </td>
                            <td class="py-2.5">
                                @if($listing->condition === 'new')
                                    <span class="bg-emerald-900/60 text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold">جديد</span>
                                @else
                                    <span class="bg-blue-900/60 text-blue-400 px-2 py-0.5 rounded text-[10px] font-bold">مستعمل</span>
                                @endif
                            </td>
                            <td class="py-2.5 font-bold text-amber-400">
                                {{ number_format($listing->price, 0) }} {{ $listing->currency === 'USD' ? '$' : 'ل.س' }}
                            </td>
                            <td class="py-2.5 text-slate-300">
                                {{ $listing->user->name ?? '-' }}
                            </td>
                            <td class="py-2.5 text-center">
                                <a href="{{ route('listings.show', $listing->slug) }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Users & Quick Actions -->
    <div class="space-y-6">
        <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-5">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-700">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-users text-blue-400"></i> أحدث الأعضاء
                </h3>
                <a href="{{ route('admin.users.index') }}" class="text-xs text-amber-400 hover:underline">عرض الكل</a>
            </div>

            <div class="space-y-3">
                @foreach($recentUsers as $u)
                    <div class="flex items-center justify-between text-xs py-1.5 border-b border-slate-700/30 last:border-0">
                        <div>
                            <span class="font-bold text-white block">{{ $u->name }}</span>
                            <span class="text-[10px] text-slate-400">{{ $u->email }} • {{ $u->phone }}</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded {{ $u->role === 'seller' ? 'bg-amber-900/50 text-amber-300' : 'bg-slate-700 text-slate-300' }}">
                            {{ $u->role === 'seller' ? 'بائع' : 'مستخدم' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-5">
            <h3 class="text-sm font-bold text-white mb-3">روابط إدارية سريعة</h3>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <a href="{{ route('admin.categories.index') }}" class="bg-slate-700/70 hover:bg-slate-700 p-2.5 rounded-xl text-center text-slate-200 transition font-bold">
                    إدارة الأقسام
                </a>
                <a href="{{ route('admin.listings.index') }}" class="bg-slate-700/70 hover:bg-slate-700 p-2.5 rounded-xl text-center text-slate-200 transition font-bold">
                    إدارة السلع
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-slate-700/70 hover:bg-slate-700 p-2.5 rounded-xl text-center text-slate-200 transition font-bold">
                    توثيق البائعين
                </a>
                <a href="{{ route('admin.comments.index') }}" class="bg-slate-700/70 hover:bg-slate-700 p-2.5 rounded-xl text-center text-slate-200 transition font-bold">
                    مراقبة التعليقات
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

