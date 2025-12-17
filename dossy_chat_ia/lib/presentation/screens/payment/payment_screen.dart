import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutterwave_standard/flutterwave.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/services/payment_service.dart';
import '../../../data/providers/subscription_provider.dart';
import '../../../data/providers/auth_provider.dart';
import '../../widgets/payment/payment_method_selector.dart';
import '../../widgets/payment/payment_summary_card.dart';

/// Écran de paiement avec intégration Flutterwave
/// Fonctionnalités :
/// - Paiement mobile money (MTN, Orange, Moov)
/// - Paiement par carte bancaire
/// - Récapitulatif et confirmation
/// - Gestion des codes promo
/// - Historique des transactions
class PaymentScreen extends StatefulWidget {
  final String planId;
  final String planName;
  final int amount;
  final String currency;
  final String billingCycle;

  const PaymentScreen({
    Key? key,
    required this.planId,
    required this.planName,
    required this.amount,
    required this.currency,
    required this.billingCycle,
  }) : super(key: key);

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  final PaymentService _paymentService = PaymentService();
  final TextEditingController _promoCodeController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  String _selectedPaymentMethod = 'mobile_money';
  String _selectedProvider = 'mtn';
  String _phoneNumber = '';
  bool _isProcessing = false;
  double _discount = 0.0;
  String? _promoCode;

  @override
  void dispose() {
    _promoCodeController.dispose();
    super.dispose();
  }

  /// Calculer le montant final
  int get _finalAmount {
    return (widget.amount * (1 - _discount / 100)).round();
  }

  /// Appliquer un code promo
  Future<void> _applyPromoCode() async {
    if (_promoCodeController.text.trim().isEmpty) return;

    setState(() {
      _isProcessing = true;
    });

    try {
      // TODO: Appel API pour valider le code promo
      // Simulation pour le moment
      setState(() {
        _discount = 10.0; // 10% de réduction
        _promoCode = _promoCodeController.text.trim();
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Code promo appliqué avec succès !'),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Code promo invalide: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isProcessing = false;
      });
    }
  }

  /// Initier le paiement
  Future<void> _initiatePayment() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = context.read<AuthProvider>();
    final user = authProvider.currentUser;

    if (user == null) {
      _showErrorDialog('Erreur', 'Vous devez être connecté pour effectuer un paiement');
      return;
    }

    setState(() {
      _isProcessing = true;
    });

    try {
      if (_selectedPaymentMethod == 'mobile_money') {
        await _processMobileMoneyPayment(user.email, user.fullName);
      } else {
        await _processCardPayment(user.email, user.fullName);
      }
    } catch (e) {
      _showErrorDialog('Erreur de paiement', e.toString());
    } finally {
      setState(() {
        _isProcessing = false;
      });
    }
  }

  /// Paiement Mobile Money
  Future<void> _processMobileMoneyPayment(String email, String fullName) async {
    final result = await _paymentService.initiateMobileMoneyPayment(
      amount: _finalAmount.toDouble(),
      currency: widget.currency,
      planId: widget.planId,
      phoneNumber: _phoneNumber,
      provider: _selectedProvider,
    );

    if (result['status'] == 'success') {
      // Attendre la confirmation du paiement
      await _showPaymentConfirmationDialog(result['transactionId']);
    } else {
      throw Exception(result['message'] ?? 'Échec du paiement');
    }
  }

  /// Paiement par carte
  Future<void> _processCardPayment(String email, String fullName) async {
    final customer = Customer(
      name: fullName,
      phoneNumber: _phoneNumber,
      email: email,
    );

    final flutterwaveStyle = FlutterwaveStyle(
      appBarText: "Paiement ${widget.planName}",
      buttonColor: AppConstants.primaryColor,
      appBarIcon: const Icon(Icons.arrow_back, color: Colors.white),
      buttonTextStyle: const TextStyle(
        color: Colors.white,
        fontWeight: FontWeight.bold,
        fontSize: 16,
      ),
      appBarColor: AppConstants.primaryColor,
      dialogCancelTextStyle: const TextStyle(
        color: Colors.redAccent,
        fontSize: 18,
      ),
      dialogContinueTextStyle: const TextStyle(
        color: Colors.blue,
        fontSize: 18,
      ),
    );

    final flutterwave = Flutterwave(
      context: context,
      style: flutterwaveStyle,
      publicKey: AppConstants.flutterwavePublicKey,
      currency: widget.currency,
      redirectUrl: "https://dossypro.com/payment-callback",
      txRef: "DOSSY_${DateTime.now().millisecondsSinceEpoch}",
      amount: _finalAmount.toString(),
      customer: customer,
      paymentOptions: "card, banktransfer",
      customization: Customization(title: "Abonnement ${widget.planName}"),
      isTestMode: AppConstants.isTestMode,
    );

    final ChargeResponse response = await flutterwave.charge();
    
    if (response.success == true) {
      await _handleSuccessfulPayment(response.transactionId!);
    } else {
      throw Exception(response.status ?? 'Paiement annulé');
    }
  }

  /// Afficher la confirmation de paiement
  Future<void> _showPaymentConfirmationDialog(String transactionId) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Confirmation de paiement'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const CircularProgressIndicator(),
            SizedBox(height: 16.h),
            Text(
              'Veuillez confirmer le paiement sur votre téléphone',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 14.sp),
            ),
            SizedBox(height: 8.h),
            Text(
              'Transaction: $transactionId',
              style: TextStyle(
                fontSize: 12.sp,
                color: Colors.grey,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
            },
            child: const Text('Annuler'),
          ),
        ],
      ),
    );
  }

  /// Gérer le paiement réussi
  Future<void> _handleSuccessfulPayment(String transactionId) async {
    try {
      // Mettre à jour l'abonnement
      final subscriptionProvider = context.read<SubscriptionProvider>();
      await subscriptionProvider.updateSubscription(widget.planId);

      // Afficher le succès
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => AlertDialog(
          title: Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green, size: 32.sp),
              SizedBox(width: 12.w),
              const Text('Paiement réussi !'),
            ],
          ),
          content: Text(
            'Votre abonnement ${widget.planName} a été activé avec succès.',
            style: TextStyle(fontSize: 14.sp),
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Navigator.of(context).pop(true);
              },
              child: const Text('OK'),
            ),
          ],
        ),
      );
    } catch (e) {
      _showErrorDialog('Erreur', 'Le paiement a réussi mais l\'activation a échoué: $e');
    }
  }

  /// Afficher une erreur
  void _showErrorDialog(String title, String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
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
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16.w),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Récapitulatif
              PaymentSummaryCard(
                planName: widget.planName,
                amount: widget.amount,
                discount: _discount,
                finalAmount: _finalAmount,
                currency: widget.currency,
                billingCycle: widget.billingCycle,
              ),

              SizedBox(height: 24.h),

              // Code promo
              Text(
                'Code promo',
                style: TextStyle(
                  fontSize: 16.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),
              SizedBox(height: 12.h),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _promoCodeController,
                      decoration: InputDecoration(
                        hintText: 'Entrez votre code',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                        enabled: _promoCode == null,
                      ),
                      textCapitalization: TextCapitalization.characters,
                    ),
                  ),
                  SizedBox(width: 12.w),
                  ElevatedButton(
                    onPressed: _promoCode == null && !_isProcessing
                        ? _applyPromoCode
                        : null,
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(
                        horizontal: 16.w,
                        vertical: 16.h,
                      ),
                    ),
                    child: Text(_promoCode == null ? 'Appliquer' : 'Appliqué'),
                  ),
                ],
              ),

              SizedBox(height: 24.h),

              // Méthode de paiement
              PaymentMethodSelector(
                selectedMethod: _selectedPaymentMethod,
                selectedProvider: _selectedProvider,
                phoneNumber: _phoneNumber,
                onMethodChanged: (value) {
                  setState(() {
                    _selectedPaymentMethod = value;
                  });
                },
                onProviderChanged: (value) {
                  setState(() {
                    _selectedProvider = value;
                  });
                },
                onPhoneNumberChanged: (value) {
                  setState(() {
                    _phoneNumber = value;
                  });
                },
              ),

              SizedBox(height: 32.h),

              // Bouton de paiement
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isProcessing ? null : _initiatePayment,
                  style: ElevatedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 16.h),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                  ),
                  child: _isProcessing
                      ? SizedBox(
                          height: 20.h,
                          width: 20.h,
                          child: const CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : Text(
                          'Payer ${_finalAmount} ${widget.currency}',
                          style: TextStyle(
                            fontSize: 16.sp,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),

              SizedBox(height: 16.h),

              // Informations de sécurité
              Container(
                padding: EdgeInsets.all(12.w),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(12.r),
                ),
                child: Row(
                  children: [
                    Icon(Icons.lock, color: Colors.blue, size: 20.sp),
                    SizedBox(width: 12.w),
                    Expanded(
                      child: Text(
                        'Paiement sécurisé par Flutterwave',
                        style: TextStyle(
                          fontSize: 12.sp,
                          color: Colors.blue.shade900,
                        ),
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
