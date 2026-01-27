import 'package:flutter/material.dart';
import 'flutterwave_payment_screen.dart';

/// Redirects to FlutterwavePaymentScreen
/// This is kept for backward compatibility
class PaymentScreen extends StatelessWidget {
  final String planId;
  final String planName;
  final int amount;
  final String currency;
  final String billingCycle;

  const PaymentScreen({
    super.key,
    required this.planId,
    required this.planName,
    required this.amount,
    required this.currency,
    required this.billingCycle,
  });

  @override
  Widget build(BuildContext context) {
    return FlutterwavePaymentScreen(
      planId: planId,
      planName: planName,
      amount: amount,
      currency: currency,
      billingCycle: billingCycle,
    );
  }
}
