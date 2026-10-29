@extends('layouts.app')

@section('title', 'تسجيل الدخول | مزاد سوريا')

@section('content')

<div class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl mzad-gradient flex items-center justify-center text-white mx-auto mb-3 shadow-md">
                <i class="fa-solid fa-lock text-amber-400"></i>
            </div>
            <h1 class="text-xl font-black text-slate-900">تسجيل الدخول</h1>
            <p class="text-xs text-slate-500 mt-1">أهلاً بك مجدداً في منصة مزاد سوريا</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border-r-4 border-red-500 p-3 rounded-xl mb-4">
                <ul class="text-xs text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني</label>
                <div class="relative">
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none" required>
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">كلمة المرور</label>
                <div class="relative">
                    <input type="password" name="password" placeholder="••••••••" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none" required>
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">
                        <i class="fa-solid fa-key"></i>
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-1.5 text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded text-brand-500">
                    <span>تذكرني على هذا الجهاز</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2 mt-2">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>تسجيل الدخول</span>
            </button>
        </form>

        <!-- Google Sign-In Option -->
        <div class="mt-4">
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-slate-400 text-[11px] font-bold">أو المتابعة عبر</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <form action="{{ route('auth.google') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 px-4 rounded-xl transition border border-slate-300 shadow-sm flex items-center justify-center gap-2">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-4 h-4" alt="Google">
                    <span>تسجيل الدخول السريع باستخدام Google</span>
                </button>
            </form>
        </div>

        <div class="text-center mt-6 pt-6 border-t border-slate-100 text-xs text-slate-600">
            ليس لديك حساب بعد؟ 
            <a href="{{ route('register') }}" class="font-bold text-brand-500 hover:underline">
                إنشاء حساب جديد مجاناً
            </a>
        </div>
    </div>
</div>

@endsection

