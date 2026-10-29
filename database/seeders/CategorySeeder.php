<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_ar' => 'سيارات ومركبات',
                'name_en' => 'Cars & Vehicles',
                'slug' => 'cars-vehicles',
                'icon' => 'car',
                'description' => 'سيارات جديدة ومستعملة للبيع، دراجات، شاحنات، وقطع غيار أصلية',
                'subcategories' => [
                    ['name_ar' => 'سيارات للبيع', 'name_en' => 'Cars for Sale', 'slug' => 'cars-for-sale', 'icon' => 'car'],
                    ['name_ar' => 'قطع غيار واكسسوارات', 'name_en' => 'Auto Parts & Accessories', 'slug' => 'auto-parts', 'icon' => 'settings'],
                    ['name_ar' => 'دراجات نارية وسكوتر', 'name_en' => 'Motorcycles & Scooters', 'slug' => 'motorcycles', 'icon' => 'bike'],
                    ['name_ar' => 'شاحنات ومعدات ثقيلة', 'name_en' => 'Trucks & Heavy Equipment', 'slug' => 'trucks-equipment', 'icon' => 'truck'],
                    ['name_ar' => 'لوحات وأرقام مميزة', 'name_en' => 'Special Plates', 'slug' => 'special-plates', 'icon' => 'hash'],
                    ['name_ar' => 'قوارب ويخوت', 'name_en' => 'Boats & Marine', 'slug' => 'boats-marine', 'icon' => 'anchor'],
                ],
            ],
            [
                'name_ar' => 'عقارات وأملاك',
                'name_en' => 'Real Estate',
                'slug' => 'real-estate',
                'icon' => 'home',
                'description' => 'شقق للبيع والإيجار، منازل وفلل، أراضي ومزارع ومحلات تجارية في كافة المحافظات',
                'subcategories' => [
                    ['name_ar' => 'شقق للبيع', 'name_en' => 'Apartments for Sale', 'slug' => 'apartments-sale', 'icon' => 'home'],
                    ['name_ar' => 'شقق للإيجار', 'name_en' => 'Apartments for Rent', 'slug' => 'apartments-rent', 'icon' => 'key'],
                    ['name_ar' => 'بيوت ومنازل وفلل', 'name_en' => 'Houses & Villas', 'slug' => 'houses-villas', 'icon' => 'building'],
                    ['name_ar' => 'أراضي ومزارع', 'name_en' => 'Lands & Farms', 'slug' => 'lands-farms', 'icon' => 'map'],
                    ['name_ar' => 'محلات ومكاتب تجارية', 'name_en' => 'Commercial & Offices', 'slug' => 'commercial-offices', 'icon' => 'briefcase'],
                    ['name_ar' => 'مستودعات وهناجر', 'name_en' => 'Warehouses', 'slug' => 'warehouses', 'icon' => 'box'],
                ],
            ],
            [
                'name_ar' => 'إلكترونيات وأجهزة',
                'name_en' => 'Electronics',
                'slug' => 'electronics',
                'icon' => 'smartphone',
                'description' => 'موبايلات، لابتوبات، شاشات، كونسول ألعاب، ومعدات منزلية ذكية',
                'subcategories' => [
                    ['name_ar' => 'هواتف ذكية وأجهزة لوحية', 'name_en' => 'Phones & Tablets', 'slug' => 'phones-tablets', 'icon' => 'smartphone'],
                    ['name_ar' => 'لابتوبات وكمبيوترات', 'name_en' => 'Laptops & Computers', 'slug' => 'laptops-computers', 'icon' => 'laptop'],
                    ['name_ar' => 'شاشات وتلفزيونات', 'name_en' => 'TVs & Screens', 'slug' => 'tvs-screens', 'icon' => 'tv'],
                    ['name_ar' => 'ألعاب فيديو وبلايستيشن', 'name_en' => 'Video Games & Consoles', 'slug' => 'video-games', 'icon' => 'gamepad-2'],
                    ['name_ar' => 'كاميرات وتصوير احترافي', 'name_en' => 'Cameras & Photography', 'slug' => 'cameras', 'icon' => 'camera'],
                    ['name_ar' => 'أجهزة كهربائية منزلية', 'name_en' => 'Home Appliances', 'slug' => 'home-appliances', 'icon' => 'zap'],
                    ['name_ar' => 'طاقة شمسية وبطاريات', 'name_en' => 'Solar Power & Batteries', 'slug' => 'solar-energy', 'icon' => 'sun'],
                ],
            ],
            [
                'name_ar' => 'أثاث ومفروشات وديكور',
                'name_en' => 'Furniture & Decor',
                'slug' => 'furniture-decor',
                'icon' => 'armchair',
                'description' => 'غرف نوم، كنبايات، طاولات، سجاد، إنارة وتحف منزلية فاخرة',
                'subcategories' => [
                    ['name_ar' => 'غرف نوم وأسرة', 'name_en' => 'Bedrooms & Beds', 'slug' => 'bedrooms', 'icon' => 'bed'],
                    ['name_ar' => 'كنب وأطقم جلوس وصالونات', 'name_en' => 'Living Room & Sofas', 'slug' => 'living-rooms', 'icon' => 'armchair'],
                    ['name_ar' => 'طاولات وسفرة وكراسي', 'name_en' => 'Dining & Tables', 'slug' => 'dining-tables', 'icon' => 'grid'],
                    ['name_ar' => 'مطابخ وخزائن', 'name_en' => 'Kitchens & Cabinets', 'slug' => 'kitchens', 'icon' => 'package'],
                    ['name_ar' => 'سجاد وموكيت وستائر', 'name_en' => 'Carpets & Curtains', 'slug' => 'carpets-curtains', 'icon' => 'layers'],
                    ['name_ar' => 'تحف وديكورات وإنارة', 'name_en' => 'Antiques & Lighting', 'slug' => 'antiques-lighting', 'icon' => 'sparkles'],
                ],
            ],
            [
                'name_ar' => 'أزياء وموضة وجمال',
                'name_en' => 'Fashion & Beauty',
                'slug' => 'fashion-beauty',
                'icon' => 'shirt',
                'description' => 'ألبسة رجالية ونسائية ولادية، ساعات، مجوهرات وعطور',
                'subcategories' => [
                    ['name_ar' => 'ساعات ومجوهرات واكسسوارات', 'name_en' => 'Watches & Jewelry', 'slug' => 'watches-jewelry', 'icon' => 'watch'],
                    ['name_ar' => 'عطور ومستحضرات تجميل', 'name_en' => 'Perfumes & Cosmetics', 'slug' => 'perfumes', 'icon' => 'heart'],
                    ['name_ar' => 'ملابس وأزياء رجالية', 'name_en' => 'Men Fashion', 'slug' => 'men-fashion', 'icon' => 'shirt'],
                    ['name_ar' => 'ملابس وفساتين نسائية', 'name_en' => 'Women Fashion', 'slug' => 'women-fashion', 'icon' => 'shopping-bag'],
                    ['name_ar' => 'أحذية وحقائب', 'name_en' => 'Shoes & Bags', 'slug' => 'shoes-bags', 'icon' => 'briefcase'],
                    ['name_ar' => 'مستلزمات أطفال ورضّع', 'name_en' => 'Baby & Kids', 'slug' => 'baby-kids', 'icon' => 'smile'],
                ],
            ],
            [
                'name_ar' => 'خدمات وأعمال ومهن',
                'name_en' => 'Services & Business',
                'slug' => 'services-jobs',
                'icon' => 'briefcase',
                'description' => 'الناس تعرض فيها خدماتها: صيانة، نقل عفش، شحن، برمجة، تشطيبات واستشارات',
                'subcategories' => [
                    ['name_ar' => 'خدمات نقل وشحن وتوصيل', 'name_en' => 'Moving & Shipping', 'slug' => 'moving-shipping', 'icon' => 'truck'],
                    ['name_ar' => 'صيانة منازل وكهرباء وتصليح', 'name_en' => 'Maintenance & Repair', 'slug' => 'home-maintenance', 'icon' => 'tool'],
                    ['name_ar' => 'برمجة وتصميم وتسويق رقمي', 'name_en' => 'IT, Design & Marketing', 'slug' => 'it-design', 'icon' => 'code'],
                    ['name_ar' => 'مقاولات وتشطيبات وبناء وديكور', 'name_en' => 'Contracting & Decoration', 'slug' => 'contracting', 'icon' => 'layers'],
                    ['name_ar' => 'دروس خصوصية وتدريب ولغات', 'name_en' => 'Tutoring & Training', 'slug' => 'tutoring', 'icon' => 'book-open'],
                    ['name_ar' => 'استشارات قانونية ومحاسبة وأعمال', 'name_en' => 'Consulting & Accounting', 'slug' => 'consulting', 'icon' => 'file-text'],
                ],
            ],
            [
                'name_ar' => 'وظائف وفرص عمل',
                'name_en' => 'Jobs & Careers',
                'slug' => 'jobs-employment',
                'icon' => 'user-check',
                'description' => 'قسم العمل: شركات تنشر وظائف ومطلوب موظفين + أشخاص يبحثون عن عمل وسير ذاتية',
                'subcategories' => [
                    ['name_ar' => 'مطلوب موظفين (فرص عمل شاغرة)', 'name_en' => 'Hiring - Job Vacancies', 'slug' => 'job-vacancies', 'icon' => 'briefcase'],
                    ['name_ar' => 'باحثون عن عمل (سير ذاتية وكفاءات)', 'name_en' => 'Job Seekers & Resumes', 'slug' => 'job-seekers', 'icon' => 'user'],
                    ['name_ar' => 'مطلوب عمال ومهنيون وحرفيون', 'name_en' => 'Craftsmen & Workers Wanted', 'slug' => 'workers-wanted', 'icon' => 'tool'],
                    ['name_ar' => 'وظائف عن بعد وعمل حر', 'name_en' => 'Remote & Freelance Jobs', 'slug' => 'remote-jobs', 'icon' => 'globe'],
                ],
            ],
            [
                'name_ar' => 'حيوانات وطيور ومواشي',
                'name_en' => 'Animals & Pets',
                'slug' => 'animals-pets',
                'icon' => 'feather',
                'description' => 'طيور زينة، حيوانات أليفة، خيول، أغنام ومستلزمات العناية بها',
                'subcategories' => [
                    ['name_ar' => 'طيور وحمام وأسماك', 'name_en' => 'Birds & Fish', 'slug' => 'birds-fish', 'icon' => 'feather'],
                    ['name_ar' => 'قطط وكلاب أليفة', 'name_en' => 'Cats & Dogs', 'slug' => 'cats-dogs', 'icon' => 'heart'],
                    ['name_ar' => 'أغنام وأبقار وخيول', 'name_en' => 'Livestock & Horses', 'slug' => 'livestock', 'icon' => 'compass'],
                    ['name_ar' => 'أعلاف وأقفاص ومستلزمات', 'name_en' => 'Pet Supplies', 'slug' => 'pet-supplies', 'icon' => 'package'],
                ],
            ],
            [
                'name_ar' => 'معدات صناعية وبناء',
                'name_en' => 'Industrial & Construction',
                'slug' => 'industrial-construction',
                'icon' => 'hammer',
                'description' => 'معدات ورشات، مولدات كهربائية، أدوات بناء وصناعة ثقيلة',
                'subcategories' => [
                    ['name_ar' => 'مولدات كهربائية ومحركات', 'name_en' => 'Generators & Motors', 'slug' => 'generators', 'icon' => 'zap'],
                    ['name_ar' => 'أدوات ومعدات ورشات', 'name_en' => 'Workshop Tools', 'slug' => 'workshop-tools', 'icon' => 'hammer'],
                    ['name_ar' => 'مواد بناء وإكساء', 'name_en' => 'Building Materials', 'slug' => 'building-materials', 'icon' => 'layers'],
                ],
            ],
        ];

        $sortCat = 1;
        foreach ($categories as $cat) {
            $category = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name_ar' => $cat['name_ar'],
                    'name_en' => $cat['name_en'],
                    'icon' => $cat['icon'],
                    'description' => $cat['description'],
                    'sort_order' => $sortCat++,
                    'is_active' => true,
                ]
            );

            $sortSub = 1;
            foreach ($cat['subcategories'] as $sub) {
                Subcategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'slug' => $sub['slug'],
                    ],
                    [
                        'name_ar' => $sub['name_ar'],
                        'name_en' => $sub['name_en'],
                        'icon' => $sub['icon'],
                        'sort_order' => $sortSub++,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

