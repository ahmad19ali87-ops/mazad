@extends('layouts.admin')

@section('title', 'إدارة البنرات الإعلانية والسلايدر | لوحة التحكم')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-xs text-amber-400 font-bold mb-1">
                <i class="fa-solid fa-rectangle-ad"></i>
                <span>الإعلانات الممولة والسلايدر التفاعلي</span>
            </div>
            <h1 class="text-2xl font-black text-white">إدارة بنرات السلايدر (الموقع وتطبيق الجوال)</h1>
            <p class="text-xs text-slate-400 mt-1">
                تحكم بالبنرات الإعلانية المتحركة (سلايدر كل 5 ثوانٍ)؛ يمكنك ربط البنر بإعلان معروض في الموقع أو رابط موقع وتطبيق خارجي
            </p>
        </div>

        <button type="button" onclick="document.getElementById('addBannerModal').classList.remove('hidden')" 
                class="bg-brand-500 hover:bg-brand-600 text-white font-black text-xs px-5 py-3 rounded-xl shadow-lg transition flex items-center gap-2 self-start md:self-auto">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة بنر إعلاني جديد</span>
        </button>
    </div>

    <!-- Active Banners Count Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-400 font-bold block mb-1">إجمالي البنرات</span>
                <span class="text-2xl font-black text-white">{{ $banners->count() }}</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-300 flex items-center justify-center text-xl">
                <i class="fa-solid fa-images"></i>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-xs text-emerald-400 font-bold block mb-1">البنرات المفعلة حالياً</span>
                <span class="text-2xl font-black text-emerald-400">{{ $banners->where('is_active', true)->count() }}</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-950/60 text-emerald-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-xs text-amber-400 font-bold block mb-1">إجمالي النقرات</span>
                <span class="text-2xl font-black text-amber-400">{{ number_format($banners->sum('clicks_count')) }}</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-950/60 text-amber-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-arrow-pointer"></i>
            </div>
        </div>
    </div>

    <!-- Banners Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
                <i class="fa-solid fa-list text-amber-400"></i>
                <span>قائمة البنرات النشطة والمعطلة</span>
            </h3>
            <span class="text-xs text-slate-400 font-bold">يتم التبديل بينها كل 5 ثوانٍ تلقائياً</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-bold">
                    <tr>
                        <th class="p-4">الصورة والمعاينة</th>
                        <th class="p-4">العنوان والنص الترويجي</th>
                        <th class="p-4">الشارة</th>
                        <th class="p-4">نوع الرابط والوجهة</th>
                        <th class="p-4 text-center">الترتيب</th>
                        <th class="p-4 text-center">الحالة</th>
                        <th class="p-4 text-center">النقرات</th>
                        <th class="p-4 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($banners as $banner)
                        <tr class="hover:bg-slate-850/50 transition">
                            <td class="p-4">
                                <div class="w-28 h-16 rounded-xl overflow-hidden bg-slate-950 border border-slate-800 relative group">
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-white text-sm block mb-1">{{ $banner->title }}</span>
                                @if($banner->subtitle)
                                    <span class="text-slate-400 text-[11px] block line-clamp-1">{{ $banner->subtitle }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="bg-amber-400/20 text-amber-300 border border-amber-400/40 text-[10px] font-black px-2.5 py-1 rounded-full whitespace-nowrap">
                                    {{ $banner->badge_text ?? 'إعلان ممول' }}
                                </span>
                            </td>
                            <td class="p-4">
                                @if($banner->link_type === 'listing' && $banner->listing)
                                    <div class="flex items-center gap-1.5 text-emerald-400">
                                        <i class="fa-solid fa-tag"></i>
                                        <span class="font-bold">سلعة في الموقع:</span>
                                    </div>
                                    <a href="{{ route('listings.show', $banner->listing->slug) }}" target="_blank" class="text-slate-400 hover:text-white underline text-[11px] block mt-0.5 max-w-xs truncate">
                                        {{ $banner->listing->title }}
                                    </a>
                                @elseif($banner->link_type === 'external' && $banner->external_url)
                                    <div class="flex items-center gap-1.5 text-blue-400">
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                        <span class="font-bold">رابط خارجي:</span>
                                    </div>
                                    <a href="{{ $banner->external_url }}" target="_blank" class="text-slate-400 hover:text-white underline text-[11px] block mt-0.5 max-w-xs truncate">
                                        {{ $banner->external_url }}
                                    </a>
                                @else
                                    <span class="text-slate-500 font-bold">بدون رابط (صورة ونص فقط)</span>
                                @endif
                            </td>
                            <td class="p-4 text-center font-mono font-bold">
                                {{ $banner->sort_order }}
                            </td>
                            <td class="p-4 text-center">
                                <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-full font-bold text-[10px] transition {{ $banner->is_active ? 'bg-emerald-900/60 text-emerald-300 border border-emerald-500/50 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}" title="اضغط لتغيير الحالة">
                                        {{ $banner->is_active ? 'نشط ويظهر ✓' : 'معطل مؤقتاً' }}
                                    </button>
                                </form>
                            </td>
                            <td class="p-4 text-center font-mono text-slate-400 font-bold">
                                {{ $banner->clicks_count }}
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك بحذف هذا البنر الإعلاني نهائياً؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:text-rose-300 p-2 rounded-lg hover:bg-rose-950/50 transition" title="حذف البنر">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-500">
                                <i class="fa-solid fa-images text-3xl mb-3 block text-slate-600"></i>
                                <span>لا توجد أي بنرات إعلانية بعد. أضف بنرك الأول لعرضه في سلايدر الموقع وتطبيق الموبايل.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Banner Modal -->
<div id="addBannerModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 text-slate-200 shadow-2xl relative">
        <div class="flex justify-between items-center pb-4 border-b border-slate-800 mb-6">
            <h3 class="text-lg font-black text-white flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-amber-400"></i>
                <span>إضافة بنر إعلاني ممول جديد</span>
            </h3>
            <button type="button" onclick="document.getElementById('addBannerModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <!-- Title -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">عنوان البنر الإعلاني <span class="text-rose-400">*</span></label>
                <input type="text" name="title" required placeholder="مثال: خصم 20% على هواتف الآيفون 16" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <!-- Subtitle -->
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">النص الفرعي / الوصف الترويجي</label>
                <input type="text" name="subtitle" placeholder="مثال: متوفر حصرياً عبر متجر التقنية الحديثة في دمشق وحلب مع ضمان شامل" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Badge Text -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">نص الشارة العلوية</label>
                    <input type="text" name="badge_text" value="إعلان ممول ⭐" placeholder="مثال: إعلان ممول ⭐ أو عرض VIP" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">الترتيب في السلايدر</label>
                    <input type="number" name="sort_order" value="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <!-- Link Type -->
            <div x-data="{ linkType: 'listing' }" class="bg-slate-950 border border-slate-800 p-4 rounded-2xl">
                <label class="block text-xs font-bold text-slate-300 mb-2">نوع الوجهة عند الضغط على البنر <span class="text-rose-400">*</span></label>
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-800 cursor-pointer hover:bg-slate-900 text-xs">
                        <input type="radio" name="link_type" value="listing" x-model="linkType" class="text-brand-500" checked>
                        <span>سلعة معروضة</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-800 cursor-pointer hover:bg-slate-900 text-xs">
                        <input type="radio" name="link_type" value="external" x-model="linkType" class="text-brand-500">
                        <span>موقع / تطبيق خارجي</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-800 cursor-pointer hover:bg-slate-900 text-xs">
                        <input type="radio" name="link_type" value="none" x-model="linkType" class="text-brand-500">
                        <span>بدون رابط</span>
                    </label>
                </div>

                <!-- Select Listing -->
                <div x-show="linkType === 'listing'" class="space-y-1">
                    <label class="block text-xs font-bold text-slate-300">اختر السلعة المعروضة</label>
                    <select name="listing_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white cursor-pointer">
                        <option value="">-- اختر السلعة لربطها بالبنر --</option>
                        @foreach($featuredListings as $listing)
                            <option value="{{ $listing->id }}">
                                #{{ $listing->id }} - {{ $listing->title }} ({{ number_format($listing->price) }} {{ $listing->currency }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- External URL -->
                <div x-show="linkType === 'external'" class="space-y-1">
                    <label class="block text-xs font-bold text-slate-300">رابط الموقع أو التطبيق الخارجي</label>
                    <input type="url" name="external_url" placeholder="https://..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none">
                </div>
            </div>

            <!-- Image Upload or URL -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">رفع صورة البنر من الجهاز</label>
                    <input type="file" name="image_file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-500 file:text-white hover:file:bg-brand-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">أو إدخال رابط صورة مباشرة</label>
                    <input type="text" name="image_url" placeholder="https://..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white focus:outline-none">
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addBannerModal').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-300 hover:bg-slate-850">
                    إلغاء
                </button>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white font-black text-xs px-6 py-2.5 rounded-xl shadow-lg transition">
                    حفظ ونشر البنر
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

