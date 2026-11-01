import 'dart:async';
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
  static const Color maroon = Color(0xFF8B1D30); // Primary brand color
  static const Color maroonDark = Color(0xFF5E0F1C);
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

  static Future<void> logout() async {
    try {
      if (token != null) {
        await http.post(
          Uri.parse('${AppConfig.baseUrl}/logout'),
          headers: authHeaders,
        );
      }
    } catch (_) {}
    token = null;
    user = null;
    followedSellers.clear();
  }
}

// ---------------------------------------------------------
// Banner Item Model & 5-Second Auto-Slide Carousel
// ---------------------------------------------------------
class BannerItem {
  final int id;
  final String title;
  final String? subtitle;
  final String? badgeText;
  final String imageUrl;
  final String linkType; // 'listing', 'external', 'none'
  final int? listingId;
  final String? externalUrl;

  BannerItem({
    required this.id,
    required this.title,
    this.subtitle,
    this.badgeText,
    required this.imageUrl,
    required this.linkType,
    this.listingId,
    this.externalUrl,
  });

  factory BannerItem.fromJson(Map<String, dynamic> json) {
    return BannerItem(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      subtitle: json['subtitle'],
      badgeText: json['badge_text'] ?? 'إعلان ممول ⭐',
      imageUrl: AppConfig.resolveUrl(json['image_path']),
      linkType: json['link_type'] ?? 'none',
      listingId: json['listing_id'] is int ? json['listing_id'] : int.tryParse(json['listing_id']?.toString() ?? ''),
      externalUrl: json['external_url'],
    );
  }
}

class BannerCarousel extends StatefulWidget {
  final List<BannerItem> banners;
  const BannerCarousel({super.key, required this.banners});

  @override
  State<BannerCarousel> createState() => _BannerCarouselState();
}

class _BannerCarouselState extends State<BannerCarousel> {
  late final PageController _pageController;
  int _currentIndex = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    if (widget.banners.length > 1) {
      _timer = Timer.periodic(const Duration(seconds: 5), (_) {
        if (!mounted || widget.banners.isEmpty) return;
        final next = (_currentIndex + 1) % widget.banners.length;
        _pageController.animateToPage(
          next,
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeInOut,
        );
      });
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  void _onBannerTap(BannerItem banner) {
    if (banner.linkType == 'listing' && banner.listingId != null) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => ListingDetailScreen(listingId: banner.listingId!),
        ),
      );
    } else if (banner.linkType == 'external' && banner.externalUrl != null) {
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.open_in_new, color: Color(0xFF8B1D30)),
              SizedBox(width: 8),
              Text('إعلان خارجي ممول', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(banner.title, style: const TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              const Text('رابط الوجهة الخارجية:', style: TextStyle(fontSize: 12, color: Colors.grey)),
              SelectableText(
                banner.externalUrl!,
                style: const TextStyle(color: Colors.blue, fontSize: 12),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إغلاق'),
            ),
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.banners.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: SizedBox(
        height: 165,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: Stack(
            children: [
              PageView.builder(
                controller: _pageController,
                itemCount: widget.banners.length,
                onPageChanged: (i) => setState(() => _currentIndex = i),
                itemBuilder: (context, index) {
                  final b = widget.banners[index];
                  return GestureDetector(
                    onTap: () => _onBannerTap(b),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.network(
                          b.imageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => Container(
                            color: AppColors.maroonDark,
                            child: const Icon(Icons.campaign_rounded, color: Colors.white, size: 48),
                          ),
                        ),
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.transparent,
                                Colors.black.withValues(alpha: 0.85),
                              ],
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: 12,
                          right: 14,
                          left: 14,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (b.badgeText != null && b.badgeText!.isNotEmpty)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  margin: const EdgeInsets.only(bottom: 4),
                                  decoration: BoxDecoration(
                                    color: AppColors.amber,
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    b.badgeText!,
                                    style: const TextStyle(
                                      color: Colors.black87,
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              Text(
                                b.title,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w900,
                                  fontSize: 15,
                                ),
                              ),
                              if (b.subtitle != null && b.subtitle!.isNotEmpty)
                                Text(
                                  b.subtitle!,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white70,
                                    fontSize: 11,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
              if (widget.banners.length > 1)
                Positioned(
                  bottom: 8,
                  left: 0,
                  right: 0,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      widget.banners.length,
                      (i) => AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        margin: const EdgeInsets.symmetric(horizontal: 2.5),
                        width: _currentIndex == i ? 18 : 6,
                        height: 6,
                        decoration: BoxDecoration(
                          color: _currentIndex == i ? AppColors.amber : Colors.white.withValues(alpha: 0.5),
                          borderRadius: BorderRadius.circular(3),
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
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
  List<BannerItem> _banners = [];
  bool _isLoading = true;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  final FocusNode _searchFocusNode = FocusNode();

  // Filters
  String _selectedCondition = ''; // '' = all, 'new', 'used'
  String _selectedListingType = ''; // '' = all, 'sale', 'wanted', 'rent', 'rent_wanted'
  String _selectedCategorySlug = '';
  int? _selectedGovernorateId;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchInitialData();
  }

  @override
  void dispose() {
    _searchFocusNode.dispose();
    _searchController.dispose();
    super.dispose();
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

      // 2. Fetch banners
      try {
        final banRes = await http.get(Uri.parse('${AppConfig.baseUrl}/banners'));
        if (banRes.statusCode == 200) {
          final bData = jsonDecode(banRes.body)['data'] as List<dynamic>? ?? [];
          _banners = bData.map((e) => BannerItem.fromJson(e)).toList();
        }
      } catch (e) {
        debugPrint('Banners fetch error: $e');
      }

      // 3. Fetch categories
      final catRes = await http.get(Uri.parse('${AppConfig.baseUrl}/categories'));
      if (catRes.statusCode == 200) {
        final data = jsonDecode(catRes.body);
        _categories = data['data'] ?? [];
      }

      // 4. Fetch locations
      final locRes = await http.get(Uri.parse('${AppConfig.baseUrl}/locations'));
      if (locRes.statusCode == 200) {
        final data = jsonDecode(locRes.body);
        _governorates = data['data'] ?? [];
      }

      // 5. Fetch listings
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

  void _showMoreMenu() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => MoreMenuSheet(
        onStateChange: () {
          setState(() {});
          _fetchInitialData();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      drawer: CategoriesDrawer(
        categories: _categories,
        onSelectCategory: (cat, subId) {
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
      ),
      appBar: AppBar(
        leading: Builder(
          builder: (ctx) => IconButton(
            icon: const Icon(Icons.menu_rounded, size: 28),
            tooltip: 'الأقسام والفروع',
            onPressed: () => Scaffold.of(ctx).openDrawer(),
          ),
        ),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(5),
              decoration: BoxDecoration(
                color: Colors.white24,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.gavel_rounded, color: AppColors.amber, size: 18),
            ),
            const SizedBox(width: 8),
            Text(
              AppConfig.siteName,
              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 17),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_outline_rounded),
            tooltip: 'حسابي',
            onPressed: _openUserAccount,
          ),
          IconButton(
            icon: const Icon(Icons.search_rounded),
            tooltip: 'بحث',
            onPressed: () {
              _searchFocusNode.requestFocus();
            },
          ),
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
                        focusNode: _searchFocusNode,
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

                  // Quick Filter Segmented Chips (الكل | للبيع | مطلوب | تأجير | وظائف | خدمات)
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Row(
                      children: [
                        _buildQuickTypeChip('🌟 كافة العروض', '', ''),
                        _buildQuickTypeChip('🏷️ معروض للبيع', 'sale', ''),
                        _buildQuickTypeChip('🔍 مطلوب للشراء', 'wanted', ''),
                        _buildQuickTypeChip('🔑 معروض للإيجار', 'rent', ''),
                        _buildQuickTypeChip('🏠 مطلوب للاستئجار', 'rent_wanted', ''),
                        _buildQuickTypeChip('💼 فرص عمل ووظائف', '', 'services-jobs'),
                        _buildQuickTypeChip('✨ جديد بالكرتونة', '', '', condition: 'new'),
                        _buildQuickTypeChip('🔄 مستعمل', '', '', condition: 'used'),
                      ],
                    ),
                  ),

                  // Sponsored Banners 5-Second Slider
                  if (_banners.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    BannerCarousel(banners: _banners),
                  ],

                  const SizedBox(height: 12),

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
          if (index == 0) {
            setState(() {
              _currentBottomNavIndex = 0;
              _selectedListingType = '';
              _selectedCategorySlug = '';
              _selectedCondition = '';
              _searchController.clear();
            });
            _fetchListings();
          } else if (index == 1) {
            _scaffoldKey.currentState?.openDrawer();
          } else if (index == 2) {
            Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const CreateListingScreen()),
            ).then((_) => _fetchListings());
          } else if (index == 3) {
            if (AppState.isLoggedIn) {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const MyListingsScreen()),
              ).then((_) => _fetchListings());
            } else {
              _showAuthDialog();
            }
          } else if (index == 4) {
            _showMoreMenu();
          }
        },
        items: [
          const BottomNavigationBarItem(icon: Icon(Icons.home_filled), label: 'الرئيسية'),
          const BottomNavigationBarItem(icon: Icon(Icons.grid_view_rounded), label: 'الأقسام'),
          BottomNavigationBarItem(
            icon: Container(
              padding: const EdgeInsets.all(5),
              decoration: BoxDecoration(
                color: AppColors.maroon,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: AppColors.maroon.withValues(alpha: 0.35),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: const Icon(Icons.add, color: Colors.white, size: 20),
            ),
            label: 'أضف إعلان',
          ),
          const BottomNavigationBarItem(icon: Icon(Icons.inventory_2_outlined), label: 'إعلاناتي'),
          const BottomNavigationBarItem(icon: Icon(Icons.more_horiz_rounded), label: 'المزيد'),
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
    final isRent = item['listing_type'] == 'rent';
    final isRentWanted = item['listing_type'] == 'rent_wanted';
    final isSold = item['is_sold'] == true;
    final isFeatured = item['is_featured'] == true;
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
                  // Badge: Featured / Rent / Wanted / Sale / Sold
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                      decoration: BoxDecoration(
                        color: isSold
                            ? Colors.black87
                            : (isFeatured
                                ? AppColors.amber
                                : (isWanted
                                    ? AppColors.purple
                                    : (isRent
                                        ? Colors.teal
                                        : (isRentWanted
                                            ? Colors.indigo
                                            : (isNew ? AppColors.emerald : AppColors.blue))))),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isSold
                            ? 'تم البيع'
                            : (isFeatured
                                ? 'VIP ⭐'
                                : (isWanted
                                    ? 'مطلوب'
                                    : (isRent
                                        ? 'للإيجار'
                                        : (isRentWanted
                                            ? 'مطلوب إيجار'
                                            : (isNew ? 'جديد' : 'مستعمل'))))),
                        style: TextStyle(
                          color: isFeatured ? Colors.black87 : Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                        ),
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
    final isRent = item['listing_type'] == 'rent';
    final isRentWanted = item['listing_type'] == 'rent_wanted';
    final isSold = item['is_sold'] == true;
    final isFeatured = item['is_featured'] == true;
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
                        color: isSold
                            ? Colors.black87
                            : (isFeatured
                                ? AppColors.amber
                                : (isWanted
                                    ? AppColors.purple
                                    : (isRent
                                        ? Colors.teal
                                        : (isRentWanted
                                            ? Colors.indigo
                                            : (isNew ? AppColors.emerald : AppColors.blue))))),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isSold
                            ? 'تم البيع'
                            : (isFeatured
                                ? 'VIP ⭐'
                                : (isWanted
                                    ? 'مطلوب'
                                    : (isRent
                                        ? 'للإيجار'
                                        : (isRentWanted
                                            ? 'مطلوب إيجار'
                                            : (isNew ? 'جديد' : 'مستعمل'))))),
                        style: TextStyle(
                          color: isFeatured ? Colors.black87 : Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                        ),
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
  bool _isFavorited = false;
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
          _isFavorited = data['is_favorited'] ?? false;
        });
      }
    } catch (e) {
      debugPrint('Error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleFavorite() async {
    if (!AppState.isLoggedIn) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تسجيل الدخول أولاً لحفظ السلعة في المفضلة')),
      );
      return;
    }

    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/listings/${widget.listingId}/favorite'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _isFavorited = data['is_favorited'] ?? !_isFavorited;
        });
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(data['message'] ?? 'تم تحديث المفضلة')),
          );
        }
      }
    } catch (e) {
      debugPrint('Favorite error: $e');
    }
  }

  Future<void> _deleteListing() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تأكيد حذف الإعلان', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: const Text('هل أنت متأكد من حذف هذا الإعلان نهائياً؟ لا يمكن التراجع عن هذه الخطوة.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('حذف نهائي'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final res = await http.delete(
          Uri.parse('${AppConfig.baseUrl}/listings/${widget.listingId}'),
          headers: AppState.authHeaders,
        );
        if (res.statusCode == 200) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('تم حذف الإعلان بنجاح')),
            );
            Navigator.pop(context, true);
          }
        }
      } catch (e) {
        debugPrint('Delete error: $e');
      }
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
        actions: [
          IconButton(
            icon: Icon(
              _isFavorited ? Icons.favorite_rounded : Icons.favorite_border_rounded,
              color: _isFavorited ? Colors.redAccent : Colors.white,
            ),
            tooltip: _isFavorited ? 'إزالة من المفضلة' : 'حفظ في المفضلة',
            onPressed: _toggleFavorite,
          ),
          if (isOwner)
            IconButton(
              icon: const Icon(Icons.edit_rounded),
              tooltip: 'تعديل الإعلان',
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => EditListingScreen(listing: _listing!)),
                ).then((_) => _fetchListingDetails());
              },
            ),
        ],
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
                // Tags Row: VIP Badge + Type (للبيع / مطلوب / تأجير) + Condition + Jobs
                Row(
                  children: [
                    if (_listing!['is_featured'] == true) ...[
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppColors.amber,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.workspace_premium, size: 13, color: Colors.black87),
                            SizedBox(width: 3),
                            Text('مميز VIP', style: TextStyle(color: Colors.black87, fontSize: 11, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                      const SizedBox(width: 6),
                    ],
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: _listing!['listing_type'] == 'wanted'
                            ? AppColors.purple
                            : (_listing!['listing_type'] == 'rent'
                                ? Colors.teal
                                : (_listing!['listing_type'] == 'rent_wanted'
                                    ? Colors.indigo
                                    : AppColors.maroon)),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        _listing!['listing_type'] == 'wanted'
                            ? '🔍 مطلوب للشراء'
                            : (_listing!['listing_type'] == 'rent'
                                ? '🔑 معروض للإيجار'
                                : (_listing!['listing_type'] == 'rent_wanted'
                                    ? '🏠 مطلوب للاستئجار'
                                    : '🏷️ معروض للبيع')),
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

                // Owner Controls: Full Action Box (Toggle Sold, Edit, Delete)
                if (isOwner) ...[
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.amber.shade50,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Colors.amber.shade300),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Row(
                          children: [
                            Icon(Icons.admin_panel_settings_rounded, color: AppColors.maroon, size: 18),
                            SizedBox(width: 6),
                            Text(
                              'لوحة تحكم صاحب الإعلان (إجراءات الإعلان)',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.maroon),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Expanded(
                              flex: 2,
                              child: ElevatedButton.icon(
                                onPressed: _toggleSoldStatus,
                                icon: Icon(isSold ? Icons.replay_rounded : Icons.check_circle_outline_rounded, size: 15),
                                label: Text(isSold ? 'إعادة العرض' : 'تمييز كـ تم البيع', style: const TextStyle(fontSize: 11)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: isSold ? AppColors.emerald : Colors.brown,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 8),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                ),
                              ),
                            ),
                            const SizedBox(width: 6),
                            Expanded(
                              flex: 2,
                              child: ElevatedButton.icon(
                                onPressed: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(builder: (_) => EditListingScreen(listing: _listing!)),
                                  ).then((_) => _fetchListingDetails());
                                },
                                icon: const Icon(Icons.edit_rounded, size: 15),
                                label: const Text('تعديل الإعلان', style: TextStyle(fontSize: 11)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppColors.blue,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(vertical: 8),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                ),
                              ),
                            ),
                            const SizedBox(width: 6),
                            IconButton(
                              onPressed: _deleteListing,
                              icon: const Icon(Icons.delete_forever_rounded, color: Colors.red),
                              tooltip: 'حذف الإعلان نهائياً',
                              style: IconButton.styleFrom(
                                backgroundColor: Colors.red.shade100,
                              ),
                            ),
                          ],
                        ),
                      ],
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

  String _listingType = 'sale'; // 'sale', 'wanted', 'rent', 'rent_wanted'
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
  final List<String> _selectedImages = [
    'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=600&q=80',
  ];
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
        title: Text(
          _listingType == 'wanted'
              ? 'أضف طلب شراء (مطلوب)'
              : (_listingType == 'rent'
                  ? 'أضف عقار أو سلعة للإيجار'
                  : (_listingType == 'rent_wanted'
                      ? 'أضف طلب استئجار'
                      : 'أضف سلعتك للبيع في سوريا')),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Mode Toggle: للبيع / مطلوب للشراء / معروض للإيجار / مطلوب للاستئجار
          const Text('نوع الإعلان والغرض *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                child: _buildListingTypeChip('🏷️ معروض للبيع', 'sale', AppColors.maroon),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: _buildListingTypeChip('🔍 مطلوب للشراء', 'wanted', AppColors.purple),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                child: _buildListingTypeChip('🔑 معروض للإيجار', 'rent', Colors.teal),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: _buildListingTypeChip('🏠 مطلوب للاستئجار', 'rent_wanted', Colors.indigo),
              ),
            ],
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
          const SizedBox(height: 16),

          // Interactive Image Manager with 'X' Remove Button
          const Text('صور السلعة والمعاينة (اضغط X لإزالة أي صورة) *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade300),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (_selectedImages.isNotEmpty)
                  SizedBox(
                    height: 95,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      itemCount: _selectedImages.length,
                      itemBuilder: (context, idx) {
                        final url = _selectedImages[idx];
                        return Container(
                          width: 85,
                          margin: const EdgeInsets.only(left: 10),
                          child: Stack(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: Image.network(
                                  url,
                                  width: 85,
                                  height: 85,
                                  fit: BoxFit.cover,
                                  errorBuilder: (context, error, stackTrace) => Container(
                                    width: 85,
                                    height: 85,
                                    color: Colors.grey.shade200,
                                    child: const Icon(Icons.image, color: Colors.grey),
                                  ),
                                ),
                              ),
                              // 'X' Remove button
                              Positioned(
                                top: 2,
                                left: 2,
                                child: InkWell(
                                  onTap: () {
                                    setState(() {
                                      _selectedImages.removeAt(idx);
                                    });
                                  },
                                  child: Container(
                                    padding: const EdgeInsets.all(3),
                                    decoration: const BoxDecoration(
                                      color: Colors.red,
                                      shape: BoxShape.circle,
                                    ),
                                    child: const Icon(Icons.close_rounded, color: Colors.white, size: 14),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  )
                else
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 8),
                    child: Text('لم يتم إضافة أي صور بعد. أضف صورة أو أكثر لزيادة سرعة بيع سلعتك.', style: TextStyle(fontSize: 11, color: Colors.grey)),
                  ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: _showAddImageDialog,
                  icon: const Icon(Icons.add_photo_alternate_rounded, size: 16),
                  label: const Text('إضافة صورة للسلعة', style: TextStyle(fontSize: 12)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.maroon,
                    side: BorderSide(color: AppColors.maroon),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ],
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
                    _listingType == 'wanted'
                        ? 'نشر طلب الشراء الآن'
                        : (_listingType == 'rent'
                            ? 'نشر عرض الإيجار الآن'
                            : (_listingType == 'rent_wanted'
                                ? 'نشر طلب الاستئجار الآن'
                                : 'نشر السلعة للبيع الآن')),
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildListingTypeChip(String label, String value, Color color) {
    final bool isSelected = _listingType == value;
    return InkWell(
      onTap: () => setState(() => _listingType = value),
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
        decoration: BoxDecoration(
          color: isSelected ? color : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: isSelected ? color : Colors.grey.shade300, width: isSelected ? 1.5 : 1),
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            color: isSelected ? Colors.white : Colors.black87,
          ),
        ),
      ),
    );
  }

  void _showAddImageDialog() {
    final ctrl = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('إضافة صورة للسلعة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('أدخل رابط الصورة أو اختر من النماذج:', style: TextStyle(fontSize: 12)),
            const SizedBox(height: 8),
            TextField(
              controller: ctrl,
              decoration: InputDecoration(
                hintText: 'https://example.com/image.jpg',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              ),
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 6,
              children: [
                ActionChip(
                  label: const Text('🚗 صورة سيارة', style: TextStyle(fontSize: 10)),
                  onPressed: () {
                    ctrl.text = 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=600&q=80';
                  },
                ),
                ActionChip(
                  label: const Text('🏠 صورة عقار', style: TextStyle(fontSize: 10)),
                  onPressed: () {
                    ctrl.text = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=80';
                  },
                ),
                ActionChip(
                  label: const Text('📱 صورة هاتف', style: TextStyle(fontSize: 10)),
                  onPressed: () {
                    ctrl.text = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=600&q=80';
                  },
                ),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () {
              if (ctrl.text.trim().isNotEmpty) {
                setState(() => _selectedImages.add(ctrl.text.trim()));
              }
              Navigator.pop(ctx);
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.maroon, foregroundColor: Colors.white),
            child: const Text('إضافة'),
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

  Future<void> _deleteListing(int id) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تأكيد حذف الإعلان', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: const Text('هل أنت متأكد من حذف هذا الإعلان نهائياً من حسابك؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('حذف'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final res = await http.delete(
          Uri.parse('${AppConfig.baseUrl}/listings/$id'),
          headers: AppState.authHeaders,
        );
        if (res.statusCode == 200) {
          _loadDashboard();
        }
      } catch (e) {
        debugPrint('Delete error: $e');
      }
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
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(item['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                          Text('${item['price']} ${item['currency'] == 'USD' ? '\$' : 'ل.س'}', style: TextStyle(color: AppColors.maroon, fontWeight: FontWeight.bold)),
                                          const SizedBox(height: 4),
                                          Text(
                                            isSold ? 'الحالة: تم البيع وإغلاق العرض ✅' : 'الحالة: معروض نشط 🟢',
                                            style: TextStyle(fontSize: 11, color: isSold ? Colors.red : Colors.green),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                                const Divider(height: 16),
                                Row(
                                  children: [
                                    Expanded(
                                      child: ElevatedButton(
                                        onPressed: () => _toggleSold(item['id']),
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: isSold ? AppColors.emerald : Colors.brown,
                                          foregroundColor: Colors.white,
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                        ),
                                        child: Text(isSold ? 'إعادة العرض' : 'تمييز تم البيع', style: const TextStyle(fontSize: 10)),
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: ElevatedButton(
                                        onPressed: () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(builder: (_) => EditListingScreen(listing: item)),
                                          ).then((_) => _loadDashboard());
                                        },
                                        style: ElevatedButton.styleFrom(
                                          backgroundColor: AppColors.blue,
                                          foregroundColor: Colors.white,
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                        ),
                                        child: const Text('تعديل الإعلان', style: TextStyle(fontSize: 10)),
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 18),
                                      tooltip: 'حذف الإعلان',
                                      onPressed: () => _deleteListing(item['id']),
                                    ),
                                  ],
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


// ---------------------------------------------------------------------------------
// Categories Drawer with Real-Time Search & Subcategory Accordion
// ---------------------------------------------------------------------------------
class CategoriesDrawer extends StatefulWidget {
  final List<dynamic> categories;
  final Function(Map<String, dynamic> category, int? subId) onSelectCategory;

  const CategoriesDrawer({
    super.key,
    required this.categories,
    required this.onSelectCategory,
  });

  @override
  State<CategoriesDrawer> createState() => _CategoriesDrawerState();
}

class _CategoriesDrawerState extends State<CategoriesDrawer> {
  final TextEditingController _searchCtrl = TextEditingController();
  String _filter = '';

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final filteredCategories = widget.categories.where((cat) {
      if (_filter.isEmpty) return true;
      final name = (cat['name_ar'] ?? '').toString().toLowerCase();
      if (name.contains(_filter)) return true;
      final subs = cat['subcategories'] as List<dynamic>? ?? [];
      return subs.any((s) => (s['name_ar'] ?? '').toString().toLowerCase().contains(_filter));
    }).toList();

    return Drawer(
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(16, 45, 16, 16),
            color: AppColors.maroon,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: Colors.white24,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.grid_view_rounded, color: AppColors.amber, size: 22),
                        ),
                        const SizedBox(width: 10),
                        const Text(
                          'أقسام وفروع مزاد سوريا',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w900, fontSize: 16),
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, color: Colors.white),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: TextField(
                    controller: _searchCtrl,
                    onChanged: (val) => setState(() => _filter = val.trim().toLowerCase()),
                    decoration: const InputDecoration(
                      hintText: 'ابحث عن قسم أو فرع محدد...',
                      hintStyle: TextStyle(fontSize: 12, color: Colors.grey),
                      prefixIcon: Icon(Icons.search_rounded, color: Colors.grey, size: 18),
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(vertical: 6),
              itemCount: filteredCategories.length,
              itemBuilder: (context, index) {
                final cat = filteredCategories[index];
                final subs = cat['subcategories'] as List<dynamic>? ?? [];
                return ExpansionTile(
                  leading: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: AppColors.maroon.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.folder_outlined, color: AppColors.maroon, size: 20),
                  ),
                  title: Text(
                    cat['name_ar'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                  ),
                  children: [
                    ListTile(
                      contentPadding: const EdgeInsets.only(right: 36, left: 16),
                      title: const Text('كافة إعلانات هذا القسم', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.maroon)),
                      trailing: const Icon(Icons.arrow_back_ios_new, size: 12, color: AppColors.maroon),
                      onTap: () {
                        Navigator.pop(context);
                        widget.onSelectCategory(cat, null);
                      },
                    ),
                    ...subs.map((s) {
                      return ListTile(
                        contentPadding: const EdgeInsets.only(right: 36, left: 16),
                        title: Text(s['name_ar'] ?? '', style: const TextStyle(fontSize: 12)),
                        trailing: const Icon(Icons.arrow_back_ios_new, size: 12, color: Colors.grey),
                        onTap: () {
                          Navigator.pop(context);
                          widget.onSelectCategory(cat, s['id']);
                        },
                      );
                    }),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// More Menu Bottom Sheet (...)
// ---------------------------------------------------------------------------------
class MoreMenuSheet extends StatelessWidget {
  final VoidCallback onStateChange;

  const MoreMenuSheet({super.key, required this.onStateChange});

  void _showHelpDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.help_center_rounded, color: AppColors.maroon),
            SizedBox(width: 8),
            Text('مركز المساعدة والدعم', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('كيف أبيع أو أعرض سلعتي؟', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            SizedBox(height: 4),
            Text('اضغط على زر (+) أضف إعلان من الشريط السفلي، املأ المواصفات والسعر والمحافظة السورية والصور، ثم انشر فوراً.', style: TextStyle(fontSize: 12, color: Colors.black87)),
            Divider(height: 20),
            Text('كيف أتابع البائعين المفضلين؟', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            SizedBox(height: 4),
            Text('في صفحة أي سلعة، اضغط زر (متابعة صاحب الإعلان) لتصلك إشعارات وتنبيهات فور نشر أي جديد.', style: TextStyle(fontSize: 12, color: Colors.black87)),
            Divider(height: 20),
            Text('للتواصل مع فريق مزاد سوريا:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            SizedBox(height: 4),
            Text('واتساب: 963944000000+\nالبريد: support@mzad-syria.com', style: TextStyle(fontSize: 12, color: AppColors.maroon)),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إغلاق')),
        ],
      ),
    );
  }

  void _showLanguageDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => SimpleDialog(
        title: const Text('اختر لغة التطبيق / Choose Language', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
        children: [
          SimpleDialogOption(
            onPressed: () => Navigator.pop(ctx),
            child: const Row(
              children: [
                Icon(Icons.check_circle, color: AppColors.maroon, size: 18),
                SizedBox(width: 10),
                Text('العربية (سوريا)', style: TextStyle(fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          SimpleDialogOption(
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('تم تفعيل اللغة الإنجليزية')),
              );
            },
            child: const Row(
              children: [
                Icon(Icons.radio_button_unchecked, color: Colors.grey, size: 18),
                SizedBox(width: 10),
                Text('English (US)'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showShareDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.share_rounded, color: AppColors.maroon),
            SizedBox(width: 8),
            Text('مشاركة تطبيق مزاد سوريا', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('شارك رابط المنصة مع أصدقائك وتجار سوريا عبر واتساب أو تليغرام أو فيسبوك:', style: TextStyle(fontSize: 12)),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(8)),
              child: const SelectableText('https://mzad-syria.com/app', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue, fontSize: 12)),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('تم نسخ رابط التطبيق')),
              );
            },
            child: const Text('نسخ الرابط'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.maroon, foregroundColor: Colors.white),
            child: const Text('تم'),
          ),
        ],
      ),
    );
  }

  void _showPolicyDialog(BuildContext context, String title, String body) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        content: SingleChildScrollView(
          child: Text(body, style: const TextStyle(fontSize: 12, height: 1.6)),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إغلاق')),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
            ),
            const SizedBox(height: 16),

            // Profile banner or Login prompt
            InkWell(
              onTap: () {
                Navigator.pop(context);
                if (AppState.isLoggedIn) {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const UserDashboardScreen())).then((_) => onStateChange());
                } else {
                  showDialog(context: context, builder: (_) => const AuthDialog()).then((_) => onStateChange());
                }
              },
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [AppColors.maroon, AppColors.maroonDark],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 24,
                      backgroundColor: Colors.white24,
                      child: Icon(
                        AppState.isLoggedIn ? Icons.person_rounded : Icons.lock_open_rounded,
                        color: Colors.white,
                        size: 26,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppState.isLoggedIn ? (AppState.user?['name'] ?? 'حسابي الشخصي') : 'مرحباً بك في مزاد سوريا',
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            AppState.isLoggedIn ? 'عرض لوحة التحكم وإعلاناتك ومبيعاتك' : 'سجل الدخول لعرض سلعك وحفظ إعلاناتك المفضلة',
                            style: const TextStyle(color: Colors.white70, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                    const Icon(Icons.arrow_back_ios_new, color: Colors.white70, size: 14),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Quick Links Grid
            Row(
              children: [
                _buildQuickAction(
                  context,
                  icon: Icons.add_circle_outline_rounded,
                  color: AppColors.emerald,
                  title: 'أضف إعلان',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const CreateListingScreen())).then((_) => onStateChange());
                  },
                ),
                const SizedBox(width: 8),
                _buildQuickAction(
                  context,
                  icon: Icons.inventory_2_outlined,
                  color: AppColors.maroon,
                  title: 'إعلاناتي',
                  onTap: () {
                    Navigator.pop(context);
                    if (AppState.isLoggedIn) {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const MyListingsScreen())).then((_) => onStateChange());
                    } else {
                      showDialog(context: context, builder: (_) => const AuthDialog()).then((_) => onStateChange());
                    }
                  },
                ),
                const SizedBox(width: 8),
                _buildQuickAction(
                  context,
                  icon: Icons.workspace_premium_outlined,
                  color: AppColors.amber,
                  title: 'إعلانات مميزة',
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(context, MaterialPageRoute(builder: (_) => const FeaturedListingsScreen()));
                  },
                ),
                const SizedBox(width: 8),
                _buildQuickAction(
                  context,
                  icon: Icons.favorite_border_rounded,
                  color: Colors.redAccent,
                  title: 'المفضلة',
                  onTap: () {
                    Navigator.pop(context);
                    if (AppState.isLoggedIn) {
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const FavoritesScreen()));
                    } else {
                      showDialog(context: context, builder: (_) => const AuthDialog()).then((_) => onStateChange());
                    }
                  },
                ),
              ],
            ),

            const Divider(height: 28),

            // Menu Items
            _buildMenuItem(
              icon: Icons.chat_rounded,
              color: Colors.green.shade700,
              title: 'الدردشة مباشرة مع الدعم الفني',
              subtitle: 'تواصل عبر واتساب أو فريق خدمة عملاء مزاد',
              onTap: () => _showHelpDialog(context),
            ),
            _buildMenuItem(
              icon: Icons.help_outline_rounded,
              color: AppColors.blue,
              title: 'مركز المساعدة والأسئلة الشائعة',
              subtitle: 'دليل الاستخدام والأمان وطرق إتمام الصفقات',
              onTap: () => _showHelpDialog(context),
            ),
            _buildMenuItem(
              icon: Icons.translate_rounded,
              color: Colors.purple,
              title: 'تغيير اللغة (Language)',
              subtitle: 'العربية / English',
              onTap: () => _showLanguageDialog(context),
            ),
            _buildMenuItem(
              icon: Icons.share_rounded,
              color: Colors.teal,
              title: 'شارك البرنامج',
              subtitle: 'شارك التطبيق مع أصدقائك وزملائك',
              onTap: () => _showShareDialog(context),
            ),
            _buildMenuItem(
              icon: Icons.rule_rounded,
              color: Colors.orange.shade800,
              title: 'تعليمات وشروط الاستخدام',
              subtitle: 'القواعد والسلع المسموح بتداولها في سوريا',
              onTap: () => _showPolicyDialog(
                context,
                'تعليمات وشروط الاستخدام',
                'أهلاً بك في منصة مزاد سوريا.\n1. يمنع منعاً باتاً نشر أي بضائع محظورة أو غير قانونية.\n2. يتحمل المعلن المسؤولية القانونية الكاملة عن صحة معلومات السلعة.\n3. المعاينة والفحص الشخصي قبل الدفع هما الضمان الأساسي لحقوق المشتري.\n4. المنصة تتيح التواصل المباشر بين البائع والمشتري دون أي وسيط أو عمولة إجبارية.',
              ),
            ),
            _buildMenuItem(
              icon: Icons.security_rounded,
              color: Colors.blueGrey,
              title: 'سياسة الخصوصية وحماية البيانات',
              subtitle: 'كيف نحمي حسابك ومعلومات اتصالك بأعلى معايير الأمان',
              onTap: () => _showPolicyDialog(
                context,
                'سياسة الخصوصية وحماية البيانات',
                'نلتزم في منصة مزاد سوريا بحماية خصوصية بيانات المستخدمين:\n- لا نقوم ببيع أو مشاركة بياناتك الخاصة مع أي طرف ثالث.\n- يتم حماية كلمات المرور والاتصال وفق بروتوكولات الحماية المتقدمة.\n- بإمكانك تفعيل أو إلغاء خاصية "تذكر معلوماتي" في أي وقت من لوحة تحكم حسابك.',
              ),
            ),

            const SizedBox(height: 14),

            // Login / Logout Toggle Button
            ElevatedButton.icon(
              onPressed: () async {
                Navigator.pop(context);
                if (AppState.isLoggedIn) {
                  await AppState.logout();
                  onStateChange();
                } else {
                  showDialog(context: context, builder: (_) => const AuthDialog()).then((_) => onStateChange());
                }
              },
              icon: Icon(AppState.isLoggedIn ? Icons.logout_rounded : Icons.login_rounded),
              label: Text(AppState.isLoggedIn ? 'تسجيل الخروج من الحساب' : 'تسجيل الدخول / إنشاء حساب جديد'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppState.isLoggedIn ? Colors.red.shade700 : AppColors.maroon,
                foregroundColor: Colors.white,
                minimumSize: const Size.fromHeight(46),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickAction(
    BuildContext context, {
    required IconData icon,
    required Color color,
    required String title,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: color.withValues(alpha: 0.2)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, color: color, size: 24),
              const SizedBox(height: 6),
              Text(
                title,
                textAlign: TextAlign.center,
                style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 11),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(icon, color: color, size: 20),
      ),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 11, color: Colors.grey)),
      trailing: const Icon(Icons.arrow_back_ios_new, size: 14, color: Colors.grey),
      onTap: onTap,
    );
  }
}

// ---------------------------------------------------------------------------------
// Dedicated My Listings Screen
// ---------------------------------------------------------------------------------
class MyListingsScreen extends StatefulWidget {
  const MyListingsScreen({super.key});

  @override
  State<MyListingsScreen> createState() => _MyListingsScreenState();
}

class _MyListingsScreenState extends State<MyListingsScreen> {
  List<dynamic> _listings = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchMyListings();
  }

  Future<void> _fetchMyListings() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppConfig.baseUrl}/my-listings'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _listings = data['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint('Fetch error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleSold(int id) async {
    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/listings/$id/sold'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        _fetchMyListings();
      }
    } catch (e) {
      debugPrint('Sold error: $e');
    }
  }

  Future<void> _deleteListing(int id) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تأكيد حذف الإعلان', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: const Text('هل أنت متأكد من حذف هذا الإعلان نهائياً من حسابك؟ لا يمكن التراجع عن هذه الخطوة.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('حذف نهائي'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final res = await http.delete(
          Uri.parse('${AppConfig.baseUrl}/listings/$id'),
          headers: AppState.authHeaders,
        );
        if (res.statusCode == 200) {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('تم حذف الإعلان بنجاح')),
            );
          }
          _fetchMyListings();
        }
      } catch (e) {
        debugPrint('Delete error: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إعلاناتي وسلعي المعروضة'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            tooltip: 'أضف إعلان جديد',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const CreateListingScreen()),
              ).then((_) => _fetchMyListings());
            },
          ),
        ],
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: AppColors.maroon))
          : _listings.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey),
                        const SizedBox(height: 12),
                        const Text('ليس لديك أي إعلانات منشورة حتى الآن', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        const SizedBox(height: 6),
                        const Text('ابدأ بعرض سيارتك، عقارك، هاتفك، أو طلبك مجاناً الآن في سوريا', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey)),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(builder: (_) => const CreateListingScreen()),
                            ).then((_) => _fetchMyListings());
                          },
                          icon: const Icon(Icons.add),
                          label: const Text('أضف إعلانك الأول الآن'),
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.maroon, foregroundColor: Colors.white),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _listings.length,
                  itemBuilder: (context, index) {
                    final item = _listings[index];
                    final isSold = item['is_sold'] == true;
                    final images = item['images'] as List<dynamic>? ?? [];
                    String imgUrl = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=400&q=80';
                    if (images.isNotEmpty && images[0]['image_path'] != null) {
                      imgUrl = AppConfig.resolveUrl(images[0]['image_path'].toString());
                    }

                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.grey.shade200),
                        boxShadow: [
                          BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 6, offset: const Offset(0, 2)),
                        ],
                      ),
                      child: Column(
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(12),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(10),
                                  child: Image.network(
                                    imgUrl,
                                    width: 70,
                                    height: 70,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) => Container(
                                      width: 70,
                                      height: 70,
                                      color: Colors.grey.shade200,
                                      child: const Icon(Icons.image, color: Colors.grey),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        item['title'] ?? '',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '${item['price']} ${item['currency'] == 'USD' ? '\$' : 'ل.س'}',
                                        style: TextStyle(color: AppColors.maroon, fontWeight: FontWeight.bold, fontSize: 14),
                                      ),
                                      const SizedBox(height: 4),
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: isSold ? Colors.red.shade100 : Colors.green.shade100,
                                              borderRadius: BorderRadius.circular(4),
                                            ),
                                            child: Text(
                                              isSold ? 'تم البيع وإغلاق الطلب ✅' : 'معروض نشط 🟢',
                                              style: TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: isSold ? Colors.red.shade900 : Colors.green.shade900,
                                              ),
                                            ),
                                          ),
                                          if (item['is_featured'] == true) ...[
                                            const SizedBox(width: 6),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: AppColors.amber.withValues(alpha: 0.2),
                                                borderRadius: BorderRadius.circular(4),
                                              ),
                                              child: const Text('مميز VIP ⭐', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.brown)),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const Divider(height: 1),
                          // Action Row
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            child: Row(
                              children: [
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: () => _toggleSold(item['id']),
                                    icon: Icon(isSold ? Icons.replay : Icons.check_circle_outline, size: 14),
                                    label: Text(isSold ? 'إعادة العرض' : 'تمييز تم البيع', style: const TextStyle(fontSize: 11)),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: isSold ? AppColors.emerald : Colors.brown,
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: () {
                                      Navigator.push(
                                        context,
                                        MaterialPageRoute(builder: (_) => EditListingScreen(listing: item)),
                                      ).then((_) => _fetchMyListings());
                                    },
                                    icon: const Icon(Icons.edit_rounded, size: 14),
                                    label: const Text('تعديل', style: TextStyle(fontSize: 11)),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: AppColors.blue,
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                IconButton(
                                  icon: const Icon(Icons.delete_outline_rounded, color: Colors.red, size: 18),
                                  tooltip: 'حذف الإعلان',
                                  onPressed: () => _deleteListing(item['id']),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Edit Listing Screen with Image Removal & Update PUT API
// ---------------------------------------------------------------------------------
class EditListingScreen extends StatefulWidget {
  final Map<String, dynamic> listing;

  const EditListingScreen({super.key, required this.listing});

  @override
  State<EditListingScreen> createState() => _EditListingScreenState();
}

class _EditListingScreenState extends State<EditListingScreen> {
  final _titleController = TextEditingController();
  final _priceController = TextEditingController();
  final _descController = TextEditingController();
  final _districtController = TextEditingController();
  final _streetController = TextEditingController();
  final _phoneController = TextEditingController();

  late String _listingType;
  late String _condition;
  late String _currency;
  int? _selectedCategoryId;
  int? _selectedSubcategoryId;
  int? _selectedGovernorateId;
  int? _selectedRegionId;

  List<dynamic> _categories = [];
  List<dynamic> _governorates = [];
  List<dynamic> _regions = [];
  List<dynamic> _existingImages = [];
  final List<int> _deleteImageIds = [];
  final List<String> _newImages = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final l = widget.listing;
    _titleController.text = l['title'] ?? '';
    _priceController.text = l['price']?.toString() ?? '';
    _descController.text = l['description'] ?? '';
    _districtController.text = l['district_name'] ?? l['district']?['name_ar'] ?? '';
    _streetController.text = l['street_address'] ?? '';
    _phoneController.text = l['phone'] ?? '';

    _listingType = l['listing_type'] ?? 'sale';
    _condition = l['condition'] ?? 'used';
    _currency = l['currency'] ?? 'SYP';
    _selectedCategoryId = l['category_id'] is int ? l['category_id'] : int.tryParse(l['category_id']?.toString() ?? '');
    _selectedSubcategoryId = l['subcategory_id'] is int ? l['subcategory_id'] : int.tryParse(l['subcategory_id']?.toString() ?? '');
    _selectedGovernorateId = l['governorate_id'] is int ? l['governorate_id'] : int.tryParse(l['governorate_id']?.toString() ?? '');
    _selectedRegionId = l['region_id'] is int ? l['region_id'] : int.tryParse(l['region_id']?.toString() ?? '');

    _existingImages = List.from(l['images'] ?? []);
    _loadMetadata();
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
        if (_selectedGovernorateId != null) {
          final gov = _governorates.firstWhere((g) => g['id'] == _selectedGovernorateId, orElse: () => null);
          if (gov != null && gov['regions'] != null) {
            setState(() {
              _regions = gov['regions'];
            });
          }
        }
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

  Future<void> _saveChanges() async {
    if (_titleController.text.trim().isEmpty ||
        _priceController.text.trim().isEmpty ||
        _descController.text.trim().isEmpty ||
        _selectedCategoryId == null ||
        _selectedGovernorateId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى ملء كافة الحقول الأساسية')),
      );
      return;
    }

    setState(() => _isLoading = true);
    try {
      final payload = {
        'title': _titleController.text.trim(),
        'category_id': _selectedCategoryId,
        if (_selectedSubcategoryId != null) 'subcategory_id': _selectedSubcategoryId,
        'listing_type': _listingType,
        'condition': _condition,
        'price': _priceController.text.trim(),
        'currency': _currency,
        'description': _descController.text.trim(),
        'governorate_id': _selectedGovernorateId,
        if (_selectedRegionId != null) 'region_id': _selectedRegionId,
        'district_name': _districtController.text.trim(),
        'street_address': _streetController.text.trim(),
        'phone': _phoneController.text.trim(),
        'delete_image_ids': _deleteImageIds,
      };

      final res = await http.put(
        Uri.parse('${AppConfig.baseUrl}/listings/${widget.listing['id']}'),
        headers: AppState.authHeaders,
        body: jsonEncode(payload),
      );

      if (res.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('تم حفظ التعديلات بنجاح')),
          );
          Navigator.pop(context, true);
        }
      } else {
        final err = jsonDecode(res.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(err['message'] ?? 'فشل حفظ التعديلات')),
          );
        }
      }
    } catch (e) {
      debugPrint('Save error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showAddImageDialog() {
    final ctrl = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('إضافة صورة جديدة', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('أدخل رابط الصورة الإضافية:', style: TextStyle(fontSize: 12)),
            const SizedBox(height: 8),
            TextField(
              controller: ctrl,
              decoration: InputDecoration(
                hintText: 'https://example.com/image.jpg',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () {
              if (ctrl.text.trim().isNotEmpty) {
                setState(() => _newImages.add(ctrl.text.trim()));
              }
              Navigator.pop(ctx);
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.maroon, foregroundColor: Colors.white),
            child: const Text('إضافة'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final selectedCat = _categories.firstWhere((c) => c['id'] == _selectedCategoryId, orElse: () => {});
    final subcategories = selectedCat['subcategories'] as List<dynamic>? ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('تعديل بيانات الإعلان'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Title
          TextField(
            controller: _titleController,
            decoration: InputDecoration(
              labelText: 'عنوان الإعلان *',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Listing Type: sale, wanted, rent, rent_wanted
          const Text('نوع الإعلان والغرض *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                child: ChoiceChip(
                  label: const Text('🏷️ معروض للبيع'),
                  selected: _listingType == 'sale',
                  selectedColor: AppColors.maroon,
                  labelStyle: TextStyle(color: _listingType == 'sale' ? Colors.white : Colors.black87, fontSize: 11),
                  onSelected: (_) => setState(() => _listingType = 'sale'),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: ChoiceChip(
                  label: const Text('🔍 مطلوب للشراء'),
                  selected: _listingType == 'wanted',
                  selectedColor: AppColors.purple,
                  labelStyle: TextStyle(color: _listingType == 'wanted' ? Colors.white : Colors.black87, fontSize: 11),
                  onSelected: (_) => setState(() => _listingType = 'wanted'),
                ),
              ),
            ],
          ),
          Row(
            children: [
              Expanded(
                child: ChoiceChip(
                  label: const Text('🔑 معروض للإيجار'),
                  selected: _listingType == 'rent',
                  selectedColor: Colors.teal,
                  labelStyle: TextStyle(color: _listingType == 'rent' ? Colors.white : Colors.black87, fontSize: 11),
                  onSelected: (_) => setState(() => _listingType = 'rent'),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: ChoiceChip(
                  label: const Text('🏠 مطلوب للاستئجار'),
                  selected: _listingType == 'rent_wanted',
                  selectedColor: Colors.indigo,
                  labelStyle: TextStyle(color: _listingType == 'rent_wanted' ? Colors.white : Colors.black87, fontSize: 11),
                  onSelected: (_) => setState(() => _listingType = 'rent_wanted'),
                ),
              ),
            ],
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

          // Condition: new vs used
          Row(
            children: [
              Expanded(
                child: ChoiceChip(
                  label: const Text('جديد بالكرتونة'),
                  selected: _condition == 'new',
                  selectedColor: AppColors.emerald,
                  labelStyle: TextStyle(color: _condition == 'new' ? Colors.white : Colors.black87, fontSize: 11),
                  onSelected: (_) => setState(() => _condition = 'new'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: ChoiceChip(
                  label: const Text('مستعمل بحالة جيدة'),
                  selected: _condition == 'used',
                  selectedColor: AppColors.blue,
                  labelStyle: TextStyle(color: _condition == 'used' ? Colors.white : Colors.black87, fontSize: 11),
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
                    labelText: 'السعر المطلوب *',
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
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Street
          TextField(
            controller: _streetController,
            decoration: InputDecoration(
              labelText: 'العنوان ومعالم قريبة',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),

          // Phone
          TextField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: 'رقم الهاتف للتواصل',
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
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 16),

          // Existing & New Images Manager with 'X' Remove Buttons
          const Text('إدارة صور الإعلان (اضغط X لحذف الصورة) *', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade300),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (_existingImages.isNotEmpty || _newImages.isNotEmpty)
                  SizedBox(
                    height: 95,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      children: [
                        ...List.generate(_existingImages.length, (idx) {
                          final img = _existingImages[idx];
                          final url = AppConfig.resolveUrl(img['image_path']?.toString());
                          return Container(
                            width: 85,
                            margin: const EdgeInsets.only(left: 10),
                            child: Stack(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(10),
                                  child: Image.network(
                                    url,
                                    width: 85,
                                    height: 85,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) => Container(
                                      width: 85,
                                      height: 85,
                                      color: Colors.grey.shade200,
                                      child: const Icon(Icons.image, color: Colors.grey),
                                    ),
                                  ),
                                ),
                                Positioned(
                                  top: 2,
                                  left: 2,
                                  child: InkWell(
                                    onTap: () {
                                      setState(() {
                                        if (img['id'] != null) {
                                          _deleteImageIds.add(img['id']);
                                        }
                                        _existingImages.removeAt(idx);
                                      });
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.all(3),
                                      decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                                      child: const Icon(Icons.close_rounded, color: Colors.white, size: 14),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        }),
                        ...List.generate(_newImages.length, (idx) {
                          final url = _newImages[idx];
                          return Container(
                            width: 85,
                            margin: const EdgeInsets.only(left: 10),
                            child: Stack(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(10),
                                  child: Image.network(
                                    url,
                                    width: 85,
                                    height: 85,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) => Container(
                                      width: 85,
                                      height: 85,
                                      color: Colors.grey.shade200,
                                      child: const Icon(Icons.image, color: Colors.grey),
                                    ),
                                  ),
                                ),
                                Positioned(
                                  top: 2,
                                  left: 2,
                                  child: InkWell(
                                    onTap: () {
                                      setState(() => _newImages.removeAt(idx));
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.all(3),
                                      decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                                      child: const Icon(Icons.close_rounded, color: Colors.white, size: 14),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        }),
                      ],
                    ),
                  )
                else
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 8),
                    child: Text('لا توجد صور متبقية للإعلان.', style: TextStyle(fontSize: 11, color: Colors.grey)),
                  ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: _showAddImageDialog,
                  icon: const Icon(Icons.add_photo_alternate_rounded, size: 16),
                  label: const Text('إضافة صورة جديدة', style: TextStyle(fontSize: 12)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.maroon,
                    side: BorderSide(color: AppColors.maroon),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Submit Changes
          ElevatedButton(
            onPressed: _isLoading ? null : _saveChanges,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.maroon,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: _isLoading
                ? const CircularProgressIndicator(color: Colors.white)
                : const Text('حفظ التعديلات والتحديث', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Favorites Screen
// ---------------------------------------------------------------------------------
class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  List<dynamic> _favorites = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchFavorites();
  }

  Future<void> _fetchFavorites() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(
        Uri.parse('${AppConfig.baseUrl}/favorites'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _favorites = data['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint('Favorites error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _removeFavorite(int listingId) async {
    try {
      final res = await http.post(
        Uri.parse('${AppConfig.baseUrl}/listings/$listingId/favorite'),
        headers: AppState.authHeaders,
      );
      if (res.statusCode == 200) {
        _fetchFavorites();
      }
    } catch (e) {
      debugPrint('Remove favorite error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الإعلانات المفضلة'),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: AppColors.maroon))
          : _favorites.isEmpty
              ? const Center(
                  child: Padding(
                    padding: EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.favorite_border_rounded, size: 64, color: Colors.grey),
                        SizedBox(height: 12),
                        Text('قائمة المفضلة فارغة حالياً', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        SizedBox(height: 6),
                        Text('يمكنك حفظ أي سلعة ترغب بمتابعتها بالضغط على أيقونة القلب في صفحة السلعة.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _favorites.length,
                  itemBuilder: (context, index) {
                    final item = _favorites[index];
                    final images = item['images'] as List<dynamic>? ?? [];
                    String imgUrl = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=400&q=80';
                    if (images.isNotEmpty && images[0]['image_path'] != null) {
                      imgUrl = AppConfig.resolveUrl(images[0]['image_path'].toString());
                    }

                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: ListTile(
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => ListingDetailScreen(listingId: item['id'])),
                          ).then((_) => _fetchFavorites());
                        },
                        leading: ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.network(
                            imgUrl,
                            width: 55,
                            height: 55,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) => Container(
                              width: 55,
                              height: 55,
                              color: Colors.grey.shade200,
                              child: const Icon(Icons.image, color: Colors.grey),
                            ),
                          ),
                        ),
                        title: Text(item['title'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                        subtitle: Text('${item['price']} ${item['currency'] == 'USD' ? '\$' : 'ل.س'}', style: TextStyle(color: AppColors.maroon, fontWeight: FontWeight.bold)),
                        trailing: IconButton(
                          icon: const Icon(Icons.favorite, color: Colors.redAccent),
                          tooltip: 'إزالة من المفضلة',
                          onPressed: () => _removeFavorite(item['id']),
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

// ---------------------------------------------------------------------------------
// Featured Listings VIP Screen
// ---------------------------------------------------------------------------------
class FeaturedListingsScreen extends StatefulWidget {
  const FeaturedListingsScreen({super.key});

  @override
  State<FeaturedListingsScreen> createState() => _FeaturedListingsScreenState();
}

class _FeaturedListingsScreenState extends State<FeaturedListingsScreen> {
  List<dynamic> _listings = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchFeaturedListings();
  }

  Future<void> _fetchFeaturedListings() async {
    setState(() => _isLoading = true);
    try {
      final res = await http.get(Uri.parse('${AppConfig.baseUrl}/listings?featured=1'));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        setState(() {
          _listings = data['data'] ?? [];
        });
      }
    } catch (e) {
      debugPrint('Featured fetch error: $e');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Row(
          children: [
            Icon(Icons.workspace_premium_rounded, color: AppColors.amber),
            SizedBox(width: 8),
            Text('الإعلانات المميزة VIP'),
          ],
        ),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: AppColors.maroon))
          : _listings.isEmpty
              ? const Center(
                  child: Padding(
                    padding: EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.workspace_premium_outlined, size: 64, color: AppColors.amber),
                        SizedBox(height: 12),
                        Text('لا توجد إعلانات مميزة حالياً', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                        SizedBox(height: 6),
                        Text('يقوم مدير الموقع بتمييز أفضل العروض لتظهر في صدارة الموقع والتطبيق.', textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Colors.grey)),
                      ],
                    ),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _listings.length,
                  itemBuilder: (context, index) {
                    final item = _listings[index];
                    final images = item['images'] as List<dynamic>? ?? [];
                    String imgUrl = 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?auto=format&fit=crop&w=400&q=80';
                    if (images.isNotEmpty && images[0]['image_path'] != null) {
                      imgUrl = AppConfig.resolveUrl(images[0]['image_path'].toString());
                    }

                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.amber.withValues(alpha: 0.5)),
                        boxShadow: [
                          BoxShadow(color: AppColors.amber.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, 2)),
                        ],
                      ),
                      child: InkWell(
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => ListingDetailScreen(listingId: item['id'])),
                          );
                        },
                        borderRadius: BorderRadius.circular(16),
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Row(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: Image.network(
                                  imgUrl,
                                  width: 80,
                                  height: 80,
                                  fit: BoxFit.cover,
                                  errorBuilder: (context, error, stackTrace) => Container(
                                    width: 80,
                                    height: 80,
                                    color: Colors.grey.shade200,
                                    child: const Icon(Icons.image, color: Colors.grey),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(color: AppColors.amber, borderRadius: BorderRadius.circular(6)),
                                      child: const Text('إعلان ممول VIP ⭐', style: TextStyle(color: Colors.black87, fontSize: 9, fontWeight: FontWeight.bold)),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(item['title'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                    const SizedBox(height: 4),
                                    Text('${item['price']} ${item['currency'] == 'USD' ? '\$' : 'ل.س'}', style: TextStyle(color: AppColors.maroon, fontWeight: FontWeight.bold, fontSize: 14)),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}
