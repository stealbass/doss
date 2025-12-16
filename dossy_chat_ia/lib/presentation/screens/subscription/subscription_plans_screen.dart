import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/subscription_provider.dart';
import '../../../data/providers/auth_provider.dart';
import '../../widgets/subscription/plan_card.dart';

class SubscriptionPlansScreen extends StatefulWidget {
  const SubscriptionPlansScreen({super.key});

  @override
  State<SubscriptionPlansScreen> createState() => _SubscriptionPlansScreenState();
}

class _SubscriptionPlansScreenState extends State<SubscriptionPlansScreen> {
  String _selectedDuration = 'monthly';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Plans d\'abonnement'),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: EdgeInsets.all(16.w),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Text(
                'Choisissez votre plan',
                style: TextStyle(
                  fontSize: 24.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),
              
              SizedBox(height: 8.h),
              
              Text(
                'Débloquez toutes les fonctionnalités de DOSSY CHAT IA',
                style: TextStyle(
                  fontSize: 14.sp,
                  color: AppColors.textSecondary,
                ),
              ),
              
              SizedBox(height: 24.h),
              
              // Duration Toggle
              Container(
                padding: EdgeInsets.all(4.w),
                decoration: BoxDecoration(
                  color: AppColors.inputBackground,
                  borderRadius: BorderRadius.circular(12.r),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: GestureDetector(
                        onTap: () {
                          setState(() {
                            _selectedDuration = 'monthly';
                          });
                        },
                        child: Container(
                          padding: EdgeInsets.symmetric(vertical: 12.h),
                          decoration: BoxDecoration(
                            color: _selectedDuration == 'monthly'
                                ? Colors.white
                                : Colors.transparent,
                            borderRadius: BorderRadius.circular(8.r),
                          ),
                          child: Text(
                            'Mensuel',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 14.sp,
                              fontWeight: _selectedDuration == 'monthly'
                                  ? FontWeight.w600
                                  : FontWeight.normal,
                              color: _selectedDuration == 'monthly'
                                  ? AppColors.primary
                                  : AppColors.textSecondary,
                            ),
                          ),
                        ),
                      ),
                    ),
                    Expanded(
                      child: GestureDetector(
                        onTap: () {
                          setState(() {
                            _selectedDuration = 'annual';
                          });
                        },
                        child: Container(
                          padding: EdgeInsets.symmetric(vertical: 12.h),
                          decoration: BoxDecoration(
                            color: _selectedDuration == 'annual'
                                ? Colors.white
                                : Colors.transparent,
                            borderRadius: BorderRadius.circular(8.r),
                          ),
                          child: Column(
                            children: [
                              Text(
                                'Annuel',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  fontWeight: _selectedDuration == 'annual'
                                      ? FontWeight.w600
                                      : FontWeight.normal,
                                  color: _selectedDuration == 'annual'
                                      ? AppColors.primary
                                      : AppColors.textSecondary,
                                ),
                              ),
                              if (_selectedDuration == 'annual')
                                Text(
                                  'Économisez 20%',
                                  style: TextStyle(
                                    fontSize: 10.sp,
                                    color: AppColors.success,
                                    fontWeight: FontWeight.w600,
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
              
              SizedBox(height: 24.h),
              
              // Plans List
              Consumer2<SubscriptionProvider, AuthProvider>(
                builder: (context, subscriptionProvider, authProvider, child) {
                  return Column(
                    children: subscriptionProvider.plans.map((plan) {
                      final isCurrentPlan = 
                          authProvider.user?.plan == plan['name'];
                      final price = plan['price'] as int;
                      final annualPrice = (price * 10).toInt(); // 20% discount
                      
                      return Padding(
                        padding: EdgeInsets.only(bottom: 16.h),
                        child: PlanCard(
                          planName: plan['name'] as String,
                          price: _selectedDuration == 'monthly' 
                              ? price 
                              : annualPrice,
                          currency: plan['currency'] as String,
                          duration: _selectedDuration == 'monthly'
                              ? plan['duration'] as String
                              : 'Par an',
                          features: (plan['features'] as List).cast<String>(),
                          isCurrentPlan: isCurrentPlan,
                          isPopular: plan['name'] == 'Professionnel',
                          onSelect: () => _selectPlan(
                            context,
                            plan['id'] as String,
                            _selectedDuration,
                          ),
                        ),
                      );
                    }).toList(),
                  );
                },
              ),
              
              SizedBox(height: 16.h),
              
              // Features Comparison
              Container(
                padding: EdgeInsets.all(16.w),
                decoration: BoxDecoration(
                  color: AppColors.info.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12.r),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.info_outline,
                          size: 20.sp,
                          color: AppColors.info,
                        ),
                        SizedBox(width: 8.w),
                        Text(
                          'Toutes les fonctionnalités',
                          style: TextStyle(
                            fontSize: 16.sp,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 12.h),
                    _buildFeatureItem('✅ Chat IA avec RAG'),
                    _buildFeatureItem('✅ Bibliothèque juridique'),
                    _buildFeatureItem('✅ Support 14 pays africains'),
                    _buildFeatureItem('✅ Multi-langue (FR/EN)'),
                    _buildFeatureItem('✅ Mode Sombre'),
                  ],
                ),
              ),
              
              SizedBox(height: 24.h),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFeatureItem(String text) {
    return Padding(
      padding: EdgeInsets.only(bottom: 8.h),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 13.sp,
          color: AppColors.textSecondary,
        ),
      ),
    );
  }

  Future<void> _selectPlan(
    BuildContext context,
    String planId,
    String duration,
  ) async {
    // Show confirmation dialog
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Confirmer l\'abonnement'),
          content: Text(
            'Voulez-vous souscrire au plan ${planId.toUpperCase()} ?',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Confirmer'),
            ),
          ],
        );
      },
    );

    if (confirmed != true) return;
    if (!context.mounted) return;

    // TODO: Navigate to payment screen
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Redirection vers le paiement Flutterwave...'),
        backgroundColor: AppColors.info,
      ),
    );
  }
}
