@extends('layouts.admin')

@section('title', 'إدارة السلع والإعلانات | إدارة مزاد سوريا')

@section('content')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h1 class="text-xl font-black text-white">إدارة كافة السلع والمعروضات</h1>
        <p class="text-xs text-slate-400 mt-0.5">التحكم بحالة الإعلانات، التمييز، والاعتماد أو الحذف</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-4 mb-6">
    <form action="{{ route('admin.listings.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث بعنوان السلعة..." class="bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white">
        
        <select name="status" class="bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white cursor-pointer" onchange="this.form.submit()">
            <option value="">كافة الحالات</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط (Active)</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>بانتظار المراجعة (Pending)</option>
            <option value="sold" {{ request('status') === 'sold' ? 'selected' : '' }}>تم البيع (Sold)</option>
        </select>

        <select name="category_id" class="bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white cursor-pointer" onchange="this.form.submit()">
            <option value="">كافة الأقسام</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name_ar }}</option>
            @endforeach
        </select>

        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl p-2.5 transition">
            تصفية
        </button>
    </form>
</div>

<!-- Listings Table -->
<div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-900/60 text-slate-400 font-bold border-b border-slate-700">
                <tr>
                    <th class="p-3.5">السلعة</th>
                    <th class="p-3.5">القسم</th>
                    <th class="p-3.5">الحالة</th>
                    <th class="p-3.5">السعر</th>
                    <th class="p-3.5">البائع</th>
                    <th class="p-3.5">الموقع</th>
                    <th class="p-3.5">التمييز</th>
                    <th class="p-3.5 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @foreach($listings as $item)
                    <tr class="hover:bg-slate-700/30">
                        <td class="p-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-lg bg-slate-900 overflow-hidden flex-shrink-0">
                                    @php $img = $item->images->first(); @endphp
                                    <img src="{{ $img ? $img->url : 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=150&q=80' }}" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <a href="{{ route('listings.show', $item->slug) }}" target="_blank" class="font-bold text-white hover:text-amber-400 line-clamp-1">
                                        {{ $item->title }}
                                    </a>
                                    <span class="text-[10px] text-slate-400">#{{ $item->id }} • {{ $item->created_at->format('Y/m/d') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 text-slate-300">
                            {{ $item->category->name_ar ?? '-' }}
                        </td>
                        <td class="p-3.5">
                            @if($item->condition === 'new')
                                <span class="bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">جديد</span>
                            @else
                                <span class="bg-blue-950 text-blue-400 border border-blue-800 text-[10px] font-bold px-2 py-0.5 rounded">مستعمل</span>
                            @endif
                        </td>
                        <td class="p-3.5 font-bold text-amber-400">
                            {{ number_format($item->price, 0) }} {{ $item->currency }}
                        </td>
                        <td class="p-3.5 text-slate-300">
                            {{ $item->user->name ?? '-' }}
                        </td>
                        <td class="p-3.5 text-slate-400">
                            {{ $item->governorate->name_ar ?? '' }}
                        </td>
                        <td class="p-3.5">
                            <form action="{{ route('admin.listings.featured', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2 py-1 rounded text-[10px] font-bold transition {{ $item->is_featured ? 'bg-amber-400 text-slate-950' : 'bg-slate-700 text-slate-400' }}">
                                    {{ $item->is_featured ? '★ مميز (VIP)' : 'عادي' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-3.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('listings.show', $item->slug) }}" target="_blank" class="p-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded" title="معاينة">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <form action="{{ route('admin.listings.destroy', $item->id) }}" method="POST" onsubmit="return confirm('حذف هذا الإعلان نهائياً؟');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-red-950 text-red-400 hover:bg-red-900 rounded" title="حذف">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-700">
        {{ $listings->links() }}
    </div>
</div>

@endsection

