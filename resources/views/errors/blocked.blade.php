@extends('errors.layout')

@section('title', 'تم حظر عنوان IP لدواعي الأمان')

@section('content')
<div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-red-100 text-red-600 mb-4 shadow-inner">
    <i class="fa-solid fa-shield-virus text-4xl"></i>
</div>

<span class="inline-block px-3 py-1 rounded-full bg-red-50 text-red-700 font-black text-xs tracking-wider mb-2 border border-red-200">
    SECURITY FIREWALL BLOCK (WAF)
</span>

<h1 class="text-2xl md:text-3xl font-black text-slate-900 mb-2">
    تم حظر الوصول إلى منصة مزاد سوريا
</h1>

<div class="bg-red-50/80 border border-red-200 rounded-2xl p-4 text-xs text-red-800 max-w-md mx-auto mb-6 text-right space-y-2">
    <p><strong>سبب الحظر:</strong> {{ $reason ?? 'تم رصد نشاط مخالف لمعايير الأمان أو تكرار محاولات تسجيل دخول غير مصرح بها.' }}</p>
    <p><strong>عنوان IP الخاص بك:</strong> <code class="bg-white px-2 py-0.5 rounded border border-red-200 font-mono">{{ $ip ?? request()->ip() }}</code></p>
    <p><strong>مدة الحظر:</strong> {{ $blocked_until ?? 'حظر أمني مؤقت' }}</p>
</div>

<p class="text-xs text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
    إذا كنت تعتقد أن هذا الحظر تم عن طريق الخطأ، يرجى تزويد إدارة النظام بعنوان IP الموضح أعلاه عبر قنوات الدعم الرسمية لمراجعته وفك الحظر.
</p>
@endsection

