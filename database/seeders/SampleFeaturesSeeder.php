<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Governorate;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Site & Mobile Appearance Settings
        $defaultSettings = [
            'site_name' => ['value' => 'مزاد سوريا', 'group' => 'general', 'label' => 'اسم المنصة'],
            'site_slogan' => ['value' => 'السوق السوري المفتوح لكافة السلع والوظائف والخدمات', 'group' => 'general', 'label' => 'شعار الموقع'],
            'contact_email' => ['value' => 'support@mazad.sy', 'group' => 'contact', 'label' => 'البريد الإلكتروني للدعم الفني'],
            'contact_phone' => ['value' => '+963 11 2345678', 'group' => 'contact', 'label' => 'هاتف الإدارة'],
            'contact_whatsapp' => ['value' => '+963 944 112233', 'group' => 'contact', 'label' => 'واتساب خدمة العملاء'],
            'theme_primary_color' => ['value' => '#8b1d30', 'group' => 'appearance', 'type' => 'color', 'label' => 'اللون الرئيسي للمنصة (عنابي مزاد)'],
            'theme_accent_color' => ['value' => '#d97706', 'group' => 'appearance', 'type' => 'color', 'label' => 'اللون الثانوي (ذهبي)'],
            'hero_title' => ['value' => 'سوقك المفتوح لكل السلع والوظائف والخدمات في سوريا', 'group' => 'hero', 'label' => 'عنوان الترويسة الرئيسية'],
            'hero_subtitle' => ['value' => 'تصفح آلاف الإعلانات المفحوصة، ميز الجديد من المستعمل، اعرض بضاعتك للبيع أو اطلب ما تحتاجه، وتابع بائعيك المفضلين', 'group' => 'hero', 'label' => 'الوصف الترويجي'],
            'mobile_app_name' => ['value' => 'مزاد سوريا', 'group' => 'mobile', 'label' => 'اسم تطبيق الجوال'],
            'mobile_google_login_enabled' => ['value' => '1', 'group' => 'mobile', 'type' => 'boolean', 'label' => 'تفعيل تسجيل الدخول بجوجل في التطبيق'],
            'mobile_wanted_feature_enabled' => ['value' => '1', 'group' => 'mobile', 'type' => 'boolean', 'label' => 'تفعيل خاصية "مطلوب" للشراء في التطبيق'],
            'mobile_jobs_feature_enabled' => ['value' => '1', 'group' => 'mobile', 'type' => 'boolean', 'label' => 'تفعيل قسم التوظيف والباحثين عن عمل في التطبيق'],
        ];

        foreach ($defaultSettings as $key => $data) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $data['value'],
                    'group' => $data['group'],
                    'type' => $data['type'] ?? 'text',
                    'label_ar' => $data['label'],
                ]
            );
        }

        // 2. Fetch Users & Categories
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $seller = User::where('role', 'seller')->first() ?? $admin;
        $normalUser = User::where('role', 'user')->first() ?? $admin;

        $jobsCategory = Category::where('slug', 'jobs-employment')->first();
        $servicesCategory = Category::where('slug', 'services-jobs')->first();
        $carsCategory = Category::where('slug', 'cars-vehicles')->first();

        $damascus = Governorate::where('name_ar', 'دمشق')->first();
        $damascusRegion = Region::where('governorate_id', $damascus?->id)->first();

        $aleppo = Governorate::where('name_ar', 'حلب')->first();
        $aleppoRegion = Region::where('governorate_id', $aleppo?->id)->first();

        // 3. Sample: "مطلوب للشراء" (Wanted Listing)
        if ($carsCategory && $damascus) {
            $wantedCar = Listing::updateOrCreate(
                ['title' => 'مطلوب للشراء: سيارة كيا سيراتو موديل 2019-2022 خالية العلام والدفع كاش فوري'],
                [
                    'user_id' => $normalUser->id,
                    'category_id' => $carsCategory->id,
                    'subcategory_id' => Subcategory::where('category_id', $carsCategory->id)->first()?->id,
                    'slug' => 'wanted-kia-cerato-damascus-' . Str::random(4),
                    'description' => 'مطلوب سيارة كيا سيراتو أوتوماتيك بحالة ممتازة وخالية تماماً من الحوادث والقص، موديل من 2019 حتى 2022، لون أسود أو فضي أو أبيض، والدفع جاهز كاش فور الاتفاق والفحص عند الميكانيكي.',
                    'price' => 220000000,
                    'currency' => 'SYP',
                    'is_negotiable' => true,
                    'condition' => 'used',
                    'listing_type' => 'wanted', // مطلوب
                    'is_sold' => false,
                    'governorate_id' => $damascus->id,
                    'region_id' => $damascusRegion?->id,
                    'street_address' => 'دمشق - المزة أوتوستراد جانب برج دمشق',
                    'phone' => '0933221100',
                    'whatsapp' => '0933221100',
                    'allow_comments' => true,
                    'status' => 'active',
                    'is_featured' => true,
                    'specs' => [
                        'الموديل المطلوب' => '2019 - 2022',
                        'ناقل الحركة' => 'أوتوماتيك',
                        'نوع الطلب' => 'مطلوب للشراء كاش فوري',
                        'طريقة الدفع' => 'تسليم باليد عند الفراغ',
                    ],
                ]
            );

            ListingImage::firstOrCreate(
                ['listing_id' => $wantedCar->id, 'image_path' => 'listings/cars/cerato.jpg'],
                ['is_primary' => true, 'sort_order' => 1]
            );
        }

        // 4. Sample: "فرصة عمل / مطلوب محاسب" (Job Vacancy)
        if ($jobsCategory && $damascus) {
            $vacSub = Subcategory::where('category_id', $jobsCategory->id)->where('slug', 'job-vacancies')->first()
                ?? Subcategory::where('category_id', $jobsCategory->id)->first();

            $jobVacancy = Listing::updateOrCreate(
                ['title' => 'مطلوب محاسب مالي خبرة سنتين لشركة تجارية واستيراد في دمشق'],
                [
                    'user_id' => $seller->id,
                    'category_id' => $jobsCategory->id,
                    'subcategory_id' => $vacSub?->id,
                    'slug' => 'job-vacancy-accountant-damascus-' . Str::random(4),
                    'description' => 'تعلن شركة تجارية كبرى في دمشق - الفحامة عن حاجتها لتوظيف محاسب مالي ذو خبرة لا تقل عن سنتين في إعداد القيود اليومية والميزانيات وإدارة المخازن باستخدام برامج المحاسبة (الأمين أو الرشيد). الراتب ممتاز مع حوافز وتأمينات.',
                    'price' => 3500000,
                    'currency' => 'SYP',
                    'is_negotiable' => true,
                    'condition' => 'new',
                    'listing_type' => 'sale',
                    'job_type' => 'vacancy', // فرصة عمل
                    'is_sold' => false,
                    'governorate_id' => $damascus->id,
                    'region_id' => $damascusRegion?->id,
                    'street_address' => 'دمشق - الفحامة، مقابل مدرسة دمشق الثانوية، برج النور',
                    'phone' => '0112233445',
                    'whatsapp' => '0944556677',
                    'allow_comments' => true,
                    'status' => 'active',
                    'is_featured' => true,
                    'specs' => [
                        'نوع الإعلان' => 'فرصة عمل شاغرة (مطلوب موظف)',
                        'المسمى الوظيفي' => 'محاسب مالي ومخازن',
                        'الخبرة المطلوبة' => 'سنتان فأكثر',
                        'البرامج المطلوبة' => 'الأمين للمحاسبة والمستودعات + Excel',
                        'طبيعة الدوام' => 'دوام كامل من 9 صباحاً حتى 5 مساءً',
                        'الراتب المقترح' => '3,500,000 ل.س + مكافآت دورية',
                    ],
                ]
            );

            ListingImage::firstOrCreate(
                ['listing_id' => $jobVacancy->id, 'image_path' => 'listings/electronics/iphone16.jpg'],
                ['is_primary' => true, 'sort_order' => 1]
            );
        }

        // 5. Sample: "باحث عن عمل / سيرة ذاتية" (Job Seeker)
        if ($jobsCategory && $aleppo) {
            $seekerSub = Subcategory::where('category_id', $jobsCategory->id)->where('slug', 'job-seekers')->first()
                ?? Subcategory::where('category_id', $jobsCategory->id)->first();

            $jobSeeker = Listing::updateOrCreate(
                ['title' => 'محاسب ومدير حسابات خبرة 6 سنوات يبحث عن عمل في حلب أو عن بعد'],
                [
                    'user_id' => $normalUser->id,
                    'category_id' => $jobsCategory->id,
                    'subcategory_id' => $seekerSub?->id,
                    'slug' => 'job-seeker-senior-accountant-aleppo-' . Str::random(4),
                    'description' => 'خريج كلية الاقتصاد جامعة حلب قسم محاسبة، خبرة 6 سنوات متواصلة في مسك الدفاتر المحاسبية، الجرد السنوي، التسويات البنكية، والتقارير الضريبية. أبحث عن فرصة عمل تناسب الخبرة دوام كامل أو جزئي أو عن بعد للشركات.',
                    'price' => 0,
                    'currency' => 'SYP',
                    'is_negotiable' => true,
                    'condition' => 'new',
                    'listing_type' => 'wanted',
                    'job_type' => 'seeker', // باحث عن عمل
                    'is_sold' => false,
                    'governorate_id' => $aleppo->id,
                    'region_id' => $aleppoRegion?->id,
                    'street_address' => 'حلب - حي الشهباء القديمة، جانب مشفى الكندي',
                    'phone' => '0988776655',
                    'whatsapp' => '0988776655',
                    'allow_comments' => true,
                    'status' => 'active',
                    'is_featured' => true,
                    'specs' => [
                        'نوع الإعلان' => 'باحث عن عمل (سيرة ذاتية وخبرات)',
                        'المؤهل العلمي' => 'إجازة في الاقتصاد - قسم المحاسبة',
                        'سنوات الخبرة' => '6 سنوات متفرغة',
                        'المهارات' => 'الأمين، البيان، Excel المتقدم، إعداد القوائم المالية',
                        'طبيعة العمل المفضلة' => 'دوام كامل أو استشارات محاسبية عن بعد',
                    ],
                ]
            );

            ListingImage::firstOrCreate(
                ['listing_id' => $jobSeeker->id, 'image_path' => 'listings/electronics/iphone16.jpg'],
                ['is_primary' => true, 'sort_order' => 1]
            );
        }

        // 6. Sample: "خدمات" (Services)
        if ($servicesCategory && $damascus) {
            $serviceListing = Listing::updateOrCreate(
                ['title' => 'ورشة متخصصة في تمديدات وصيانة الطاقة الشمسية والكهرباء الذكية والمكيفات في دمشق'],
                [
                    'user_id' => $seller->id,
                    'category_id' => $servicesCategory->id,
                    'subcategory_id' => Subcategory::where('category_id', $servicesCategory->id)->first()?->id,
                    'slug' => 'services-solar-electrical-damascus-' . Str::random(4),
                    'description' => 'نقدم كافة خدمات تركيب وصيانة منظومات الطاقة الشمسية، حساب الأحمال، تركيب الإنفرترات والبطاريات الليثيوم، فحص كفاءة الألواح، بالإضافة إلى تمديدات الكهرباء المنزلية والصناعية بضمان وأعلى درجات الإتقان.',
                    'price' => 150000,
                    'currency' => 'SYP',
                    'is_negotiable' => true,
                    'condition' => 'new',
                    'listing_type' => 'sale',
                    'is_sold' => false,
                    'governorate_id' => $damascus->id,
                    'region_id' => $damascusRegion?->id,
                    'street_address' => 'دمشق وريف دمشق - خدمة منزلية متنقلة وفورية',
                    'phone' => '0955443322',
                    'whatsapp' => '0955443322',
                    'allow_comments' => true,
                    'status' => 'active',
                    'is_featured' => true,
                    'specs' => [
                        'نوع الخدمة' => 'خدمة صيانة وتمديدات متكاملة',
                        'المنطقة المغطاة' => 'كافة أحياء دمشق وضواحيها',
                        'الضمان' => 'ضمان حقيقي على جودة العمل والمواد',
                        'سرعة الوصول' => 'خلال ساعة واحدة من الاتصال للخدمات الطارئة',
                    ],
                ]
            );

            ListingImage::firstOrCreate(
                ['listing_id' => $serviceListing->id, 'image_path' => 'listings/electronics/solar.jpg'],
                ['is_primary' => true, 'sort_order' => 1]
            );
        }
    }
}

