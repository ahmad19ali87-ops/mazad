@extends('layouts.app')

@section('title', 'لوحة تحكم المستخدم | مزاد سوريا')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- User Header Profile Card -->
    <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/80 shadow-sm mb-8 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-right">
            <div class="relative">
                <div class="w-20 h-20 rounded-2xl bg-brand-500 text-white flex items-center justify-center font-black text-2xl shadow-md overflow-hidden">
                    @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" class="w-full h-full object-cover">
                    @elseif($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" class="w-full h-full object-cover">
                    @else
                        {{ mb_substr($user->name, 0, 1) }}
                    @endif
                </div>
                @if($user->is_verified)
                    <span class="absolute -bottom-1.5 -right-1.5 bg-blue-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs shadow" title="حساب موثق">
                        <i class="fa-solid fa-check"></i>
                    </span>
                @endif
            </div>

            <div>
                <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900">{{ $user->name }}</h1>
                    @if($user->role === 'seller')
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">بائع معتمد</span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                    <span><i class="fa-solid fa-envelope ml-1 text-slate-400"></i> {{ $user->email }}</span>
                    <span>•</span>
                    <span><i class="fa-solid fa-phone ml-1 text-slate-400"></i> {{ $user->phone }}</span>
                    @if($user->governorate)
                        <span>•</span>
                        <span><i class="fa-solid fa-location-dot ml-1 text-slate-400"></i> {{ $user->governorate }}</span>
                    @endif
                </p>
                @if($user->bio)
                    <p class="text-xs text-slate-600 mt-2 max-w-xl">{{ $user->bio }}</p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('listings.create') }}" class="mzad-gradient text-white text-xs font-bold px-5 py-3 rounded-xl shadow flex items-center gap-2 hover:opacity-95 transition">
                <i class="fa-solid fa-plus"></i>
                <span>أضف سلعة أو طلباً جديداً</span>
            </a>
            <a href="{{ route('sellers.show', $user->id) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-3 rounded-xl transition border border-slate-200 flex items-center gap-1.5">
                <i class="fa-solid fa-store"></i>
                <span>صفحتي العامة</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Metric Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 text-center">
            <span class="text-xs text-slate-500 font-bold block mb-1">إجمالي إعلاناتي</span>
            <span class="text-xl font-black text-slate-900">{{ number_format($counts['total']) }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 text-center">
            <span class="text-xs text-emerald-600 font-bold block mb-1">السلع النشطة</span>
            <span class="text-xl font-black text-emerald-600">{{ number_format($counts['active']) }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 text-center">
            <span class="text-xs text-blue-600 font-bold block mb-1">تم بيعها بنجاح</span>
            <span class="text-xl font-black text-blue-600">{{ number_format($counts['sold']) }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 text-center">
            <span class="text-xs text-amber-600 font-bold block mb-1">طلبات "مطلوب"</span>
            <span class="text-xl font-black text-amber-600">{{ number_format($counts['wanted']) }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 text-center">
            <span class="text-xs text-slate-500 font-bold block mb-1">المتابعون</span>
            <span class="text-xl font-black text-slate-900">{{ number_format($counts['followers']) }}</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 text-center">
            <span class="text-xs text-slate-500 font-bold block mb-1">أتابعهم</span>
            <span class="text-xl font-black text-slate-900">{{ number_format($counts['following']) }}</span>
        </div>
    </div>

    <!-- Main Tabs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden" x-data="{ tab: 'listings' }">
        <!-- Tab Headers -->
        <div class="flex border-b border-slate-200 overflow-x-auto text-xs font-bold">
            <button @click="tab = 'listings'" :class="tab === 'listings' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/30' : 'text-slate-600 hover:text-slate-900'" class="px-6 py-4 flex items-center gap-2 transition whitespace-nowrap">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>إعلاناتي ومبيعاتي ({{ $counts['total'] }})</span>
            </button>
            <button @click="tab = 'saved'" :class="tab === 'saved' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/30' : 'text-slate-600 hover:text-slate-900'" class="px-6 py-4 flex items-center gap-2 transition whitespace-nowrap">
                <i class="fa-solid fa-heart text-rose-500"></i>
                <span>مشترياتي والمفضلة ({{ $savedListings->count() }})</span>
            </button>
            <button @click="tab = 'following'" :class="tab === 'following' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/30' : 'text-slate-600 hover:text-slate-900'" class="px-6 py-4 flex items-center gap-2 transition whitespace-nowrap">
                <i class="fa-solid fa-users text-amber-500"></i>
                <span>الحسابات التي أتابعها ({{ $followingSellers->count() }})</span>
            </button>
            <button @click="tab = 'profile'" :class="tab === 'profile' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/30' : 'text-slate-600 hover:text-slate-900'" class="px-6 py-4 flex items-center gap-2 transition whitespace-nowrap">
                <i class="fa-solid fa-user-gear text-slate-600"></i>
                <span>الملف الشخصي وتذكر معلوماتي</span>
            </button>
        </div>

        <!-- Tab 1: Listings & Sales -->
        <div x-show="tab === 'listings'" class="p-6">
            @if($myListings->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold">
                            <tr>
                                <th class="p-3.5">السلعة أو الطلب</th>
                                <th class="p-3.5">النوع</th>
                                <th class="p-3.5">الحالة</th>
                                <th class="p-3.5">السعر</th>
                                <th class="p-3.5">الموقع</th>
                                <th class="p-3.5">المشاهدات</th>
                                <th class="p-3.5 text-center">الإجراءات والبيع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($myListings as $item)
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
                                                <span class="text-[10px] text-slate-500 block mt-0.5">نشر {{ $item->created_at->format('Y/m/d') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3.5">
                                        @if($item->listing_type === 'wanted')
                                            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded">مطلوب للشراء</span>
                                        @else
                                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">معروض للبيع</span>
                                        @endif
                                        @if($item->job_type)
                                            <span class="block text-[10px] text-sky-600 font-bold mt-1">
                                                {{ $item->job_type === 'vacancy' ? 'فرصة عمل' : 'باحث عن عمل' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3.5">
                                        @if($item->is_sold)
                                            <span class="bg-slate-800 text-white text-[10px] font-bold px-2 py-0.5 rounded">تم البيع ✓</span>
                                        @elseif($item->condition === 'new')
                                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">جديد</span>
                                        @else
                                            <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded">مستعمل</span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 font-bold text-slate-900">
                                        @if($item->price > 0)
                                            {{ number_format($item->price) }} {{ $item->currency == 'USD' ? '$' : 'ل.س' }}
                                        @else
                                            <span class="text-slate-400">حسب الاتفاق</span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-slate-500">
                                        {{ $item->governorate->name_ar ?? '-' }}
                                    </td>
                                    <td class="p-3.5 font-mono text-slate-600">
                                        {{ $item->views_count }}
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Toggle Sold Button -->
                                            <form action="{{ route('listings.sold', $item->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-[11px] font-bold px-2.5 py-1 rounded-lg transition {{ $item->is_sold ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}" title="{{ $item->is_sold ? 'إعادة عرض السلعة' : 'تمييز كـ تم البيع' }}">
                                                    <i class="fa-solid fa-check ml-1"></i>
                                                    <span>{{ $item->is_sold ? 'إعادة العرض' : 'تم البيع' }}</span>
                                                </button>
                                            </form>

                                            <!-- Edit -->
                                            <a href="{{ route('listings.edit', $item->id) }}" class="text-slate-600 hover:text-brand-500 p-1.5 rounded-lg hover:bg-slate-100" title="تعديل">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <!-- Delete -->
                                            <form action="{{ route('listings.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان نهائياً؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50" title="حذف">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $myListings->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 mb-1">لم تقم بنشر أي سلع أو إعلانات بعد</h3>
                    <p class="text-xs text-slate-500 mb-4">اعرض بضاعتك الجديدة أو المستعملة للبيع أو اطلب سلعة محددة للشراء مجاناً</p>
                    <a href="{{ route('listings.create') }}" class="mzad-gradient text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow inline-flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>إضافة أول إعلان لك الآن</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Tab 2: Saved Listings (مشترياتي والمفضلة) -->
        <div x-show="tab === 'saved'" class="p-6">
            @if($savedListings->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($savedListings as $fav)
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-md transition">
                            <div class="h-36 bg-slate-100 relative">
                                @php $fImg = $fav->images->first(); @endphp
                                <img src="{{ $fImg ? $fImg->url : 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=300&q=80' }}" class="w-full h-full object-cover">
                                @if($fav->is_sold)
                                    <span class="absolute top-2 right-2 bg-slate-900/90 text-white text-[10px] font-bold px-2 py-0.5 rounded">تم البيع</span>
                                @endif
                            </div>
                            <div class="p-3 text-right">
                                <a href="{{ route('listings.show', $fav->slug) }}" class="font-bold text-xs text-slate-900 hover:text-brand-500 line-clamp-1 block mb-1">
                                    {{ $fav->title }}
                                </a>
                                <p class="text-xs font-black text-brand-600 mb-2">
                                    {{ number_format($fav->price) }} {{ $fav->currency == 'USD' ? '$' : 'ل.س' }}
                                </p>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 pt-2">
                                    <span>{{ $fav->governorate->name_ar ?? '-' }}</span>
                                    <form action="{{ route('listings.favorite', $fav->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-rose-500 hover:underline">
                                            <i class="fa-solid fa-heart mr-1"></i> إزالة
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fa-regular fa-heart text-slate-300 text-4xl mb-3 block"></i>
                    <h3 class="text-sm font-bold text-slate-700">قائمة المفضلة والمشتريات فارغة</h3>
                    <p class="text-xs text-slate-500 mt-1">احفظ السلع التي تعجبك بالضغط على أيقونة القلب في صفحة أي سلعة</p>
                </div>
            @endif
        </div>

        <!-- Tab 3: Following Sellers (الحسابات التي أتابعها) -->
        <div x-show="tab === 'following'" class="p-6">
            @if($followingSellers->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($followingSellers as $followed)
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-brand-500 text-white flex items-center justify-center font-bold text-base shadow-sm">
                                    {{ mb_substr($followed->name, 0, 1) }}
                                </div>
                                <div>
                                    <a href="{{ route('sellers.show', $followed->id) }}" class="font-bold text-xs text-slate-900 hover:text-brand-500 block">
                                        {{ $followed->name }}
                                    </a>
                                    <span class="text-[10px] text-slate-500 block mt-0.5">
                                        {{ $followed->listings_count }} سلعة معروضة
                                    </span>
                                </div>
                            </div>
                            <form action="{{ route('sellers.follow', $followed->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-bold px-3 py-1.5 rounded-lg transition">
                                    إلغاء المتابعة
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fa-solid fa-users-slash text-slate-300 text-4xl mb-3 block"></i>
                    <h3 class="text-sm font-bold text-slate-700">أنت لا تتابع أي بائع حالياً</h3>
                    <p class="text-xs text-slate-500 mt-1">اضغط زر "متابعة البائع" في صفحة أي بائع لتصلك إشعارات فورية عند نشره سلعاً جديدة</p>
                </div>
            @endif
        </div>

        <!-- Tab 4: Profile Settings & Remember Info -->
        <div x-show="tab === 'profile'" class="p-6 max-w-2xl">
            <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-id-card text-brand-500"></i>
                <span>تعديل الملف الشخصي وبيانات التواصل</span>
            </h3>

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">الاسم الكامل أو اسم المتجر *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">رقم الهاتف الأساسي *</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">رقم الواتساب للتواصل</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">المحافظة السورية المقيم بها</label>
                    <select name="governorate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">اختر المحافظة...</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->name_ar }}" {{ $user->governorate == $gov->name_ar ? 'selected' : '' }}>{{ $gov->name_ar }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">نبذة عنك أو عن متجرك</label>
                    <textarea name="bio" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="نبذة تعريفية بالسلع التي تبيعها أو تخصصك...">{{ old('bio', $user->bio) }}</textarea>
                </div>

                <!-- Remember Contact Info Checkbox (كما طلب المستخدم: تذكر معلوماتك وغيرها بشكل احترافي) -->
                <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3.5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="remember_contact_info" value="1" {{ $user->remember_contact_info ? 'checked' : '' }} class="mt-0.5 rounded text-brand-500 focus:ring-brand-500">
                        <div>
                            <span class="text-xs font-bold text-amber-900 block">تذكر معلوماتي وتعبئتها تلقائياً</span>
                            <span class="text-[11px] text-amber-700 block mt-0.5">عند تفعيل هذا الخيار، سيتم ملء رقم هاتفك، الواتساب، ومحافظتك تلقائياً في استمارة نشر أي إعلان جديد دون الحاجة لإعادة كتابتها في كل مرة.</span>
                        </div>
                    </label>
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <h4 class="text-xs font-bold text-slate-700 mb-3">تغيير كلمة المرور (اختياري)</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input type="password" name="new_password" placeholder="كلمة المرور الجديدة..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                        <div>
                            <input type="password" name="new_password_confirmation" placeholder="تأكيد كلمة المرور..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                    </div>
                </div>

                <div class="pt-2 text-left">
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition shadow">
                        <i class="fa-solid fa-floppy-disk ml-1"></i> حفظ وتحديث البيانات
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection

