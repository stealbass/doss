import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// Carte de récapitulatif de paiement
class PaymentSummaryCard extends StatelessWidget {
  final String planName;
  final int amount;
  final double discount;
  final int finalAmount;
  final String currency;
  final String billingCycle;

  const PaymentSummaryCard({
    super.key,
    required this.planName,
    required this.amount,
    required this.discount,
    required this.finalAmount,
    required this.currency,
    required this.billingCycle,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12.r),
      ),
      child: Padding(
        padding: EdgeInsets.all(16.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Récapitulatif',
              style: TextStyle(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 16.h),

            // Plan
            _SummaryRow(
              label: 'Plan',
              value: planName,
              valueStyle: TextStyle(
                fontSize: 16.sp,
                fontWeight: FontWeight.bold,
              ),
            ),

            SizedBox(height: 12.h),

            // Cycle de facturation
            _SummaryRow(
              label: 'Période',
              value: billingCycle,
            ),

            const Divider(height: 24),

            // Montant
            _SummaryRow(
              label: 'Montant',
              value: '$amount $currency',
            ),

            if (discount > 0) ...[
              SizedBox(height: 12.h),
              _SummaryRow(
                label: 'Réduction',
                value: '-${(amount * discount / 100).round()} $currency (${discount.toStringAsFixed(0)}%)',
                valueColor: Colors.green,
              ),
            ],

            const Divider(height: 24),

            // Total
            _SummaryRow(
              label: 'Total à payer',
              value: '$finalAmount $currency',
              labelStyle: TextStyle(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
              ),
              valueStyle: TextStyle(
                fontSize: 20.sp,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).primaryColor,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Ligne de récapitulatif
class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  final TextStyle? labelStyle;
  final TextStyle? valueStyle;
  final Color? valueColor;

  const _SummaryRow({
    required this.label,
    required this.value,
    this.labelStyle,
    this.valueStyle,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: labelStyle ?? TextStyle(fontSize: 14.sp, color: Colors.grey[700]),
        ),
        Text(
          value,
          style: valueStyle ??
              TextStyle(
                fontSize: 14.sp,
                fontWeight: FontWeight.w500,
                color: valueColor,
              ),
        ),
      ],
    );
  }
}
