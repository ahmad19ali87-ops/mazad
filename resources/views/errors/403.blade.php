@extends('errors.layout')

@section('title', '403 - وصول غير مصرح')

@section('content')
<div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-rose-50 text-rose-600 mb-4 shadow-inner">
    <i class="fa-solid fa-lock text-4xl"></i>
</div>

<span class="inline-block px-3 py-1 rounded-full bg-slate-100 text-slate-600 font-black text-xs tracking-wider mb-2">
    ERROR 403
</span>

<h1 class="text-2xl md:text-3xl font-black text-slate-900 mb-2">
    عذراً، لا تملك صلاحية الوصول لهذه الصفحة!
</h1>

<p class="text-xs md:text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
    الصفحة التي طلبتها مخصصة لمدير النظام أو تتطلب إذناً خاصاً غير متوفر في حسابك الحالي.
</p>

<!-- Action Buttons -->
<div class="flex flex-wrap items-center justify-center gap-3">
    <button onclick="window.history.back()" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold px-4 py-2.5 rounded-xl transition border border-slate-200 cursor-pointer">
        <i class="fa-solid fa-arrow-right"></i>
        <span>الرجوع للخلف</span>
    </button>
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition shadow shadow-brand-500/20">
        <i class="fa-solid fa-house"></i>
        <span>الصفحة الرئيسية</span>
    </a>
</div>
@endsection

