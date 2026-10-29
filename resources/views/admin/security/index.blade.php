@extends('layouts.admin')

@section('title', 'لوحة مراقبة الأمان وصد الهجمات (Firewall & WAF Shield)')

@section('content')
<div class="space-y-6">

    <!-- Header & WAF Status Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/90 border border-slate-800 p-5 rounded-2xl">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    جدار الحماية الفوري (WAF Shield) نشط وفعّال
                </span>
                <span class="text-xs text-slate-400">| الحماية من Brute-force وحقن SQL و XSS</span>
            </div>
            <h1 class="text-xl md:text-2xl font-black text-white">لوحة مراقبة النظام وصد الهجمات الإلكترونية</h1>
            <p class="text-xs text-slate-400">رصد لحظي لمحاولات الاختراق، الفحص المشبوه، والحظر التلقائي لعناوين IP المهاجمة</p>
        </div>

        <div class="flex items-center gap-2">
            <form action="{{ route('admin.security.clear-logs') }}" method="POST" onsubmit="return confirm('هل تريد تنظيف السجلات الأقدم من 30 يوماً؟');">
                @csrf
                <button type="submit" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3.5 py-2 rounded-xl transition border border-slate-700 flex items-center gap-1.5">
                    <i class="fa-solid fa-broom"></i>
                    <span>تنظيف السجلات القديمة</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Security Metric Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1 -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400">عناوين IP المحظورة</span>
                <div class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-sm border border-rose-500/20">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-white">{{ number_format($stats['active_blocks_count']) }}</p>
            <p class="text-[11px] text-rose-400 mt-1 font-semibold">ممنوعون من الوصول للنظام تماماً</p>
        </div>

        <!-- Stat 2 -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400">هجمات تم إحباطها اليوم</span>
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm border border-amber-500/20">
                    <i class="fa-solid fa-shield-virus"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-white">{{ number_format($stats['attacks_today_count']) }}</p>
            <p class="text-[11px] text-amber-400 mt-1 font-semibold">رصد وتصدي خلال الـ 24 ساعة الماضية</p>
        </div>

        <!-- Stat 3 -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400">محاولات دخول فاشلة اليوم</span>
                <div class="w-9 h-9 rounded-xl bg-sky-500/10 text-sky-400 flex items-center justify-center text-sm border border-sky-500/20">
                    <i class="fa-solid fa-key"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-white">{{ number_format($stats['failed_logins_today']) }}</p>
            <p class="text-[11px] text-sky-400 mt-1 font-semibold">تخضع لمراقبة عتبة الحظر (5 محاولات = حظر)</p>
        </div>

        <!-- Stat 4 -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400">أحداث بالغة الخطورة</span>
                <div class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-sm border border-purple-500/20">
                    <i class="fa-solid fa-skull-crossbones"></i>
                </div>
            </div>
            <p class="text-2xl font-black text-white">{{ number_format($stats['critical_events_count']) }}</p>
            <p class="text-[11px] text-purple-400 mt-1 font-semibold">SQLi / Path Traversal تم عزلها</p>
        </div>
    </div>

    <!-- Manual Block Form & Quick Rules -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Manual IP Block Form -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-hand text-rose-500"></i>
                <span>إضافة عنوان IP للحظر اليدوي الفوري</span>
            </h3>

            <form action="{{ route('admin.security.block') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-400 mb-1">عنوان IP *</label>
                        <input type="text" name="ip_address" placeholder="مثال: 192.168.1.50" required 
                               class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 font-mono">
                    </div>

                    <div class="sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-400 mb-1">مدة الحظر</label>
                        <select name="hours" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <option value="24">24 ساعة (يوم واحد)</option>
                            <option value="72">72 ساعة (3 أيام)</option>
                            <option value="168">أسبوع كامل (7 أيام)</option>
                            <option value="720">شهر (30 يوماً)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-1 flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-300 font-semibold cursor-pointer">
                            <input type="checkbox" name="is_permanent" value="1" class="rounded bg-slate-800 border-slate-700 text-rose-600 focus:ring-rose-500">
                            <span>حظر دائم بلا انتهاء</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">سبب الحظر والتفاصيل *</label>
                    <input type="text" name="reason" placeholder="مثال: هجوم سبام متكرر، محاولات استغلال، بلاغات زبائن..." required 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <div class="text-left">
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition shadow flex items-center gap-2 mr-auto">
                        <i class="fa-solid fa-ban"></i>
                        <span>حظر عنوان IP الآن</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Protection Policy Cards -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                <span>قواعد الدفاع النشطة المطبقة</span>
            </h3>
            <ul class="space-y-3 text-xs text-slate-300">
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                    <span><strong>صد محاولات التخمين (Brute-Force):</strong> أي عنوان IP يخفق 5 مرات في تسجيل الدخول يتم حظره تلقائياً لمدة 24 ساعة.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                    <span><strong>عزل حقن SQL و XSS:</strong> فحص تلقائي لكافة المدخلات ونصوص الاستعلام وعزل أي محاولة حقن خطيرة.</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                    <span><strong>حجب برامج الفحص (Scanners):</strong> حظر محاولات طلب ملفات `.env` أو مسارات `wp-admin` وغيرها.</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Active Blocked IPs Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list text-rose-400"></i>
                <span>قائمة عناوين IP المحظورة حالياً ({{ $stats['blocked_ips']->count() }})</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-800/60 text-slate-400 font-bold border-b border-slate-800">
                    <tr>
                        <th class="p-3.5">عنوان IP</th>
                        <th class="p-3.5">سبب الحظر</th>
                        <th class="p-3.5">تكرار المحاولات</th>
                        <th class="p-3.5">المصدر</th>
                        <th class="p-3.5">تاريخ الحظر</th>
                        <th class="p-3.5">المدة / الانتهاء</th>
                        <th class="p-3.5 text-center">إجراء فك الحظر</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($stats['blocked_ips'] as $block)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="p-3.5 font-mono font-bold text-rose-400">{{ $block->ip_address }}</td>
                            <td class="p-3.5 max-w-xs truncate text-slate-300" title="{{ $block->reason }}">{{ $block->reason }}</td>
                            <td class="p-3.5"><span class="px-2 py-0.5 rounded bg-slate-800 font-bold">{{ $block->attempts_count }}</span></td>
                            <td class="p-3.5 text-slate-400">{{ $block->blocked_by }}</td>
                            <td class="p-3.5 text-slate-400 font-mono">{{ $block->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3.5">
                                @if($block->is_permanent)
                                    <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold">حظر دائم</span>
                                @else
                                    <span class="font-mono text-slate-300">{{ $block->blocked_until?->format('Y-m-d H:i') }}</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center">
                                <form action="{{ route('admin.security.unblock', $block->ip_address) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من فك الحظر عن هذا العنوان؟');">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white px-3 py-1 rounded-lg transition border border-emerald-500/30 font-bold text-[11px]">
                                        <i class="fa-solid fa-lock-open ml-1"></i> فك الحظر
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500 font-semibold">
                                لا توجد أي عناوين IP محظورة حالياً. النظام يعمل بأمان تام.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Security Attack Logs Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-400"></i>
                <span>سجل الهجمات والأنشطة المشبوهة اللحظي</span>
            </h3>
            <span class="text-xs text-slate-400 font-mono">آخر 25 نشاط مسجل</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-800/60 text-slate-400 font-bold border-b border-slate-800">
                    <tr>
                        <th class="p-3.5">الوقت</th>
                        <th class="p-3.5">عنوان IP</th>
                        <th class="p-3.5">نوع التهديد</th>
                        <th class="p-3.5">مستوى الخطورة</th>
                        <th class="p-3.5">الرابط المستهدف</th>
                        <th class="p-3.5">الإجراء المتخذ</th>
                        <th class="p-3.5">حظر سريع</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($stats['recent_logs'] as $log)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="p-3.5 font-mono text-slate-400">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="p-3.5 font-mono font-bold text-amber-400">{{ $log->ip_address }}</td>
                            <td class="p-3.5 font-bold">
                                @if($log->event_type === 'brute_force')
                                    <span class="text-rose-400"><i class="fa-solid fa-key ml-1"></i> تخمين كلمات مرور (Brute Force)</span>
                                @elseif($log->event_type === 'sql_injection')
                                    <span class="text-purple-400"><i class="fa-solid fa-database ml-1"></i> محاولة حقن SQL</span>
                                @elseif($log->event_type === 'xss_attempt')
                                    <span class="text-sky-400"><i class="fa-solid fa-code ml-1"></i> محاولة حقن نصوص XSS</span>
                                @elseif($log->event_type === 'scanner_probe')
                                    <span class="text-amber-400"><i class="fa-solid fa-radar ml-1"></i> فحص مسارات حساسة</span>
                                @else
                                    <span class="text-slate-300">{{ $log->event_type }}</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                @if($log->severity === 'critical')
                                    <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/40 font-bold">بالغ الخطورة</span>
                                @elseif($log->severity === 'high')
                                    <span class="px-2 py-0.5 rounded bg-orange-500/20 text-orange-300 border border-orange-500/40 font-bold">خطورة عالية</span>
                                @elseif($log->severity === 'medium')
                                    <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold">متوسط</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 font-bold">معلومات</span>
                                @endif
                            </td>
                            <td class="p-3.5 max-w-xs truncate font-mono text-[11px] text-slate-400" title="{{ $log->url }}">{{ $log->method }} {{ $log->url }}</td>
                            <td class="p-3.5">
                                @if($log->action_taken === 'blocked')
                                    <span class="px-2 py-0.5 rounded bg-rose-900 text-rose-200 font-bold">تم الحظر فوراً</span>
                                @elseif($log->action_taken === 'throttled')
                                    <span class="px-2 py-0.5 rounded bg-amber-900 text-amber-200 font-bold">تم تقييد المعدل</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-bold">تم التسجيل</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <form action="{{ route('admin.security.block') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="ip_address" value="{{ $log->ip_address }}">
                                    <input type="hidden" name="reason" value="حظر سريع بناء على سجل الأمان: {{ $log->event_type }}">
                                    <input type="hidden" name="hours" value="24">
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 hover:underline font-bold text-[11px]">
                                        <i class="fa-solid fa-ban"></i> حظر IP
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500 font-semibold">
                                لا توجد سجلات تهديدات حديثة. السيرفر آمن ومحمي.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

