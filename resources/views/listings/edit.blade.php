@extends('layouts.app')

@section('title', 'تعديل السلعة: ' . $listing->title . ' | مزاد سوريا')

@section('content')

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="mb-6 pb-3 border-b border-slate-200">
        <h1 class="text-2xl font-black text-slate-900">تعديل الإعلان: {{ $listing->title }}</h1>
        <p class="text-xs text-slate-500 mt-1">تحديث بيانات السلعة أو تغيير حالتها وسعرها</p>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 p-6 md:p-8 shadow-sm">
        @if ($errors->any())
            <div class="bg-red-50 border-r-4 border-red-500 p-4 rounded-xl mb-6">
                <ul class="text-xs text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('listings.update', $listing->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">اسم القطعة أو الإعلان</label>
                    <input type="text" name="title" value="{{ old('title', $listing->title) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">القسم الرئيسي</label>
                    <select name="category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs cursor-pointer" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $listing->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">حالة الإعلان</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs cursor-pointer">
                        <option value="active" {{ old('status', $listing->status) === 'active' ? 'selected' : '' }}>معروض ونشط للبيع (Active)</option>
                        <option value="sold" {{ old('status', $listing->status) === 'sold' ? 'selected' : '' }}>تم البيع بنجاح (Sold)</option>
                        <option value="pending" {{ old('status', $listing->status) === 'pending' ? 'selected' : '' }}>موقف مؤقتاً (Pending)</option>
                    </select>
                </div>

                <!-- Condition -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-2">حالة القطعة (الجديد أو المستعمل)</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="condition" value="new" {{ old('condition', $listing->condition) === 'new' ? 'checked' : '' }} class="text-emerald-600">
                            <span class="mr-2 text-xs font-bold text-slate-900">جديد (زيرو / غير مستخدم)</span>
                        </label>
                        <label class="flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="condition" value="used" {{ old('condition', $listing->condition) === 'used' ? 'checked' : '' }} class="text-blue-600">
                            <span class="mr-2 text-xs font-bold text-slate-900">مستعمل</span>
                        </label>
                    </div>
                </div>

                <!-- Price & Currency -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">السعر المطلوب</label>
                    <input type="number" step="any" name="price" value="{{ old('price', $listing->price) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">العملة والتفاوض</label>
                    <div class="flex gap-4 items-center pt-2">
                        <label class="flex items-center gap-1.5 text-xs font-bold cursor-pointer">
                            <input type="radio" name="currency" value="SYP" {{ old('currency', $listing->currency) === 'SYP' ? 'checked' : '' }} class="text-brand-500">
                            <span>ل.س</span>
                        </label>
                        <label class="flex items-center gap-1.5 text-xs font-bold cursor-pointer">
                            <input type="radio" name="currency" value="USD" {{ old('currency', $listing->currency) === 'USD' ? 'checked' : '' }} class="text-brand-500">
                            <span>$</span>
                        </label>
                        <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer mr-auto">
                            <input type="checkbox" name="is_negotiable" value="1" {{ old('is_negotiable', $listing->is_negotiable) ? 'checked' : '' }} class="rounded text-brand-500">
                            <span class="font-bold text-emerald-700">قابل للتفاوض</span>
                        </label>
                    </div>
                </div>

                <!-- Governorate -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">المحافظة</label>
                    <select name="governorate_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs cursor-pointer" required>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->id }}" {{ old('governorate_id', $listing->governorate_id) == $gov->id ? 'selected' : '' }}>
                                {{ $gov->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Street address -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تكملة العنوان ومعالم قريبة</label>
                    <input type="text" name="street_address" value="{{ old('street_address', $listing->street_address) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">الوصف التفصيلي</label>
                    <textarea name="description" rows="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>{{ old('description', $listing->description) }}</textarea>
                </div>

                <!-- Additional Images -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">إضافة صور جديدة (اختياري)</label>
                    <input type="file" name="images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('listings.show', $listing->slug) }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    إلغاء
                </a>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow transition">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

