<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AuthMailService
{
    protected string $fromAddress;
    protected string $fromName;

    public function __construct()
    {
        $this->fromAddress = config('mail.from.address', 'info@ahmadinsights.online');
        $this->fromName = config('mail.from.name', 'مزاد سوريا');
    }

    /**
     * Send 6-digit email verification code.
     */
    public function sendVerificationCode(User $user, string $code): bool
    {
        $subject = "رمز تفعيل حسابك في مزاد سوريا: [{$code}]";
        $html = $this->buildVerificationEmailHtml($user, $code);

        return $this->deliverEmail($user->email, $user->name, $subject, $html, "تفعيل البريد ({$code})");
    }

    /**
     * Send password reset 6-digit code and direct reset link.
     */
    public function sendPasswordReset(User $user, string $code, string $resetUrl): bool
    {
        $subject = "إعادة تعيين كلمة المرور - مزاد سوريا: [{$code}]";
        $html = $this->buildPasswordResetEmailHtml($user, $code, $resetUrl);

        return $this->deliverEmail($user->email, $user->name, $subject, $html, "إعادة تعيين كلمة المرور ({$code})");
    }

    /**
     * Safe email delivery wrapper with local logging fallback.
     */
    protected function deliverEmail(string $toEmail, string $toName, string $subject, string $html, string $context): bool
    {
        try {
            // Check if mailer can be dispatched
            Mail::html($html, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName)
                    ->from($this->fromAddress, $this->fromName)
                    ->subject($subject);
            });

            Log::info("AuthMailService: نجح إرسال بريد {$context} إلى {$toEmail}");
            return true;
        } catch (Throwable $e) {
            // Log fallback so local tests and offline dev continue seamlessly
            Log::warning("AuthMailService: تعذر إرسال بريد {$context} إلى {$toEmail} عبر SMTP. السبب: " . $e->getMessage());
            Log::info("AuthMailService [محاكاة]: رمز {$context} للمستخدم {$toEmail}");
            return false;
        }
    }

    /**
     * HTML template for 6-digit verification code.
     */
    protected function buildVerificationEmailHtml(User $user, string $code): string
    {
        $siteUrl = config('app.url', 'https://mazad.ahmadinsights.online');
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفعيل الحساب - مزاد سوريا</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; direction: rtl; text-align: right; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #881337 0%, #4c0519 100%); padding: 35px 30px; text-align: center;">
                            <div style="display: inline-block; background: rgba(255,255,255,0.15); padding: 12px 24px; border-radius: 50px; margin-bottom: 12px;">
                                <span style="font-size: 24px; font-weight: 900; color: #fbbf24; letter-spacing: 1px;">مزاد سوريا</span>
                            </div>
                            <p style="margin: 0; color: #fecdd3; font-size: 13px; font-weight: 500;">سوق ومزادات سوريا الأولى للسيارات والعقارات والتجارة</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 35px;">
                            <h2 style="margin: 0 0 15px; color: #0f172a; font-size: 20px; font-weight: 800;">مرحباً بك {$user->name}،</h2>
                            <p style="margin: 0 0 25px; color: #475569; font-size: 14px; line-height: 1.8;">
                                شكراً لانضمامك إلى <strong>مزاد سوريا</strong>. لإتمام إنشاء حسابك وتفعيله بنجاح، يرجى إدخال رمز التحقق السداسي التالي في شاشة التفعيل:
                            </p>

                            <!-- Code Box -->
                            <div style="background: #fff1f2; border: 2px dashed #e11d48; border-radius: 16px; padding: 25px 20px; text-align: center; margin: 30px 0;">
                                <span style="display: block; font-size: 12px; font-weight: 700; color: #9f1239; margin-bottom: 8px;">رمز التحقق السداسي (ينتهي خلال 15 دقيقة)</span>
                                <span style="display: inline-block; font-size: 38px; font-weight: 900; letter-spacing: 12px; color: #881337; font-family: 'Courier New', monospace; direction: ltr;">{$code}</span>
                            </div>

                            <p style="margin: 0 0 20px; color: #64748b; font-size: 13px; line-height: 1.7;">
                                <strong style="color: #0f172a;">ملاحظة أمنية:</strong> لا تشارك هذا الرمز مع أي شخص. فريق مزاد سوريا لن يطلب منك هذا الرمز أبداً عبر الهاتف أو وسائل التواصل.
                            </p>

                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                إذا لم تكن أنت من أنشأ هذا الحساب، يمكنك بأمان تجاهل هذا البريد الإلكتروني.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 25px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 8px; color: #64748b; font-size: 12px; font-weight: 600;">
                                تم إرسال هذه الرسالة تلقائياً من نظام التوثيق في مزاد سوريا
                            </p>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px;">
                                الدعم الفني: <a href="mailto:info@ahmadinsights.online" style="color: #e11d48; text-decoration: none;">info@ahmadinsights.online</a> | جميع الحقوق محفوظة &copy; {$year}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * HTML template for password reset (code + link).
     */
    protected function buildPasswordResetEmailHtml(User $user, string $code, string $resetUrl): string
    {
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - مزاد سوريا</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; direction: rtl; text-align: right; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #881337 0%, #4c0519 100%); padding: 35px 30px; text-align: center;">
                            <div style="display: inline-block; background: rgba(255,255,255,0.15); padding: 12px 24px; border-radius: 50px; margin-bottom: 12px;">
                                <span style="font-size: 24px; font-weight: 900; color: #fbbf24; letter-spacing: 1px;">مزاد سوريا</span>
                            </div>
                            <p style="margin: 0; color: #fecdd3; font-size: 13px; font-weight: 500;">طلب إعادة تعيين كلمة المرور</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 35px;">
                            <h2 style="margin: 0 0 15px; color: #0f172a; font-size: 20px; font-weight: 800;">مرحباً {$user->name}،</h2>
                            <p style="margin: 0 0 25px; color: #475569; font-size: 14px; line-height: 1.8;">
                                لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك المرتبط بالبريد (<strong>{$user->email}</strong>). يمكنك إتمام الاستعادة باستخدام أي من الطريقتين التاليتين:
                            </p>

                            <!-- Method 1: Direct Button -->
                            <div style="text-align: center; margin: 30px 0 25px;">
                                <a href="{$resetUrl}" style="display: inline-block; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 12px; font-weight: 800; font-size: 15px; box-shadow: 0 4px 15px rgba(225, 29, 72, 0.35);">
                                    إعادة تعيين كلمة المرور مباشرة
                                </a>
                                <span style="display: block; font-size: 11px; color: #94a3b8; margin-top: 8px;">أو استخدم الرابط: {$resetUrl}</span>
                            </div>

                            <div style="text-align: center; margin: 20px 0; position: relative;">
                                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                                <span style="position: relative; top: -11px; background: #ffffff; padding: 0 15px; font-size: 12px; color: #94a3b8; font-weight: 700;">أو باستخدام الرمز السداسي</span>
                            </div>

                            <!-- Method 2: 6-digit Reset Code -->
                            <div style="background: #fff1f2; border: 2px dashed #e11d48; border-radius: 16px; padding: 20px; text-align: center; margin: 20px 0;">
                                <span style="display: block; font-size: 12px; font-weight: 700; color: #9f1239; margin-bottom: 6px;">رمز استعادة كلمة المرور السداسي</span>
                                <span style="display: inline-block; font-size: 34px; font-weight: 900; letter-spacing: 10px; color: #881337; font-family: 'Courier New', monospace; direction: ltr;">{$code}</span>
                            </div>

                            <p style="margin: 20px 0 0; color: #64748b; font-size: 13px; line-height: 1.7;">
                                <strong style="color: #0f172a;">ملاحظة:</strong> صلاحية هذا الرابط والرمز تنتهي خلال 15 دقيقة لأسباب أمنية. إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة ولن يتم إجراء أي تغيير على حسابك.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 25px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 8px; color: #64748b; font-size: 12px; font-weight: 600;">
                                تم إرسال هذه الرسالة تلقائياً من نظام الأمان في مزاد سوريا
                            </p>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px;">
                                الدعم الفني: <a href="mailto:info@ahmadinsights.online" style="color: #e11d48; text-decoration: none;">info@ahmadinsights.online</a> | جميع الحقوق محفوظة &copy; {$year}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
