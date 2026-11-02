@extends('layouts.admin')

@section('title', 'إدارة الواجهة والمظهر للموقع وتطبيق الجوال')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800 p-5 rounded-2xl">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-500/10 text-amber-400 border border-brand-500/20 text-xs font-bold mb-1">
                <i class="fa-solid fa-palette"></i>
                <span>تخصيص الهوية البصرية ونظام المحتوى</span>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-white">إدارة مظهر وواجهة الموقع وتطبيق الجوال</h1>
            <p class="text-xs text-slate-400">تحكم كامل بالألوان، النصوص الترويجية، ميزات تطبيق الجوال، وتسجيل الدخول عبر Google</p>
        </div>
    </div>

    <form action="{{ route('admin.appearance.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 0. Listing Moderation & Approval Switch (مراجعة الإعلانات قبل النشر) -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-amber-950/30 border border-amber-500/30 rounded-2xl p-6 shadow-lg">
            <h3 class="text-sm font-bold text-white mb-2 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-clipboard-check text-amber-400 text-base"></i>
                <span class="text-amber-400">نظام فحص ومراجعة الإعلانات (Listing Moderation System)</span>
            </h3>
            <p class="text-xs text-slate-300 mb-4">التحكم بسياسة النشر الفوري أو المراجعة الإدارية المسبقة لجميع الإعلانات المنشورة من قبل المستخدمين</p>

            <label class="flex items-center justify-between p-4 bg-slate-800/80 rounded-xl border border-amber-500/20 cursor-pointer hover:bg-slate-800 transition">
                <div>
                    <p class="text-xs font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-amber-400"></i>
                        <span>تفعيل مراجعة الإعلانات قبل ظهورها للجمهور</span>
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                        • <strong>عند التفعيل:</strong> أي إعلان جديد يدخل في حالة <span class="text-amber-400 font-bold">"قيد المراجعة"</span> ولا يظهر في الموقع أو التطبيق إلا بعد موافقة الإدارة.<br>
                        • <strong>عند التعطيل:</strong> يتم نشر وقبول أي إعلان فورياً وتلقائياً دون انتظار مراجعة.
                    </p>
                </div>
                <input type="hidden" name="listings_require_approval" value="0">
                <input type="checkbox" name="listings_require_approval" value="1" {{ ($settings['listings_require_approval'] ?? '0') == '1' ? 'checked' : '' }} 
                       class="w-6 h-6 rounded bg-slate-700 border-slate-600 text-amber-500 focus:ring-amber-500">
            </label>
        </div>

        <!-- 1. General & Brand Identity + Logos -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-globe text-brand-500"></i>
                <span>الهوية الأساسية، الشعار، وأيقونة المتصفح</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">اسم المنصة والموقع</label>
                    <input type="text" name="site_name" value="{{ $settings['site_name'] ?? 'مزاد سوريا' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">شعار المنصة (Slogan)</label>
                    <input type="text" name="site_slogan" value="{{ $settings['site_slogan'] ?? 'السوق السوري المفتوح لكافة السلع' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">البريد الإلكتروني للدعم الفني</label>
                    <input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? 'support@mazad.sy' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">رقم هاتف الإدارة</label>
                    <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] ?? '+963 11 2345678' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">واتساب خدمة العملاء</label>
                    <input type="text" name="contact_whatsapp" value="{{ $settings['contact_whatsapp'] ?? '+963 944 112233' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>
            </div>

            <!-- Logo & Favicon Uploads -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-800">
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700">
                    <label class="block text-xs font-bold text-white mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-image text-brand-500"></i>
                        <span>رفع شعار الموقع (Logo)</span>
                    </label>
                    @if(!empty($settings['site_logo']))
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ Storage::disk('public')->url($settings['site_logo']) }}" alt="Site Logo" class="h-10 object-contain bg-slate-900 p-1.5 rounded-lg border border-slate-700">
                            <span class="text-[11px] text-emerald-400 font-bold">الشعار الحالي محفوظ</span>
                        </div>
                    @endif
                    <input type="file" name="site_logo" accept="image/*" 
                           class="block w-full text-xs text-slate-400 file:mr-0 file:ml-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-500 file:text-white hover:file:bg-brand-600">
                    <p class="text-[10px] text-slate-500 mt-1">المقاس المفضل: 240×60 بكسل، PNG شفاف أو SVG أو JPG</p>
                </div>

                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700">
                    <label class="block text-xs font-bold text-white mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-icons text-amber-400"></i>
                        <span>أيقونة المتصفح (Favicon)</span>
                    </label>
                    @if(!empty($settings['site_favicon']))
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ Storage::disk('public')->url($settings['site_favicon']) }}" alt="Favicon" class="w-8 h-8 object-contain bg-slate-900 p-1 rounded-lg border border-slate-700">
                            <span class="text-[11px] text-emerald-400 font-bold">الأيقونة الحالية محفوظة</span>
                        </div>
                    @endif
                    <input type="file" name="site_favicon" accept=".ico,image/png,image/x-icon,image/svg+xml" 
                           class="block w-full text-xs text-slate-400 file:mr-0 file:ml-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600">
                    <p class="text-[10px] text-slate-500 mt-1">أيقونة التبويب في المتصفح (.ico أو .png بحجم 32×32 بكسل)</p>
                </div>
            </div>
        </div>

        <!-- Social Media Links (روابط وسائل التواصل الاجتماعي) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-share-nodes text-sky-400"></i>
                <span>حسابات ووسائل التواصل الاجتماعي (Social Media)</span>
            </h3>
            <p class="text-xs text-slate-400 mb-4">تظهر هذه الروابط كأيقونات تفاعلية في أسفل الموقع وتطبيق الموبايل ويمكن تغييرها بأي وقت</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1 flex items-center gap-1.5">
                        <i class="fa-brands fa-facebook text-blue-500"></i> رابط فيسبوك (Facebook)
                    </label>
                    <input type="url" name="social_facebook" value="{{ $settings['social_facebook'] ?? 'https://facebook.com' }}" placeholder="https://facebook.com/..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1 flex items-center gap-1.5">
                        <i class="fa-brands fa-instagram text-pink-500"></i> رابط انستغرام (Instagram)
                    </label>
                    <input type="url" name="social_instagram" value="{{ $settings['social_instagram'] ?? 'https://instagram.com' }}" placeholder="https://instagram.com/..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1 flex items-center gap-1.5">
                        <i class="fa-brands fa-x-twitter text-white"></i> رابط منصة إكس / تويتر (X)
                    </label>
                    <input type="url" name="social_twitter" value="{{ $settings['social_twitter'] ?? 'https://x.com' }}" placeholder="https://x.com/..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1 flex items-center gap-1.5">
                        <i class="fa-brands fa-telegram text-sky-400"></i> رابط قناة / حساب تلغرام (Telegram)
                    </label>
                    <input type="url" name="social_telegram" value="{{ $settings['social_telegram'] ?? 'https://t.me' }}" placeholder="https://t.me/..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1 flex items-center gap-1.5">
                        <i class="fa-brands fa-whatsapp text-emerald-400"></i> رابط محادثة واتساب (WhatsApp Direct Link)
                    </label>
                    <input type="url" name="social_whatsapp" value="{{ $settings['social_whatsapp'] ?? 'https://wa.me/963944112233' }}" placeholder="https://wa.me/..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono">
                </div>
            </div>
        </div>

        <!-- Footer Texts & Copyright (نصوص الفوتر والحقوق) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-align-right text-emerald-400"></i>
                <span>نصوص الفوتر، النبذة التعريفية وحقوق الملكية</span>
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">النبذة التعريفية في الفوتر (About Us)</label>
                    <textarea name="footer_description" rows="2" 
                              class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">{{ $settings['footer_description'] ?? 'المنصة الأولى المفتوحة لعرض وبيع وشراء كافة البضائع والسلع الجديدة والمستعملة في جميع المحافظات والمناطق السورية، مع ميزة متابعة البائعين وتوثيق الإعلانات.' }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">نص حقوق الملكية الفكرية في أسفل الصفحة (Copyright)</label>
                    <input type="text" name="footer_copyright" value="{{ $settings['footer_copyright'] ?? ('© ' . date('Y') . ' منصة مزاد سوريا (Mzad Syria). جميع الحقوق محفوظة.') }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
        </div>

        <!-- 2. Colors & Appearance -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-paintbrush text-amber-400"></i>
                <span>الألوان والتنسيق البصري (Theme Colors)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">اللون الرئيسي للمنصة (Primary Color)</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="theme_primary_color" value="{{ $settings['theme_primary_color'] ?? '#8b1d30' }}" 
                               class="w-12 h-10 rounded-lg bg-transparent cursor-pointer border border-slate-700">
                        <input type="text" value="{{ $settings['theme_primary_color'] ?? '#8b1d30' }}" readonly 
                               class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono w-32">
                        <span class="text-xs text-slate-400">(اللون العنابي الكلاسيكي لستايل مزاد)</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">اللون الثانوي والتمييز (Accent Color)</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="theme_accent_color" value="{{ $settings['theme_accent_color'] ?? '#d97706' }}" 
                               class="w-12 h-10 rounded-lg bg-transparent cursor-pointer border border-slate-700">
                        <input type="text" value="{{ $settings['theme_accent_color'] ?? '#d97706' }}" readonly 
                               class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono w-32">
                        <span class="text-xs text-slate-400">(اللون الذهبي/الكهرماني للنجوم والشارات)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Hero Section & Promotion -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-bullhorn text-sky-400"></i>
                <span>الترويسة الرئيسية والبانرات في الصفحة الأولى</span>
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">عنوان الترويسة الرئيسية الكبير</label>
                    <input type="text" name="hero_title" value="{{ $settings['hero_title'] ?? 'سوقك المفتوح لكل السلع في سوريا' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">الوصف الترويجي أسفل العنوان</label>
                    <textarea name="hero_subtitle" rows="2" 
                              class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">{{ $settings['hero_subtitle'] ?? 'تصفح آلاف الإعلانات المفحوصة، ميز الجديد من المستعمل، تابع بائعيك المفضلين وتواصل فورياً مع أصحاب السلع' }}</textarea>
                </div>
            </div>
        </div>

        <!-- 4. Mobile App Customization & Toggles -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-3">
                <i class="fa-solid fa-mobile-screen text-emerald-400"></i>
                <span>إعدادات وخصائص تطبيق الجوال (Flutter Mobile App)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">اسم التطبيق في الجوال</label>
                    <input type="text" name="mobile_app_name" value="{{ $settings['mobile_app_name'] ?? 'مزاد سوريا' }}" 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <div class="space-y-3 pt-2">
                <!-- Toggle 1: Google Sign In -->
                <label class="flex items-center justify-between p-3.5 bg-slate-800/60 rounded-xl border border-slate-700 cursor-pointer hover:bg-slate-800 transition">
                    <div>
                        <p class="text-xs font-bold text-white flex items-center gap-2">
                            <i class="fa-brands fa-google text-rose-400"></i>
                            <span>تسجيل الدخول باستخدام حساب Google</span>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">إتاحة زر تسجيل الدخول السريع بحساب جوجل في تطبيق الجوال والموقع بنقرة واحدة</p>
                    </div>
                    <input type="hidden" name="mobile_google_login_enabled" value="0">
                    <input type="checkbox" name="mobile_google_login_enabled" value="1" {{ ($settings['mobile_google_login_enabled'] ?? '1') == '1' ? 'checked' : '' }} 
                           class="w-5 h-5 rounded bg-slate-700 border-slate-600 text-brand-500 focus:ring-brand-500">
                </label>

                <!-- Toggle 2: Wanted Feature -->
                <label class="flex items-center justify-between p-3.5 bg-slate-800/60 rounded-xl border border-slate-700 cursor-pointer hover:bg-slate-800 transition">
                    <div>
                        <p class="text-xs font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-hand-holding-dollar text-amber-400"></i>
                            <span>خاصية إعلانات "مطلوب للشراء" (Wanted / ISO)</span>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">تمكين المستخدمين من نشر طلبات الشراء مثل: "مطلوب سيارة كيا" أو "مطلوب شقة للإيجار"</p>
                    </div>
                    <input type="hidden" name="mobile_wanted_feature_enabled" value="0">
                    <input type="checkbox" name="mobile_wanted_feature_enabled" value="1" {{ ($settings['mobile_wanted_feature_enabled'] ?? '1') == '1' ? 'checked' : '' }} 
                           class="w-5 h-5 rounded bg-slate-700 border-slate-600 text-brand-500 focus:ring-brand-500">
                </label>

                <!-- Toggle 3: Jobs Section -->
                <label class="flex items-center justify-between p-3.5 bg-slate-800/60 rounded-xl border border-slate-700 cursor-pointer hover:bg-slate-800 transition">
                    <div>
                        <p class="text-xs font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-briefcase text-sky-400"></i>
                            <span>قسم الوظائف وفرص العمل والباحثين عن عمل</span>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">قسم التوظيف: شركات تنشر وظائف ومحاسبين شاغرة + باحثين عن عمل وسير ذاتية</p>
                    </div>
                    <input type="hidden" name="mobile_jobs_feature_enabled" value="0">
                    <input type="checkbox" name="mobile_jobs_feature_enabled" value="1" {{ ($settings['mobile_jobs_feature_enabled'] ?? '1') == '1' ? 'checked' : '' }} 
                           class="w-5 h-5 rounded bg-slate-700 border-slate-600 text-brand-500 focus:ring-brand-500">
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="text-left">
            <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold px-6 py-3 rounded-xl transition shadow flex items-center gap-2 mr-auto">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>حفظ التعديلات والتحديث الفوري</span>
            </button>
        </div>
    </form>

</div>
@endsection

