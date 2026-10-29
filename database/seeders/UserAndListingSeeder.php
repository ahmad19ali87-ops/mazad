<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\District;
use App\Models\Follow;
use App\Models\Governorate;
use App\Models\InAppNotification;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Region;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndListingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@mazad.sy'],
            [
                'name' => 'مدير النظام الرئيسي',
                'phone' => '+963988001122',
                'password' => Hash::make('AdminPass123!'),
                'role' => 'admin',
                'is_verified' => true,
                'governorate' => 'دمشق',
                'bio' => 'إدارة منصة مزاد سوريا الرسمية',
            ]
        );

        // 2. Sellers
        $seller1 = User::updateOrCreate(
            ['email' => 'alshahba.cars@mazad.sy'],
            [
                'name' => 'معرض الشهباء للسيارات الحديثة',
                'phone' => '+963933112233',
                'whatsapp' => '+963933112233',
                'password' => Hash::make('SellerPass123!'),
                'role' => 'seller',
                'is_verified' => true,
                'governorate' => 'دمشق',
                'bio' => 'بيع وشراء أحدث السيارات الحديثة والمستعملة بحالة الوكالة مع فحص فني شامل',
            ]
        );

        $seller2 = User::updateOrCreate(
            ['email' => 'sham.realestate@mazad.sy'],
            [
                'name' => 'مكتب الشام العقاري المعتمد',
                'phone' => '+963944556677',
                'whatsapp' => '+963944556677',
                'password' => Hash::make('SellerPass123!'),
                'role' => 'seller',
                'is_verified' => true,
                'governorate' => 'دمشق',
                'bio' => 'شقق وفلل ومحلات تجارية فاخرة في أرقى أحياء دمشق وريفها بطابو أخضر نظامي',
            ]
        );

        $seller3 = User::updateOrCreate(
            ['email' => 'tech.future@mazad.sy'],
            [
                'name' => 'المستقبل للإلكترونيات والطاقة',
                'phone' => '+963955778899',
                'whatsapp' => '+963955778899',
                'password' => Hash::make('SellerPass123!'),
                'role' => 'seller',
                'is_verified' => true,
                'governorate' => 'حلب',
                'bio' => 'أحدث الموبايلات، اللابتوبات، وأجهزة الطاقة الشمسية الأصلية مع كفالة حقيقية',
            ]
        );

        // 3. Regular Buyers / Users
        $buyer1 = User::updateOrCreate(
            ['email' => 'ahmad.buyer@mazad.sy'],
            [
                'name' => 'أحمد العلي',
                'phone' => '+963966223344',
                'whatsapp' => '+963966223344',
                'password' => Hash::make('UserPass123!'),
                'role' => 'user',
                'is_verified' => true,
                'governorate' => 'دمشق',
                'bio' => 'مهتم بالسيارات والأجهزة التقنية',
            ]
        );

        $buyer2 = User::updateOrCreate(
            ['email' => 'nour.user@mazad.sy'],
            [
                'name' => 'نور الهدى حمود',
                'phone' => '+963977334455',
                'whatsapp' => '+963977334455',
                'password' => Hash::make('UserPass123!'),
                'role' => 'user',
                'is_verified' => true,
                'governorate' => 'اللاذقية',
                'bio' => 'باحثة عن أثاث وديكورات منزلية',
            ]
        );

        // 4. Follow records (Buyer follows Sellers)
        Follow::firstOrCreate([
            'follower_id' => $buyer1->id,
            'seller_id' => $seller1->id,
        ]);
        Follow::firstOrCreate([
            'follower_id' => $buyer1->id,
            'seller_id' => $seller3->id,
        ]);
        Follow::firstOrCreate([
            'follower_id' => $buyer2->id,
            'seller_id' => $seller2->id,
        ]);

        // Find location helpers
        $damascus = Governorate::where('name_ar', 'دمشق')->first();
        $mezzeh = Region::where('name_ar', 'المزة')->first();
        $kafrSousa = Region::where('name_ar', 'كفرسوسة')->first();
        $dummar = Region::where('name_ar', 'دمر')->first();

        $villaGharbieh = District::where('name_ar', 'الفيلات الغربية')->first();
        $tanzim = District::where('name_ar', 'تنظيم كفرسوسة')->first();

        $aleppo = Governorate::where('name_ar', 'حلب')->first();
        $furqan = Region::where('name_ar', 'الفرقان')->first();

        // Find categories
        $carsCat = Category::where('slug', 'cars-vehicles')->first();
        $carsSaleSub = Subcategory::where('slug', 'cars-for-sale')->first();

        $realEstateCat = Category::where('slug', 'real-estate')->first();
        $aptSaleSub = Subcategory::where('slug', 'apartments-sale')->first();
        $aptRentSub = Subcategory::where('slug', 'apartments-rent')->first();

        $elecCat = Category::where('slug', 'electronics')->first();
        $phonesSub = Subcategory::where('slug', 'phones-tablets')->first();
        $solarSub = Subcategory::where('slug', 'solar-energy')->first();

        $furnitureCat = Category::where('slug', 'furniture-decor')->first();
        $sofaSub = Subcategory::where('slug', 'living-rooms')->first();

        // Sample Listings with Condition (جديد / مستعمل), Exact Syrian Addresses, and Detailed Specs
        $listingsData = [
            [
                'user_id' => $seller1->id,
                'category_id' => $carsCat->id,
                'subcategory_id' => $carsSaleSub ? $carsSaleSub->id : null,
                'title' => 'هيونداي توسان 2023 توب نظافة فل أوبشن بحالة الوكالة',
                'description' => "سيارة هيونداي توسان موديل 2023 خالية العلام تماماً من الصدام للصدام.\nالمواصفات: فتحة سقف بانوراما، كاميرا 360 درجة، رادار، مثبت سرعة ذكي، شاشة لمس 10 إنش تدعم Apple CarPlay و Android Auto، جنط كروم مقاس 19، فرش جلد بيج أصلي.\nالصيانة دورية بالوكالة مع سجل كامل بالفواتير. جاهزة عالفراغ الفوري في دمشق.",
                'price' => 38000.00,
                'currency' => 'USD',
                'is_negotiable' => true,
                'condition' => 'used', // مستعمل
                'governorate_id' => $damascus->id,
                'region_id' => $mezzeh ? $mezzeh->id : null,
                'district_id' => $villaGharbieh ? $villaGharbieh->id : null,
                'district_name' => 'الفيلات الغربية',
                'street_address' => 'شارع المدارس، جانب مدرسة دار السلام، مقابل حديقة الطلائع',
                'specs' => [
                    'سنة الصنع' => '2023',
                    'المسافة المقطوعة' => '24,000 كم',
                    'ناقل الحركة' => 'أوتوماتيك',
                    'نوع الوقود' => 'بنزين',
                    'اللون الخارجي' => 'أبيض لؤلؤي',
                    'اللون الداخلي' => 'بيج جلد',
                    'حالة الهيكل' => 'خالية من الحوادث والطلاء تماماً',
                ],
                'phone' => '+963933112233',
                'whatsapp' => '+963933112233',
                'status' => 'active',
                'is_featured' => true,
                'views_count' => 1420,
                'likes_count' => 58,
                'images' => [
                    'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1542282088-72c9c27ed0cd?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $seller1->id,
                'category_id' => $carsCat->id,
                'subcategory_id' => $carsSaleSub ? $carsSaleSub->id : null,
                'title' => 'كيا سيراتو 2025 زيرو عداد أصفار لم تسجل بعد',
                'description' => "كيا سيراتو موديل 2025 جديدة تماماً زيرو (عداد 0 كم)، لون رمادي فيراني جذاب.\nمحرك 1600cc موفر واقتصادي جداً، بصمة تشغيل ودخول ذكي، شاشة كبيرة، حساسات أمامية وخلفية، إضاءة LED نهارية كاملة.\nمستوردة حديثاً ومجمركة بالكامل مع جاهزية للتسجيل أول مالك من الوكالة.",
                'price' => 320000000.00,
                'currency' => 'SYP',
                'is_negotiable' => false,
                'condition' => 'new', // جديد
                'governorate_id' => $damascus->id,
                'region_id' => $kafrSousa ? $kafrSousa->id : null,
                'district_id' => $tanzim ? $tanzim->id : null,
                'district_name' => 'تنظيم كفرسوسة',
                'street_address' => 'أوتوستراد كفرسوسة، جانب وزارة الخارجية والمغتربين',
                'specs' => [
                    'سنة الصنع' => '2025',
                    'المسافة المقطوعة' => '0 كم (جديد زيرو)',
                    'ناقل الحركة' => 'أوتوماتيك 6 سرعات',
                    'نوع الوقود' => 'بنزين',
                    'اللون الخارجي' => 'رمادي فيراني ميتاليك',
                    'الضمان' => 'كفالة الوكيل 3 سنوات أو 100,000 كم',
                ],
                'phone' => '+963933112233',
                'whatsapp' => '+963933112233',
                'status' => 'active',
                'is_featured' => true,
                'views_count' => 980,
                'likes_count' => 42,
                'images' => [
                    'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $seller2->id,
                'category_id' => $realEstateCat->id,
                'subcategory_id' => $aptSaleSub ? $aptSaleSub->id : null,
                'title' => 'شقة فخمة طابق خامس إطلالة مفتوحة في تنظيم كفرسوسة الراقي',
                'description' => "للبيع شقة دوبلكس مكسوة سوبر ديلوكس في تنظيم كفرسوسة.\nالمساحة: 220 متر مربع، 3 غرف نوم (منهم ماستر)، صالون كبير واسع مع شرفة بإطلالة بانورامية لا تحجب على جبل قاسيون.\nمطبخ ألماني مجهز بالكامل، تدفئة مركزية مستقلة، تكييف في كافة الغرف، مصعد كهربائي مستمر على مدار الساعة، موقفين سيارات في القبو، طابو أخضر نظامي 2400 سهم بريء الذمة.",
                'price' => 195000.00,
                'currency' => 'USD',
                'is_negotiable' => true,
                'condition' => 'used',
                'governorate_id' => $damascus->id,
                'region_id' => $kafrSousa ? $kafrSousa->id : null,
                'district_id' => $tanzim ? $tanzim->id : null,
                'district_name' => 'تنظيم كفرسوسة',
                'street_address' => 'شارع الزهور، خلف مركز الشام سيتي سنتر التجاري، بناء النور رقم 14',
                'specs' => [
                    'المساحة الإجمالية' => '220 متر مربع',
                    'عدد الغرف' => '4 غرف وصالون كبير',
                    'عدد الحمامات' => '3 حمامات مع جاكوزي',
                    'الطابق' => 'الخامس (يوجد مصعد 24/7)',
                    'اتجاه الشقة' => 'قبلي غربي مشمس',
                    'الملكية' => 'طابو أخضر نظامي 2400 سهم',
                ],
                'phone' => '+963944556677',
                'whatsapp' => '+963944556677',
                'status' => 'active',
                'is_featured' => true,
                'views_count' => 2100,
                'likes_count' => 89,
                'images' => [
                    'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $seller3->id,
                'category_id' => $elecCat->id,
                'subcategory_id' => $phonesSub ? $phonesSub->id : null,
                'title' => 'آيفون 16 برو ماكس جديد بالكرتونة تيتانيوم طبيعي 512 جيجا',
                'description' => "Apple iPhone 16 Pro Max سعة 512GB لون Natural Titanium جديد كرتونة مختومة بتغليف المصنع (New Sealed).\nنسخة الشرق الأوسط شريحتين (Physical SIM + eSIM)، مجمرك رسمي على الشبكة السورية مع سيريال وكفالة سنتين.\nمتوفر التسليم يد بيد في مكتبنا أو شحن مضمون لكافة المحافظات.",
                'price' => 1850.00,
                'currency' => 'USD',
                'is_negotiable' => false,
                'condition' => 'new', // جديد
                'governorate_id' => $aleppo->id,
                'region_id' => $furqan ? $furqan->id : null,
                'district_name' => 'حي الفرقان الجامعي',
                'street_address' => 'شارع الجامعة الرئيسي، جانب بنك بيمو، مقابل كلية العلوم',
                'specs' => [
                    'السعة التخزينية' => '512 جيجابايت',
                    'اللون' => 'تيتانيوم طبيعي (Natural Titanium)',
                    'الرامات' => '8 جيجابايت',
                    'المعالج' => 'Apple A18 Pro Bionic',
                    'الحالة' => 'جديد مختوم 100%',
                    'الجمركة' => 'مجمرك رسمي على الشبكة',
                ],
                'phone' => '+963955778899',
                'whatsapp' => '+963955778899',
                'status' => 'active',
                'is_featured' => true,
                'views_count' => 1650,
                'likes_count' => 74,
                'images' => [
                    'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $seller3->id,
                'category_id' => $elecCat->id,
                'subcategory_id' => $solarSub ? $solarSub->id : null,
                'title' => 'منظومة طاقة شمسية متكاملة 5000 واط إنفرتر هجين وبطاريات ليثيوم',
                'description' => "عرض خاص لمنظومة الطاقة الشمسية المنزلية والصناعية 5.5KVA.\nتشمل: إنفرتر هايبرد 5500W أصلي يدعم الشحن من الشبكة والمولد، بطارية ليثيوم 48V 100Ah دورة تفريغ 6000 دورة، 6 ألواح شمسية 580W عالية الكفاءة Half-Cell مونو كريستالين.\nتشغل المكيفات، البراد، الإنارة والكمبيوترات على مدار 24 ساعة دون انقطاع. التركيب الفوري والضمان 5 سنوات.",
                'price' => 38500000.00,
                'currency' => 'SYP',
                'is_negotiable' => true,
                'condition' => 'new', // جديد
                'governorate_id' => $damascus->id,
                'region_id' => $dummar ? $dummar->id : null,
                'district_name' => 'مشروع دمر',
                'street_address' => 'الجزيرة 16، مدخل السوق التجاري، بناء الصنوبر',
                'specs' => [
                    'القدرة' => '5500 واط مستمر',
                    'نوع الإنفرتر' => 'هايبرد نقي Pure Sine Wave',
                    'نوع البطارية' => 'ليثيوم فوسفات الحديد LiFePO4',
                    'عدد الألواح' => '6 ألواح مونو 580W',
                    'الضمان' => '5 سنوات على الألواح و 3 سنوات على البطارية',
                ],
                'phone' => '+963955778899',
                'whatsapp' => '+963955778899',
                'status' => 'active',
                'is_featured' => false,
                'views_count' => 840,
                'likes_count' => 31,
                'images' => [
                    'https://images.unsplash.com/photo-1509391365360-2e959784a276?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1508873696983-2df5703bc20d?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $buyer2->id,
                'category_id' => $furnitureCat->id,
                'subcategory_id' => $sofaSub ? $sofaSub->id : null,
                'title' => 'طقم كنب صالون تركي كلاسيكي 9 مقاعد قماش مخملي نخب أول',
                'description' => "طقم كنب صالون فخم جداً يتسع لـ 9 أشخاص (كنبة ثلاثية + كنبة ثنائية + 4 فوتيهات مفردة) مع طربيزة وسط وطربيزتين زاوية رخام طبيعي.\nالخشب زان روماني محفور يدوي، القماش مخمل تركي عالي الجودة سهل التنظيف ولون كحلي ملكي مميز.\nاستعمال خفيف ونظيف جداً بداعي السفر بدون أي عيوب أو بقع.",
                'price' => 14500000.00,
                'currency' => 'SYP',
                'is_negotiable' => true,
                'condition' => 'used', // مستعمل
                'governorate_id' => $damascus->id,
                'region_id' => $mezzeh ? $mezzeh->id : null,
                'district_name' => 'مزة شيخ سعد',
                'street_address' => 'شارع الشهداء، دخلة جامع الهدى، الطابق الثاني',
                'specs' => [
                    'عدد المقاعد' => '9 مقاعد كاملة',
                    'نوع الخشب' => 'خشب زان طبيعي معتق',
                    'نوع القماش' => 'مخمل تركي مقاوم للبقع',
                    'الملحقات' => 'طاولة وسط رخام + طاولتين جانبيتين',
                    'الحالة' => 'مستعمل بحالة ممتازة كالجديد',
                ],
                'phone' => '+963977334455',
                'whatsapp' => '+963977334455',
                'status' => 'active',
                'is_featured' => false,
                'views_count' => 520,
                'likes_count' => 19,
                'images' => [
                    'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
        ];

        foreach ($listingsData as $data) {
            $images = $data['images'];
            unset($data['images']);

            $listing = Listing::create($data);

            // Create images
            $sortOrder = 1;
            foreach ($images as $idx => $imgUrl) {
                ListingImage::create([
                    'listing_id' => $listing->id,
                    'image_path' => $imgUrl,
                    'is_primary' => $idx === 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Create comments on listings
            if ($listing->title === 'هيونداي توسان 2023 توب نظافة فل أوبشن بحالة الوكالة') {
                $c1 = Comment::create([
                    'listing_id' => $listing->id,
                    'user_id' => $buyer1->id,
                    'comment' => 'السلام عليكم، هل السعر نهائي أم يوجد تفاوض بسيط عند المعاينة الجادة؟ وأين يمكن فحص السيارة؟',
                    'is_approved' => true,
                ]);

                Comment::create([
                    'listing_id' => $listing->id,
                    'user_id' => $seller1->id,
                    'parent_id' => $c1->id,
                    'comment' => 'وعليكم السلام أخي الكريم، أهلاً بك. يوجد مراعاة بسيطة للشاري الصامل عند المعاينة. السيارة متواجدة بمعرضنا في المزة ويمكنك فحصها في أي مركز معتمد تختاره.',
                    'is_approved' => true,
                ]);
            }

            if ($listing->title === 'آيفون 16 برو ماكس جديد بالكرتونة تيتانيوم طبيعي 512 جيجا') {
                Comment::create([
                    'listing_id' => $listing->id,
                    'user_id' => $buyer1->id,
                    'comment' => 'ما شاء الله، هل متاح الدفع بالليرة السورية حسب سعر الصرف اليومي؟',
                    'is_approved' => true,
                ]);
            }
        }

        // Create sample in-app notifications for followers
        InAppNotification::create([
            'user_id' => $buyer1->id,
            'title' => 'سلعة جديدة من بائع تتابعه!',
            'message' => 'قام "معرض الشهباء للسيارات الحديثة" بنشر سلعة جديدة: كيا سيراتو 2025 زيرو عداد أصفار.',
            'type' => 'new_listing',
            'action_url' => '/listings',
            'data' => ['seller_id' => $seller1->id],
            'is_read' => false,
        ]);

        InAppNotification::create([
            'user_id' => $seller1->id,
            'title' => 'تعليق جديد على إعلانك',
            'message' => 'علق أحمد العلي على إعلانك: هيونداي توسان 2023.',
            'type' => 'new_comment',
            'action_url' => '/listings',
            'is_read' => false,
        ]);
    }
}

