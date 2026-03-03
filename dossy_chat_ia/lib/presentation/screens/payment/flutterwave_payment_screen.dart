import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:flutterwave_standard/flutterwave.dart';
import 'package:app_links/app_links.dart';
import 'dart:async';
import '../../../core/utils/app_logger.dart';
import '../../../data/services/payment_service.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/subscription_provider.dart';

/// Écran de paiement Flutterwave - utilise url_launcher pour ouvrir dans le navigateur
/// Réplique le flux SaaS : initiate -> open browser -> callback -> verify
class FlutterwavePaymentScreen extends StatefulWidget {
  final String planId;
  final String planName;
  final int amount;
  final String currency;
  final String billingCycle;
  final String? couponCode;

  const FlutterwavePaymentScreen({
    super.key,
    required this.planId,
    required this.planName,
    required this.amount,
    required this.currency,
    required this.billingCycle,
    this.couponCode,
  });

  @override
  State<FlutterwavePaymentScreen> createState() => _FlutterwavePaymentScreenState();
}

class _FlutterwavePaymentScreenState extends State<FlutterwavePaymentScreen> {
  final PaymentService _paymentService = PaymentService();
  final AppLinks _appLinks = AppLinks();
  bool _isProcessing = false;
  StreamSubscription? _linkSubscription;

  @override
  void initState() {
    super.initState();
    // Listen for deep links (payment callback)
    _initDeepLinkListener();
    // Auto-initiate payment on screen load
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _initiatePayment();
    });
  }

  @override
  void dispose() {
    _linkSubscription?.cancel();
    super.dispose();
  }

  void _initDeepLinkListener() {
    _linkSubscription = _appLinks.uriLinkStream.listen(
      (Uri uri) {
        if (uri.scheme == 'dossychatia' && uri.host == 'payment') {
          final status = uri.queryParameters['status'];
          if (status == 'success') {
            _handlePaymentSuccess();
          } else {
            final message = uri.queryParameters['message'] ?? 'Paiement échoué';
            _showError(message);
          }
        }
      },
      onError: (err) {
        // Deep link error logged internally
      },
    );
  }

  Future<void> _initiatePayment() async {
    final authProvider = context.read<AuthProvider>();
    final user = authProvider.user;
    final token = authProvider.token;

    if (user == null || token == null) {
      _showError('Vous devez être connecté');
      return;
    }

    setState(() {
      _isProcessing = true;
    });

    try {
      // Call backend to get payment data (same as SaaS planPayWithFlutterwave)
      final result = await _paymentService.initiatePayment(
        planId: widget.planId,
        billingCycle: widget.billingCycle,
        amount: widget.amount.toDouble(),
        currency: widget.currency,
        country: user.jurisdiction,
        email: user.email,
        phone: user.phone ?? '',
        name: user.name,
        token: token,
        paymentMethod: 'flutterwave',
        couponCode: widget.couponCode,
      );

      if (result['success'] == true) {
        final data = result['data'];
        
        // Open Flutterwave payment in browser using Flutterwave SDK
        await _openFlutterwavePayment(data);
      } else {
        throw Exception(result['message'] ?? 'Failed to initialize payment');
      }
    } catch (e) {
      AppLogger.error('🔴 Payment error', error: e);
      _showError(e.toString());
    } finally {
      if (mounted) {
        setState(() {
          _isProcessing = false;
        });
      }
    }
  }

  Future<void> _openFlutterwavePayment(Map<String, dynamic> data) async {
    try {
      final publicKey = (data['public_key'] ?? '').toString();
      if (publicKey.isEmpty) {
        _showError('Clé Flutterwave manquante. Vérifiez la configuration.');
        return;
      }

      final currency = (data['currency'] ?? 'XAF').toString();
      final paymentOptions = (currency == 'XAF' || currency == 'XOF')
          ? 'card,mobilemoneyfranco'
          : 'card,mobilemoney';
      final isTestMode = publicKey.startsWith('FLWPUBK_TEST');

      final customer = Customer(
        name: data['name'] ?? '',
        phoneNumber: data['phone'] ?? '',
        email: data['email'] ?? '',
      );

      final flutterwave = Flutterwave(
        publicKey: publicKey,
        currency: currency,
        redirectUrl: data['redirect_url'] ?? '',
        txRef: data['tx_ref'] ?? '',
        amount: data['amount'].toString(),
        customer: customer,
        paymentOptions: paymentOptions,
        customization: Customization(
          title: 'DOSSY PRO',
          description: 'Paiement ${widget.planName}',
          logo: 'https://dossypro.com/logo.png',
        ),
        isTestMode: isTestMode,
      );

      final ChargeResponse response = await flutterwave.charge(context);
      
      if (response.success ?? false) {
        _handlePaymentSuccess();
      } else {
        _showError('Paiement annulé ou échoué');
        Navigator.pop(context);
      }
    } catch (e) {
      _showError('Erreur: $e');
    }
  }

  void _handlePaymentSuccess() async {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Paiement effectué ! Vérification en cours...'),
        backgroundColor: Colors.green,
        duration: Duration(seconds: 3),
      ),
    );
    
    // Wait a moment for backend to process the subscription
    await Future.delayed(const Duration(seconds: 2));
    
    // Refresh subscription data
    final authProvider = context.read<AuthProvider>();
    final token = authProvider.token;
    if (token != null) {
      await authProvider.refreshUser();
      await context.read<SubscriptionProvider>().fetchCurrentSubscription(token: token);
      
      // Also reload plans to refresh the UI
      await context.read<SubscriptionProvider>().loadPlans(token: token);
    }
    
    // Show success message
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✅ Abonnement activé avec succès !'),
          backgroundColor: Colors.green,
          duration: Duration(seconds: 2),
        ),
      );
    }
    
    // Return to subscription plans screen after a brief delay
    await Future.delayed(const Duration(milliseconds: 500));
    if (mounted) {
      Navigator.of(context).popUntil((route) => route.settings.name == '/subscription-plans' || route.isFirst);
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Erreur'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              Navigator.pop(context);
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Paiement'),
      ),
      body: Center(
        child: _isProcessing
            ? Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  CircularProgressIndicator(
                    valueColor: AlwaysStoppedAnimation<Color>(
                      Theme.of(context).primaryColor,
                    ),
                  ),
                  SizedBox(height: 16.h),
                  Text(
                    'Initialisation du paiement...',
                    style: TextStyle(fontSize: 16.sp),
                  ),
                  SizedBox(height: 8.h),
                  Text(
                    'Vous allez être redirigé vers Flutterwave',
                    style: TextStyle(fontSize: 14.sp, color: Colors.grey),
                    textAlign: TextAlign.center,
                  ),
                ],
              )
            : Padding(
                padding: EdgeInsets.all(16.w),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.payment, size: 64.sp, color: Colors.grey),
                    SizedBox(height: 16.h),
                    Text(
                      'Paiement en cours',
                      style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    SizedBox(height: 8.h),
                    Text(
                      'Complétez le paiement dans la page Flutterwave',
                      style: TextStyle(fontSize: 14.sp, color: Colors.grey),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}
