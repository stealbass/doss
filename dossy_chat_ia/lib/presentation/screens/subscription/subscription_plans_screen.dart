import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/subscription_provider.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/services/coupon_service.dart';
import '../../../l10n/app_localizations.dart';
import '../../widgets/subscription/plan_card.dart';

class SubscriptionPlansScreen extends StatefulWidget {
  const SubscriptionPlansScreen({super.key});

  @override
  State<SubscriptionPlansScreen> createState() =>
      _SubscriptionPlansScreenState();
}

class _SubscriptionPlansScreenState extends State<SubscriptionPlansScreen> {
  String _selectedDuration = 'monthly';

  @override
  void initState() {
    super.initState();
    // Load plans from API when screen initializes
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final subscriptionProvider =
          Provider.of<SubscriptionProvider>(context, listen: false);
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      subscriptionProvider.loadPlans(token: authProvider.token);
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Reload plans every time screen appears (in case prices changed in admin)
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final subscriptionProvider =
          Provider.of<SubscriptionProvider>(context, listen: false);
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      subscriptionProvider.loadPlans(token: authProvider.token);
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.subscriptionPlans),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: EdgeInsets.all(16.w),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Text(
                l10n.chooseYourPlan,
                style: TextStyle(
                  fontSize: 24.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),

              SizedBox(height: 8.h),

              Text(
                l10n.unlockAllFeatures,
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
                            l10n.monthly,
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
                                l10n.annual,
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
                                Container() // Removed "Économisez 20%" text
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
                  if (subscriptionProvider.isLoading) {
                    return const Center(
                      child: CircularProgressIndicator(),
                    );
                  }

                  if (subscriptionProvider.plans.isEmpty) {
                    return Center(
                      child: Text(
                        l10n.noPlanAvailable,
                        style: TextStyle(
                          fontSize: 14.sp,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    );
                  }

                  return Column(
                    children: subscriptionProvider.plans.map((plan) {
                      final isCurrentPlan =
                          authProvider.user?.plan == plan.name;
                      final price = plan.price.toInt();
                      // Use actual yearly price from API, or calculate if not provided
                      final annualPrice = (plan.priceYearly ?? plan.price * 12).toInt();

                      return Padding(
                        padding: EdgeInsets.only(bottom: 16.h),
                        child: PlanCard(
                          planName: plan.nameFr ?? plan.name,
                          price: _selectedDuration == 'monthly'
                              ? price
                              : annualPrice,
                          currency: plan.currency,
                          duration: _selectedDuration == 'monthly'
                              ? l10n.perMonth
                              : l10n.perYear,
                          features: plan.features,
                          isCurrentPlan: isCurrentPlan,
                          isPopular: plan.name == 'pro',
                          onSelect: () => _selectPlan(
                            context,
                            plan.id,
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
                  color: AppColors.info.withValues(alpha: 0.1),
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
                          l10n.allFeatures,
                          style: TextStyle(
                            fontSize: 16.sp,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                    SizedBox(height: 12.h),
                    _buildFeatureItem(l10n.featureChatWithRAG),
                    _buildFeatureItem(l10n.featureLegalLibrary),
                    _buildFeatureItem(l10n.featureSupport14Countries),
                    _buildFeatureItem(l10n.featureMultilanguage),
                    _buildFeatureItem(l10n.featureDarkMode),
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
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final subscriptionProvider = Provider.of<SubscriptionProvider>(context, listen: false);
    final couponController = TextEditingController();
    final couponService = CouponService();
    
    // Find the selected plan
    final selectedPlan = subscriptionProvider.plans.firstWhere(
      (plan) => plan.id == planId,
      orElse: () => subscriptionProvider.plans.first,
    );

    // Show confirmation dialog
    final confirmed = await showDialog<dynamic>(
      context: context,
      builder: (dialogContext) {
        final l10n = AppLocalizations.of(dialogContext)!;
        final price = duration == 'monthly'
            ? selectedPlan.price
            : (selectedPlan.priceYearly ?? selectedPlan.price * 12);

        bool isValidatingCoupon = false;
        bool couponApplied = false;
        Map<String, dynamic>? appliedCouponData;

        double discountAmount() {
          final raw = appliedCouponData?['pricing']?['discount_amount'];
          if (raw is num) return raw.toDouble();
          if (raw is String) return double.tryParse(raw) ?? 0.0;
          return 0.0;
        }

        int finalAmount() {
          final raw = appliedCouponData?['pricing']?['final_price'];
          if (raw is num) return raw.toInt();
          if (raw is String) {
            final parsed = double.tryParse(raw);
            if (parsed != null) return parsed.toInt();
          }
          return price.toInt();
        }
        String appliedCouponCode() =>
          appliedCouponData?['coupon']?['code'] ?? '';

        return StatefulBuilder(
          builder: (context, setState) {
            final isFr = Localizations.localeOf(context).languageCode == 'fr';
            return AlertDialog(
              title: Text(l10n.confirmSubscription),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${l10n.planLabel}: ${selectedPlan.nameFr ?? selectedPlan.name}'),
                  SizedBox(height: 8.h),
                  Text('${l10n.amountLabel}: ${finalAmount()} ${selectedPlan.currency}'),
                  if (couponApplied) ...[
                    SizedBox(height: 6.h),
                    Text(
                      '-${discountAmount().toStringAsFixed(0)} ${selectedPlan.currency} (${l10n.applied}: ${appliedCouponCode()})',
                      style: TextStyle(color: AppColors.success, fontSize: 12.sp),
                    ),
                  ],
                  SizedBox(height: 8.h),
                  Text('${l10n.durationLabel}: ${duration == "monthly" ? l10n.monthly : l10n.annual}'),
                  SizedBox(height: 12.h),
                  Text(
                    l10n.promoCode,
                    style: TextStyle(fontSize: 13.sp, fontWeight: FontWeight.w600),
                  ),
                  SizedBox(height: 6.h),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: couponController,
                          textCapitalization: TextCapitalization.characters,
                          decoration: InputDecoration(
                            hintText: l10n.enterYourCode,
                            isDense: true,
                            border: const OutlineInputBorder(),
                          ),
                        ),
                      ),
                      SizedBox(width: 8.w),
                      ElevatedButton(
                        onPressed: couponApplied || isValidatingCoupon
                            ? null
                            : () async {
                                final token = authProvider.token;
                                if (token == null) {
                                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                                    SnackBar(content: Text(l10n.loginToMakePayment)),
                                  );
                                  return;
                                }
                                if (couponController.text.trim().isEmpty) {
                                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                                    SnackBar(content: Text(l10n.promoCodeInvalid)),
                                  );
                                  return;
                                }

                                setState(() => isValidatingCoupon = true);
                                final result = await couponService.validateCoupon(
                                  couponCode: couponController.text.trim(),
                                  planId: planId,
                                  token: token,
                                );
                                setState(() => isValidatingCoupon = false);

                                if (result['success'] == true && result['coupon_valid'] == true) {
                                  setState(() {
                                    couponApplied = true;
                                    appliedCouponData = result;
                                  });
                                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                                    SnackBar(content: Text(l10n.promoCodeAppliedSuccess)),
                                  );
                                } else {
                                  ScaffoldMessenger.of(dialogContext).showSnackBar(
                                    SnackBar(content: Text(result['message'] ?? l10n.promoCodeInvalid)),
                                  );
                                }
                              },
                        child: isValidatingCoupon
                            ? SizedBox(
                                width: 16.w,
                                height: 16.h,
                                child: const CircularProgressIndicator(strokeWidth: 2),
                              )
                            : Text(l10n.apply),
                      ),
                    ],
                  ),
                  SizedBox(height: 12.h),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: TextButton.icon(
                      onPressed: () {
                        showDialog(
                          context: dialogContext,
                          builder: (instructionContext) {
                            return AlertDialog(
                              title: Text(isFr
                                  ? 'Instructions de paiement'
                                  : 'Payment instructions'),
                              content: Column(
                                mainAxisSize: MainAxisSize.min,
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(isFr
                                      ? 'Vous serez redirigé vers le checkout Flutterwave.'
                                      : 'You will be redirected to the Flutterwave checkout.'),
                                  SizedBox(height: 8.h),
                                  Text(isFr
                                      ? 'En bas, cliquez sur « Changer la méthode de paiement ».'
                                      : 'At the bottom, tap “Change payment method”.'),
                                  SizedBox(height: 6.h),
                                  Text(isFr
                                      ? 'Choisissez Carte ou Mobile Money.'
                                      : 'Choose Card or Mobile Money.'),
                                  SizedBox(height: 8.h),
                                  Text(isFr
                                      ? 'Après paiement, vous serez redirigé vers l’app et votre abonnement sera validé.'
                                      : 'After payment, you’ll be redirected to the app and your subscription will be validated.'),
                                ],
                              ),
                              actions: [
                                TextButton(
                                  onPressed: () => Navigator.pop(instructionContext),
                                  child: Text(l10n.ok),
                                ),
                              ],
                            );
                          },
                        );
                      },
                      icon: const Icon(Icons.info_outline),
                      label: Text(isFr
                          ? 'Voir les instructions de paiement'
                          : 'View payment instructions'),
                    ),
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(dialogContext, false),
                  child: Text(l10n.cancel),
                ),
                ElevatedButton(
                  onPressed: () => Navigator.pop(dialogContext, {
                    'confirmed': true,
                    'final_amount': finalAmount(),
                    'coupon_code': couponApplied ? appliedCouponCode() : null,
                  }),
                  child: Text(l10n.confirmButton),
                ),
              ],
            );
          },
        );
      },
    );

    if (confirmed == null) return;
    if (confirmed is bool && confirmed != true) return;
    if (!context.mounted) return;

    final confirmedMap = confirmed is Map<String, dynamic> ? confirmed : null;
    final amount = confirmedMap?['final_amount'] as int? ??
        (duration == 'monthly'
            ? selectedPlan.price
            : (selectedPlan.priceYearly ?? selectedPlan.price * 12)).toInt();
    final couponCode = confirmedMap?['coupon_code'] as String?;
    
    Navigator.pushNamed(
      context,
      '/payment',
      arguments: {
        'plan_id': planId,
        'plan_name': selectedPlan.nameFr ?? selectedPlan.name,
        'billing_cycle': duration,
        'amount': amount.toInt(),
        'currency': selectedPlan.currency,
        if (couponCode != null) 'coupon_code': couponCode,
      },
    );
  }
}
