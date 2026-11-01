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

                <!-- Listing Type -->
                <div class="md:col-span-2 bg-slate-50 border border-slate-200/80 rounded-2xl p-4">
                    <label class="block text-xs font-black text-slate-900 mb-2">نوع الإعلان والغرض <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <label class="flex items-center p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-emerald-500 transition">
                            <input type="radio" name="listing_type" value="sale" {{ old('listing_type', $listing->listing_type) === 'sale' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                            <div class="mr-2">
                                <span class="block text-xs font-bold text-slate-900">معروض للبيع</span>
                                <span class="block text-[10px] text-slate-500">بيع فوري</span>
                            </div>
                        </label>

                        <label class="flex items-center p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-amber-500 transition">
                            <input type="radio" name="listing_type" value="wanted" {{ old('listing_type', $listing->listing_type) === 'wanted' ? 'checked' : '' }} class="text-amber-600 focus:ring-amber-500">
                            <div class="mr-2">
                                <span class="block text-xs font-bold text-slate-900">مطلوب للشراء</span>
                                <span class="block text-[10px] text-slate-500">طلب شراء</span>
                            </div>
                        </label>

                        <label class="flex items-center p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-blue-500 transition">
                            <input type="radio" name="listing_type" value="rent" {{ old('listing_type', $listing->listing_type) === 'rent' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                            <div class="mr-2">
                                <span class="block text-xs font-bold text-slate-900">معروض للإيجار</span>
                                <span class="block text-[10px] text-slate-500">تأجير عقار/سيارة</span>
                            </div>
                        </label>

                        <label class="flex items-center p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-purple-500 transition">
                            <input type="radio" name="listing_type" value="rent_wanted" {{ old('listing_type', $listing->listing_type) === 'rent_wanted' ? 'checked' : '' }} class="text-purple-600 focus:ring-purple-500">
                            <div class="mr-2">
                                <span class="block text-xs font-bold text-slate-900">مطلوب للاستئجار</span>
                                <span class="block text-[10px] text-slate-500">طلب استئجار</span>
                            </div>
                        </label>
                    </div>
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

                <!-- Existing Images -->
                @if($listing->images->count() > 0)
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-2">الصور الحالية للسلعة</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach($listing->images as $img)
                                <div class="w-20 h-20 rounded-xl overflow-hidden border border-slate-200 relative bg-slate-100 shadow-sm">
                                    <img src="{{ $img->url }}" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Additional Images -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">إضافة صور جديدة (يمكنك حذف أي صورة قبل الحفظ بالضغط على زر X)</label>
                    <div class="border-2 border-dashed border-slate-300 hover:border-brand-500 rounded-2xl p-4 text-center transition bg-slate-50/50">
                        <input type="file" name="images[]" id="editImagesInput" multiple accept="image/jpeg,image/png,image/webp" class="hidden">
                        <label for="editImagesInput" class="cursor-pointer block">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-brand-500 mb-1"></i>
                            <p class="text-xs font-bold text-slate-800">اضغط لاختيار صور إضافية</p>
                        </label>
                        <div id="editImagesPreview" class="flex flex-wrap gap-2 mt-3 justify-center"></div>
                    </div>
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

@push('scripts')
<script>
    const editImagesInput = document.getElementById('editImagesInput');
    const editImagesPreview = document.getElementById('editImagesPreview');
    let editSelectedFiles = [];

    editImagesInput.addEventListener('change', function() {
        if (this.files) {
            Array.from(this.files).forEach(file => {
                if (editSelectedFiles.length < 10) {
                    editSelectedFiles.push(file);
                }
            });
            updateEditPreview();
        }
    });

    function updateEditPreview() {
        const dt = new DataTransfer();
        editSelectedFiles.forEach(file => dt.items.add(file));
        editImagesInput.files = dt.files;

        editImagesPreview.innerHTML = '';
        editSelectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'w-20 h-20 rounded-xl overflow-hidden border border-slate-200 relative bg-slate-100 shadow-sm';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" onclick="removeEditImage(${index})" 
                            class="absolute top-1 left-1 w-5 h-5 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center text-[10px] shadow transition" 
                            title="إزالة هذه الصورة">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                `;
                editImagesPreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    window.removeEditImage = function(index) {
        editSelectedFiles.splice(index, 1);
        updateEditPreview();
    };
</script>
@endpush

@endsection

