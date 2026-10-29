@extends('layouts.app')

@section('title', 'إعلاناتي وسلعي المعروضة | مزاد سوريا')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 pb-4 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-black text-slate-900">إعلاناتي وسلعي</h1>
            <p class="text-xs text-slate-500 mt-1">إدارة البضائع التي قمت بعرضها للبيع وتتبع حالتها</p>
        </div>
        <a href="{{ route('listings.create') }}" class="mzad-gradient text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow flex items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة سلعة جديدة</span>
        </a>
    </div>

    @if($listings->count() > 0)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold">
                        <tr>
                            <th class="p-3.5">السلعة</th>
                            <th class="p-3.5">القسم</th>
                            <th class="p-3.5">الحالة</th>
                            <th class="p-3.5">السعر</th>
                            <th class="p-3.5">الموقع</th>
                            <th class="p-3.5">المشاهدات</th>
                            <th class="p-3.5 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($listings as $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                                            @php $img = $item->images->first(); @endphp
                                            <img src="{{ $img ? $img->url : 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=200&q=80' }}" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <a href="{{ route('listings.show', $item->slug) }}" class="font-bold text-slate-900 hover:text-brand-500 line-clamp-1">
                                                {{ $item->title }}
                                            </a>
                                            <span class="text-[10px] text-slate-600 block mt-0.5">نشر {{ $item->created_at->format('Y/m/d') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3.5 font-medium text-slate-600">
                                    {{ $item->category->name_ar ?? '-' }}
                                </td>
                                <td class="p-3.5">
                                    @if($item->condition === 'new')
                                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">جديد</span>
                                    @else
                                        <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded">مستعمل</span>
                                    @endif
                                    <span class="mr-1 text-[10px] {{ $item->status === 'active' ? 'text-emerald-600 font-bold' : 'text-slate-500' }}">
                                        ({{ $item->status === 'active' ? 'نشط' : $item->status }})
                                    </span>
                                </td>
                                <td class="p-3.5 font-bold text-brand-600">
                                    {{ number_format($item->price, 0) }} {{ $item->currency === 'USD' ? '$' : 'ل.س' }}
                                </td>
                                <td class="p-3.5 text-slate-600">
                                    {{ $item->governorate->name_ar ?? '' }}
                                </td>
                                <td class="p-3.5 text-slate-600">
                                    <i class="fa-regular fa-eye ml-1 text-slate-600"></i> {{ number_format($item->views_count) }}
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('listings.show', $item->slug) }}" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg" title="عرض">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('listings.edit', $item->id) }}" class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg" title="تعديل">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="{{ route('listings.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل تريد بالتأكيد حذف هذه السلعة؟');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg" title="حذف">
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

            <div class="p-4 border-t border-slate-100">
                {{ $listings->links() }}
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto mb-4">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <h3 class="text-base font-black text-slate-800 mb-1">لم تقم بعرض أي سلع للبيع حتى الآن</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-6">
                هل لديك سيارة، عقار، موبايل، أو أثاث ترغب في بيعه؟ اعرضه الآن مجاناً بسهولة وسرعة.
            </p>
            <a href="{{ route('listings.create') }}" class="mzad-gradient text-white text-xs font-bold px-6 py-3 rounded-xl shadow">
                أضف إعلانك الأول مجاناً
            </a>
        </div>
    @endif
</div>

@endsection

