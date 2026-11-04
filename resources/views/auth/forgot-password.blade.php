@extends('layouts.app')

@section('title', 'نسيت كلمة المرور | مزاد سوريا')

@section('content')

<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        
        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 mx-auto mb-3 shadow-sm">
                <i class="fa-solid fa-key text-2xl"></i>
            </div>
            <h1 class="text-xl font-black text-slate-900">استعادة كلمة المرور</h1>
            <p class="text-xs text-slate-500 mt-1">
                أدخل بريدك الإلكتروني المسجل لنرسل لك رمزاً ورابطاً لتعيين كلمة المرور
            </p>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border-r-4 border-emerald-500 p-3 rounded-xl mb-4 text-xs text-emerald-800 font-medium">
                {{ session('success') }}
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

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني المسجل</label>
                <div class="relative">
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none" required autofocus>
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">
                    سيتم إرسال كود الاستعادة السداسي ورابط مباشر من بريدنا: <strong class="text-slate-600">info@ahmadinsights.online</strong>
                </p>
            </div>

            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2 mt-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span>إرسال كود ورابط الاستعادة</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:underline">
                <i class="fa-solid fa-arrow-right ml-1"></i> العودة لتسجيل الدخول
            </a>
            <a href="{{ route('register') }}" class="hover:text-slate-700 hover:underline">
                إنشاء حساب جديد
            </a>
        </div>
    </div>
</div>

@endsection
