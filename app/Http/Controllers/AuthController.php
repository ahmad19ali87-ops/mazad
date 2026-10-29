<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Listing;
use App\Models\User;
use App\Services\SecurityMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected SecurityMonitorService $securityMonitor;

    public function __construct(SecurityMonitorService $securityMonitor)
    {
        $this->securityMonitor = $securityMonitor;
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

        $remember = (bool)$request->remember;

        if (Auth::attempt($credentials, $remember)) {
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
            'email' => 'required|string|email|max:190|unique:users',
            'phone' => 'required|string|min:9|max:25|unique:users',
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'governorate' => $validated['governorate'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? $validated['phone'],
            'role' => $validated['role'] ?? 'user',
            'is_verified' => false,
            'remember_contact_info' => true,
        ]);

        Auth::login($user);

        return redirect()->route('home')->with('success', 'أهلاً بك في منصة مزاد سوريا! تم إنشاء حسابك بنجاح.');
    }

    /**
     * Google Sign-in handler (Web OAuth / One-tap simulated)
     */
    public function googleLogin(Request $request): RedirectResponse
    {
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
            ]);
        } else {
            $user = User::create([
                'name' => $googleName,
                'email' => $googleEmail,
                'phone' => '09' . rand(11111111, 99999999),
                'password' => Hash::make(Str::random(16)),
                'google_id' => $googleId,
                'avatar_url' => $avatarUrl,
                'role' => 'user',
                'is_verified' => true,
                'remember_contact_info' => true,
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('home'))->with('success', 'تم تسجيل الدخول بحساب Google بنجاح! أهلاً بك، ' . $user->name);
    }

    /**
     * User Central Dashboard (مشترياتي، مبيعاتي، إعلاناتي، الحسابات التي أتابعها، تذكر معلوماتي)
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

        // 2. Saved Listings (مشترياتي والمفضلة)
        $savedListings = $user->savedListings()->with(['category', 'images', 'governorate', 'user'])->get();

        // 3. Followed Sellers (الحسابات التي أتابعها)
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
