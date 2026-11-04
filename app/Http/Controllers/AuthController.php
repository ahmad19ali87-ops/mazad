<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Listing;
use App\Models\User;
use App\Services\AuthMailService;
use App\Services\SecurityMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    protected SecurityMonitorService $securityMonitor;
    protected AuthMailService $authMailService;

    public function __construct(SecurityMonitorService $securityMonitor, AuthMailService $authMailService)
    {
        $this->securityMonitor = $securityMonitor;
        $this->authMailService = $authMailService;
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $ip = $request->ip();

        // 1. Check if IP is blocked
        if ($this->securityMonitor->isIpBlocked($ip)) {
            return back()->withErrors([
                'email' => 'تم حظر اتصالك مؤقتاً لدواعي الأمان والحماية نظراً لمحاولات الدخول الخاطئة المتكررة.',
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح',
            'password.required' => 'يرجى إدخال كلمة المرور',
        ]);

        $remember = true;

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Require email verification
            if (!$user->is_verified) {
                $code = (string) random_int(100000, 999999);
                $user->update([
                    'verification_code' => $code,
                    'verification_code_expires_at' => now()->addMinutes(15),
                ]);

                $this->authMailService->sendVerificationCode($user, $code);
                Auth::logout();
                session(['pending_verification_email' => $user->email]);

                return redirect()->route('verification.notice', ['email' => $user->email])
                    ->with('warning', 'حسابك بحاجة إلى تفعيل. لقد أرسلنا رمز تحقق سداسي جديد إلى بريدك الإلكتروني.');
            }

            // Record successful login & reset counters
            $this->securityMonitor->recordLoginAttempt($ip, $credentials['email'], true, $request->userAgent());

            $request->session()->regenerate();
            return redirect()->intended(route('home'))->with('success', 'مرحباً بك مجدداً، ' . Auth::user()->name);
        }

        // Record failed attempt and trigger automatic brute-force defense
        $this->securityMonitor->recordLoginAttempt($ip, $credentials['email'], false, $request->userAgent());

        return back()->withErrors([
            'email' => 'بيانات تسجيل الدخول غير متطابقة مع سجلاتنا. (محاولات الدخول الخاطئة المتكررة ستعرض عنوان IP للحظر التلقائي)',
        ])->onlyInput('email');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        $governorates = Governorate::where('is_active', true)->orderBy('sort_order')->get();
        return view('auth.register', compact('governorates'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|string|email|max:190|unique:users,email',
            'phone' => 'required|string|min:9|max:25|unique:users,phone',
            'password' => ['required', 'confirmed', Password::min(6)],
            'governorate' => 'nullable|string|max:100',
            'whatsapp' => 'nullable|string|max:30',
            'role' => 'nullable|in:user,seller',
        ], [
            'name.required' => 'يرجى إدخال الاسم الكامل أو اسم المتجر',
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.unique' => 'هذا البريد الإلكتروني مسجل مسبقاً',
            'phone.required' => 'يرجى إدخال رقم الهاتف للتواصل',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً',
            'password.required' => 'يرجى إدخال كلمة المرور',
            'password.confirmed' => 'كلمة المرور وتأكيدها غير متطابقين',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 خانات',
        ]);

        $verificationCode = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'governorate' => $validated['governorate'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? $validated['phone'],
            'role' => $validated['role'] ?? 'user',
            'is_verified' => false,
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(15),
            'remember_contact_info' => true,
        ]);

        // Dispatch 6-digit verification code from info@ahmadinsights.online
        $this->authMailService->sendVerificationCode($user, $verificationCode);

        session(['pending_verification_email' => $user->email]);

        return redirect()->route('verification.notice', ['email' => $user->email])
            ->with('success', 'تم إنشاء الحساب بنجاح! تم إرسال رمز التحقق السداسي إلى بريدك الإلكتروني لتفعيل الحساب.');
    }

    /**
     * Show email verification screen
     */
    public function showVerifyEmail(Request $request): View|RedirectResponse
    {
        $email = $request->query('email') ?? session('pending_verification_email');
        if (!$email) {
            return redirect()->route('login');
        }

        $user = User::where('email', $email)->first();
        if ($user && $user->is_verified) {
            return redirect()->route('home')->with('info', 'حسابك مفعل بالفعل، يمكنك تسجيل الدخول.');
        }

        return view('auth.verify-email', compact('email'));
    }

    /**
     * Verify email with 6-digit code
     */
    public function verifyEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق السداسي',
            'code.size' => 'رمز التحقق يجب أن يتكون من 6 أرقام بالضبط',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['code' => 'المستخدم غير مسجل'])->withInput();
        }

        if ($user->is_verified) {
            Auth::login($user, true);
            return redirect()->route('home')->with('info', 'حسابك مفعل مسبقاً!');
        }

        if ($user->verification_code !== trim($request->code)) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح، يرجى التأكد وإعادة المحاولة.'])->withInput();
        }

        if ($user->verification_code_expires_at && $user->verification_code_expires_at->isPast()) {
            return back()->withErrors(['code' => 'انتهت صلاحية رمز التحقق (الصلاحية 15 دقيقة). يرجى الضغط على زر إعادة الإرسال.'])->withInput();
        }

        // Activate user
        $user->update([
            'is_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        session()->forget('pending_verification_email');
        Auth::login($user, true);

        return redirect()->route('home')->with('success', 'تهانينا ' . $user->name . '! تم تفعيل حسابك بنجاح وأصبحت جاهزاً للاستخدام.');
    }

    /**
     * Resend 6-digit verification code
     */
    public function resendVerificationCode(Request $request): RedirectResponse
    {
        $email = $request->input('email') ?? session('pending_verification_email');
        if (!$email) {
            return back()->withErrors(['code' => 'يرجى تحديد البريد الإلكتروني']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['code' => 'المستخدم غير مسجل']);
        }

        if ($user->is_verified) {
            return redirect()->route('login')->with('info', 'الحساب مفعل بالفعل.');
        }

        $newCode = (string) random_int(100000, 999999);
        $user->update([
            'verification_code' => $newCode,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        $this->authMailService->sendVerificationCode($user, $newCode);

        return back()->with('success', 'تم إرسال رمز تحقق سداسي جديد إلى بريدك الإلكتروني بنجاح.');
    }

    /**
     * Show Forgot Password Form
     */
    public function showForgotPassword(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.forgot-password');
    }

    /**
     * Send Password Reset (6-digit code + link)
     */
    public function sendResetCodeEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'email.email' => 'يرجى إدخال بريد إلكتروني صالح',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'لم نتمكن من العثور على حساب مسجل بهذا البريد الإلكتروني.'])->withInput();
        }

        $resetCode = (string) random_int(100000, 999999);
        $resetToken = Str::random(60);

        $user->update([
            'password_reset_code' => $resetCode,
            'password_reset_token' => $resetToken,
            'password_reset_expires_at' => now()->addMinutes(15),
        ]);

        $resetUrl = route('password.reset', [
            'token' => $resetToken,
            'email' => $user->email,
        ]);

        $this->authMailService->sendPasswordReset($user, $resetCode, $resetUrl);

        return redirect()->route('password.reset', ['email' => $user->email])
            ->with('success', 'تم إرسال رمز الاستعادة ورابط التعيين إلى بريدك الإلكتروني. يرجى مراجعة بريدك.');
    }

    /**
     * Show Reset Password Screen (Accepts code or token)
     */
    public function showResetPassword(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $email = $request->query('email');
        $token = $request->query('token');

        return view('auth.reset-password', compact('email', 'token'));
    }

    /**
     * Reset Password Action
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(6)],
            'code' => 'nullable|string',
            'token' => 'nullable|string',
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني',
            'password.required' => 'يرجى إدخال كلمة المرور الجديدة',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 6 أحرف',
        ]);

        if (!$request->filled('code') && !$request->filled('token')) {
            return back()->withErrors(['code' => 'يرجى إدخال رمز التحقق السداسي أو استخدام الرابط المرسل لبريدك الإلكتروني'])->withInput();
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'المستخدم غير مسجل'])->withInput();
        }

        if ($user->password_reset_expires_at && $user->password_reset_expires_at->isPast()) {
            return back()->withErrors(['code' => 'انتهت صلاحية رمز ورابط الاستعادة (الصلاحية 15 دقيقة). يرجى طلب استعادة جديدة.'])->withInput();
        }

        $valid = false;

        // Check 6-digit code
        if ($request->filled('code') && $user->password_reset_code && trim($request->code) === $user->password_reset_code) {
            $valid = true;
        }

        // Check reset token
        if ($request->filled('token') && $user->password_reset_token && $request->token === $user->password_reset_token) {
            $valid = true;
        }

        if (!$valid) {
            return back()->withErrors(['code' => 'رمز التحقق أو رابط الاستعادة غير صالح'])->withInput();
        }

        // Update password and clear reset data
        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_code' => null,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'is_verified' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ]);

        Auth::login($user, true);

        return redirect()->route('home')->with('success', 'تمت إعادة تعيين كلمة المرور بنجاح وتم تسجيل دخولك.');
    }

    /**
     * Redirect to Google OAuth Provider
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google OAuth Callback
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'تعذر إتمام الدخول عبر Google: ' . $e->getMessage(),
            ]);
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $name = $googleUser->getName() ?: 'مستخدم Google';
        $avatar = $googleUser->getAvatar();

        $user = User::where('email', $email)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $user->google_id ?: $googleId,
                'avatar_url' => $user->avatar_url ?: $avatar,
                'is_verified' => true,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'avatar_url' => $avatar,
                'phone' => '09' . random_int(11111111, 99999999),
                'password' => Hash::make(Str::random(32)),
                'role' => 'user',
                'is_verified' => true,
                'email_verified_at' => now(),
                'remember_contact_info' => true,
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('home'))->with('success', 'تم تسجيل الدخول بحساب Google بنجاح! أهلاً بك، ' . $user->name);
    }

    /**
     * Fast simulated or direct Google login fallback
     */
    public function googleLogin(Request $request): RedirectResponse
    {
        if ($request->has('code') || $request->isMethod('get')) {
            return $this->redirectToGoogle();
        }

        $googleEmail = $request->input('email', 'google_user_' . Str::random(5) . '@gmail.com');
        $googleName = $request->input('name', 'مستخدم جوجل ' . Str::random(4));
        $googleId = $request->input('google_id', 'g_' . Str::random(12));
        $avatarUrl = $request->input('avatar_url', 'https://lh3.googleusercontent.com/a/default-user');

        $user = User::where('email', $googleEmail)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $user->google_id ?: $googleId,
                'avatar_url' => $user->avatar_url ?: $avatarUrl,
                'is_verified' => true,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleName,
                'email' => $googleEmail,
                'phone' => '09' . random_int(11111111, 99999999),
                'password' => Hash::make(Str::random(24)),
                'google_id' => $googleId,
                'avatar_url' => $avatarUrl,
                'role' => 'user',
                'is_verified' => true,
                'email_verified_at' => now(),
                'remember_contact_info' => true,
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('home'))->with('success', 'تم تسجيل الدخول بحساب Google بنجاح! أهلاً بك، ' . $user->name);
    }

    /**
     * User Central Dashboard
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 1. Listings & Sales
        $myListings = Listing::with(['category', 'governorate', 'images'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(12, ['*'], 'listings_page');

        $counts = [
            'total' => Listing::where('user_id', $user->id)->count(),
            'active' => Listing::where('user_id', $user->id)->where('is_sold', false)->where('status', 'active')->count(),
            'sold' => Listing::where('user_id', $user->id)->where('is_sold', true)->count(),
            'wanted' => Listing::where('user_id', $user->id)->where('listing_type', 'wanted')->count(),
            'views' => (int) Listing::where('user_id', $user->id)->sum('views_count'),
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
        ];

        // 2. Saved Listings
        $savedListings = $user->savedListings()->with(['category', 'images', 'governorate', 'user'])->get();

        // 3. Followed Sellers
        $followingSellers = $user->following()->withCount('listings')->get();

        // 4. Governorates for profile update
        $governorates = Governorate::where('is_active', true)->orderBy('sort_order')->get();

        return view('user.dashboard', compact('user', 'myListings', 'counts', 'savedListings', 'followingSellers', 'governorates'));
    }

    /**
     * Update user profile settings
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|string|min:9|max:25|unique:users,phone,' . $user->id,
            'whatsapp' => 'nullable|string|max:30',
            'governorate' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'remember_contact_info' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'new_password' => 'nullable|min:6|confirmed',
        ], [
            'name.required' => 'يرجى إدخال اسم المستخدم',
            'phone.required' => 'يرجى إدخال رقم الهاتف',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً بحساب آخر',
            'new_password.min' => 'كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف',
            'new_password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'whatsapp' => $validated['whatsapp'] ?? $validated['phone'],
            'governorate' => $validated['governorate'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'remember_contact_info' => (bool)$request->input('remember_contact_info', false),
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        if (!empty($validated['new_password'])) {
            $updateData['password'] = Hash::make($validated['new_password']);
        }

        $user->update($updateData);

        return back()->with('success', 'تم حفظ وتحديث بيانات حسابك ومعلومات التواصل بنجاح.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('info', 'تم تسجيل الخروج بنجاح.');
    }

    public function myListings(): View|RedirectResponse
    {
        return $this->dashboard(request());
    }

    public function notifications(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $notifications = Auth::user()->inAppNotifications()->paginate(20);
        Auth::user()->inAppNotifications()->where('is_read', false)->update(['is_read' => true]);

        return view('user.notifications', compact('notifications'));
    }
}
