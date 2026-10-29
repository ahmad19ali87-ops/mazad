@extends('layouts.admin')

@section('title', 'إدارة الأقسام والفروع | إدارة مزاد سوريا')

@section('content')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h1 class="text-xl font-black text-white">إدارة الأقسام والفروع الرئيسية</h1>
        <p class="text-xs text-slate-400 mt-0.5">إضافة وتعديل أقسام البضائع وفروعها</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Add Category Form -->
    <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-5 h-fit">
        <h3 class="text-sm font-bold text-white mb-4 pb-2 border-b border-slate-700">إضافة قسم رئيسي جديد</h3>
        
        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block text-slate-400 mb-1">اسم القسم بالعربية</label>
                <input type="text" name="name_ar" placeholder="مثال: معدات طبية" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white" required>
            </div>
            <div>
                <label class="block text-slate-400 mb-1">الاسم بالإنجليزية (اختياري)</label>
                <input type="text" name="name_en" placeholder="Medical Equipment" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">اسم الأيقونة (FontAwesome)</label>
                <input type="text" name="icon" placeholder="fa-stethoscope" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">الوصف</label>
                <textarea name="description" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white"></textarea>
            </div>
            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold py-2.5 rounded-xl transition mt-2">
                حفظ القسم الرئيسي
            </button>
        </form>

        <hr class="my-6 border-slate-700">

        <!-- Add Subcategory Form -->
        <h3 class="text-sm font-bold text-white mb-4 pb-2 border-b border-slate-700">إضافة قسم فرعي تابع</h3>
        <form action="{{ route('admin.subcategories.store') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block text-slate-400 mb-1">اختر القسم الرئيسي</label>
                <select name="category_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white cursor-pointer" required>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1">اسم القسم الفرعي</label>
                <input type="text" name="name_ar" placeholder="مثال: أجهزة قياس ضغط" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl transition mt-2">
                حفظ القسم الفرعي
            </button>
        </form>
    </div>

    <!-- Categories List (2 cols) -->
    <div class="lg:col-span-2 space-y-4">
        @foreach($categories as $cat)
            <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-5">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-700 flex items-center justify-center text-amber-400">
                            <i class="fa-solid fa-folder"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">{{ $cat->name_ar }} <span class="text-xs text-slate-400">({{ $cat->name_en }})</span></h3>
                            <span class="text-[10px] text-slate-400">{{ $cat->listings_count }} إعلان نشط • {{ $cat->subcategories_count }} فروع</span>
                        </div>
                    </div>

                    <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('حذف هذا القسم بالكامل؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs p-1">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>

                <!-- Subcategories chips -->
                <div class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-700/50">
                    @forelse($cat->subcategories as $sub)
                        <span class="inline-flex items-center gap-1.5 bg-slate-900/80 border border-slate-700 text-slate-300 text-[11px] px-2.5 py-1 rounded-lg">
                            <span>{{ $sub->name_ar }}</span>
                            <form action="{{ route('admin.subcategories.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('حذف الفرع؟');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-red-400 text-[10px]">×</button>
                            </form>
                        </span>
                    @empty
                        <span class="text-[11px] text-slate-500">لا توجد فروع مضافة بعد</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

