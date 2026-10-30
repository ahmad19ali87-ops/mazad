<?php

namespace App\Services;

use App\Models\SecurityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecureFileUploadService
{
    /**
     * Whitelist of strictly permitted extensions and their canonical MIME types.
     */
    protected const ALLOWED_EXTENSIONS = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /**
     * List of dangerous extensions, scripting keywords, and executable types.
     */
    protected const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phar',
        'sh', 'bash', 'zsh', 'exe', 'bat', 'cmd', 'com', 'vbs', 'vbe', 'wsf', 'wsh',
        'js', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'pyc', 'pyw', 'rb',
        'svg', 'svgz', 'html', 'htm', 'shtml', 'xhtml', 'htaccess', 'htpasswd',
        'ini', 'env', 'config', 'sql', 'dll', 'so', 'bin', 'jar'
    ];

    /**
     * Malicious content patterns (PHP code, shell signatures, webshells).
     */
    protected const MALICIOUS_PATTERNS = [
        '/<\?php/i',
        '/<\?=/i',
        '/<\?[^\x00-\x1f\s]/i',
        '/<script\b[^>]*>/i',
        '/__halt_compiler\s*\(\)/i',
        '/\b(eval|passthru|shell_exec|system|popen|proc_open)\s*\(/i',
        '/\b(base64_decode|gzinflate|gzuncompress|str_rot13)\s*\([\'"][A-Za-z0-9+\/=\s]{20,}/i',
        '/\$_(GET|POST|REQUEST|COOKIE|SERVER|FILES)\[/i',
        '/<\s*iframe\b/i',
        '/<\s*object\b/i',
        '/<\s*embed\b/i',
    ];

    protected SecurityMonitorService $securityMonitor;

    public function __construct(SecurityMonitorService $securityMonitor)
    {
        $this->securityMonitor = $securityMonitor;
    }

    /**
     * Thoroughly validate, scan, and inspect an uploaded file.
     * 
     * @return array{is_safe: bool, reason: ?string, safe_extension: ?string, safe_mime: ?string}
     */
    public function inspect(UploadedFile $file, ?string $ip = null): array
    {
        $ip = $ip ?? request()->ip();
        $originalName = $file->getClientOriginalName();
        $realPath = $file->getRealPath();

        // 1. Basic upload check
        if (!$file->isValid() || !file_exists($realPath)) {
            return [
                'is_safe' => false,
                'reason' => 'الملف المرفوع غير صالح أو تم قطعه أثناء الرفع.',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        // 2. Check for null byte injection (%00, \0)
        if (str_contains($originalName, "\0") || str_contains($originalName, '%00')) {
            $this->logThreat($ip, 'null_byte_injection', "محاولة حقن Null Byte في اسم الملف: {$originalName}");
            return [
                'is_safe' => false,
                'reason' => 'تم رفض الملف: تم رصد محاولة تلاعب بالاسم والامتداد (Null Byte).',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        // 3. Double Extension and Fake Extension Scan
        // Example: image.php.jpg or shell.phar.png or evil.php
        $nameParts = explode('.', strtolower($originalName));
        array_shift($nameParts); // remove base name

        foreach ($nameParts as $part) {
            $part = trim($part);
            if (in_array($part, self::DANGEROUS_EXTENSIONS, true)) {
                $this->logThreat($ip, 'double_extension_attack', "محاولة رفع ملف بامتداد مزدوج أو خطر: {$originalName}");
                return [
                    'is_safe' => false,
                    'reason' => 'تم رفض الملف: يحتوي على امتداد محظور أو امتداد مزدوج مشبوه.',
                    'safe_extension' => null,
                    'safe_mime' => null,
                ];
            }
        }

        // 4. File extension check from client
        $clientExt = strtolower($file->getClientOriginalExtension());
        if (!array_key_exists($clientExt, self::ALLOWED_EXTENSIONS)) {
            return [
                'is_safe' => false,
                'reason' => "الامتداد (.{$clientExt}) غير مسموح به. الامتدادات المسموحة فقط: JPG, JPEG, PNG, WEBP.",
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        // 5. Binary MIME inspection via Fileinfo (magic bytes)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $realPath);
        finfo_close($finfo);

        $allowedMimesForExt = self::ALLOWED_EXTENSIONS[$clientExt] ?? [];
        if (!in_array($realMime, $allowedMimesForExt, true)) {
            $this->logThreat($ip, 'mime_type_mismatch', "عدم تطابق نوع الملف الحقيقي ({$realMime}) مع الامتداد المزعوم ({$clientExt}) للملف {$originalName}");
            return [
                'is_safe' => false,
                'reason' => 'تم رفض الملف: نوع الملف الفعلي لا يتطابق مع امتداده (امتداد وهمي أو ملف مموه).',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        // 6. Binary Content Deep Inspection (Anti-Polyglot / WebShell Payload Detection)
        // Scan the file for PHP code, webshells, or executable scripts BEFORE parsing with GD/getimagesize
        $fileSize = $file->getSize();
        if ($fileSize > 10 * 1024 * 1024) { // 10MB limit
            return [
                'is_safe' => false,
                'reason' => 'حجم الصورة يتجاوز الحد الأقصى المسموح (10 ميجابايت).',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        $handle = @fopen($realPath, 'rb');
        if ($handle) {
            // Read first 512KB and last 512KB where payloads are commonly hidden
            $contentHead = fread($handle, 524288);
            
            $contentTail = '';
            if ($fileSize > 524288) {
                fseek($handle, max(0, $fileSize - 524288));
                $contentTail = fread($handle, 524288);
            }
            fclose($handle);

            $contentSample = $contentHead . "\n" . $contentTail;

            foreach (self::MALICIOUS_PATTERNS as $pattern) {
                if (preg_match($pattern, $contentSample)) {
                    $this->logThreat($ip, 'embedded_code_in_image', "رصد أكواد برمجية خبيثة مدمجة داخل الصورة: {$originalName}");
                    return [
                        'is_safe' => false,
                        'reason' => 'تم رفض الملف فورياً: تم رصد شيفرات أو نصوص برمجية مخفية داخل الصورة (Webshell / Polyglot Payload).',
                        'safe_extension' => null,
                        'safe_mime' => null,
                    ];
                }
            }
        }

        // 7. Deep image structure validation using getimagesize()
        $imageInfo = @getimagesize($realPath);
        if ($imageInfo === false) {
            $this->logThreat($ip, 'corrupted_image_header', "فشل قراءة بنية الصورة بواسطة getimagesize: {$originalName}");
            return [
                'is_safe' => false,
                'reason' => 'الملف ليس صورة صالحة أو يحتوي على رأس تالف ومشبوه.',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        [$width, $height, $imageType] = $imageInfo;
        if ($width <= 0 || $height <= 0) {
            return [
                'is_safe' => false,
                'reason' => 'أبعاد الصورة غير صالحة.',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (defined('IMAGETYPE_WEBP')) {
            $validTypes[] = IMAGETYPE_WEBP;
        }
        if (!in_array($imageType, $validTypes, true)) {
            return [
                'is_safe' => false,
                'reason' => 'صيغة الصورة الداخلية غير مدعومة.',
                'safe_extension' => null,
                'safe_mime' => null,
            ];
        }

        // Determine canonical extension based on real MIME
        $canonicalExt = match ($realMime) {
            'image/jpeg', 'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        return [
            'is_safe' => true,
            'reason' => null,
            'safe_extension' => $canonicalExt,
            'safe_mime' => $realMime,
        ];
    }

    /**
     * Store an inspected file safely, discarding the client's filename.
     * Optionally re-renders image using GD to neutralize any remaining metadata or steganography.
     */
    public function storeSafely(UploadedFile $file, string $destinationDir, ?string $safeExt = null): string
    {
        $safeExt = $safeExt ?? 'jpg';
        $randomName = Str::uuid()->toString() . '.' . $safeExt;
        $targetDirectory = storage_path('app/public/' . trim($destinationDir, '/'));

        if (!is_dir($targetDirectory)) {
            @mkdir($targetDirectory, 0775, true);
        }

        $destinationPath = $targetDirectory . '/' . $randomName;

        // Try to re-encode image with GD if available to sanitize EXIF / trailing bytes
        $reEncoded = $this->trySanitizeWithGd($file->getRealPath(), $destinationPath, $safeExt);

        if (!$reEncoded) {
            // Fallback: move file directly if GD is unavailable or fails
            copy($file->getRealPath(), $destinationPath);
        }

        @chmod($destinationPath, 0644);

        return trim($destinationDir, '/') . '/' . $randomName;
    }

    /**
     * Re-renders image using GD to strip EXIF and destroy any appended payloads.
     */
    protected function trySanitizeWithGd(string $sourcePath, string $destPath, string $ext): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }

        try {
            $img = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
                'png' => @imagecreatefrompng($sourcePath),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
                default => false,
            };

            if (!$img) {
                return false;
            }

            // Preserve alpha channel for PNG/WEBP
            if (in_array($ext, ['png', 'webp'])) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }

            $success = match ($ext) {
                'jpg', 'jpeg' => @imagejpeg($img, $destPath, 90),
                'png' => @imagepng($img, $destPath, 8),
                'webp' => function_exists('imagewebp') ? @imagewebp($img, $destPath, 90) : false,
                default => false,
            };

            imagedestroy($img);
            return (bool) $success;
        } catch (\Throwable $e) {
            Log::warning('GD image sanitization fallback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a security violation and ban IP if repeat offender.
     */
    protected function logThreat(string $ip, string $threatType, string $description): void
    {
        $this->securityMonitor->logSecurityEvent(
            ip: $ip,
            eventType: $threatType,
            severity: 'critical',
            url: request()->fullUrl(),
            method: request()->method(),
            userAgent: request()->userAgent(),
            payload: substr($description, 0, 1000),
            actionTaken: 'rejected'
        );

        // Immediate IP block on severe file upload attacks
        $this->securityMonitor->blockIp(
            ip: $ip,
            reason: "حظر أمني فوري: محاولة اختراق عبر رفع ملفات خطرة أو ملغمة ({$threatType})",
            hours: 72,
            isPermanent: false,
            blockedBy: 'جدار الحماية وفاحص الملفات (File Shield WAF)'
        );
    }
}
