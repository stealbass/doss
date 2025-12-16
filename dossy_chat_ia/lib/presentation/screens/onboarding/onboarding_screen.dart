import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;
  
  final List<Map<String, dynamic>> _pages = [
    {
      'icon': Icons.balance,
      'title': 'Assistant Juridique Intelligent',
      'description': 'Accédez à une base de données juridique complète pour l\'Afrique francophone avec recherche intelligente par IA',
    },
    {
      'icon': Icons.school,
      'title': 'Outils pour Étudiants',
      'description': 'Générez des fiches d\'arrêt, fiches de révision, QCM et bénéficiez du mode révision active guidée',
    },
    {
      'icon': Icons.business_center,
      'title': 'Solutions Professionnelles',
      'description': 'Anonymisation automatique, modèles de contrats, veille juridique et assistant fiscal & social',
    },
    {
      'icon': Icons.language,
      'title': 'Multi-juridictions',
      'description': 'Sélectionnez votre pays et accédez aux textes juridiques spécifiques à votre juridiction',
    },
  ];

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _completeOnboarding() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.onboardingKey, true);
    
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            // Skip Button
            Padding(
              padding: EdgeInsets.all(16.w),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  if (_currentPage < _pages.length - 1)
                    TextButton(
                      onPressed: _completeOnboarding,
                      child: Text(
                        'Passer',
                        style: TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 14.sp,
                        ),
                      ),
                    ),
                ],
              ),
            ),
            
            // Page View
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                itemCount: _pages.length,
                itemBuilder: (context, index) {
                  final page = _pages[index];
                  return Padding(
                    padding: EdgeInsets.symmetric(horizontal: 32.w),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Icon
                        Container(
                          width: 120.w,
                          height: 120.w,
                          decoration: BoxDecoration(
                            gradient: AppColors.primaryGradient,
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            page['icon'],
                            size: 60.sp,
                            color: Colors.white,
                          ),
                        ),
                        
                        SizedBox(height: 48.h),
                        
                        // Title
                        Text(
                          page['title'],
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 24.sp,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        
                        SizedBox(height: 16.h),
                        
                        // Description
                        Text(
                          page['description'],
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 16.sp,
                            color: AppColors.textSecondary,
                            height: 1.5,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
            
            // Page Indicators
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                _pages.length,
                (index) => AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  margin: EdgeInsets.symmetric(horizontal: 4.w),
                  width: _currentPage == index ? 24.w : 8.w,
                  height: 8.h,
                  decoration: BoxDecoration(
                    color: _currentPage == index
                        ? AppColors.primary
                        : AppColors.textHint,
                    borderRadius: BorderRadius.circular(4.r),
                  ),
                ),
              ),
            ),
            
            SizedBox(height: 32.h),
            
            // Next/Get Started Button
            Padding(
              padding: EdgeInsets.symmetric(horizontal: 32.w),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    if (_currentPage < _pages.length - 1) {
                      _pageController.nextPage(
                        duration: const Duration(milliseconds: 300),
                        curve: Curves.easeInOut,
                      );
                    } else {
                      _completeOnboarding();
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 16.h),
                  ),
                  child: Text(
                    _currentPage < _pages.length - 1
                        ? 'Suivant'
                        : 'Commencer',
                    style: TextStyle(fontSize: 16.sp),
                  ),
                ),
              ),
            ),
            
            SizedBox(height: 32.h),
          ],
        ),
      ),
    );
  }
}
