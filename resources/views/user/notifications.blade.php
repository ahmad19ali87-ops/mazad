@extends('layouts.app')

@section('title', 'مركز الإشعارات والتنبيهات | مزاد سوريا')

@section('content')

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="mb-6 pb-3 border-b border-slate-200">
        <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-bell text-brand-500"></i> الإشعارات والتنبيهات
        </h1>
        <p class="text-xs text-slate-500 mt-1">تنبيهات فورية بالسلع الجديدة من البائعين الذين تتابعهم والتعليقات</p>
    </div>

    @if($notifications->count() > 0)
        <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden shadow-sm">
            @foreach($notifications as $notif)
                <div class="p-4 flex items-start gap-3.5 hover:bg-slate-50/80 transition {{ !$notif->is_read ? 'bg-amber-50/40' : '' }}">
                    <div class="w-10 h-10 rounded-full {{ $notif->type === 'new_listing' ? 'bg-brand-50 text-brand-600' : 'bg-blue-50 text-blue-600' }} flex items-center justify-center flex-shrink-0 text-sm">
                        <i class="fa-solid {{ $notif->type === 'new_listing' ? 'fa-box-open' : 'fa-comment-dots' }}"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex justify-between items-start gap-2">
                            <h4 class="text-xs font-bold text-slate-900">{{ $notif->title }}</h4>
                            <span class="text-[10px] text-slate-600">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $notif->message }}</p>

                        @if($notif->action_url)
                            <a href="{{ $notif->action_url }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-brand-500 hover:underline mt-2">
                                <span>عرض التفاصيل</span>
                                <i class="fa-solid fa-arrow-left text-[9px]"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto mb-4">
                <i class="fa-regular fa-bell-slash"></i>
            </div>
            <h3 class="text-base font-black text-slate-800 mb-1">لا توجد إشعارات حالياً</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                عند متابعتك لأي بائع أو تفاعل الآخرين مع إعلاناتك، ستصلك كافة التنبيهات هنا لحظة بلحظة.
            </p>
        </div>
    @endif
</div>

@endsection

