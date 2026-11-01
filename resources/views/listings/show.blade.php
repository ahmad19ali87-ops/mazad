@extends('layouts.app')

@section('title', $listing->title . ' | مزاد سوريا')

@section('content')

<!-- Breadcrumb -->
<div class="bg-white border-b border-slate-200 py-3">
    <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-brand-500">الرئيسية</a>
        <span>/</span>
        <a href="{{ route('listings.index', ['category' => $listing->category->slug ?? '']) }}" class="hover:text-brand-500">
            {{ $listing->category->name_ar ?? 'القسم' }}
        </a>
        @if($listing->subcategory)
            <span>/</span>
            <a href="{{ route('listings.index', ['subcategory' => $listing->subcategory->slug]) }}" class="hover:text-brand-500">
                {{ $listing->subcategory->name_ar }}
            </a>
        @endif
        <span>/</span>
        <span class="text-slate-800 font-bold truncate max-w-xs">{{ $listing->title }}</span>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">

    @auth
        @if(auth()->id() === $listing->user_id || auth()->user()->isAdmin())
            <div class="bg-gradient-to-r from-slate-900 via-brand-800 to-slate-900 text-white rounded-2xl p-4 sm:p-5 mb-6 border border-slate-700 shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-bold text-lg flex-shrink-0">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm">لوحة إدارة السلعة (أنت صاحب هذا الإعلان)</h3>
                        <p class="text-[11px] text-slate-300">
                            الحالة الحالية: 
                            <strong class="{{ $listing->is_sold ? 'text-amber-300' : 'text-emerald-400' }}">
                                {{ $listing->is_sold ? 'تم البيع' : 'نشط ومعروض للبيع' }}
                            </strong>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 self-stretch sm:self-auto justify-end">
                    <!-- Toggle Sold Status Form -->
                    <form action="{{ route('listings.sold', $listing->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow {{ $listing->is_sold ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-400 hover:bg-amber-500 text-slate-950' }}">
                            <i class="fa-solid fa-check-circle"></i>
                            <span>{{ $listing->is_sold ? 'إعادة العرض في السوق' : 'تمييز كـ "تم البيع"' }}</span>
                        </button>
                    </form>

                    <!-- Edit Listing Link -->
                    <a href="{{ route('listings.edit', $listing->id) }}" class="bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>تعديل الإعلان</span>
                    </a>

                    <!-- Delete Listing Form -->
                    <form action="{{ route('listings.destroy', $listing->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان نهائياً؟ لا يمكن التراجع.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-1.5">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>حذف</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif
    @endauth

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Cols: Gallery, Details, Specs, Comments -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Main Card with Gallery -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                <!-- Gallery -->
                <div class="p-4" x-data="{ 
                    activeImg: '{{ $listing->images->first() ? $listing->images->first()->url : "https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=1200&q=80" }}' 
                }">
                    <!-- Main Image Display -->
                    <div class="relative w-full h-80 sm:h-96 md:h-[450px] bg-slate-900 rounded-xl overflow-hidden flex items-center justify-center">
                        <img :src="activeImg" id="mainImage" alt="{{ $listing->title }}" class="max-w-full max-h-full object-contain transition duration-300">
                        
                        <!-- Condition Badge Floating -->
                        <div class="absolute top-4 right-4 flex gap-2">
                            @if($listing->condition === 'new')
                                <span class="bg-emerald-600 text-white text-xs font-black px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-sparkles"></i> جديد بالكرتونة (زيرو)
                                </span>
                            @else
                                <span class="bg-blue-600 text-white text-xs font-black px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-clock-rotate-left"></i> مستعمل
                                </span>
                            @endif

                            @if($listing->is_featured)
                                <span class="bg-amber-400 text-slate-950 text-xs font-black px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-1.5">
                                    <i class="fa-solid fa-crown"></i> إعلان مميز
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Thumbnails row -->
                    @if($listing->images->count() > 1)
                        <div class="flex items-center gap-2 mt-3 overflow-x-auto pb-2">
                            @foreach($listing->images as $img)
                                <button type="button" @click="activeImg = '{{ $img->url }}'" 
                                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border-2 border-transparent hover:border-brand-500 focus:border-brand-500 flex-shrink-0 transition bg-slate-100">
                                    <img src="{{ $img->url }}" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Title & Price Block -->
                <div class="p-6 border-t border-slate-100">
                    <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
                        <div class="flex-1">
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 leading-snug mb-2">
                                {{ $listing->title }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-clock text-amber-500"></i> نشر {{ $listing->created_at->diffForHumans() }}</span>
                                <span>•</span>
                                <span class="flex items-center gap-1"><i class="fa-regular fa-eye text-blue-500"></i> {{ number_format($listing->views_count) }} مشاهدة</span>
                                <span>•</span>
                                <span class="flex items-center gap-1"><i class="fa-solid fa-hashtag text-slate-400"></i> رقم الإعلان: #{{ $listing->id }}</span>
                            </div>
                        </div>

                        <!-- Price Box -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-left min-w-[180px]">
                            <p class="text-[11px] font-bold text-slate-500 mb-0.5">السعر المطلوب</p>
                            <div class="flex items-baseline gap-1 text-brand-600">
                                <span class="text-2xl md:text-3xl font-black">{{ number_format($listing->price, 0) }}</span>
                                <span class="text-sm font-bold text-slate-700">{{ $listing->currency === 'USD' ? 'دولار ($)' : 'ليرة سورية (ل.س)' }}</span>
                            </div>
                            @if($listing->is_negotiable)
                                <span class="inline-block mt-1 text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">
                                    <i class="fa-solid fa-handshake ml-1"></i> السعر قابل للتفاوض البسيط
                                </span>
                            @else
                                <span class="inline-block mt-1 text-[10px] font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded">
                                    سعر نهائي قطعي
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Full Detailed Location Breakdown (Syrian Hierarchy) -->
                    <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 my-6">
                        <h4 class="text-xs font-black text-amber-900 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-location-dot text-brand-500"></i>
                            <span>الموقع والعنوان التفصيلي في سوريا:</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                            <div class="bg-white p-2.5 rounded-xl border border-amber-100">
                                <span class="text-slate-500 block text-[10px]">المحافظة:</span>
                                <strong class="text-slate-900">{{ $listing->governorate ? $listing->governorate->name_ar : 'غير محدد' }}</strong>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-amber-100">
                                <span class="text-slate-500 block text-[10px]">المنطقة / المدينة:</span>
                                <strong class="text-slate-900">{{ $listing->region ? $listing->region->name_ar : 'غير محدد' }}</strong>
                            </div>
                            <div class="bg-white p-2.5 rounded-xl border border-amber-100">
                                <span class="text-slate-500 block text-[10px]">الحي:</span>
                                <strong class="text-slate-900">{{ $listing->district ? $listing->district->name_ar : ($listing->district_name ?? 'غير محدد') }}</strong>
                            </div>
                        </div>

                        @if($listing->street_address)
                            <div class="bg-white p-2.5 rounded-xl border border-amber-100 mt-2 text-xs">
                                <span class="text-slate-500 block text-[10px]">تكملة العنوان وأقرب معلم:</span>
                                <strong class="text-slate-900">{{ $listing->street_address }}</strong>
                            </div>
                        @endif
                    </div>

                    <!-- Specifications Table (المواصفات الكاملة) -->
                    @if(!empty($listing->specs) && is_array($listing->specs))
                        <div class="my-6">
                            <h3 class="text-sm font-black text-slate-900 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-list-check text-brand-500"></i> المواصفات الفنية والتفاصيل
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                @foreach($listing->specs as $key => $val)
                                    <div class="flex justify-between items-center bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                        <span class="text-slate-500 font-semibold">{{ $key }}:</span>
                                        <span class="font-bold text-slate-900">{{ $val }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Detailed Description -->
                    <div class="my-6 pt-4 border-t border-slate-100">
                        <h3 class="text-sm font-black text-slate-900 mb-2">الوصف والملاحظات</h3>
                        <div class="text-xs md:text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            {{ $listing->description }}
                        </div>
                    </div>

                    <!-- Actions (Share, Bookmark, Owner Edit) -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-2">
                            <!-- Favorite Toggle -->
                            <form action="{{ route('listings.favorite', $listing->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 font-bold transition {{ $isFavorited ? 'text-red-500 bg-red-50 border-red-200' : 'text-slate-700' }}">
                                    <i class="fa-{{ $isFavorited ? 'solid' : 'regular' }} fa-heart"></i>
                                    <span>{{ $isFavorited ? 'محفوظ في المفضلة' : 'حفظ السلعة' }}</span>
                                </button>
                            </form>
                        </div>

                        @auth
                            @if(auth()->id() === $listing->user_id || auth()->user()->isAdmin())
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('listings.edit', $listing->id) }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-3 py-2 rounded-xl transition flex items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square"></i> تعديل السلعة
                                    </a>
                                    <form action="{{ route('listings.destroy', $listing->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف هذا الإعلان نهائياً؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold px-3 py-2 rounded-xl transition flex items-center gap-1 border border-red-200">
                                            <i class="fa-solid fa-trash"></i> حذف
                                        </button>
                                    </form>
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Comments & Questions Section -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm" id="comments-section">
                <div class="flex justify-between items-center mb-6 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-comments text-brand-500"></i> التعليقات والاستفسارات
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">يمكنك الاستفسار عن السلعة ومناقشة البائع مباشرة</p>
                    </div>
                    <span class="bg-slate-100 text-slate-800 text-xs font-bold px-2.5 py-1 rounded-lg">
                        {{ $listing->comments->count() }} تعليق
                    </span>
                </div>

                <!-- Add Comment Form -->
                @if($listing->allow_comments)
                    <form action="{{ route('comments.store', $listing->id) }}" method="POST" class="mb-8">
                        @csrf
                        <div class="flex gap-3">
                            <div class="w-10 h-10 rounded-full bg-brand-500 text-white font-bold flex items-center justify-center flex-shrink-0 text-sm">
                                @auth
                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                @else
                                    <i class="fa-solid fa-user"></i>
                                @endauth
                            </div>
                            <div class="flex-1">
                                <textarea name="comment" rows="2" placeholder="{{ auth()->check() ? 'اكتب استفسارك أو تعليقك هنا...' : 'يرجى تسجيل الدخول لتتمكن من كتابة استفسار' }}" 
                                          class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none" 
                                          {{ auth()->check() ? 'required' : 'disabled' }}></textarea>
                                
                                <div class="flex justify-end mt-2">
                                    @auth
                                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs px-5 py-2 rounded-xl transition shadow flex items-center gap-1.5">
                                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                                            <span>نشر التعليق</span>
                                        </button>
                                    @else
                                        <a href="{{ route('login') }}" class="bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs px-4 py-2 rounded-xl transition">
                                            تسجيل الدخول للتعليق
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="bg-slate-50 p-3 rounded-xl text-center text-xs text-slate-500 mb-6">
                        <i class="fa-solid fa-lock ml-1"></i> التعليقات مغلقة على هذه السلعة
                    </div>
                @endif

                <!-- Comments List -->
                <div class="space-y-4">
                    @forelse($listing->comments as $comment)
                        <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-100" id="comment-{{ $comment->id }}">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center text-xs">
                                        {{ mb_substr($comment->user->name ?? 'مستخدم', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-slate-900">{{ $comment->user->name ?? 'مستخدم' }}</span>
                                            @if($comment->user_id === $listing->user_id)
                                                <span class="bg-brand-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded">صاحب الإعلان</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-600">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                @auth
                                    @if(auth()->id() === $comment->user_id || auth()->user()->isAdmin())
                                        <form action="{{ route('comments.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('حذف هذا التعليق؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-600 hover:text-red-500 text-xs">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>

                            <p class="text-xs text-slate-700 leading-relaxed pr-10 mb-3">
                                {{ $comment->comment }}
                            </p>

                            <!-- Replies if any -->
                            @if($comment->replies->count() > 0)
                                <div class="mr-8 space-y-2 mt-2 pt-2 border-t border-slate-200/60">
                                    @foreach($comment->replies as $reply)
                                        <div class="bg-white p-3 rounded-xl border border-slate-200/80">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-bold text-slate-900">{{ $reply->user->name ?? 'مستخدم' }}</span>
                                                @if($reply->user_id === $listing->user_id)
                                                    <span class="bg-amber-400 text-slate-950 text-[9px] font-black px-1.5 py-0.2 rounded">البائع</span>
                                                @endif
                                                <span class="text-[10px] text-slate-600">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-xs text-slate-700">{{ $reply->comment }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Reply Box Trigger -->
                            @auth
                                <div class="mr-8 mt-2" x-data="{ openReply: false }">
                                    <button @click="openReply = !openReply" type="button" class="text-[11px] font-bold text-brand-500 hover:underline flex items-center gap-1">
                                        <i class="fa-solid fa-reply"></i> رد على هذا التعليق
                                    </button>
                                    
                                    <form x-show="openReply" action="{{ route('comments.store', $listing->id) }}" method="POST" class="mt-2 flex gap-2">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                        <input type="text" name="comment" placeholder="اكتب ردك..." class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                                        <button type="submit" class="bg-brand-500 text-white text-xs font-bold px-3 py-1.5 rounded-lg">إرسال</button>
                                    </form>
                                </div>
                            @endauth
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-600">
                            لا توجد تعليقات حتى الآن. كن أول من يسأل أو يستفسر عن هذه السلعة!
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Col: Seller Profile Card & Contact Info -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Seller Card with Follow Button -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm sticky top-24">
                <div class="text-center pb-4 border-b border-slate-100">
                    <div class="w-20 h-20 rounded-full bg-brand-500 text-white font-black text-2xl flex items-center justify-center mx-auto mb-3 shadow-md border-4 border-white">
                        {{ mb_substr($listing->user->name ?? 'بائع', 0, 1) }}
                    </div>

                    <div class="flex items-center justify-center gap-1.5">
                        <h3 class="text-base font-black text-slate-900">{{ $listing->user->name ?? 'بائع معتمد' }}</h3>
                        @if($listing->user && $listing->user->is_verified)
                            <i class="fa-solid fa-circle-check text-blue-500 text-sm" title="بائع موثق ومعتمد"></i>
                        @endif
                    </div>

                    @if($listing->user && $listing->user->bio)
                        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">{{ $listing->user->bio }}</p>
                    @endif

                    <div class="flex justify-center items-center gap-4 mt-3 text-xs text-slate-600 font-semibold">
                        <span><strong class="text-slate-900 font-bold">{{ $listing->user ? $listing->user->followers()->count() : 0 }}</strong> متابع</span>
                        <span>•</span>
                        <span>عضو منذ {{ $listing->user ? $listing->user->created_at->format('Y/m') : '' }}</span>
                    </div>

                    <!-- Follow Button (زر المتابعة لتصلك إشعارات بالسلع الجديدة) -->
                    @if($listing->user)
                        <form action="{{ route('sellers.follow', $listing->user->id) }}" method="POST" class="mt-4">
                            @csrf
                            <button type="submit" class="w-full py-2.5 rounded-xl font-bold text-xs transition shadow flex items-center justify-center gap-2 {{ $isFollowingSeller ? 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300' : 'bg-brand-500 hover:bg-brand-600 text-white' }}">
                                <i class="fa-solid {{ $isFollowingSeller ? 'fa-user-check text-emerald-600' : 'fa-bell text-amber-300' }}"></i>
                                <span>{{ $isFollowingSeller ? 'أنت تتابع هذا البائع (إلغاء)' : 'متابعة البائع (إشعارات السلع الجديدة)' }}</span>
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Contact Buttons -->
                <div class="pt-4 space-y-2.5">
                    @php
                        $phone = $listing->phone ?: ($listing->user ? $listing->user->phone : null);
                        $whatsapp = $listing->whatsapp ?: ($listing->user ? $listing->user->whatsapp : null);
                    @endphp

                    @if($phone)
                        <a href="tel:{{ $phone }}" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs py-3 rounded-xl transition shadow flex items-center justify-center gap-2">
                            <i class="fa-solid fa-phone text-amber-400"></i>
                            <span>اتصال بالهاتف ({{ $phone }})</span>
                        </a>
                    @endif

                    @if($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsapp) }}?text={{ urlencode('مرحباً، أستفسر بخصوص إعلانكم على مزاد سوريا: ' . $listing->title) }}" 
                           target="_blank" 
                           class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-3 rounded-xl transition shadow flex items-center justify-center gap-2">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                            <span>مراسلة عبر واتساب</span>
                        </a>
                    @endif

                    @if($listing->user)
                        <a href="{{ route('sellers.show', $listing->user->id) }}" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 border border-slate-200">
                            <i class="fa-solid fa-store text-brand-500"></i>
                            <span>مشاهدة كافة معروضات هذا البائع</span>
                        </a>
                    @endif
                </div>

                <!-- Safety Note -->
                <div class="mt-6 bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-[11px] text-slate-500 leading-relaxed">
                    <p class="font-bold text-slate-700 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved text-emerald-600"></i> نصائح للأمان:
                    </p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>عاين السلعة وتأكد من مطابقة المواصفات قبل الدفع.</li>
                        <li>لا تقم بتحويل أي عربون مالي قبل المعاينة على أرض الواقع.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

