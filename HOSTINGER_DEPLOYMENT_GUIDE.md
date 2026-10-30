# دليل رفع وتشغيل منصة "مزاد سوريا" على استضافة هوستنجر (Hostinger)

تم تجهيز وتأمين نظام "مزاد سوريا" ليعمل بكفاءة مطلقة وأعلى درجات الأمان على استضافات هوستنجر (سواء الاستضافة المشتركة Shared Web Hosting أو السحابية Cloud Hosting أو السيرفرات الخاصة VPS).

---

## 🛡️ أبرز ميزات الأمان والتوافق الذاتي المضافة:

1. **حماية الملفات والرفع (Anti-Webshell & File Shield)**:
   - فحص الامتدادات الحقيقية ومنع الامتدادات المزدوجة نهائياً (مثل `image.php.jpg` أو `.phtml` أو `.phar`).
   - فحص التوقيع الثنائي الحقيقي (Binary Magic Bytes) وتطابق MIME الحقيقي وليس المزعوم.
   - فحص عميق للشيفرات المخفية (Anti-Polyglot): كشف وحظر أي أكواد PHP أو أوامر استدعاء مثل `eval()` و `system()` و `base64_decode()` المدمجة داخل الصور أو في بيانات EXIF.
   - إعادة تسمية الملفات عشوائياً بتشفير UUID فريد لمنع استهداف مسارات الملفات.
   - ملف حماية `.htaccess` داخل مجلد التخزين `storage/app/public` يمنع خادم أباتشي نهائياً من تشغيل أو تنفيذ أي سكربت برمجي داخل مجلد الصور حتى وإن حاول أحد رفعه.

2. **حل مشكلة الرابط الرمزي (Symlink Fallback for Hostinger)**:
   - في بيئات هوستنجر المشتركة، غالباً ما تكون دالة `symlink()` معطلة في `php.ini`.
   - تم بناء كونترولر ذكي مستقل [`MediaController`](file:///C:/Users/ahmad/mazad/app/Http/Controllers/MediaController.php) ومسار بديل تلقائي يخدم الصور مباشرة من مجلد التخزين بسرعة فائقة مع كاش سنوي (`Cache-Control`) وترويسة `X-Content-Type-Options: nosniff`.
   - لن تختفي أي صور ولن يظهر خطأ 404 أو 403 نهائياً حتى بدون رابط رمزي.

3. **الإنشاء الذاتي للمجلدات المفقودة (Self-Healing Storage)**:
   - عند رفع الموقع عبر Git أو أرشيف ZIP، يتجاهل جيت المجلدات الفارغة مثل (`storage/framework/views`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/app/public/listings`).
   - تم تزويد مزود الخدمة [`AppServiceProvider`](file:///C:/Users/ahmad/mazad/app/Providers/AppServiceProvider.php) بخاصية الإنشاء التلقائي الذاتي وتعيين الصلاحيات الآمنة `0775` عند أول زيارة للموقع تلقائياً لمنع أخطاء توقف النظام.

4. **حماية ملفات الإعدادات والجذر (Root .htaccess Hardening)**:
   - تم إنشاء ملف `.htaccess` في جذر المشروع يوجه الزوار مباشرة إلى مجلد `public` ويمنع منعاً باتاً استعراض ملف `.env` أو مجلدات النظام (`app`, `database`, `storage`, `config`).

---

## 🚀 خطوات الرفع على هوستنجر خطوة بخطوة:

### الخطوة 1: رفع ملفات المشروع إلى هوستنجر
- **الطريقة الأولى (موصى بها - Git)**:
  - من لوحة التحكم **hPanel** > قسم **Git** > اربط المستودع:
    `https://github.com/ahmad19ali87-ops/mazad.git` على فرع `main`.
- **الطريقة الثانية (مدير الملفات File Manager)**:
  - ضغط ملفات المشروع ورفعها إلى مجلد `public_html`، واستخراجها هناك.

---

### الخطوة 2: إعداد قاعدة البيانات (MySQL) في hPanel
1. ادخل إلى **لوحة hPanel** > **قواعد البيانات (Databases)**.
2. أنشئ قاعدة بيانات MySQL جديدة، مثل: `u123456789_mazad`.
3. أنشئ اسم مستخدم وكلمة مرور قوية.

---

### الخطوة 3: ضبط ملف الإعدادات (`.env`)
1. انسخ ملف [`.env.example`](file:///C:/Users/ahmad/mazad/.env.example) وسمّه `.env`.
2. أدخل بيانات قاعدة البيانات التي أنشأتها في هوستنجر:
   ```env
   APP_NAME="مزاد سوريا"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u123456789_mazad
   DB_USERNAME=u123456789_user
   DB_PASSWORD=your_database_password
   ```

---

### الخطوة 4: تشغيل التهيئة السريعة وتوليد المفتاح
من خلال الطرفية (SSH Console أو Terminal) في هوستنجر، نفذ الأوامر التالية:

```bash
# توليد مفتاح التشفير
php artisan key:generate

# تشغيل التهيئة الذاتية لهوستنجر
php artisan hostinger:setup

# تشغيل المايجريشن والبيانات الأساسية للموقع (التصنيفات والمحافظات)
php artisan migrate --seed
```

---

## 🛠️ أوامر الصيانة المفيدة على هوستنجر:

```bash
# فحص حالة توافق بيئة هوستنجر ومجلدات الكاش والوسائط:
php artisan hostinger:setup

# تنظيف وإعادة بناء كاش المسارات والإعدادات:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
