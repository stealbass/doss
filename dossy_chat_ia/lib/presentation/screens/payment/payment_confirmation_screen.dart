import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../data/services/coupon_service.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../core/theme/app_colors.dart';
import 'flutterwave_payment_screen.dart';

/// Écran de confirmation de paiement avec possibilité d'appliquer un code promo
class PaymentConfirmationScreen extends StatefulWidget {
  final String planId;
  final String planName;
  final int amount;
  final String currency;
  final String billingCycle;

  const PaymentConfirmationScreen({
    super.key,
    required this.planId,
    required this.planName,
    required this.amount,
    required this.currency,
    required this.billingCycle,
  });

  @override
  State<PaymentConfirmationScreen> createState() =>
      _PaymentConfirmationScreenState();
}

class _PaymentConfirmationScreenState extends State<PaymentConfirmationScreen> {
  final TextEditingController _couponController = TextEditingController();
  final CouponService _couponService = CouponService();
  
  bool _isValidatingCoupon = false;
  bool _couponApplied = false;
  Map<String, dynamic>? _appliedCouponData;
  
  int get _originalAmount => widget.amount;
  double get _discountAmount => _appliedCouponData?['pricing']?['discount_amount'] ?? 0.0;
  int get _finalAmount => _appliedCouponData?['pricing']?['final_price']?.toInt() ?? _originalAmount;
  String get _appliedCouponCode => _appliedCouponData?['coupon']?['code'] ?? '';

  @override
  void dispose() {
    _couponController.dispose();
    super.dispose();
  }

  Future<void> _validateCoupon() async {
    if (_couponController.text.trim().isEmpty) {
      _showMessage('Veuillez saisir un code promo', isError: true);
      return;
    }

    final authProvider = context.read<AuthProvider>();
    final token = authProvider.token;

    if (token == null) {
      _showMessage('Erreur d\'authentification', isError: true);
      return;
    }

    setState(() {
      _isValidatingCoupon = true;
    });

    try {
      final result = await _couponService.validateCoupon(
        couponCode: _couponController.text.trim(),
        planId: widget.planId,
        token: token,
      );

      if (result['success'] == true && result['coupon_valid'] == true) {
        setState(() {
          _couponApplied = true;
          _appliedCouponData = result;
        });
        _showMessage(result['message'] ?? 'Code promo appliqué avec succès !');
      } else {
        _showMessage(result['message'] ?? 'Code promo invalide', isError: true);
      }
    } catch (e) {
      _showMessage('Erreur: $e', isError: true);
    } finally {
      setState(() {
        _isValidatingCoupon = false;
      });
    }
  }

  void _removeCoupon() {
    setState(() {
      _couponApplied = false;
      _appliedCouponData = null;
      _couponController.clear();
    });
  }

  void _proceedToPayment() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => FlutterwavePaymentScreen(
          planId: widget.planId,
          planName: widget.planName,
          amount: _finalAmount,
          currency: widget.currency,
          billingCycle: widget.billingCycle,
          couponCode: _couponApplied ? _appliedCouponCode : null,
        ),
      ),
    );
  }

  void _showMessage(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Confirmer le paiement'),
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Plan Summary Card
            Container(
              padding: EdgeInsets.all(16.w),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12.r),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Résumé de l\'abonnement',
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 16.h),
                  
                  _buildInfoRow('Plan', widget.planName),
                  SizedBox(height: 8.h),
                  _buildInfoRow(
                    'Durée',
                    widget.billingCycle == 'monthly' ? 'Mensuel' : 'Annuel',
                  ),
                  SizedBox(height: 8.h),
                  _buildInfoRow(
                    'Prix original',
                    '$_originalAmount ${widget.currency}',
                    bold: false,
                  ),
                  
                  if (_couponApplied) ...[
                    SizedBox(height: 8.h),
                    _buildInfoRow(
                      'Réduction (${_appliedCouponData!['coupon']['discount']}%)',
                      '-${_discountAmount.toStringAsFixed(0)} ${widget.currency}',
                      color: Colors.green,
                    ),
                  ],
                  
                  Divider(height: 24.h),
                  
                  _buildInfoRow(
                    'Total à payer',
                    '$_finalAmount ${widget.currency}',
                    bold: true,
                    large: true,
                  ),
                ],
              ),
            ),

            SizedBox(height: 24.h),

            // Coupon Section
            Text(
              'Code promo',
              style: TextStyle(
                fontSize: 16.sp,
                fontWeight: FontWeight.w600,
              ),
            ),
            SizedBox(height: 8.h),
            Text(
              'Vous avez un code promo ? Appliquez-le pour bénéficier d\'une réduction',
              style: TextStyle(
                fontSize: 13.sp,
                color: AppColors.textSecondary,
              ),
            ),

            SizedBox(height: 16.h),

            if (!_couponApplied) ...[
              // Coupon Input Field
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _couponController,
                      decoration: InputDecoration(
                        hintText: 'Ex: PROMO2024',
                        prefixIcon: const Icon(Icons.discount_outlined),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                        filled: true,
                        fillColor: AppColors.inputBackground,
                      ),
                      textCapitalization: TextCapitalization.characters,
                      enabled: !_isValidatingCoupon,
                    ),
                  ),
                  SizedBox(width: 12.w),
                  ElevatedButton(
                    onPressed: _isValidatingCoupon ? null : _validateCoupon,
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(
                        horizontal: 20.w,
                        vertical: 16.h,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12.r),
                      ),
                    ),
                    child: _isValidatingCoupon
                        ? SizedBox(
                            width: 20.w,
                            height: 20.h,
                            child: const CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                            ),
                          )
                        : const Text('Appliquer'),
                  ),
                ],
              ),
            ] else ...[
              // Applied Coupon Display
              Container(
                padding: EdgeInsets.all(16.w),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(12.r),
                  border: Border.all(color: Colors.green, width: 2),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.check_circle,
                      color: Colors.green,
                      size: 24.sp,
                    ),
                    SizedBox(width: 12.w),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Code promo appliqué',
                            style: TextStyle(
                              fontSize: 14.sp,
                              fontWeight: FontWeight.w600,
                              color: Colors.green.shade900,
                            ),
                          ),
                          SizedBox(height: 4.h),
                          Text(
                            _appliedCouponCode,
                            style: TextStyle(
                              fontSize: 16.sp,
                              fontWeight: FontWeight.bold,
                              color: Colors.green.shade700,
                              letterSpacing: 1.2,
                            ),
                          ),
                          if (_appliedCouponData?['coupon']?['description'] != null)
                            Text(
                              _appliedCouponData!['coupon']['description'],
                              style: TextStyle(
                                fontSize: 12.sp,
                                color: Colors.green.shade700,
                              ),
                            ),
                        ],
                      ),
                    ),
                    IconButton(
                      onPressed: _removeCoupon,
                      icon: const Icon(Icons.close),
                      color: Colors.green.shade900,
                    ),
                  ],
                ),
              ),
            ],

            SizedBox(height: 32.h),

            // Payment Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _proceedToPayment,
                style: ElevatedButton.styleFrom(
                  padding: EdgeInsets.symmetric(vertical: 16.h),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12.r),
                  ),
                ),
                child: Text(
                  'Procéder au paiement ($_finalAmount ${widget.currency})',
                  style: TextStyle(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),

            SizedBox(height: 16.h),

            // Payment Methods Info
            Center(
              child: Text(
                'Paiement sécurisé via Flutterwave',
                style: TextStyle(
                  fontSize: 12.sp,
                  color: AppColors.textSecondary,
                ),
              ),
            ),
            Center(
              child: Text(
                'Carte bancaire • Mobile Money',
                style: TextStyle(
                  fontSize: 12.sp,
                  color: AppColors.textSecondary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(
    String label,
    String value, {
    bool bold = false,
    bool large = false,
    Color? color,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: large ? 16.sp : 14.sp,
            fontWeight: bold ? FontWeight.w600 : FontWeight.normal,
            color: color ?? AppColors.textSecondary,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: large ? 20.sp : 14.sp,
            fontWeight: bold ? FontWeight.bold : FontWeight.w600,
            color: color ?? AppColors.textPrimary,
          ),
        ),
      ],
    );
  }
}
