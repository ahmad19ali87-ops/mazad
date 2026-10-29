import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart' as intl;

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MazadApp());
}

// ---------------------------------------------------------
// Theme & Constants
// ---------------------------------------------------------
class AppColors {
  static Color maroon = const Color(0xFF8B1D30); // Primary brand color
  static Color maroonDark = const Color(0xFF5E0F1C);
  static const Color amber = Color(0xFFF59E0B);
  static const Color emerald = Color(0xFF059669);
  static const Color blue = Color(0xFF2563EB);
  static const Color purple = Color(0xFF7C3AED);
  static const Color background = Color(0xFFF8FAFC);
  static const Color surface = Colors.white;
}

class AppConfig {
  static String siteName = 'مزاد سوريا';
  static String heroSubtitle = 'السوق السوري المفتوح لبيع وشراء كافة السلع والسيارات والعقارات والوظائف';

  static String get host {
    if (Platform.isAndroid) {
      return 'http://10.0.2.2:8000';
    }
    return 'http://127.0.0.1:8000';
  }

  static String get baseUrl => '$host/api';

  static String resolveUrl(String? path) {
    if (path == null || path.isEmpty) {
      return 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=400&q=80';
    }
    if (path.startsWith('http')) return path;
    return '$host/storage/$path';
  }
}

// ---------------------------------------------------------
// Root App with RTL & Arabic Localizations
// ---------------------------------------------------------
class MazadApp extends StatefulWidget {
  const MazadApp({super.key});

  @override
  State<MazadApp> createState() => _MazadAppState();
}

class _MazadAppState extends State<MazadApp> {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: AppConfig.siteName,
      debugShowCheckedModeBanner: false,
      locale: const Locale('ar', 'SY'),
      supportedLocales: const [
        Locale('ar', 'SY'),
        Locale('en', 'US'),
      ],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: ThemeData(
        fontFamily: 'Cairo',
        useMaterial3: true,
        scaffoldBackgroundColor: AppColors.background,
        colorScheme: ColorScheme.fromSeed(
          seedColor: AppColors.maroon,
          primary: AppColors.maroon,
          secondary: AppColors.amber,
        ),
        appBarTheme: AppBarTheme(
          backgroundColor: AppColors.maroon,
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: false,
        ),
      ),
      home: const MainHomeScreen(),
    );
  }
}

// ---------------------------------------------------------
// Session & Auth State Manager
// ---------------------------------------------------------
class AppState {
  static String? token;
  static Map<String, dynamic>? user;
  static final Set<int> followedSellers = {};

  static bool get isLoggedIn => token != null;

  static Map<String, String> get authHeaders => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  // Fast Google Sign-in API call
  static Future<bool> googleLogin({
    String? email,
    String? name,
    String? googleId,
    String? avatarUrl,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/auth/google'),
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': email ?? 'ahmad.suri.google@gmail.com',
          'name': name ?? 'أحمد السوري (Google)',
          'google_id': googleId ?? 'gid_google_user_9921',
          'avatar_url': avatarUrl ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80',
        }),
      );

      if (res.statusCode == 200 || res.statusCode == 201) {
        final data = jsonDecode(res.body);
        token = data['token'];
        user = data['user'];
        return true;
      }
    } catch (e) {
      debugPrint('Google login error: $e');
    }
    return false;
  }
}

// ---------------------------------------------------------
// Main Home Screen (Mzad Qatar Style Category Mini-Cards)
// ---------------------------------------------------------
class MainHomeScreen extends StatefulWidget {
  const MainHomeScreen({super.key});

  @override
  State<MainHomeScreen> createState() => _MainHomeScreenState();
}

class _MainHomeScreenState extends State<MainHomeScreen> {
  int _currentBottomNavIndex = 0;
  List<dynamic> _categories = [];
  List<dynamic> _listings = [];
  List<dynamic> _governorates = [];
  bool _isLoading = true;

  // Filters
  String _selectedCondition = ''; // '' = all, 'new', 'used'
  String _selectedListingType = ''; // '' = all, 'sale', 'wanted'
  String _selectedCategorySlug = '';
  int? _selectedGovernorateId;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchInitialData();
  }

  Future<void> _fetchInitialData() async {
    setState(() => _isLoading = true);
    try {
      // 1. Fetch settings & appearance
      try {
        final setRes = await http.get(Uri.parse('${AppConfig.baseUrl}/settings'));
        if (setRes.statusCode == 200) {
          final sData = jsonDecode(setRes.body)['data'] ?? {};
          if (sData['site_name'] != null) {
            AppConfig.siteName = sData['site_name'];
          }
          if (sData['hero_subtitle'] != null) {
            AppConfig.heroSubtitle = sData['hero_subtitle'];
          }
        }
      } catch (_) {}

      // 2. Fetch categories
      final catRes = await http.get(Uri.parse('${AppConfig.baseUrl}/categories'));
      if (catRes.statusCode == 200) {
        final data = jsonDecode(catRes.body);
        _categories = data['data'] ?? [];
      }

      // 3. Fetch locations
      final locRes = await http.get(Uri.parse('${AppConfig.baseUrl}/locations'));
      if (locRes.statusCode == 200) {
        final data = jsonDecode(locRes.body);
        _governorates = data['data'] ?? [];
      }

      // 4. Fetch listings
      await _fetchListings();
    } catch (e) {
      debugPrint('Error fetching data: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _fetchListings() async {
    try {
      final queryParams = <String, String>{};
      if (_selectedCondition.isNotEmpty) {
        queryParams['condition'] = _selectedCondition;
      }
      if (_selectedListingType.isNotEmpty) {
        queryParams['listing_type'] = _selectedListingType;
      }
      if (_selectedCategorySlug.isNotEmpty) {
        queryParams['category'] = _selectedCategorySlug;
      }
      if (_selectedGovernorateId != null) {
        queryParams['governorate_id'] = _selectedGovernorateId.toString();
      }
      if (_searchController.text.trim().isNotEmpty) {
        queryParams['q'] = _searchController.text.trim();
      }

      final uri = Uri.parse('${AppConfig.baseUrl}/listings').replace(queryParameters: queryParams);
      final res = await http.get(uri);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _listings = data['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint('Error fetching listings: $e');
    }
  }

  IconData _getCategoryIcon(String? slug) {
    switch (slug) {
      case 'cars-vehicles':
        return Icons.directions_car_rounded;
      case 'real-estate':
        return Icons.location_city_rounded;
      case 'electronics':
        return Icons.phone_iphone_rounded;
      case 'furniture-decor':
        return Icons.chair_rounded;
      case 'fashion-beauty':
        return Icons.checkroom_rounded;
      case 'services-jobs':
      case 'jobs-employment':
        return Icons.work_rounded;
      case 'animals-pets':
        return Icons.pets_rounded;
      case 'industrial-construction':
        return Icons.handyman_rounded;
      default:
        return Icons.category_rounded;
    }
  }

  Color _getCategoryColor(String? slug) {
    switch (slug) {
      case 'cars-vehicles':
        return const Color(0xFFDC2626);
      case 'real-estate':
        return const Color(0xFF2563EB);
      case 'electronics':
        return const Color(0xFF059669);
      case 'furniture-decor':
        return const Color(0xFFD97706);
      case 'fashion-beauty':
        return const Color(0xFFDB2777);
      case 'services-jobs':
      case 'jobs-employment':
        return const Color(0xFF7C3AED);
      case 'animals-pets':
        return const Color(0xFF0D9488);
      default:
        return AppColors.maroon;
    }
  }

  void _openUserAccount() {
    if (AppState.isLoggedIn) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => const UserDashboardScreen()),
      ).then((_) => setState(() {}));
    } else {
      _showAuthDialog();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.gavel_rounded, color: AppColors.amber, size: 20),
            ),
            const SizedBox(width: 8),
            Text(
              AppConfig.siteName,
              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none_rounded),
            tooltip: 'الإشعارات',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              );
            },
          ),
          IconButton(
            icon: Icon(AppState.isLoggedIn ? Icons.account_circle : Icons.login),
            tooltip: AppState.isLoggedIn ? 'حسابي ولوحة التحكم' : 'تسجيل الدخول',
            onPressed: _openUserAccount,
          ),
        ],
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: AppColors.maroon))
          : RefreshIndicator(
              onRefresh: _fetchInitialData,
              color: AppColors.maroon,
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 12),
                children: [
                  // Search Bar with Syrian Governorate Filter
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: TextField(
                        controller: _searchController,
                        textInputAction: TextInputAction.search,
                        onSubmitted: (_) => _fetchListings(),
                        decoration: InputDecoration(
                          hintText: 'ابحث عن سيارات، عقارات، هواتف، وظائف...',
                          hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
                          prefixIcon: Icon(Icons.search_rounded, color: AppColors.maroon),
                          suffixIcon: IconButton(
                            icon: const Icon(Icons.tune_rounded, size: 18, color: Colors.grey),
                            onPressed: _showGovernorateFilterDialog,
                          ),
                          border: InputBorder.none,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: 12),

                  // Quick Filter Segmented Chips (الكل | للبيع | مطلوب | وظائف | خدمات)
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: [
                        _buildQuickTypeChip('🌟 كافة العروض', '', ''),
                        _buildQuickTypeChip('🏷️ معروض للبيع', 'sale', ''),
                        _buildQuickTypeChip('🔍 مطلوب للشراء', 'wanted', ''),
                        _buildQuickTypeChip('💼 فرص عمل ووظائف', '', 'services-jobs'),
                        _buildQuickTypeChip('✨ جديد بالكرتونة', '', '', condition: 'new'),
                        _buildQuickTypeChip('🔄 مستعمل', '', '', condition: 'used'),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Header of Categories
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'تصفح الأقسام الرئيسية',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900),
                        ),
                        Text(
                          '${_categories.length} أقسام',
                          style: const TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 10),

                  // Categories Mini-Cards Grid (صورة أو أيقونة وتحتها اسم القسم)
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 4,
                        mainAxisSpacing: 10,
                        crossAxisSpacing: 10,
                        childAspectRatio: 0.85,
                      ),
                      itemCount: _categories.length,
                      itemBuilder: (context, index) {
                        final cat = _categories[index];
                        final icon = _getCategoryIcon(cat['slug']);
                        final color = _getCategoryColor(cat['slug']);

                        return InkWell(
                          onTap: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => CategoryDetailScreen(
                                  category: cat,
                                  allCategories: _categories,
                                ),
                              ),
                            );
                          },
                          borderRadius: BorderRadius.circular(16),
                          child: Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: Colors.grey.withValues(alpha: 0.2)),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.02),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Container(
                                  width: 44,
                                  height: 44,
                                  decoration: BoxDecoration(
                                    color: color.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Icon(icon, color: color, size: 24),
                                ),
                                const SizedBox(height: 6),
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 4),
                                  child: Text(
                                    cat['name_ar'] ?? '',
                                    textAlign: TextAlign.center,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.black87,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Listings Feed Header
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            const Text(
                              'أحدث السلع والإعلانات',
                              style: TextStyle(fontSize: 15, fontWeight: FontWeight.w900),
                            ),
                            if (_selectedGovernorateId != null) ...[
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppColors.amber.withValues(alpha: 0.2),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  _governorates.firstWhere((g) => g['id'] == _selectedGovernorateId, orElse: () => {})['name_ar'] ?? '',
                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.brown),
                                ),
                              ),
                            ],
                          ],
                        ),
                        Text(
                          '${_listings.length} إعلان',
                          style: const TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 10),

                  // Listings Feed
                  if (_listings.isEmpty)
                    Container(
                      margin: const EdgeInsets.all(16),
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Column(
                        children: [
                          Icon(Icons.search_off_rounded, size: 48, color: Colors.grey),
                          SizedBox(height: 8),
                          Text('لا توجد سلع تطابق بحثك حالياً', style: TextStyle(fontWeight: FontWeight.bold)),
                          Text('جرب تغيير الفلتر أو اختيار محافظة أخرى', style: TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ),
                    )
                  else
                    ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: _listings.length,
                      itemBuilder: (context, index) {
                        final item = _listings[index];
                        return _buildListingCard(item);
                      },
                    ),
                ],
              ),
            ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentBottomNavIndex,
        selectedItemColor: AppColors.maroon,
        unselectedItemColor: Colors.grey,
        type: BottomNavigationBarType.fixed,
        onTap: (index) {
          setState(() => _currentBottomNavIndex = index);
          if (index == 2) {
            // Post ad
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const CreateListingScreen()),
            ).then((_) => _fetchListings());
          } else if (index == 3) {
            // Notifications
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const NotificationsScreen()),
            );
          } else if (index == 4) {
            // Account / Dashboard
            _openUserAccount();
          }
        },
        items: [
          const BottomNavigationBarItem(icon: Icon(Icons.home_filled), label: 'الرئيسية'),
          const BottomNavigationBarItem(icon: Icon(Icons.explore_outlined), label: 'الأقسام'),
          BottomNavigationBarItem(
            icon: CircleAvatar(
              radius: 16,
              backgroundColor: AppColors.maroon,
              child: const Icon(Icons.add, color: Colors.white, size: 18),
            ),
            label: 'أضف إعلانك',
          ),
          const BottomNavigationBarItem(icon: Icon(Icons.notifications_none), label: 'تنبيهاتي'),
          const BottomNavigationBarItem(icon: Icon(Icons.person_outline), label: 'حسابي'),
        ],
      ),
    );
  }

  Widget _buildQuickTypeChip(String label, String type, String catSlug, {String condition = ''}) {
    final bool isSelected = (_selectedListingType == type && _selectedCategorySlug == catSlug && _selectedCondition == condition);
    return Padding(
      padding: const EdgeInsets.only(left: 6),
      child: FilterChip(
        label: Text(label),
        selected: isSelected,
        selectedColor: AppColors.maroon,
        labelStyle: TextStyle(
          color: isSelected ? Colors.white : Colors.black87,
          fontSize: 11,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        onSelected: (val) {
          setState(() {
            _selectedListingType = val ? type : '';
            _selectedCategorySlug = val ? catSlug : '';
            _selectedCondition = val ? condition : '';
          });
          _fetchListings();
        },
      ),
    );
  }

  Widget _buildListingCard(dynamic item) {
    final images = item['images'] as List<dynamic>? ?? [];
    String imgUrl = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=400&q=80';
    if (images.isNotEmpty && images[0]['image_path'] != null) {
      imgUrl = AppConfig.resolveUrl(images[0]['image_path'].toString());
    }

    final isNew = item['condition'] == 'new';
    final isWanted = item['listing_type'] == 'wanted';
    final isSold = item['is_sold'] == true;
    final jobType = item['job_type']; // 'vacancy' vs 'seeker'

    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0;
    final currency = item['currency'] == 'USD' ? '\$' : 'ل.س';
    final formatter = intl.NumberFormat('#,###');

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => ListingDetailScreen(listingId: item['id']),
            ),
          ).then((_) => _fetchListings());
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Thumbnail with Status Badge
              Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(
                      imgUrl,
                      width: 95,
                      height: 95,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => Container(
                        width: 95,
                        height: 95,
                        color: Colors.grey.shade200,
                        child: const Icon(Icons.image_not_supported, color: Colors.grey),
                      ),
                    ),
                  ),
                  // Badge: Wanted vs Sale vs Sold
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        color: isSold
                            ? Colors.black87
                            : (isWanted ? AppColors.purple : (isNew ? AppColors.emerald : AppColors.blue)),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isSold ? 'تم البيع' : (isWanted ? 'مطلوب' : (isNew ? 'جديد' : 'مستعمل')),
                        style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(width: 12),

              // Details
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Job tag or Type Tag
                    if (jobType != null) ...[
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                        margin: const EdgeInsets.only(bottom: 3),
                        decoration: BoxDecoration(
                          color: jobType == 'vacancy' ? Colors.orange.shade100 : Colors.indigo.shade100,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          jobType == 'vacancy' ? '🏢 فرصة عمل شاغرة' : '👨‍💼 باحث عن عمل',
                          style: TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                            color: jobType == 'vacancy' ? Colors.orange.shade900 : Colors.indigo.shade900,
                          ),
                        ),
                      ),
                    ],

                    Text(
                      item['title'] ?? '',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, height: 1.3),
                    ),
                    const SizedBox(height: 4),

                    // Syrian Location Pill
                    Row(
                      children: [
                        const Icon(Icons.location_on_rounded, size: 12, color: AppColors.amber),
                        const SizedBox(width: 2),
                        Text(
                          '${item['governorate']?['name_ar'] ?? ''}${item['region'] != null ? ' - ${item['region']['name_ar']}' : ''}',
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                        ),
                      ],
                    ),

                    const SizedBox(height: 6),

                    // Price & Currency
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '${formatter.format(price)} $currency',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w900,
                            color: isWanted ? AppColors.purple : AppColors.maroon,
                          ),
                        ),
                        if (item['is_negotiable'] == true)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1.5),
                            decoration: BoxDecoration(
                              color: AppColors.emerald.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text(
                              'قابل للتفاوض',
                              style: TextStyle(color: AppColors.emerald, fontSize: 9, fontWeight: FontWeight.bold),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showGovernorateFilterDialog() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return Container(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('اختر المحافظة السورية', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              const Divider(),
              ListTile(
                title: const Text('كافة المحافظات'),
                trailing: _selectedGovernorateId == null ? Icon(Icons.check, color: AppColors.maroon) : null,
                onTap: () {
                  setState(() => _selectedGovernorateId = null);
                  Navigator.pop(context);
                  _fetchListings();
                },
              ),
              Expanded(
                child: ListView.builder(
                  itemCount: _governorates.length,
                  itemBuilder: (context, idx) {
                    final gov = _governorates[idx];
                    final isSel = _selectedGovernorateId == gov['id'];
                    return ListTile(
                      title: Text(gov['name_ar'] ?? ''),
                      trailing: isSel ? Icon(Icons.check, color: AppColors.maroon) : null,
                      onTap: () {
                        setState(() => _selectedGovernorateId = gov['id']);
                        Navigator.pop(context);
                        _fetchListings();
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showAuthDialog() {
    showDialog(
      context: context,
      builder: (_) => const AuthDialog(),
    ).then((_) => setState(() {}));
  }
}

// ---------------------------------------------------------------------------------
// Category Detail Screen with TOP HAMBURGER MENU DRAWER for Fast Branch Navigation
// ---------------------------------------------------------------------------------
class CategoryDetailScreen extends StatefulWidget {
  final Map<String, dynamic> category;
  final List<dynamic> allCategories;

  const CategoryDetailScreen({
    super.key,
    required this.category,
    required this.allCategories,
  });

  @override
  State<CategoryDetailScreen> createState() => _CategoryDetailScreenState();
}

class _CategoryDetailScreenState extends State<CategoryDetailScreen> {
  late Map<String, dynamic> _currentCategory;
  int? _selectedSubcategoryId;
  String _selectedCondition = '';
  String _selectedListingType = '';
  List<dynamic> _listings = [];
  bool _isLoading = true;
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _currentCategory = widget.category;
    _fetchCategoryListings();
  }

  Future<void> _fetchCategoryListings() async {
    setState(() => _isLoading = true);
    try {
      final queryParams = <String, String>{
        'category_id': _currentCategory['id'].toString(),
      };
      if (_selectedSubcategoryId != null) {
        queryParams['subcategory_id'] = _selectedSubcategoryId.toString();
      }
      if (_selectedCondition.isNotEmpty) {
        queryParams['condition'] = _selectedCondition;
      }
      if (_selectedListingType.isNotEmpty) {
        queryParams['listing_type'] = _selectedListingType;
      }

      final uri = Uri.parse('${AppConfig.baseUrl}/listings').replace(queryParameters: queryParams);
      final res = await http.get(uri);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        _listings = data['data'] ?? [];
      }
    } catch (e) {
      debugPrint('Error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _switchCategory(Map<String, dynamic> newCat, {int? subId}) {
    setState(() {
      _currentCategory = newCat;
      _selectedSubcategoryId = subId;
    });
    if (_scaffoldKey.currentState?.isDrawerOpen ?? false) {
      Navigator.pop(context);
    }
    _fetchCategoryListings();
  }

  @override
  Widget build(BuildContext context) {
    final subcategories = _currentCategory['subcategories'] as List<dynamic>? ?? [];

    return Scaffold(
      key: _scaffoldKey,
      appBar: AppBar(
        title: Text(
          _currentCategory['name_ar'] ?? 'القسم',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.menu_rounded, size: 28),
            tooltip: 'القائمة الجانبية للأقسام والفروع',
            onPressed: () {
              _scaffoldKey.currentState?.openDrawer();
            },
          ),
        ],
      ),

      drawer: Drawer(
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 50, 20, 20),
              color: AppColors.maroon,
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.category_rounded, color: AppColors.amber, size: 24),
                      SizedBox(width: 8),
                      Text(
                        'كافة الأقسام والفروع',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                    ],
                  ),
                  SizedBox(height: 6),
                  Text(
                    'تنقل مباشرة بين الفروع دون الحاجة للرجوع للرئيسية',
                    style: TextStyle(color: Colors.white70, fontSize: 11),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: widget.allCategories.length,
                itemBuilder: (context, index) {
                  final cat = widget.allCategories[index];
                  final isCurrent = cat['id'] == _currentCategory['id'];
                  final subs = cat['subcategories'] as List<dynamic>? ?? [];

                  return ExpansionTile(
                    initiallyExpanded: isCurrent,
                    leading: Icon(
                      Icons.folder_outlined,
                      color: isCurrent ? AppColors.maroon : Colors.grey,
                      size: 20,
                    ),
                    title: Text(
                      cat['name_ar'] ?? '',
                      style: TextStyle(
                        fontWeight: isCurrent ? FontWeight.w900 : FontWeight.bold,
                        fontSize: 13,
                        color: isCurrent ? AppColors.maroon : Colors.black87,
                      ),
                    ),
                    children: [
                      ListTile(
                        contentPadding: const EdgeInsets.only(right: 48, left: 16),
                        title: const Text('كافة فروع هذا القسم', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                        trailing: const Icon(Icons.arrow_left, size: 16),
                        onTap: () => _switchCategory(cat),
                      ),
                      ...subs.map((sub) {
                        final isSubCurrent = isCurrent && _selectedSubcategoryId == sub['id'];
                        return ListTile(
                          contentPadding: const EdgeInsets.only(right: 48, left: 16),
                          title: Text(
                            sub['name_ar'] ?? '',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: isSubCurrent ? FontWeight.bold : FontWeight.normal,
                              color: isSubCurrent ? AppColors.maroon : Colors.black87,
                            ),
                          ),
                          onTap: () => _switchCategory(cat, subId: sub['id']),
                        );
                      }),
                    ],
                  );
                },
              ),
            ),
          ],
        ),
      ),

      body: Column(
        children: [
          // Subcategories Horizontal Chips Bar
          if (subcategories.isNotEmpty)
            Container(
              height: 52,
              color: Colors.white,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                children: [
                  Padding(
                    padding: const EdgeInsets.only(left: 8),
                    child: ChoiceChip(
                      label: const Text('الكل', style: TextStyle(fontSize: 11)),
                      selected: _selectedSubcategoryId == null,
                      selectedColor: AppColors.maroon,
                      labelStyle: TextStyle(
                        color: _selectedSubcategoryId == null ? Colors.white : Colors.black87,
                        fontWeight: FontWeight.bold,
                      ),
                      onSelected: (_) {
                        setState(() => _selectedSubcategoryId = null);
                        _fetchCategoryListings();
                      },
                    ),
                  ),
                  ...subcategories.map((sub) {
                    final isSel = _selectedSubcategoryId == sub['id'];
                    return Padding(
                      padding: const EdgeInsets.only(left: 8),
                      child: ChoiceChip(
                        label: Text(sub['name_ar'] ?? '', style: const TextStyle(fontSize: 11)),
                        selected: isSel,
                        selectedColor: AppColors.maroon,
                        labelStyle: TextStyle(
                          color: isSel ? Colors.white : Colors.black87,
                          fontWeight: FontWeight.bold,
                        ),
                        onSelected: (_) {
                          setState(() => _selectedSubcategoryId = sub['id']);
                          _fetchCategoryListings();
                        },
                      ),
                    );
                  }),
                ],
              ),
            ),

          // Filters (للبيع | مطلوب | جديد | مستعمل)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.grey.shade100,
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  _buildTypePill('الكل', ''),
                  const SizedBox(width: 6),
                  _buildTypePill('🏷️ للبيع', 'sale'),
                  const SizedBox(width: 6),
                  _buildTypePill('🔍 مطلوب للشراء', 'wanted', color: AppColors.purple),
                  const SizedBox(width: 12),
                  const VerticalDivider(width: 1),
                  const SizedBox(width: 12),
                  _buildConditionPill('الكل', ''),
                  const SizedBox(width: 6),
                  _buildConditionPill('جديد زيرو', 'new', color: AppColors.emerald),
                  const SizedBox(width: 6),
                  _buildConditionPill('مستعمل', 'used', color: AppColors.blue),
                ],
              ),
            ),
          ),

          // Listings
          Expanded(
            child: _isLoading
                ? Center(child: CircularProgressIndicator(color: AppColors.maroon))
                : _listings.isEmpty
                    ? const Center(
                        child: Text(
                          'لا توجد سلع معروضة في هذا الفرع حالياً',
                          style: TextStyle(fontSize: 13, color: Colors.grey),
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listings.length,
                        itemBuilder: (context, index) {
                          final item = _listings[index];
                          return _buildListingCard(item);
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildTypePill(String label, String val, {Color? color}) {
    final isSel = _selectedListingType == val;
    return GestureDetector(
      onTap: () {
        setState(() => _selectedListingType = val);
        _fetchCategoryListings();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: isSel ? (color ?? AppColors.maroon) : Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSel ? Colors.transparent : Colors.grey.shade300),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
            color: isSel ? Colors.white : Colors.black87,
          ),
        ),
      ),
    );
  }

  Widget _buildConditionPill(String label, String val, {Color? color}) {
    final isSel = _selectedCondition == val;
    return GestureDetector(
      onTap: () {
        setState(() => _selectedCondition = val);
        _fetchCategoryListings();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: isSel ? (color ?? AppColors.maroon) : Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: isSel ? Colors.transparent : Colors.grey.shade300),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isSel ? FontWeight.bold : FontWeight.normal,
            color: isSel ? Colors.white : Colors.black87,
          ),
        ),
      ),
    );
  }

  Widget _buildListingCard(dynamic item) {
    final images = item['images'] as List<dynamic>? ?? [];
    String imgUrl = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=400&q=80';
    if (images.isNotEmpty && images[0]['image_path'] != null) {
      imgUrl = AppConfig.resolveUrl(images[0]['image_path'].toString());
    }

    final isNew = item['condition'] == 'new';
    final isWanted = item['listing_type'] == 'wanted';
    final isSold = item['is_sold'] == true;
    final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0;
    final currency = item['currency'] == 'USD' ? '\$' : 'ل.س';
    final formatter = intl.NumberFormat('#,###');

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => ListingDetailScreen(listingId: item['id']),
            ),
          ).then((_) => _fetchCategoryListings());
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(
                      imgUrl,
                      width: 90,
                      height: 90,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => Container(width: 90, height: 90, color: Colors.grey.shade200),
                    ),
                  ),
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        color: isSold ? Colors.black87 : (isWanted ? AppColors.purple : (isNew ? AppColors.emerald : AppColors.blue)),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isSold ? 'تم البيع' : (isWanted ? 'مطلوب' : (isNew ? 'جديد' : 'مستعمل')),
                        style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['title'] ?? '',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${formatter.format(price)} $currency',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w900,
                        color: isWanted ? AppColors.purple : AppColors.maroon,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Listing Detail Screen with Full Info, Toggle Sold, Follow, & Comments
// ---------------------------------------------------------------------------------
class ListingDetailScreen extends StatefulWidget {
  final int listingId;

  const ListingDetailScreen({super.key, required this.listingId});

  @override
  State<ListingDetailScreen> createState() => _ListingDetailScreenState();
}

class _ListingDetailScreenState extends State<ListingDetailScreen> {
  Map<String, dynamic>? _listing;
  bool _isLoading = true;
  bool _isFollowingSeller = false;
  final TextEditingController _commentController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchListingDetails();
  }

  Future<void> _fetchListingDetails() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppConfig.baseUrl}/listings/${widget.listingId}'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _listing = data['data'];
          _isFollowingSeller = data['is_following_seller'] ?? false;
        });
      }
    } catch (e) {
      debugPrint('Error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleFollowSeller() async {
    if (!AppState.isLoggedIn) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تسجيل الدخول أولاً لمتابعة البائع')),
      );
      return;
    }

    final sellerId = _listing?['user']?['id'];
    if (sellerId == null) return;

    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/sellers/$sellerId/toggle-follow'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _isFollowingSeller = data['is_following'];
        });
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'تم تحديث المتابعة')),
          );
        }
      }
    } catch (e) {
      debugPrint('Follow error: $e');
    }
  }

  Future<void> _toggleSoldStatus() async {
    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/listings/${widget.listingId}/sold'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'تم تحديث حالة السلعة')),
          );
        }
        _fetchListingDetails();
      }
    } catch (e) {
      debugPrint('Sold toggle error: $e');
    }
  }

  Future<void> _addComment() async {
    final text = _commentController.text.trim();
    if (text.isEmpty) return;

    if (!AppState.isLoggedIn) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تسجيل الدخول لكتابة تعليق')),
      );
      return;
    }

    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/listings/${widget.listingId}/comments'),
        headers: AppState.authHeaders,
        body: jsonEncode({'comment': text}),
      );
      if (res.statusCode == 201) {
        _commentController.clear();
        _fetchListingDetails();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('تمت إضافة تعليقك بنجاح')),
          );
        }
      }
    } catch (e) {
      debugPrint('Comment error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('تفاصيل السلعة')),
        body: Center(child: CircularProgressIndicator(color: AppColors.maroon)),
      );
    }

    if (_listing == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('خطأ')),
        body: const Center(child: Text('تعذر العثور على السلعة المطلوبة')),
      );
    }

    final isNew = _listing!['condition'] == 'new';
    final isWanted = _listing!['listing_type'] == 'wanted';
    final isSold = _listing!['is_sold'] == true;
    final jobType = _listing!['job_type'];

    final price = double.tryParse(_listing!['price']?.toString() ?? '0') ?? 0;
    final currency = _listing!['currency'] == 'USD' ? '\$' : 'ل.س';
    final formatter = intl.NumberFormat('#,###');
    final images = _listing!['images'] as List<dynamic>? ?? [];
    final seller = _listing!['user'];
    final comments = _listing!['comments'] as List<dynamic>? ?? [];
    final specs = _listing!['specs'] as Map<String, dynamic>? ?? {};

    final bool isOwner = AppState.isLoggedIn && AppState.user?['id'] == _listing!['user_id'];

    return Scaffold(
      appBar: AppBar(
        title: Text(_listing!['title'] ?? 'تفاصيل السلعة', style: const TextStyle(fontSize: 15)),
      ),
      body: ListView(
        children: [
          // Sold Banner
          if (isSold)
            Container(
              padding: const EdgeInsets.all(10),
              color: Colors.red.shade900,
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
                  SizedBox(width: 8),
                  Text(
                    'تم البيع وإتمام الصفقة بنجاح - الإعلان غير متاح حالياً',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                  ),
                ],
              ),
            ),

          // Photos Gallery Carousel
          if (images.isNotEmpty)
            SizedBox(
              height: 260,
              child: PageView.builder(
                itemCount: images.length,
                itemBuilder: (context, i) {
                  final url = AppConfig.resolveUrl(images[i]['image_path']?.toString());
                  return Image.network(url, fit: BoxFit.cover);
                },
              ),
            )
          else
            Container(height: 200, color: Colors.grey.shade200, child: const Icon(Icons.image, size: 64)),

          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Tags Row: Type (للبيع / مطلوب) + Condition + Jobs
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: isWanted ? AppColors.purple : AppColors.maroon,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        isWanted ? '🔍 طلب شراء (مطلوب)' : '🏷️ معروض للبيع',
                        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: isNew ? AppColors.emerald : AppColors.blue,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        isNew ? 'جديد بالكرتونة (زيرو)' : 'مستعمل',
                        style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ),
                    if (jobType != null) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.orange.shade700,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          jobType == 'vacancy' ? 'فرصة عمل 🏢' : 'باحث عن عمل 👨‍💼',
                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ],
                ),

                const SizedBox(height: 10),

                // Title
                Text(
                  _listing!['title'] ?? '',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
                ),

                const SizedBox(height: 10),

                // Price Box
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.maroon.withValues(alpha: 0.06),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.maroon.withValues(alpha: 0.15)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(isWanted ? 'الميزانية المعروضة للشراء' : 'السعر المطلوب', style: const TextStyle(fontSize: 10, color: Colors.grey)),
                          Text(
                            '${formatter.format(price)} $currency',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w900,
                              color: isWanted ? AppColors.purple : AppColors.maroon,
                            ),
                          ),
                        ],
                      ),
                      if (_listing!['is_negotiable'] == true)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.emerald.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: const Text('قابل للتفاوض', style: TextStyle(color: AppColors.emerald, fontWeight: FontWeight.bold, fontSize: 11)),
                        ),
                    ],
                  ),
                ),

                // Owner Controls: Toggle Sold
                if (isOwner) ...[
                  const SizedBox(height: 12),
                  ElevatedButton.icon(
                    onPressed: _toggleSoldStatus,
                    icon: Icon(isSold ? Icons.replay : Icons.check_circle_outline),
                    label: Text(isSold ? 'إعادة عرض السلعة (إلغاء حالة تم البيع)' : 'تمييز الإعلان كـ "تم البيع" وإغلاق الطلب'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isSold ? AppColors.emerald : Colors.brown,
                      foregroundColor: Colors.white,
                      minimumSize: const Size.fromHeight(40),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ],

                const SizedBox(height: 16),

                // Detailed Syrian Address Box
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.amber.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.amber.shade200),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.location_on, color: AppColors.maroon, size: 16),
                          const SizedBox(width: 4),
                          const Text('الموقع والعنوان في سوريا:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'المحافظة: ${_listing!['governorate']?['name_ar'] ?? ''} - المنطقة: ${_listing!['region']?['name_ar'] ?? ''} - الحي: ${_listing!['district_name'] ?? _listing!['district']?['name_ar'] ?? ''}',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                      ),
                      if (_listing!['street_address'] != null && _listing!['street_address'].toString().isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          'العنوان ومعلم قريب: ${_listing!['street_address']}',
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade800),
                        ),
                      ],
                    ],
                  ),
                ),

                const SizedBox(height: 16),

                // Specs Table
                if (specs.isNotEmpty) ...[
                  const Text('المواصفات الفنية', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 6),
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      children: specs.entries.map((e) {
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(e.key, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                              Text(e.value.toString(), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Description
                const Text('الوصف والتفاصيل', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                const SizedBox(height: 6),
                Text(
                  _listing!['description'] ?? '',
                  style: const TextStyle(fontSize: 12, height: 1.6),
                ),

                const SizedBox(height: 20),

                // Seller Card with Follow Button
                if (seller != null)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            CircleAvatar(
                              backgroundColor: AppColors.maroon,
                              foregroundColor: Colors.white,
                              child: Text(seller['name']?[0] ?? 'S'),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Text(seller['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                      if (seller['is_verified'] == true) ...[
                                        const SizedBox(width: 4),
                                        const Icon(Icons.verified, color: Colors.blue, size: 14),
                                      ],
                                    ],
                                  ),
                                  Text('${seller['followers_count'] ?? 0} متابع', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        ElevatedButton.icon(
                          onPressed: _toggleFollowSeller,
                          icon: Icon(_isFollowingSeller ? Icons.check : Icons.notification_add, size: 16),
                          label: Text(_isFollowingSeller ? 'أنت تتابع هذا الحساب (إلغاء)' : 'متابعة صاحب الإعلان (إشعار بالجديد)'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: _isFollowingSeller ? Colors.grey.shade200 : AppColors.maroon,
                            foregroundColor: _isFollowingSeller ? Colors.black87 : Colors.white,
                            minimumSize: const Size.fromHeight(40),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: 24),

                // Comments Section
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('التعليقات والاستفسارات', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    Text('${comments.length} تعليق', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                  ],
                ),
                const SizedBox(height: 8),

                // Add comment input
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _commentController,
                        decoration: InputDecoration(
                          hintText: 'اكتب استفسارك هنا لصاحب الإعلان...',
                          hintStyle: const TextStyle(fontSize: 12),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      icon: Icon(Icons.send_rounded, color: AppColors.maroon),
                      onPressed: _addComment,
                    ),
                  ],
                ),

                const SizedBox(height: 12),

                // Comments list
                ...comments.map((c) {
                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade100),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(c['user']?['name'] ?? 'مستخدم', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            const Text('منذ قليل', style: TextStyle(fontSize: 10, color: Colors.grey)),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(c['comment'] ?? '', style: const TextStyle(fontSize: 12)),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Post New Ad Screen ("معروض للبيع" vs "مطلوب للشراء" + فرص العمل)
// ---------------------------------------------------------------------------------
class CreateListingScreen extends StatefulWidget {
  const CreateListingScreen({super.key});

  @override
  State<CreateListingScreen> createState() => _CreateListingScreenState();
}

class _CreateListingScreenState extends State<CreateListingScreen> {
  final _titleController = TextEditingController();
  final _priceController = TextEditingController();
  final _descController = TextEditingController();
  final _districtController = TextEditingController();
  final _streetController = TextEditingController();
  final _phoneController = TextEditingController();

  String _listingType = 'sale'; // 'sale' vs 'wanted'
  String _jobType = 'vacancy'; // 'vacancy' (مطلوب موظف) vs 'seeker' (باحث عن عمل)
  String _condition = 'used'; // 'new' vs 'used'
  String _currency = 'SYP';
  final bool _isNegotiable = true;
  int? _selectedCategoryId;
  int? _selectedSubcategoryId;
  int? _selectedGovernorateId;
  int? _selectedRegionId;

  List<dynamic> _categories = [];
  List<dynamic> _governorates = [];
  List<dynamic> _regions = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadMetadata();
    _autoFillRememberedData();
  }

  void _autoFillRememberedData() {
    if (AppState.user != null) {
      if (AppState.user!['phone'] != null) {
        _phoneController.text = AppState.user!['phone'].toString();
      }
    }
  }

  Future<void> _loadMetadata() async {
    try {
      final catRes = await http.get(Uri.parse('${AppConfig.baseUrl}/categories'));
      final locRes = await http.get(Uri.parse('${AppConfig.baseUrl}/locations'));
      if (catRes.statusCode == 200 && locRes.statusCode == 200) {
        setState(() {
          _categories = jsonDecode(catRes.body)['data'] ?? [];
          _governorates = jsonDecode(locRes.body)['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint('Metadata error: $e');
    }
  }

  void _onGovernorateChanged(int? govId) {
    setState(() {
      _selectedGovernorateId = govId;
      _selectedRegionId = null;
      _regions = [];
    });
    if (govId != null) {
      final gov = _governorates.firstWhere((g) => g['id'] == govId, orElse: () => null);
      if (gov != null && gov['regions'] != null) {
        setState(() {
          _regions = gov['regions'];
        });
      }
    }
  }

  Future<void> _submitListing() async {
    if (!AppState.isLoggedIn) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تسجيل الدخول أو إنشاء حساب لنشر الإعلان')),
      );
      return;
    }

    if (_titleController.text.trim().isEmpty ||
        _priceController.text.trim().isEmpty ||
        _descController.text.trim().isEmpty ||
        _selectedCategoryId == null ||
        _selectedGovernorateId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى ملء جميع الحقول المطلوبة واختيار المحافظة')),
      );
      return;
    }

    setState(() => _isLoading = true);
    try {
      final uri = Uri.parse('${AppConfig.baseUrl}/listings');
      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll({
        'Accept': 'application/json',
        if (AppState.token != null) 'Authorization': 'Bearer ${AppState.token}',
      });

      request.fields['title'] = _titleController.text.trim();
      request.fields['category_id'] = _selectedCategoryId.toString();
      if (_selectedSubcategoryId != null) {
        request.fields['subcategory_id'] = _selectedSubcategoryId.toString();
      }
      request.fields['listing_type'] = _listingType;
      request.fields['condition'] = _condition;
      request.fields['price'] = _priceController.text.trim();
      request.fields['currency'] = _currency;
      request.fields['is_negotiable'] = _isNegotiable ? '1' : '0';
      request.fields['description'] = _descController.text.trim();
      request.fields['governorate_id'] = _selectedGovernorateId.toString();

      // If category is jobs/employment or has jobs
      final selectedCat = _categories.firstWhere((c) => c['id'] == _selectedCategoryId, orElse: () => {});
      final catSlug = selectedCat['slug'] ?? '';
      if (catSlug == 'services-jobs' || catSlug == 'jobs-employment') {
        request.fields['job_type'] = _jobType;
      }

      if (_selectedRegionId != null) {
        request.fields['region_id'] = _selectedRegionId.toString();
      }
      if (_districtController.text.trim().isNotEmpty) {
        request.fields['district_name'] = _districtController.text.trim();
      }
      if (_streetController.text.trim().isNotEmpty) {
        request.fields['street_address'] = _streetController.text.trim();
      }
      if (_phoneController.text.trim().isNotEmpty) {
        request.fields['phone'] = _phoneController.text.trim();
      }

      request.files.add(http.MultipartFile.fromString(
        'images[0]',
        'dummy content',
        filename: 'sample.jpg',
      ));

      final streamedRes = await request.send();
      final res = await http.Response.fromStream(streamedRes);

      if (res.statusCode == 201) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('تم نشر الإعلان بنجاح وتم إشعار المتابعين!')),
          );
          Navigator.pop(context);
        }
      } else {
        final err = jsonDecode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(err['message'] ?? 'فشل نشر الإعلان')),
          );
        }
      }
    } catch (e) {
      debugPrint('Submit error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final selectedCat = _categories.firstWhere((c) => c['id'] == _selectedCategoryId, orElse: () => {});
    final subcategories = selectedCat['subcategories'] as List<dynamic>? ?? [];
    final catSlug = selectedCat['slug'] ?? '';
    final isJobsCat = catSlug == 'services-jobs' || catSlug == 'jobs-employment';

    return Scaffold(
      appBar: AppBar(
        title: Text(_listingType == 'wanted' ? 'أضف طلب شراء (مطلوب)' : 'أضف سلعتك للبيع في سوريا'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Mode Toggle: للبيع vs مطلوب للشراء
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.grey.shade200,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _listingType = 'sale'),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _listingType == 'sale' ? AppColors.maroon : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.sell_outlined, size: 16, color: _listingType == 'sale' ? Colors.white : Colors.black87),
                          const SizedBox(width: 6),
                          Text(
                            'معروض للبيع',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 13,
                              color: _listingType == 'sale' ? Colors.white : Colors.black87,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _listingType = 'wanted'),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _listingType == 'wanted' ? AppColors.purple : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.search_rounded, size: 16, color: _listingType == 'wanted' ? Colors.white : Colors.black87),
                          const SizedBox(width: 6),
                          Text(
                            'مطلوب للشراء',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 13,
                              color: _listingType == 'wanted' ? Colors.white : Colors.black87,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Title
          TextField(
            controller: _titleController,
            decoration: InputDecoration(
              labelText: _listingType == 'wanted' ? 'عنوان الطلب (مثال: مطلوب سيارة كيا سيراتو موديل 2020) *' : 'اسم القطعة أو عنوان السلعة للبيع *',
              hintText: _listingType == 'wanted' ? 'مطلوب شقة للإيجار، مطلوب محاسب، مطلوب هاتف...' : 'كيا سيراتو 2024 زيرو، آيفون 16...',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Category
          DropdownButtonFormField<int>(
            initialValue: _selectedCategoryId,
            decoration: InputDecoration(
              labelText: 'القسم الرئيسي *',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            items: _categories.map<DropdownMenuItem<int>>((c) {
              return DropdownMenuItem<int>(
                value: c['id'],
                child: Text(c['name_ar'] ?? ''),
              );
            }).toList(),
            onChanged: (val) {
              setState(() {
                _selectedCategoryId = val;
                _selectedSubcategoryId = null;
              });
            },
          ),
          const SizedBox(height: 14),

          // If Jobs Category selected: choose Vacancy (مطلوب موظف) vs Seeker (باحث عن عمل)
          if (isJobsCat) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.indigo.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.indigo.shade200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('نوع إعلان الوظائف والعمل *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: ChoiceChip(
                          label: const Text('🏢 فرصة عمل (مطلوب موظف)'),
                          selected: _jobType == 'vacancy',
                          selectedColor: Colors.indigo,
                          labelStyle: TextStyle(color: _jobType == 'vacancy' ? Colors.white : Colors.black87, fontSize: 11),
                          onSelected: (_) => setState(() => _jobType = 'vacancy'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ChoiceChip(
                          label: const Text('👨‍💼 باحث عن عمل (سيرة ذاتية)'),
                          selected: _jobType == 'seeker',
                          selectedColor: Colors.indigo,
                          labelStyle: TextStyle(color: _jobType == 'seeker' ? Colors.white : Colors.black87, fontSize: 11),
                          onSelected: (_) => setState(() => _jobType = 'seeker'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
          ],

          // Subcategory
          if (subcategories.isNotEmpty) ...[
            DropdownButtonFormField<int>(
              initialValue: _selectedSubcategoryId,
              decoration: InputDecoration(
                labelText: 'القسم الفرعي',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              items: subcategories.map<DropdownMenuItem<int>>((s) {
                return DropdownMenuItem<int>(
                  value: s['id'],
                  child: Text(s['name_ar'] ?? ''),
                );
              }).toList(),
              onChanged: (val) => setState(() => _selectedSubcategoryId = val),
            ),
            const SizedBox(height: 14),
          ],

          // Condition: New vs Used
          const Text('حالة السلعة *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                child: ChoiceChip(
                  label: const Text('جديد بالكرتونة (زيرو)'),
                  selected: _condition == 'new',
                  selectedColor: AppColors.emerald,
                  labelStyle: TextStyle(color: _condition == 'new' ? Colors.white : Colors.black87, fontSize: 12),
                  onSelected: (_) => setState(() => _condition = 'new'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ChoiceChip(
                  label: const Text('مستعمل بحالة جيدة'),
                  selected: _condition == 'used',
                  selectedColor: AppColors.blue,
                  labelStyle: TextStyle(color: _condition == 'used' ? Colors.white : Colors.black87, fontSize: 12),
                  onSelected: (_) => setState(() => _condition = 'used'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Price & Currency
          Row(
            children: [
              Expanded(
                flex: 2,
                child: TextField(
                  controller: _priceController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: _listingType == 'wanted' ? 'الميزانية المتوقعة *' : 'السعر المطلوب *',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                flex: 1,
                child: DropdownButtonFormField<String>(
                  initialValue: _currency,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'SYP', child: Text('ل.س')),
                    DropdownMenuItem(value: 'USD', child: Text('\$')),
                  ],
                  onChanged: (v) => setState(() => _currency = v!),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Syrian Governorate
          DropdownButtonFormField<int>(
            initialValue: _selectedGovernorateId,
            decoration: InputDecoration(
              labelText: 'المحافظة السورية *',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            items: _governorates.map<DropdownMenuItem<int>>((g) {
              return DropdownMenuItem<int>(
                value: g['id'],
                child: Text(g['name_ar'] ?? ''),
              );
            }).toList(),
            onChanged: _onGovernorateChanged,
          ),
          const SizedBox(height: 14),

          // Region / City
          if (_regions.isNotEmpty) ...[
            DropdownButtonFormField<int>(
              initialValue: _selectedRegionId,
              decoration: InputDecoration(
                labelText: 'المنطقة / المدينة',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              items: _regions.map<DropdownMenuItem<int>>((r) {
                return DropdownMenuItem<int>(
                  value: r['id'],
                  child: Text(r['name_ar'] ?? ''),
                );
              }).toList(),
              onChanged: (v) => setState(() => _selectedRegionId = v),
            ),
            const SizedBox(height: 14),
          ],

          // District
          TextField(
            controller: _districtController,
            decoration: InputDecoration(
              labelText: 'الحي',
              hintText: 'مثال: الميدان، الفيلات الغربية، الشهباء',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Street Address & Landmark
          TextField(
            controller: _streetController,
            decoration: InputDecoration(
              labelText: 'تكملة العنوان ومعالم قريبة',
              hintText: 'اسم الشارع، جانب مدرسة كذا، مقابل مشفى كذا',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Contact Phone (Auto-remembered)
          TextField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: 'رقم الهاتف للتواصل',
              hintText: '09xxxxxxxx',
              prefixIcon: const Icon(Icons.phone),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Description
          TextField(
            controller: _descController,
            maxLines: 4,
            decoration: InputDecoration(
              labelText: 'الوصف والمواصفات الكاملة *',
              hintText: 'اكتب مواصفات السلعة، حالتها، الفحص، الفراغ، أو متطلبات الوظيفة...',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 20),

          // Submit Button
          ElevatedButton(
            onPressed: _isLoading ? null : _submitListing,
            style: ElevatedButton.styleFrom(
              backgroundColor: _listingType == 'wanted' ? AppColors.purple : AppColors.maroon,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: _isLoading
                ? const CircularProgressIndicator(color: Colors.white)
                : Text(
                    _listingType == 'wanted' ? 'نشر طلب الشراء الآن' : 'نشر السلعة للبيع الآن',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// User Dashboard Screen (المبيعات، المشتريات، المتابعات، تذكر معلوماتك)
// ---------------------------------------------------------------------------------
class UserDashboardScreen extends StatefulWidget {
  const UserDashboardScreen({super.key});

  @override
  State<UserDashboardScreen> createState() => _UserDashboardScreenState();
}

class _UserDashboardScreenState extends State<UserDashboardScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  Map<String, dynamic>? _dashboardData;
  List<dynamic> _myListings = [];

  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _whatsappCtrl = TextEditingController();
  final _bioCtrl = TextEditingController();
  bool _rememberContactInfo = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppConfig.baseUrl}/user/dashboard'),
        headers: AppState.authHeaders,
      );
      final listRes = await http.get(
        Uri.parse('${AppConfig.baseUrl}/my-listings'),
        headers: AppState.authHeaders,
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body)['data'];
        setState(() {
          _dashboardData = data;
          final user = data['user'] ?? {};
          _nameCtrl.text = user['name'] ?? '';
          _phoneCtrl.text = user['phone'] ?? '';
          _whatsappCtrl.text = user['whatsapp'] ?? '';
          _bioCtrl.text = user['bio'] ?? '';
          _rememberContactInfo = user['remember_contact_info'] == true || user['remember_contact_info'] == 1;
        });
      }

      if (listRes.statusCode == 200) {
        final lData = jsonDecode(listRes.body);
        setState(() {
          _myListings = lData['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint('Dashboard error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _updateProfile() async {
    try {
      final res = await http.put(
        Uri.parse('${AppConfig.baseUrl}/user/profile'),
        headers: AppState.authHeaders,
        body: jsonEncode({
          'name': _nameCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
          'whatsapp': _whatsappCtrl.text.trim(),
          'bio': _bioCtrl.text.trim(),
          'remember_contact_info': _rememberContactInfo,
        }),
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        AppState.user = data['user'];
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('تم حفظ بياناتك وتفضيلات "تذكر معلوماتي" بنجاح')),
          );
        }
      }
    } catch (e) {
      debugPrint('Profile error: $e');
    }
  }

  Future<void> _toggleSold(int id) async {
    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/listings/$id/sold'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        _loadDashboard();
      }
    } catch (e) {
      debugPrint('Toggle error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('لوحة تحكم المستخدم')),
        body: Center(child: CircularProgressIndicator(color: AppColors.maroon)),
      );
    }

    final user = _dashboardData?['user'] ?? AppState.user ?? {};
    final counts = _dashboardData?['counts'] ?? {};
    final followingSellers = _dashboardData?['following_sellers'] as List<dynamic>? ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('لوحة تحكم المستخدم'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            tooltip: 'تسجيل الخروج',
            onPressed: () {
              AppState.token = null;
              AppState.user = null;
              Navigator.pop(context);
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // User Card Profile Header
          Container(
            padding: const EdgeInsets.all(16),
            color: Colors.white,
            child: Row(
              children: [
                CircleAvatar(
                  radius: 30,
                  backgroundColor: AppColors.maroon,
                  backgroundImage: user['avatar_url'] != null ? NetworkImage(user['avatar_url']) : null,
                  child: user['avatar_url'] == null
                      ? Text(user['name']?[0] ?? 'U', style: const TextStyle(fontSize: 22, color: Colors.white))
                      : null,
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(user['name'] ?? '', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
                          const SizedBox(width: 6),
                          const Icon(Icons.verified, color: Colors.blue, size: 16),
                        ],
                      ),
                      Text(user['email'] ?? '', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                      Text(user['phone'] ?? '', style: const TextStyle(fontSize: 11, color: Colors.black87)),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // Stats Quick Grid
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            color: Colors.grey.shade100,
            child: Row(
              children: [
                _buildStatItem('إعلاناتي', '${counts['total_listings'] ?? 0}', Icons.inventory_2_outlined),
                _buildStatItem('تم بيعها', '${counts['sold_listings'] ?? 0}', Icons.check_circle_outline, color: AppColors.emerald),
                _buildStatItem('طلبات شراء', '${counts['wanted_listings'] ?? 0}', Icons.search_rounded, color: AppColors.purple),
                _buildStatItem('أتابعهم', '${counts['following_count'] ?? 0}', Icons.people_outline, color: AppColors.blue),
              ],
            ),
          ),

          // Tab Bar
          TabBar(
            controller: _tabController,
            labelColor: AppColors.maroon,
            indicatorColor: AppColors.maroon,
            unselectedLabelColor: Colors.grey,
            tabs: const [
              Tab(text: 'إعلاناتي ومبيعاتي'),
              Tab(text: 'الحسابات المتابعة'),
              Tab(text: 'تذكر معلوماتي'),
            ],
          ),

          // Tab Views
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                // Tab 1: My Listings & Sales
                _myListings.isEmpty
                    ? const Center(child: Text('لا توجد إعلانات منشورة باسمك حتى الآن'))
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _myListings.length,
                        itemBuilder: (context, idx) {
                          final item = _myListings[idx];
                          final isSold = item['is_sold'] == true;
                          return Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: Colors.grey.shade200),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(item['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                      Text('${item['price']} ${item['currency']}', style: TextStyle(color: AppColors.maroon, fontWeight: FontWeight.bold)),
                                      const SizedBox(height: 4),
                                      Text(
                                        isSold ? 'الحالة: تم البيع وإغلاق العرض ✅' : 'الحالة: معروض نشط 🟢',
                                        style: TextStyle(fontSize: 11, color: isSold ? Colors.red : Colors.green),
                                      ),
                                    ],
                                  ),
                                ),
                                ElevatedButton(
                                  onPressed: () => _toggleSold(item['id']),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: isSold ? AppColors.emerald : Colors.brown,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                  ),
                                  child: Text(isSold ? 'إعادة العرض' : 'تمييز كـ (تم البيع)', style: const TextStyle(fontSize: 10)),
                                ),
                              ],
                            ),
                          );
                        },
                      ),

                // Tab 2: Following Sellers
                followingSellers.isEmpty
                    ? const Center(child: Text('أنت لا تتابع أي حسابات حالياً'))
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: followingSellers.length,
                        itemBuilder: (context, idx) {
                          final s = followingSellers[idx];
                          return ListTile(
                            leading: CircleAvatar(
                              backgroundColor: AppColors.maroon,
                              foregroundColor: Colors.white,
                              child: Text(s['name']?[0] ?? 'S'),
                            ),
                            title: Text(s['name'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Text('${s['listings_count'] ?? 0} إعلان منشور'),
                            trailing: TextButton(
                              onPressed: () async {
                                await http.post(
                                  Uri.parse('${AppConfig.baseUrl}/sellers/${s['id']}/toggle-follow'),
                                  headers: AppState.authHeaders,
                                );
                                _loadDashboard();
                              },
                              child: const Text('إلغاء المتابعة', style: TextStyle(color: Colors.red, fontSize: 11)),
                            ),
                          );
                        },
                      ),

                // Tab 3: Remember Contact Info Settings
                ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.blue.shade200),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.bolt_rounded, color: Colors.blue, size: 28),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('خاصية "تذكر معلوماتي"', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                Text(
                                  'عند تفعيلها، يتم تعبئة رقم هاتفك وعنوانك السوري تلقائياً عند إضافة أي إعلان جديد لتوفير وقتك!',
                                  style: TextStyle(fontSize: 11, color: Colors.grey.shade800),
                                ),
                              ],
                            ),
                          ),
                          Switch(
                            value: _rememberContactInfo,
                            activeThumbColor: AppColors.maroon,
                            onChanged: (val) => setState(() => _rememberContactInfo = val),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _nameCtrl,
                      decoration: InputDecoration(
                        labelText: 'الاسم الكامل',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _phoneCtrl,
                      decoration: InputDecoration(
                        labelText: 'رقم الهاتف للتواصل السريع',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _whatsappCtrl,
                      decoration: InputDecoration(
                        labelText: 'رقم الواتساب',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _bioCtrl,
                      maxLines: 2,
                      decoration: InputDecoration(
                        labelText: 'نبذة عنك / اسم معرضك أو شركتك',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _updateProfile,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.maroon,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      child: const Text('حفظ التعديلات والتفضيلات', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem(String label, String count, IconData icon, {Color? color}) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 4),
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Column(
          children: [
            Icon(icon, size: 16, color: color ?? AppColors.maroon),
            const SizedBox(height: 2),
            Text(count, style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14)),
            Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Notifications Screen
// ---------------------------------------------------------------------------------
class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('التنبيهات والإشعارات')),
      body: const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.notifications_active_outlined, size: 56, color: AppColors.amber),
              SizedBox(height: 12),
              Text('تنبيهات السلع والمتابعات', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
              SizedBox(height: 6),
              Text(
                'عند متابعتك لأي بائع، ستصلك إشعارات فورية بكل سلعة جديدة يقوم بنشرها مباشرة هنا.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey, fontSize: 12),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Auth Dialog with Google Sign-In & Email/Password
// ---------------------------------------------------------------------------------
class AuthDialog extends StatefulWidget {
  const AuthDialog({super.key});

  @override
  State<AuthDialog> createState() => _AuthDialogState();
}

class _AuthDialogState extends State<AuthDialog> {
  bool _isRegister = false;
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _loading = false;

  Future<void> _submit() async {
    setState(() => _loading = true);
    try {
      final endpoint = _isRegister ? 'register' : 'login';
      final payload = _isRegister
          ? {
              'name': _nameCtrl.text.trim(),
              'email': _emailCtrl.text.trim(),
              'phone': _phoneCtrl.text.trim(),
              'password': _passCtrl.text.trim(),
            }
          : {
              'email': _emailCtrl.text.trim(),
              'password': _passCtrl.text.trim(),
            };

      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/$endpoint'),
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      );

      final data = jsonDecode(res.body);
      if (res.statusCode == 200 || res.statusCode == 201) {
        AppState.token = data['token'];
        AppState.user = data['user'];
        if (mounted) Navigator.pop(context);
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'فشل الدخول')),
          );
        }
      }
    } catch (e) {
      debugPrint('Auth error: $e');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _handleGoogleSignIn() async {
    setState(() => _loading = true);
    final success = await AppState.googleLogin();
    if (mounted) {
      setState(() => _loading = false);
      if (success) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تم تسجيل الدخول بنجاح عبر حساب Google')),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر تسجيل الدخول عبر Google')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                _isRegister ? 'إنشاء حساب جديد' : 'تسجيل الدخول',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 16),

              // Google Sign-In Button
              OutlinedButton.icon(
                onPressed: _loading ? null : _handleGoogleSignIn,
                icon: Image.network(
                  'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_%22G%22_logo.svg/480px-Google_%22G%22_logo.svg.png',
                  height: 18,
                  errorBuilder: (_, _, _) => const Icon(Icons.g_mobiledata, color: Colors.blue),
                ),
                label: const Text(
                  'المتابعة باستخدام Google',
                  style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black87),
                ),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(42),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  side: BorderSide(color: Colors.grey.shade300),
                ),
              ),

              const SizedBox(height: 14),

              Row(
                children: [
                  Expanded(child: Divider(color: Colors.grey.shade300)),
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 8),
                    child: Text('أو عبر البريد الإلكتروني', style: TextStyle(fontSize: 11, color: Colors.grey)),
                  ),
                  Expanded(child: Divider(color: Colors.grey.shade300)),
                ],
              ),

              const SizedBox(height: 14),

              if (_isRegister) ...[
                TextField(
                  controller: _nameCtrl,
                  decoration: InputDecoration(
                    labelText: 'الاسم الكامل',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _phoneCtrl,
                  decoration: InputDecoration(
                    labelText: 'رقم الهاتف',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
                const SizedBox(height: 10),
              ],
              TextField(
                controller: _emailCtrl,
                decoration: InputDecoration(
                  labelText: 'البريد الإلكتروني',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _passCtrl,
                obscureText: true,
                decoration: InputDecoration(
                  labelText: 'كلمة المرور',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _loading ? null : _submit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.maroon,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(42),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: _loading
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : Text(_isRegister ? 'إنشاء الحساب' : 'دخول'),
              ),
              TextButton(
                onPressed: () => setState(() => _isRegister = !_isRegister),
                child: Text(_isRegister ? 'لديك حساب بالفعل؟ سجل دخولك' : 'ليس لديك حساب؟ إنشاء حساب جديد'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
