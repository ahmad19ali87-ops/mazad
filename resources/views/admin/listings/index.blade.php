@extends('layouts.admin')

@section('title', 'إدارة ومراجعة السلع والإعلانات | إدارة مزاد سوريا')

@section('content')

<div class="space-y-6">

    <!-- Header & Review Mode Controller -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-slate-900 border border-slate-800 p-5 rounded-2xl">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-500/10 text-amber-400 border border-brand-500/20 text-xs font-bold mb-1">
                <i class="fa-solid fa-list-check"></i>
                <span>مركز إدارة واعتماد المعروضات</span>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-white">إدارة وفحص إعلانات المستخدمين</h1>
            <p class="text-xs text-slate-400">مراجعة الإعلانات الجديدة، الموافقة والنشر، الرفض مع إشعار البائع، والتحكم بسياسة النشر</p>
        </div>

        <!-- Quick Moderation Switch -->
        <div class="bg-slate-800/90 border {{ $requireApproval ? 'border-amber-500/40' : 'border-slate-700' }} p-3.5 rounded-xl flex items-center justify-between gap-4 w-full lg:w-auto">
            <div>
                <span class="block text-xs font-bold text-white flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved {{ $requireApproval ? 'text-amber-400' : 'text-slate-400' }}"></i>
                    <span>نظام المراجعة المسبقة:</span>
                    <strong class="{{ $requireApproval ? 'text-amber-400' : 'text-emerald-400' }}">
                        {{ $requireApproval ? 'مفعل (إلزامي)' : 'معطل (نشر فوري)' }}
                    </strong>
                </span>
                <span class="text-[10px] text-slate-400">
                    {{ $requireApproval ? 'الإعلانات الجديدة تتطلب موافقة الإدارة قبل الظهور' : 'أي إعلان ينشر يظهر مباشرة وتلقائياً للجميع' }}
                </span>
            </div>
            <form action="{{ route('admin.listings.toggle-moderation') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs font-bold px-3.5 py-1.5 rounded-lg transition shadow flex items-center gap-1.5 {{ $requireApproval ? 'bg-amber-500 hover:bg-amber-600 text-slate-950' : 'bg-slate-700 hover:bg-slate-600 text-white' }}">
                    <i class="fa-solid fa-power-off text-xs"></i>
                    <span>{{ $requireApproval ? 'تعطيل المراجعة' : 'تفعيل المراجعة' }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Status Tabs with Badges -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 text-xs font-bold">
        <a href="{{ route('admin.listings.index') }}" 
           class="px-4 py-2 rounded-xl border transition flex items-center gap-2 whitespace-nowrap {{ !request('status') ? 'bg-brand-500 text-white border-brand-500 shadow' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700' }}">
            <span>كافة الإعلانات</span>
            <span class="bg-black/30 text-white text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $counts['total'] }}</span>
        </a>

        <a href="{{ route('admin.listings.index', ['status' => 'pending']) }}" 
           class="px-4 py-2 rounded-xl border transition flex items-center gap-2 whitespace-nowrap {{ request('status') === 'pending' ? 'bg-amber-500 text-slate-950 border-amber-400 shadow' : 'bg-slate-800 text-amber-400 border-slate-700 hover:bg-slate-700' }}">
            <i class="fa-solid fa-hourglass-half"></i>
            <span>قيد المراجعة والاعتماد</span>
            <span class="bg-amber-400/20 text-amber-300 text-[10px] px-2 py-0.5 rounded-full font-mono font-black">{{ $counts['pending'] }}</span>
        </a>

        <a href="{{ route('admin.listings.index', ['status' => 'active']) }}" 
           class="px-4 py-2 rounded-xl border transition flex items-center gap-2 whitespace-nowrap {{ request('status') === 'active' ? 'bg-emerald-600 text-white border-emerald-500 shadow' : 'bg-slate-800 text-emerald-400 border-slate-700 hover:bg-slate-700' }}">
            <i class="fa-solid fa-circle-check"></i>
            <span>نشط ومنشور للجمهور</span>
            <span class="bg-emerald-500/20 text-emerald-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $counts['active'] }}</span>
        </a>

        <a href="{{ route('admin.listings.index', ['status' => 'rejected']) }}" 
           class="px-4 py-2 rounded-xl border transition flex items-center gap-2 whitespace-nowrap {{ request('status') === 'rejected' ? 'bg-rose-600 text-white border-rose-500 shadow' : 'bg-slate-800 text-rose-400 border-slate-700 hover:bg-slate-700' }}">
            <i class="fa-solid fa-ban"></i>
            <span>مرفوض أو مخفي</span>
            <span class="bg-rose-500/20 text-rose-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $counts['rejected'] }}</span>
        </a>

        <a href="{{ route('admin.listings.index', ['status' => 'sold']) }}" 
           class="px-4 py-2 rounded-xl border transition flex items-center gap-2 whitespace-nowrap {{ request('status') === 'sold' ? 'bg-slate-700 text-white border-slate-600 shadow' : 'bg-slate-800 text-slate-400 border-slate-700 hover:bg-slate-700' }}">
            <i class="fa-solid fa-tag"></i>
            <span>تم البيع</span>
            <span class="bg-slate-700 text-slate-300 text-[10px] px-2 py-0.5 rounded-full font-mono">{{ $counts['sold'] }}</span>
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-slate-800/80 rounded-2xl border border-slate-700/80 p-4">
        <form action="{{ route('admin.listings.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث بعنوان السلعة..." class="bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            
            <select name="category_id" class="bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-500" onchange="this.form.submit()">
                <option value="">كافة الأقسام الرئيسية</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name_ar }}</option>
                @endforeach
            </select>

            <div>
                <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl p-2.5 transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>تصفية النتائج</span>
                </button>
            </div>

            @if(request('q') || request('category_id') || request('status'))
                <div>
                    <a href="{{ route('admin.listings.index') }}" class="w-full bg-slate-700 hover:bg-slate-600 text-slate-200 font-bold rounded-xl p-2.5 transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>إلغاء التصفية</span>
                    </a>
                </div>
            @endif
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
                        <th class="p-3.5">حالة النشر والاعتماد</th>
                        <th class="p-3.5">التمييز (VIP)</th>
                        <th class="p-3.5 text-center">إجراءات المراجعة والتحكم</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    @forelse($listings as $item)
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-12 h-12 rounded-lg bg-slate-900 overflow-hidden flex-shrink-0 border border-slate-700">
                                        @php $img = $item->images->first(); @endphp
                                        <img src="{{ $img ? $img->url : 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=150&q=80' }}" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <a href="{{ route('listings.show', $item->slug) }}" target="_blank" class="font-bold text-white hover:text-amber-400 line-clamp-1">
                                            {{ $item->title }}
                                        </a>
                                        <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                            <span>#{{ $item->id }}</span>
                                            <span>•</span>
                                            <span>{{ $item->created_at->format('Y/m/d H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-300">
                                <span class="bg-slate-900 px-2 py-1 rounded text-slate-300 font-medium border border-slate-700">
                                    {{ $item->category->name_ar ?? '-' }}
                                </span>
                            </td>
                            <td class="p-3.5">
                                @if($item->condition === 'new')
                                    <span class="bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded">جديد</span>
                                @else
                                    <span class="bg-blue-950 text-blue-400 border border-blue-800 text-[10px] font-bold px-2 py-0.5 rounded">مستعمل</span>
                                @endif
                            </td>
                            <td class="p-3.5 font-bold text-amber-400 font-mono">
                                {{ number_format($item->price, 0) }} {{ $item->currency }}
                            </td>
                            <td class="p-3.5 text-slate-300">
                                <div class="font-semibold text-white">{{ $item->user->name ?? '-' }}</div>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $item->user->phone ?? '' }}</span>
                            </td>
                            <td class="p-3.5 text-slate-400">
                                {{ $item->governorate->name_ar ?? '' }}
                            </td>
                            <!-- Status Badge -->
                            <td class="p-3.5">
                                @if($item->status === 'active')
                                    <span class="inline-flex items-center gap-1 bg-emerald-950/80 text-emerald-400 border border-emerald-800/80 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-circle-check text-[10px]"></i> نشط معتمد
                                    </span>
                                @elseif($item->status === 'pending')
                                    <span class="inline-flex items-center gap-1 bg-amber-950/80 text-amber-400 border border-amber-800/80 text-[11px] font-black px-2.5 py-1 rounded-full animate-pulse">
                                        <i class="fa-solid fa-hourglass-half text-[10px]"></i> قيد المراجعة
                                    </span>
                                @elseif(in_array($item->status, ['rejected', 'hidden']))
                                    <span class="inline-flex items-center gap-1 bg-rose-950/80 text-rose-400 border border-rose-800/80 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-ban text-[10px]"></i> مرفوض / مخفي
                                    </span>
                                @elseif($item->status === 'sold' || $item->is_sold)
                                    <span class="inline-flex items-center gap-1 bg-slate-900 text-slate-400 border border-slate-700 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-tag text-[10px]"></i> تم البيع
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">{{ $item->status }}</span>
                                @endif
                            </td>
                            <!-- VIP Toggle -->
                            <td class="p-3.5">
                                <form action="{{ route('admin.listings.featured', $item->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2 py-1 rounded text-[10px] font-bold transition {{ $item->is_featured ? 'bg-amber-400 text-slate-950 shadow' : 'bg-slate-700 text-slate-400 hover:text-white' }}">
                                        {{ $item->is_featured ? '★ مميز (VIP)' : 'عادي' }}
                                    </button>
                                </form>
                            </td>
                            <!-- Action Buttons -->
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- View -->
                                    <a href="{{ route('listings.show', $item->slug) }}" target="_blank" class="p-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg transition" title="معاينة في الموقع">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <!-- If Pending or Rejected: Approve Button -->
                                    @if($item->status === 'pending' || in_array($item->status, ['rejected', 'hidden']))
                                        <form action="{{ route('admin.listings.status', $item->id) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="p-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition shadow" title="موافقة ونشر فوري">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- If Active: Hide/Reject Button -->
                                    @if($item->status === 'active')
                                        <form action="{{ route('admin.listings.status', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('إخفاء هذا الإعلان عن الزوار؟ سيصل إشعار للبائع.');">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="p-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg transition" title="إخفاء / رفض الإعلان">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete Button -->
                                    <form action="{{ route('admin.listings.destroy', $item->id) }}" method="POST" onsubmit="return confirm('حذف هذا الإعلان نهائياً وإشعار صاحبه بالحذف؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-rose-950 text-rose-400 hover:bg-rose-900 border border-rose-800 rounded-lg transition" title="حذف نهائي">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-600 block"></i>
                                <span>لا توجد إعلانات مطابقة لمعايير البحث الحالية</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-700">
            {{ $listings->links() }}
        </div>
    </div>

</div>

@endsection

