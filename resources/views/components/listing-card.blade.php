@php
    $primaryImg = $listing->images->first();
    $imgUrl = $primaryImg ? $primaryImg->url : 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=600&q=80';
@endphp

<div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden mzad-card-hover flex flex-col justify-between group relative">
    
    <!-- Thumbnail & Badges -->
    <div class="relative w-full h-48 bg-slate-100 overflow-hidden">
        <a href="{{ route('listings.show', $listing->slug) }}">
            <img src="{{ $imgUrl }}" alt="{{ $listing->title }}" 
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                 loading="lazy">
        </a>

        <!-- Condition Badge: الجديد من القديم -->
        <div class="absolute top-2.5 right-2.5 flex flex-col gap-1">
            @if($listing->is_sold)
                <span class="bg-slate-900/90 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm">
                    تم البيع ✓
                </span>
            @endif

            @if($listing->listing_type === 'wanted')
                <span class="bg-amber-500 text-slate-950 text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm flex items-center gap-1">
                    <i class="fa-solid fa-hand-holding-dollar text-[9px]"></i> مطلوب
                </span>
            @endif

            @if($listing->condition === 'new')
                <span class="bg-emerald-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm flex items-center gap-1">
                    <i class="fa-solid fa-sparkles text-[9px]"></i> جديد
                </span>
            @else
                <span class="bg-blue-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm flex items-center gap-1">
                    <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> مستعمل
                </span>
            @endif

            @if($listing->job_type)
                <span class="bg-sky-600 text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                    {{ $listing->job_type === 'vacancy' ? 'فرصة عمل' : 'سيرة ذاتية' }}
                </span>
            @endif

            @if($listing->is_featured)
                <span class="bg-amber-400 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
                    <i class="fa-solid fa-crown text-[9px]"></i> مميز
                </span>
            @endif
        </div>

        <!-- Location Pill -->
        <div class="absolute bottom-2.5 right-2.5 bg-slate-900/80 backdrop-blur-sm text-white text-[10px] font-bold px-2.5 py-1 rounded-md flex items-center gap-1">
            <i class="fa-solid fa-location-dot text-amber-400"></i>
            <span>{{ $listing->governorate ? $listing->governorate->name_ar : 'سوريا' }}</span>
            @if($listing->region)
                <span> - {{ $listing->region->name_ar }}</span>
            @endif
        </div>
    </div>

    <!-- Body Information -->
    <div class="p-3.5 flex-1 flex flex-col justify-between">
        <div>
            <!-- Category & Time -->
            <div class="flex justify-between items-center text-[10px] text-slate-600 font-semibold mb-1.5">
                <span class="text-brand-500 font-bold bg-brand-50 px-2 py-0.5 rounded">
                    {{ $listing->category ? $listing->category->name_ar : '' }}
                </span>
                <span>{{ $listing->created_at->diffForHumans() }}</span>
            </div>

            <!-- Title -->
            <h3 class="text-xs font-bold text-slate-900 hover:text-brand-500 transition line-clamp-2 leading-relaxed mb-2">
                <a href="{{ route('listings.show', $listing->slug) }}">
                    {{ $listing->title }}
                </a>
            </h3>
        </div>

        <div>
            <!-- Price -->
            <div class="flex items-baseline justify-between mt-2 pt-2 border-t border-slate-100">
                <div class="text-slate-900">
                    <span class="text-base font-black text-brand-600">
                        {{ number_format($listing->price, 0) }}
                    </span>
                    <span class="text-xs font-bold text-slate-500">
                        {{ $listing->currency === 'USD' ? '$' : 'ل.س' }}
                    </span>
                    @if($listing->is_negotiable)
                        <span class="text-[9px] text-emerald-600 bg-emerald-50 font-bold px-1.5 py-0.5 rounded mr-1">قابل للتفاوض</span>
                    @endif
                </div>

                <!-- Seller link & avatar -->
                @if($listing->user)
                    <a href="{{ route('sellers.show', $listing->user->id) }}" class="flex items-center gap-1 text-[11px] text-slate-500 hover:text-brand-500 transition" title="عرض متجر البائع">
                        <span class="max-w-[80px] truncate font-medium">{{ $listing->user->name }}</span>
                        @if($listing->user->is_verified)
                            <i class="fa-solid fa-circle-check text-blue-500 text-[10px]" title="بائع موثق"></i>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

