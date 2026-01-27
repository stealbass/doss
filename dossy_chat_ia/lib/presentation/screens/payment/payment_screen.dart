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
        SnackBar(
          content: Text(l10n.promoCodeApplied),
          backgroundColor: Colors.green,
        ),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${l10n.promoCodeInvalidError}: $e'),
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
    final user = authProvider.user;

    if (user == null) {
      _showErrorDialog('Erreur', 'Vous devez être connecté pour effectuer un paiement');
      return;
    }

    setState(() {
      _isProcessing = true;
    });

    try {
      // Initiate payment with backend
      final token = authProvider.token;
      if (token == null) {
        throw Exception('Token d\'authentification manquant');
      }

      final result = await _paymentService.initiatePayment(
        planId: widget.planId,
        billingCycle: widget.billingCycle,
        amount: _finalAmount.toDouble(),
        currency: widget.currency,
        email: user.email,
        phone: user.phone ?? _phoneNumber,
        name: user.name,
        token: token,
        paymentMethod: _selectedPaymentMethod,
      );

      if (result['success'] == true) {
        // Open Flutterwave payment page
        final paymentLink = result['payment_link'];
        if (paymentLink != null) {
          await _openPaymentPage(paymentLink, result['transaction_reference']);
        } else {
          throw Exception('Lien de paiement non disponible');
        }
      } else {
        throw Exception(result['message'] ?? 'Échec du paiement');
      }
    } catch (e) {
      print('🔴 Payment error: $e');
      _showErrorDialog('Erreur de paiement', e.toString());
    } finally {
      if (mounted) {
        setState(() {
          _isProcessing = false;
        });
      }
    }
  }

  /// Open payment page in browser
  Future<void> _openPaymentPage(String paymentLink, String transactionRef) async {
    try {
      final proceed = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(l10n.redirectingPayment),
          content: const Text(
            'Vous allez être redirigé vers la page de paiement sécurisée Flutterwave. '
            'Une fois le paiement effectué, vous reviendrez automatiquement dans l\'application.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(l10n.cancel),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(l10n.continue_),
            ),
          ],
        ),
      );

      if (proceed != true || !mounted) return;

      final result = await Navigator.push<Map<String, dynamic>?>(
        context,
        MaterialPageRoute(
          builder: (_) => PaymentWebView(
            url: paymentLink,
            transactionRef: transactionRef,
          ),
        ),
      );

      if (result != null && result['status'] == 'success') {
        final callbackUrl = result['callback_url'] as String?;
        final txRef = result['tx_ref'] as String? ?? transactionRef;
        final transactionId = result['transaction_id'] as String? ?? transactionRef;

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(l10n.paymentCompleted),
          ),
        );

        // Call backend verify endpoint
        final verify = await _paymentService.verifyPayment(
          transactionId: transactionId,
          txRef: txRef,
          token: context.read<AuthProvider>().token!,
        );

        if (verify['success'] == true) {
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(l10n.paymentVerified)),
          );
          Navigator.pop(context, {'status': 'success', 'callback': callbackUrl});
        } else {
          throw Exception(verify['message'] ?? 'Vérification échouée');
        }
      }
    } catch (e) {
      _showErrorDialog('Erreur', 'Impossible d\'ouvrir la page de paiement: $e');
    }
  }

  /// Paiement Mobile Money (deprecated - use initiatePayment instead)
  Future<void> _processMobileMoneyPayment(String email, String fullName) async {
    final authProvider = context.read<AuthProvider>();
    final token = authProvider.token;
    
    if (token == null) {
      throw Exception('Token d\'authentification manquant');
    }
    
    final result = await _paymentService.initiateMobileMoneyPayment(
      planId: widget.planId,
      phone: _phoneNumber,
      operator: _selectedProvider,
      amount: _finalAmount.toDouble(),
      currency: widget.currency,
      email: email,
      name: fullName,
      token: token,
    );

    if (result['success'] == true) {
      // Attendre la confirmation du paiement
      await _showPaymentConfirmationDialog(result['transaction_id'] ?? '');
    } else {
      throw Exception(result['message'] ?? 'Échec du paiement');
    }
  }

  /// Paiement par carte
  Future<void> _processCardPayment(String email, String fullName) async {
    // TODO: Implémenter le paiement par carte avec Flutterwave
    // Pour l'instant, afficher un message
    _showErrorDialog(
      'Fonctionnalité en développement',
      'Le paiement par carte sera bientôt disponible. Veuillez utiliser Mobile Money pour le moment.',
    );
    throw Exception('Paiement par carte non encore implémenté');
  }

  /// Afficher la confirmation de paiement
  Future<void> _showPaymentConfirmationDialog(String transactionId) async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Text(l10n.paymentConfirmation),
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
            child: Text(l10n.cancel),
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
      subscriptionProvider.updateSubscription(
        plan: widget.planName,
        endDate: DateTime.now().add(
          widget.billingCycle == 'yearly' 
            ? const Duration(days: 365) 
            : const Duration(days: 30)
        ),
      );

      // Afficher le succès
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => AlertDialog(
          title: Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green, size: 32.sp),
              SizedBox(width: 12.w),
              Text(l10n.paymentSuccess),
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
              child: Text(l10n.ok),
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
            child: Text(l10n.ok),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.payment),
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
                          'Payer $_finalAmount ${widget.currency}',
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

/// WebView pour afficher la page Flutterwave en in-app
class PaymentWebView extends StatefulWidget {
  final String url;
  final String transactionRef;

  const PaymentWebView({
    super.key,
    required this.url,
    required this.transactionRef,
  });

  @override
  State<PaymentWebView> createState() => _PaymentWebViewState();
}

class _PaymentWebViewState extends State<PaymentWebView> {
  late final WebViewController _controller;
  double _progress = 0;
  bool _loadError = false;
  String? _errorDescription;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setUserAgent(
        'Mozilla/5.0 (Linux; Android 10; WebView) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
      )
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) => setState(() => _progress = 0.05),
          onProgress: (p) => setState(() => _progress = p / 100),
          onPageFinished: (_) => setState(() => _progress = 1.0),
          onWebResourceError: (error) async {
            setState(() {
              _loadError = true;
              _errorDescription = error.description;
            });
            // Fallback: open in external browser if WebView fails
            await _openExternal(widget.url);
          },
          onNavigationRequest: (request) {
            // Intercepter le deep-link de callback
            if (request.url.startsWith('dossychatia://payment/callback')) {
              final uri = Uri.parse(request.url);
              final txRef = uri.queryParameters['tx_ref'];
              final transactionId = uri.queryParameters['transaction_id'];
              Navigator.pop(context, {
                'status': 'success',
                'callback_url': request.url,
                'tx_ref': txRef,
                'transaction_id': transactionId,
              });
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.securedPayment),
      ),
      body: Column(
        children: [
          if (_progress < 1.0)
            LinearProgressIndicator(value: _progress),
          Expanded(
            child: _loadError
                ? _ErrorFallback(
                    description: _errorDescription,
                    onRetry: () {
                      setState(() {
                        _loadError = false;
                        _progress = 0;
                      });
                      _controller.loadRequest(Uri.parse(widget.url));
                    },
                    onOpenExternal: () => _openExternal(widget.url),
                  )
                : WebViewWidget(controller: _controller),
          ),
        ],
      ),
    );
  }

  Future<void> _openExternal(String url) async {
    if (!await launchUrlString(url, mode: LaunchMode.externalApplication)) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.cannotOpenPaymentLink)),
      );
    }
  }
}

class _ErrorFallback extends StatelessWidget {
  final String? description;
  final VoidCallback onRetry;
  final Future<void> Function() onOpenExternal;

  const _ErrorFallback({
    required this.description,
    required this.onRetry,
    required this.onOpenExternal,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off, size: 48, color: Colors.grey),
            const SizedBox(height: 12),
            const Text(
              'Page de paiement indisponible',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
            if (description != null) ...[
              const SizedBox(height: 8),
              Text(
                description!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.grey),
              ),
            ],
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                ElevatedButton(
                  onPressed: onRetry,
                  child: Text(l10n.retryAction),
                ),
                const SizedBox(width: 12),
                OutlinedButton(
                  onPressed: onOpenExternal,
                  child: Text(l10n.openInBrowser),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}