@extends('layouts.app')

@section('title', 'أضف إعلانك وسلعتك للبيع | مزاد سوريا')

@section('content')

<div class="max-w-4xl mx-auto px-4 py-10">
    
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-600 px-3.5 py-1.5 rounded-full text-xs font-bold mb-2">
            <i class="fa-solid fa-plus-circle text-amber-500"></i>
            <span>إعلان مجاني لكافة السلع والبضائع</span>
        </div>
        <h1 class="text-2xl md:text-3xl font-black text-slate-900">اعرض سلعتك للبيع في سوريا</h1>
        <p class="text-xs md:text-sm text-slate-500 mt-1 max-w-lg mx-auto">
            أدخل تفاصيل القطعة بدقة، حدد إن كانت جديدة أو مستعملة، وأضف الصور ومكان تواجدها لتصل لآلاف المشترين
        </p>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 md:p-8 shadow-sm">
        
        @if ($errors->any())
            <div class="bg-red-50 border-r-4 border-red-500 p-4 rounded-xl mb-6">
                <p class="text-xs font-bold text-red-800 mb-1">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('listings.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8" id="adForm">
            @csrf

            <!-- Section 1: Basic Information -->
            <div>
                <h3 class="text-sm font-black text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-brand-500 text-white flex items-center justify-center text-xs font-bold">1</span>
                    <span>بيانات السلعة الأساسية</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Listing Type: Sale vs Wanted -->
                    <div class="md:col-span-2 bg-slate-50 border border-slate-200/80 rounded-2xl p-4">
                        <label class="block text-xs font-black text-slate-900 mb-2">نوع الإعلان والغرض <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-emerald-500 transition">
                                <input type="radio" name="listing_type" value="sale" {{ old('listing_type', 'sale') === 'sale' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500" required>
                                <div class="mr-3">
                                    <span class="block text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-tag text-emerald-600"></i> معروض للبيع أو التقديم
                                    </span>
                                    <span class="block text-[10px] text-slate-500">لديك سلعة أو خدمة وتريد بيعها</span>
                                </div>
                            </label>

                            <label class="flex items-center p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-amber-500 transition">
                                <input type="radio" name="listing_type" value="wanted" {{ old('listing_type') === 'wanted' ? 'checked' : '' }} class="text-amber-600 focus:ring-amber-500">
                                <div class="mr-3">
                                    <span class="block text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i> مطلوب للشراء / إعلان طلب
                                    </span>
                                    <span class="block text-[10px] text-slate-500">تبحث عن سلعة، سيارة، شقة، أو عامل لتوظيفه</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">اسم القطعة أو عنوان الإعلان <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="مثال: هيونداي توسان 2023 خالية العلام، أو آيفون 16 جديد بالكرتونة" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none" required>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">القسم الرئيسي <span class="text-red-500">*</span></label>
                        <select name="category_id" id="categorySelect" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer" required>
                            <option value="">-- اختر القسم المناسب --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subcategory -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">القسم الفرعي</label>
                        <select name="subcategory_id" id="subcategorySelect" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                            <option value="">-- اختر القسم الفرعي --</option>
                        </select>
                    </div>

                    <!-- Condition: New vs Used (الجديد من القديم) -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-2">حالة القطعة (الجديد أو المستعمل) <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex items-center p-3 rounded-2xl border cursor-pointer border-slate-200 hover:bg-slate-50 transition peer-checked:border-emerald-600">
                                <input type="radio" name="condition" value="new" {{ old('condition') === 'new' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500" required>
                                <div class="mr-3">
                                    <span class="block text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-sparkles text-emerald-600"></i> جديد بالكامل (زيرو / كرتونة مختومة)
                                    </span>
                                    <span class="block text-[11px] text-slate-500">سلعة غير مستخدمة إطلاقاً</span>
                                </div>
                            </label>

                            <label class="relative flex items-center p-3 rounded-2xl border cursor-pointer border-slate-200 hover:bg-slate-50 transition peer-checked:border-blue-600">
                                <input type="radio" name="condition" value="used" {{ old('condition', 'used') === 'used' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                <div class="mr-3">
                                    <span class="block text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-clock-rotate-left text-blue-600"></i> مستعمل
                                    </span>
                                    <span class="block text-[11px] text-slate-500">سلعة مستخدمة بحالة جيدة أو ممتازة</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Price & Currency -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">سعر القطعة المطلوب <span class="text-red-500">*</span></label>
                        <input type="number" step="any" name="price" value="{{ old('price') }}" placeholder="أدخل السعر بالأرقام" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">العملة <span class="text-red-500">*</span></label>
                        <div class="flex gap-4 items-center pt-2">
                            <label class="flex items-center gap-1.5 text-xs font-bold cursor-pointer">
                                <input type="radio" name="currency" value="SYP" {{ old('currency', 'SYP') === 'SYP' ? 'checked' : '' }} class="text-brand-500">
                                <span>ليرة سورية (ل.س)</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-xs font-bold cursor-pointer">
                                <input type="radio" name="currency" value="USD" {{ old('currency') === 'USD' ? 'checked' : '' }} class="text-brand-500">
                                <span>دولار أمريكي ($)</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer mr-auto">
                                <input type="checkbox" name="is_negotiable" value="1" {{ old('is_negotiable') ? 'checked' : '' }} class="rounded text-brand-500">
                                <span class="font-bold text-emerald-700">قابل للتفاوض</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Exact Location in Syria (Governorate -> Region -> District -> Street Details) -->
            <div>
                <h3 class="text-sm font-black text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-brand-500 text-white flex items-center justify-center text-xs font-bold">2</span>
                    <span>مكان تواجد السلعة في سوريا (العنوان التفصيلي)</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- 1. Syrian Governorate -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">المحافظة السورية <span class="text-red-500">*</span></label>
                        <select name="governorate_id" id="governorateSelect" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer" required>
                            <option value="">-- اختر المحافظة --</option>
                            @foreach($governorates as $gov)
                                <option value="{{ $gov->id }}" {{ old('governorate_id') == $gov->id ? 'selected' : '' }}>
                                    {{ $gov->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Region / City (AJAX Dynamic) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">المنطقة / المدينة</label>
                        <select name="region_id" id="regionSelect" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                            <option value="">-- اختر المنطقة بعد تحديد المحافظة --</option>
                        </select>
                    </div>

                    <!-- 3. District / Neighborhood -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الحي</label>
                        <input type="text" name="district_name" value="{{ old('district_name') }}" placeholder="مثال: الفيلات الغربية، الميدان..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <!-- 4. Street and Landmark (تكملة العنوان) -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">تكملة العنوان ومعالم قريبة (اسم الشارع، جانب مدرسة كذا، مقابل كذا...)</label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" placeholder="مثال: شارع الشهداء، جانب مدرسة دار السلام، مقابل حديقة الطلائع" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Section 3: Photos Upload -->
            <div>
                <h3 class="text-sm font-black text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-brand-500 text-white flex items-center justify-center text-xs font-bold">3</span>
                    <span>صور السلعة (يمكنك رفع حتى 10 صور واضحة) <span class="text-red-500">*</span></span>
                </h3>

                <div class="border-2 border-dashed border-slate-300 hover:border-brand-500 rounded-2xl p-6 text-center transition bg-slate-50/50">
                    <input type="file" name="images[]" id="imagesInput" multiple accept="image/jpeg,image/png,image/webp" class="hidden" required>
                    <label for="imagesInput" class="cursor-pointer block">
                        <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl shadow-sm">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-800 mb-1">اضغط هنا لاختيار الصور أو اسحب الصور إلى هذا المربع</p>
                        <p class="text-[11px] text-slate-600">صيغ مسموحة: JPG, PNG, WEBP (الحد الأقصى 5 ميجابايت لكل صورة)</p>
                    </label>

                    <!-- Preview Container -->
                    <div id="imagesPreview" class="flex flex-wrap gap-3 mt-4 justify-center"></div>
                </div>
            </div>

            <!-- Section 4: Specifications & Description -->
            <div>
                <h3 class="text-sm font-black text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-brand-500 text-white flex items-center justify-center text-xs font-bold">4</span>
                    <span>الوصف والمواصفات الكاملة</span>
                </h3>

                <!-- Description -->
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-700 mb-1">الوصف التفصيلي للسلعة <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="5" placeholder="اكتب كل ما يهم المشتري: حالة الفحص، سبب البيع، الملحقات المرفقة، الفراغ، الضمان..." 
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none leading-relaxed" required>{{ old('description') }}</textarea>
                </div>

                <!-- Custom Specifications Key-Value Rows -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-bold text-slate-700">مواصفات إضافية (اختياري)</label>
                        <button type="button" id="addSpecBtn" class="text-xs font-bold text-brand-500 hover:underline flex items-center gap-1">
                            <i class="fa-solid fa-plus"></i> إضافة حقل مواصفة (مثل: سنة الصنع، المسافة، اللون...)
                        </button>
                    </div>

                    <div id="specsContainer" class="space-y-2">
                        <div class="grid grid-cols-2 gap-2 spec-row">
                            <input type="text" name="specs_keys[]" placeholder="اسم الخاصية (مثال: سنة الصنع)" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                            <input type="text" name="specs_values[]" placeholder="القيمة (مثال: 2024)" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Contact & Settings -->
            <div>
                <h3 class="text-sm font-black text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-brand-500 text-white flex items-center justify-center text-xs font-bold">5</span>
                    <span>بيانات التواصل والتعليقات</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهاتف للاتصال</label>
                        <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="+9639..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">رقم واتساب للمراسلة</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', auth()->user()->whatsapp) }}" placeholder="+9639..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div class="md:col-span-2 pt-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="allow_comments" value="1" {{ old('allow_comments', '1') ? 'checked' : '' }} class="rounded text-brand-500">
                            <span>السماح للمستخدمين بكتابة تعليقات واستفسارات على هذا الإعلان</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('home') }}" class="px-5 py-3 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    إلغاء
                </a>
                <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white font-black text-xs px-8 py-3 rounded-xl transition shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>نشر السلعة الآن</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Categories and Subcategories
    const categoriesData = @json($categories);
    const categorySelect = document.getElementById('categorySelect');
    const subcategorySelect = document.getElementById('subcategorySelect');

    categorySelect.addEventListener('change', function() {
        const catId = parseInt(this.value);
        subcategorySelect.innerHTML = '<option value="">-- اختر القسم الفرعي --</option>';
        
        const cat = categoriesData.find(c => c.id === catId);
        if (cat && cat.subcategories) {
            cat.subcategories.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name_ar;
                subcategorySelect.appendChild(opt);
            });
        }
    });

    // Governorates and Regions AJAX
    const governorateSelect = document.getElementById('governorateSelect');
    const regionSelect = document.getElementById('regionSelect');

    governorateSelect.addEventListener('change', function() {
        const govId = this.value;
        regionSelect.innerHTML = '<option value="">جاري تحميل المناطق...</option>';

        if (!govId) {
            regionSelect.innerHTML = '<option value="">-- اختر المنطقة بعد تحديد المحافظة --</option>';
            return;
        }

        fetch(`/locations/regions/${govId}`)
            .then(res => res.json())
            .then(regions => {
                regionSelect.innerHTML = '<option value="">-- اختر المنطقة / المدينة --</option>';
                regions.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name_ar;
                    regionSelect.appendChild(opt);
                });
            })
            .catch(() => {
                regionSelect.innerHTML = '<option value="">خطأ في تحميل المناطق</option>';
            });
    });

    // Images preview
    const imagesInput = document.getElementById('imagesInput');
    const imagesPreview = document.getElementById('imagesPreview');

    imagesInput.addEventListener('change', function() {
        imagesPreview.innerHTML = '';
        if (this.files) {
            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'w-20 h-20 rounded-xl overflow-hidden border border-slate-200 relative bg-slate-100 shadow-sm';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        ${index === 0 ? '<span class="absolute bottom-0 inset-x-0 bg-brand-500 text-white text-[9px] text-center font-bold">الرئيسية</span>' : ''}
                    `;
                    imagesPreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
    });

    // Add more specs rows
    document.getElementById('addSpecBtn').addEventListener('click', function() {
        const container = document.getElementById('specsContainer');
        const div = document.createElement('div');
        div.className = 'grid grid-cols-2 gap-2 spec-row';
        div.innerHTML = `
            <input type="text" name="specs_keys[]" placeholder="اسم الخاصية" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
            <input type="text" name="specs_values[]" placeholder="القيمة" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
        `;
        container.appendChild(div);
    });
</script>
@endpush

@endsection

