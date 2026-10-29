@extends('layouts.app')

@section('title', 'إنشاء حساب جديد | مزاد سوريا')

@section('content')

<div class="max-w-xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl border border-slate-200 p-8 shadow-sm">
        
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl mzad-gradient flex items-center justify-center text-white mx-auto mb-3 shadow-md">
                <i class="fa-solid fa-user-plus text-amber-400"></i>
            </div>
            <h1 class="text-xl font-black text-slate-900">إنشاء حساب جديد</h1>
            <p class="text-xs text-slate-500 mt-1">انضم لآلاف البائعين والمشترين في مزاد سوريا</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border-r-4 border-red-500 p-4 rounded-xl mb-6">
                <ul class="text-xs text-red-700 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Account Type: Buyer vs Seller -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع الحساب</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="role" value="user" {{ old('role', 'user') === 'user' ? 'checked' : '' }} class="text-brand-500">
                        <div class="mr-2.5">
                            <span class="block text-xs font-bold text-slate-900">مشتري / مستخدم عادي</span>
                            <span class="block text-[10px] text-slate-500">تصفح وشراء ومتابعة</span>
                        </div>
                    </label>
                    <label class="flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="role" value="seller" {{ old('role') === 'seller' ? 'checked' : '' }} class="text-brand-500">
                        <div class="mr-2.5">
                            <span class="block text-xs font-bold text-slate-900">بائع / صاحب معرض</span>
                            <span class="block text-[10px] text-slate-500">عرض بضائع وصفحة متجر</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Full Name -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">الاسم الكامل أو اسم المتجر <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="الاسم الشخصي أو التجاري" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:bg-white focus:outline-none" required>
            </div>

            <!-- Email & Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">البريد الإلكتروني <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="user@domain.com" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهاتف <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+9639..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                </div>
            </div>

            <!-- Governorate & WhatsApp -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">المحافظة</label>
                    <select name="governorate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                        <option value="">-- اختر محافظتك --</option>
                        @foreach($governorates as $gov)
                            <option value="{{ $gov->name_ar }}" {{ old('governorate') == $gov->name_ar ? 'selected' : '' }}>
                                {{ $gov->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">رقم واتساب (اختياري)</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="+9639..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <!-- Password & Confirm -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">كلمة المرور <span class="text-red-500">*</span></label>
                    <input type="password" name="password" placeholder="••••••••" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">تأكيد كلمة المرور <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation" placeholder="••••••••" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none" required>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-500 hover:bg-brand-600 text-white font-bold text-xs py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2 mt-4">
                <i class="fa-solid fa-user-plus"></i>
                <span>إنشاء الحساب وبدء الاستخدام</span>
            </button>
        </form>

        <!-- Google Register Option -->
        <div class="mt-4">
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-slate-400 text-[11px] font-bold">أو التسجيل السريع عبر</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <form action="{{ route('auth.google') }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 px-4 rounded-xl transition border border-slate-300 shadow-sm flex items-center justify-center gap-2">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" class="w-4 h-4" alt="Google">
                    <span>التسجيل بنقرة واحدة باستخدام Google</span>
                </button>
            </form>
        </div>

        <div class="text-center mt-6 pt-6 border-t border-slate-100 text-xs text-slate-600">
            لديك حساب بالفعل؟ 
            <a href="{{ route('login') }}" class="font-bold text-brand-500 hover:underline">
                تسجيل الدخول هنا
            </a>
        </div>
    </div>
</div>

@endsection

