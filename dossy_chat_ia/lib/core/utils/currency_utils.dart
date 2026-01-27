import 'package:intl/intl.dart';

/// Currency formatting utilities for FCFA
class CurrencyUtils {
  /// Format amount in FCFA (1 000 FCFA)
  static String formatFCFA(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'fr_FR',
      symbol: 'FCFA',
      decimalDigits: 0,
      customPattern: '#,##0 ¤',
    );
    return formatter.format(amount);
  }

  /// Format amount without currency symbol
  static String formatAmount(double amount) {
    final formatter = NumberFormat('#,##0', 'fr_FR');
    return formatter.format(amount);
  }

  /// Parse FCFA string to double
  static double? parseFCFA(String value) {
    try {
      // Remove currency symbol and spaces
      final cleaned = value.replaceAll(RegExp(r'[^0-9,.-]'), '');
      // Replace comma with dot for parsing
      final normalized = cleaned.replaceAll(',', '.');
      return double.tryParse(normalized);
    } catch (e) {
      return null;
    }
  }

  /// Calculate discount amount
  static double calculateDiscount(double amount, double discountPercent) {
    return amount * (discountPercent / 100);
  }

  /// Calculate final amount after discount
  static double applyDiscount(double amount, double discountPercent) {
    final discount = calculateDiscount(amount, discountPercent);
    return amount - discount;
  }

  /// Calculate yearly price from monthly (with discount)
  static double calculateYearlyPrice(double monthlyPrice, {double discount = 20}) {
    final yearlyTotal = monthlyPrice * 12;
    return applyDiscount(yearlyTotal, discount);
  }

  /// Format discount percentage
  static String formatDiscount(double percent) {
    return '-${percent.toStringAsFixed(0)}%';
  }

  /// Format savings amount
  static String formatSavings(double amount) {
    return 'Économisez ${formatFCFA(amount)}';
  }

  /// Calculate price per month for yearly subscription
  static double calculateMonthlyEquivalent(double yearlyPrice) {
    return yearlyPrice / 12;
  }

  /// Format price comparison (monthly vs yearly)
  static String formatPriceComparison(double monthlyPrice, double yearlyPrice) {
    final monthlyEquivalent = calculateMonthlyEquivalent(yearlyPrice);
    final savings = monthlyPrice - monthlyEquivalent;
    
    return 'Soit ${formatFCFA(monthlyEquivalent)}/mois (${formatSavings(savings * 12)})';
  }

  /// Validate amount
  static bool isValidAmount(double? amount, {double min = 0}) {
    if (amount == null) return false;
    return amount >= min;
  }

  /// Format subscription price with billing cycle
  static String formatSubscriptionPrice(double amount, String billingCycle) {
    final formatted = formatFCFA(amount);
    if (billingCycle == 'monthly') {
      return '$formatted/mois';
    } else if (billingCycle == 'yearly') {
      return '$formatted/an';
    } else {
      return formatted;
    }
  }

  /// Calculate tax (VAT for some countries)
  static double calculateTax(double amount, {double taxRate = 18}) {
    return amount * (taxRate / 100);
  }

  /// Calculate total with tax
  static double calculateTotalWithTax(double amount, {double taxRate = 18}) {
    final tax = calculateTax(amount, taxRate: taxRate);
    return amount + tax;
  }

  /// Format amount with tax breakdown
  static String formatWithTax(double amount, {double taxRate = 18}) {
    final tax = calculateTax(amount, taxRate: taxRate);
    final total = amount + tax;
    
    return '${formatFCFA(total)} (TVA ${taxRate.toStringAsFixed(0)}% incluse)';
  }

  /// Convert XOF to other currencies (approximate rates)
  static double convertCurrency(double amountXOF, String targetCurrency) {
    // Approximate conversion rates (should be updated from API in production)
    final rates = {
      'EUR': 0.0015,  // 1 XOF = 0.0015 EUR
      'USD': 0.0017,  // 1 XOF = 0.0017 USD
      'GBP': 0.0013,  // 1 XOF = 0.0013 GBP
    };
    
    final rate = rates[targetCurrency] ?? 1.0;
    return amountXOF * rate;
  }

  /// Format number with K/M suffix for large amounts
  static String formatCompact(double amount) {
    if (amount >= 1000000) {
      return '${(amount / 1000000).toStringAsFixed(1)}M';
    } else if (amount >= 1000) {
      return '${(amount / 1000).toStringAsFixed(1)}K';
    } else {
      return amount.toStringAsFixed(0);
    }
  }

  /// Calculate referral reward (typically a percentage of subscription)
  static double calculateReferralReward(double subscriptionAmount, {double rewardPercent = 10}) {
    return subscriptionAmount * (rewardPercent / 100);
  }
}
