import 'package:flutter/material.dart';

class SubscriptionProvider with ChangeNotifier {
  String _currentPlan = 'Gratuit';
  DateTime? _subscriptionEnd;
  bool _isLoading = false;
  String? _error;
  
  String get currentPlan => _currentPlan;
  DateTime? get subscriptionEnd => _subscriptionEnd;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  bool get isSubscriptionActive {
    if (_subscriptionEnd == null) return false;
    return _subscriptionEnd!.isAfter(DateTime.now());
  }
  
  // Subscription Plans Configuration
  final List<Map<String, dynamic>> plans = [
    {
      'id': 'gratuit',
      'name': 'Gratuit',
      'price': 0,
      'currency': 'XAF',
      'duration': 'Permanent',
      'features': [
        '5 recherches par mois',
        '2 analyses IA par mois',
        'Accès bibliothèque juridique de base',
        'Chat IA limité',
      ],
      'limits': {
        'searches': 5,
        'analyses': 2,
        'downloads': 0,
      }
    },
    {
      'id': 'etudiant',
      'name': 'Étudiant',
      'price': 5000,
      'currency': 'XAF',
      'duration': 'Par mois',
      'features': [
        '50 recherches par mois',
        '20 analyses IA par mois',
        '10 téléchargements PDF',
        'Générateur de fiches d\'arrêt',
        'Générateur de fiches de révision',
        'QCM interactifs',
        'Mode révision active',
        'Transcription audio des cours',
      ],
      'limits': {
        'searches': 50,
        'analyses': 20,
        'downloads': 10,
      }
    },
    {
      'id': 'professionnel',
      'name': 'Professionnel',
      'price': 15000,
      'currency': 'XAF',
      'duration': 'Par mois',
      'features': [
        '200 recherches par mois',
        '100 analyses IA par mois',
        '50 téléchargements PDF',
        'Anonymisation automatique',
        'Transcription audio',
        'Export Word éditable',
        'Modèles de contrats',
        'Veille juridique et alertes',
        'Assistant fiscal et social',
      ],
      'limits': {
        'searches': 200,
        'analyses': 100,
        'downloads': 50,
      }
    },
    {
      'id': 'cabinet',
      'name': 'Cabinet/Entreprise',
      'price': 50000,
      'currency': 'XAF',
      'duration': 'Par mois',
      'features': [
        'Recherches illimitées',
        'Analyses IA illimitées',
        'Téléchargements illimités',
        'Multi-comptes (jusqu\'à 10 utilisateurs)',
        'Anonymisation automatique',
        'Export Word éditable',
        'Modèles de contrats premium',
        'Simulateurs RH et paie',
        'Veille juridique personnalisée',
        'Alertes Email et WhatsApp',
        'Support prioritaire 24/7',
        'Formation et onboarding',
      ],
      'limits': {
        'searches': -1, // unlimited
        'analyses': -1,
        'downloads': -1,
      }
    },
  ];
  
  // Get Plan Details
  Map<String, dynamic>? getPlanDetails(String planName) {
    return plans.firstWhere(
      (plan) => plan['name'] == planName,
      orElse: () => plans[0],
    );
  }
  
  // Update Subscription
  void updateSubscription({
    required String plan,
    DateTime? endDate,
  }) {
    _currentPlan = plan;
    _subscriptionEnd = endDate;
    notifyListeners();
  }
  
  // Initiate Subscription Payment
  Future<bool> initiatePayment({
    required String planId,
    required String duration, // monthly, annual
    String? couponCode,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      // TODO: Implement Flutterwave payment integration
      // This is a placeholder for the actual payment implementation
      
      await Future.delayed(const Duration(seconds: 2));
      
      // Simulate successful payment
      final plan = plans.firstWhere((p) => p['id'] == planId);
      _currentPlan = plan['name'];
      _subscriptionEnd = DateTime.now().add(
        duration == 'annual'
            ? const Duration(days: 365)
            : const Duration(days: 30),
      );
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Cancel Subscription
  Future<bool> cancelSubscription() async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      // TODO: Implement subscription cancellation API call
      
      await Future.delayed(const Duration(seconds: 1));
      
      _currentPlan = 'Gratuit';
      _subscriptionEnd = null;
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
  
  // Apply Coupon
  Future<Map<String, dynamic>?> applyCoupon(String couponCode) async {
    _isLoading = true;
    _error = null;
    notifyListeners();
    
    try {
      // TODO: Implement coupon validation API call
      
      await Future.delayed(const Duration(seconds: 1));
      
      // Simulate coupon response
      final discount = {
        'valid': true,
        'discount_percentage': 20,
        'discount_amount': 1000,
        'message': 'Coupon appliqué avec succès : -20%',
      };
      
      _isLoading = false;
      notifyListeners();
      return discount;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }
  
  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
