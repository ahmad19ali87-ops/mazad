@extends('layouts.app')

@section('title', 'تفعيل الحساب برمز التحقق | مزاد سوريا')

@section('content')

<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 mx-auto mb-3 shadow-sm">
                <i class="fa-solid fa-envelope-circle-check text-2xl text-brand-600"></i>
            </div>
            <h1 class="text-xl font-black text-slate-900">تفعيل حسابك</h1>
            <p class="text-xs text-slate-500 mt-1">
                أدخل رمز التحقق السداسي المرسل إلى بريدك
            </p>
            <div class="mt-2 inline-block bg-slate-100 px-3 py-1 rounded-full text-xs font-mono font-bold text-slate-700">
                {{ $email }}
            </div>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border-r-4 border-emerald-500 p-3 rounded-xl mb-4 text-xs text-emerald-800 font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-amber-50 border-r-4 border-amber-500 p-3 rounded-xl mb-4 text-xs text-amber-800 font-medium">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border-r-4 border-red-500 p-3 rounded-xl mb-4">
                <ul class="text-xs text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('verification.verify') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <label class="block text-center text-xs font-bold text-slate-700 mb-2">
                    رمز التحقق السداسي (6 أرقام)
                </label>
                <div class="relative max-w-[260px] mx-auto">
                    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" 
                           placeholder="••••••" 
                           class="w-full text-center tracking-[12px] font-mono text-2xl font-bold bg-slate-50 border-2 border-slate-200 focus:border-brand-500 rounded-2xl py-3 focus:bg-white focus:outline-none transition shadow-inner" 
                           required autofocus autocomplete="one-time-code">
                </div>
                <p class="text-[11px] text-slate-400 text-center mt-2">
                    الرمز مرسل من بريدنا: <span class="font-bold text-slate-600">info@ahmadinsights.online</span> (صالح لمدة 15 دقيقة)
                </p>
            </div>

            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-md flex items-center justify-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>تأكيد الرمز وتفعيل الحساب</span>
            </button>
        </form>

        <!-- Resend Code Section -->
        <div class="mt-6 pt-6 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-500 mb-2">لم يصلك الرمز؟ تفقد مجلد الرسائل غير المرغوب فيها (Spam) أو أعد الإرسال:</p>
            
            <form action="{{ route('verification.resend') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" class="text-xs font-bold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1.5 py-1">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>إعادة إرسال رمز جديد</span>
                </button>
            </form>
        </div>

        <div class="text-center mt-4 text-xs text-slate-400">
            <a href="{{ route('login') }}" class="hover:text-slate-600 hover:underline">
                العودة إلى تسجيل الدخول
            </a>
        </div>
    </div>
</div>

@endsection
