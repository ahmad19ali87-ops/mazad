<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $governoratesData = [
            [
                'name_ar' => 'دمشق',
                'name_en' => 'Damascus',
                'regions' => [
                    ['name_ar' => 'المزة', 'name_en' => 'Al-Mezzeh', 'districts' => ['الفيلات الغربية', 'الفيلات الشرقية', 'الشيخ سعد', 'أوتوستراد المزة', 'مزة 86']],
                    ['name_ar' => 'كفرسوسة', 'name_en' => 'Kafr Sousa', 'districts' => ['كفرسوسة البلد', 'تنظيم كفرسوسة', 'اللواحات']],
                    ['name_ar' => 'الصالحية', 'name_en' => 'Al-Salihiyah', 'districts' => ['الشهداء', 'الميسات', 'الشاغور الجواني']],
                    ['name_ar' => 'الميدان', 'name_en' => 'Al-Midan', 'districts' => ['الجزماتية', 'القاعة', 'الميدان الفوقاني']],
                    ['name_ar' => 'الشعلان', 'name_en' => 'Al-Shaalan', 'districts' => ['شارع الحمرا', 'شارع الطلياني', 'نجم الدين']],
                    ['name_ar' => 'دمر', 'name_en' => 'Dummar', 'districts' => ['مشروع دمر', 'دمر الشرقية', 'الجزيرة 16', 'الجزيرة 24']],
                    ['name_ar' => 'المالكي وأبو رمانة', 'name_en' => 'Al-Malki & Abu Rummaneh', 'districts' => ['شارع أبو رمانة', 'حديقة الجاحظ', 'شارع شكري القوتلي']],
                    ['name_ar' => 'القصاع وباب توما', 'name_en' => 'Al-Qassa & Bab Touma', 'districts' => ['ساحة التحرير', 'برج الروس', 'حارة الزيتون']],
                ],
            ],
            [
                'name_ar' => 'ريف دمشق',
                'name_en' => 'Rif Dimashq',
                'regions' => [
                    ['name_ar' => 'جرمانا', 'name_en' => 'Jaramana', 'districts' => ['حي السيوف', 'حي الحمصي', 'ساحة السيوف', 'دف الصخر']],
                    ['name_ar' => 'قدسيا', 'name_en' => 'Qudsaya', 'districts' => ['ضاحية قدسيا', 'قدسيا البلد', 'السكن الشبابي']],
                    ['name_ar' => 'صحنايا وأشرفيتها', 'name_en' => 'Sahnaya', 'districts' => ['صحنايا البلد', 'أشرفية صحنايا']],
                    ['name_ar' => 'السيدة زينب', 'name_en' => 'Sayyidah Zaynab', 'districts' => ['شارع الفنادق', 'حجيرة', 'الذيابية']],
                    ['name_ar' => 'الزبداني ومضايا', 'name_en' => 'Al-Zabadani', 'districts' => ['سهل الزبداني', 'مضايا البلد', 'بلودان']],
                    ['name_ar' => 'التل وصيدنايا', 'name_en' => 'Al-Tall & Saidnaya', 'districts' => ['التل الفوقاني', 'معرة صيدنايا', 'صيدنايا']],
                    ['name_ar' => 'النبك ويبرود', 'name_en' => 'Al-Nabk & Yabroud', 'districts' => ['شارع المشفى', 'يبرود القديمة']],
                ],
            ],
            [
                'name_ar' => 'حلب',
                'name_en' => 'Aleppo',
                'regions' => [
                    ['name_ar' => 'الفرقان', 'name_en' => 'Al-Furqan', 'districts' => ['جامعة حلب', 'دوار العمارة', 'السكن الجامعي']],
                    ['name_ar' => 'الشهباء', 'name_en' => 'Al-Shahbaa', 'districts' => ['الشهباء الجديدة', 'الشهباء القديمة']],
                    ['name_ar' => 'حلب الجديدة', 'name_en' => 'New Aleppo', 'districts' => ['القطاع الشمالي', 'القطاع الجنوبي']],
                    ['name_ar' => 'السليمانية والعزيزية', 'name_en' => 'Sulaymaniyah & Aziziyeh', 'districts' => ['شارع فيصل', 'حديقة السبيل', 'المحطة']],
                    ['name_ar' => 'الميدان والجميلية', 'name_en' => 'Midan & Jamiliyeh', 'districts' => ['ساحة سعد الله الجابري', 'بستان كل آب']],
                ],
            ],
            [
                'name_ar' => 'حمص',
                'name_en' => 'Homs',
                'regions' => [
                    ['name_ar' => 'الإنشاءات', 'name_en' => 'Al-Inshaat', 'districts' => ['شارع البرازيل', 'دوار السيد الرئيس']],
                    ['name_ar' => 'الوعر', 'name_en' => 'Al-Waer', 'districts' => ['الوعر القديم', 'الوعر الجديد', 'أبراج الجزيرة']],
                    ['name_ar' => 'الحمرا والغوطة', 'name_en' => 'Al-Hamra & Ghouta', 'districts' => ['شارع الحمرا', 'شارع الخراب']],
                    ['name_ar' => 'عكرمة', 'name_en' => 'Akrama', 'districts' => ['عكرمة الجديدة', 'عكرمة القديمة', 'شارع العشاق']],
                ],
            ],
            [
                'name_ar' => 'اللاذقية',
                'name_en' => 'Latakia',
                'regions' => [
                    ['name_ar' => 'مشروع دمشق والأوقاف', 'name_en' => 'Mashrou Dimashq', 'districts' => ['دوار الزراعة', 'الكورنيش الجنوبي']],
                    ['name_ar' => 'الزراعة ومسبح الشعب', 'name_en' => 'Zeraa & Masbah', 'districts' => ['أوتوستراد الزراعة', 'ساحة الحمام']],
                    ['name_ar' => 'الشاطئ الأزرق', 'name_en' => 'Blue Beach', 'districts' => ['منتجع الشاطئ', 'برج إسلام', 'ميريديان']],
                    ['name_ar' => 'الصليبة والشيخ ضاهر', 'name_en' => 'Slaibeh', 'districts' => ['شارع هنانو', 'حي القلعة']],
                ],
            ],
            [
                'name_ar' => 'طرطوس',
                'name_en' => 'Tartus',
                'regions' => [
                    ['name_ar' => 'الكورنيش والمرفأ', 'name_en' => 'Corniche & Port', 'districts' => ['الكورنيش البحري', 'شارع الثورة']],
                    ['name_ar' => 'الإنشاءات والرمل', 'name_en' => 'Inshaat & Raml', 'districts' => ['حي الغدير', 'حي المطار']],
                    ['name_ar' => 'صافيتا والدريكيش', 'name_en' => 'Safita & Dreikish', 'districts' => ['برج صافيتا', 'نبع الدريكيش']],
                    ['name_ar' => 'بانياس', 'name_en' => 'Baniyas', 'districts' => ['الكورنيش الشمالي', 'البلد']],
                ],
            ],
            [
                'name_ar' => 'حماة',
                'name_en' => 'Hama',
                'regions' => [
                    ['name_ar' => 'الشريعة والقصور', 'name_en' => 'Al-Sharia & Qosoor', 'districts' => ['دوار الأربعين', 'جنوب الملعب']],
                    ['name_ar' => 'الحاضر والدباغة', 'name_en' => 'Hader & Dabagha', 'districts' => ['ساحة العاصي', 'النواعير']],
                ],
            ],
            [
                'name_ar' => 'السويداء',
                'name_en' => 'As-Suwayda',
                'regions' => [
                    ['name_ar' => 'مدينة السويداء', 'name_en' => 'Suwayda City', 'districts' => ['ساحة تشرين', 'المزرعة', 'المقوس']],
                    ['name_ar' => 'شهبا وصلخد', 'name_en' => 'Shahba & Salkhad', 'districts' => ['قلعة صلخد', 'متحف شهبا']],
                ],
            ],
            [
                'name_ar' => 'درعا',
                'name_en' => 'Daraa',
                'regions' => [
                    ['name_ar' => 'مدينة درعا', 'name_en' => 'Daraa City', 'districts' => ['درعا المحطة', 'درعا البلد', 'حي السبيل']],
                    ['name_ar' => 'الصنمين وإزرع', 'name_en' => 'Sanamayn & Izra', 'districts' => ['الصنمين الغربية', 'إزرع المحطة']],
                ],
            ],
            [
                'name_ar' => 'إدلب',
                'name_en' => 'Idlib',
                'regions' => [
                    ['name_ar' => 'مدينة إدلب', 'name_en' => 'Idlib City', 'districts' => ['الضبيط', 'القصور', 'شارع الجلاء']],
                    ['name_ar' => 'سرمدا والدانا', 'name_en' => 'Sarmada & Dana', 'districts' => ['السوق التجاري', 'باب الهوى']],
                ],
            ],
            [
                'name_ar' => 'القنيطرة',
                'name_en' => 'Quneitra',
                'regions' => [
                    ['name_ar' => 'مدينة البعث وخان أرنبة', 'name_en' => 'Al-Baath City', 'districts' => ['حي الثورة', 'حي السلام']],
                ],
            ],
            [
                'name_ar' => 'دير الزور',
                'name_en' => 'Deir ez-Zor',
                'regions' => [
                    ['name_ar' => 'مدينة دير الزور', 'name_en' => 'Deir ez-Zor City', 'districts' => ['حي القصور', 'حي الجورة', 'شارع النهر']],
                    ['name_ar' => 'الميادين والبوكمال', 'name_en' => 'Mayadin & Bukamal', 'districts' => ['الشارع العام', 'السوق']],
                ],
            ],
            [
                'name_ar' => 'الرقة',
                'name_en' => 'Raqqa',
                'regions' => [
                    ['name_ar' => 'مدينة الرقة', 'name_en' => 'Raqqa City', 'districts' => ['شارع تل أبيض', 'شارع المنصور', 'الثكنة']],
                    ['name_ar' => 'الطبقة (الثورة)', 'name_en' => 'Al-Thawrah (Tabqa)', 'districts' => ['الحي الأول', 'الحي الثاني', 'الحي الثالث']],
                ],
            ],
            [
                'name_ar' => 'الحسكة',
                'name_en' => 'Hasakah',
                'regions' => [
                    ['name_ar' => 'مدينة القامشلي', 'name_en' => 'Qamishli', 'districts' => ['حي السياحي', 'حي الوسطى', 'حي الغربي', 'الكورنيش']],
                    ['name_ar' => 'مدينة الحسكة', 'name_en' => 'Hasakah City', 'districts' => ['حي الصالحية', 'حي المفتي', 'حي غويران']],
                ],
            ],
        ];

        $sortGov = 1;
        foreach ($governoratesData as $govData) {
            $governorate = Governorate::updateOrCreate(
                ['name_ar' => $govData['name_ar']],
                [
                    'name_en' => $govData['name_en'],
                    'sort_order' => $sortGov++,
                    'is_active' => true,
                ]
            );

            $sortReg = 1;
            foreach ($govData['regions'] as $regData) {
                $region = Region::updateOrCreate(
                    [
                        'governorate_id' => $governorate->id,
                        'name_ar' => $regData['name_ar'],
                    ],
                    [
                        'name_en' => $regData['name_en'] ?? null,
                        'sort_order' => $sortReg++,
                    ]
                );

                if (!empty($regData['districts'])) {
                    $sortDist = 1;
                    foreach ($regData['districts'] as $distName) {
                        District::updateOrCreate(
                            [
                                'region_id' => $region->id,
                                'name_ar' => $distName,
                            ],
                            [
                                'name_en' => null,
                                'sort_order' => $sortDist++,
                            ]
                        );
                    }
                }
            }
        }
    }
}

