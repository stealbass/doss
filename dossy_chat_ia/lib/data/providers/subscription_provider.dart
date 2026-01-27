import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/subscription_plan.dart';

class SubscriptionProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  String _currentPlan = 'Gratuit';
  DateTime? _subscriptionEnd;
  bool _isLoading = false;
  String? _error;
  List<SubscriptionPlan> _plans = [];

  String get currentPlan => _currentPlan;
  DateTime? get subscriptionEnd => _subscriptionEnd;
  bool get isLoading => _isLoading;
  String? get error => _error;
  List<SubscriptionPlan> get plans => _plans;

  bool get isSubscriptionActive {
    if (_subscriptionEnd == null) return false;
    return _subscriptionEnd!.isAfter(DateTime.now());
  }

  // Load Subscription Plans from API
  Future<void> loadPlans({String? token}) async {
    _isLoading = true;
    _error = null;
    _plans = []; // Clear cache first
    notifyListeners();

    try {
      print('DEBUG: Loading plans with token: ${token != null ? "present" : "null"}'); // Debug
      final response = await _apiService.getSubscriptionPlans(token: token);
      print('DEBUG: Plans response: $response'); // Debug
      final plansData = response['plans'] ?? response['data'];

      if (response['success'] == true && plansData is List && plansData.isNotEmpty) {
        _plans = plansData
            .whereType<Map<String, dynamic>>()
            .map(_normalizePlan)
            .map((plan) => SubscriptionPlan.fromJson(plan))
            .toList();
        print('DEBUG: Loaded ${_plans.length} plans'); // Debug
        for (final p in _plans) {
          final yearly = p.priceYearly != null ? p.priceYearly!.toInt() : (p.price * 12).toInt();
          print('  - ${p.name}: monthly=${p.price.toInt()} ${p.currency}, yearly=$yearly ${p.currency}');
        }
      } else {
        _plans = _getDefaultPlans();
        if (response['success'] != true) {
          _error = response['message']?.toString();
        }
      }
    } catch (e) {
      print('DEBUG: Error loading plans: $e'); // Debug
      _error = e.toString();
      _plans = _getDefaultPlans();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Map<String, dynamic> _normalizePlan(Map<String, dynamic> plan) {
    final normalized = Map<String, dynamic>.from(plan);

    // Basic required fields
    normalized['id'] = normalized['id']?.toString() ?? normalized['slug']?.toString() ?? normalized['name']?.toString() ?? '';
    normalized['name'] = normalized['name']?.toString() ?? normalized['title']?.toString() ?? '';

    // Pricing: prefer explicit price, then monthly/yearly values
    final priceValue = normalized['price'] ?? normalized['price_monthly'] ?? normalized['price_yearly'] ?? 0;
    normalized['price'] = priceValue is num
        ? priceValue.toDouble()
        : double.tryParse(priceValue.toString()) ?? 0.0;

    // Currency & duration defaults
    normalized['currency'] = normalized['currency'] ?? 'XAF';
    normalized['duration'] = normalized['duration'] ?? 'Par mois';

    // Limits from API (ints) else 0
    final limitsMap = normalized['limits'] is Map
        ? Map<String, int>.from(normalized['limits'] as Map)
        : <String, int>{};

    // Features: prefer API list, else derive from limits + flags (no demo text)
    if (normalized['features'] is List) {
      normalized['features'] = List<String>.from(normalized['features'] as List);
    } else {
      final searches = limitsMap['searches'] ?? normalized['searches_limit'] ?? 0;
      final analyses = limitsMap['analyses'] ?? normalized['ai_analyses_limit'] ?? 0;
      final downloads = limitsMap['downloads'] ?? normalized['pdf_downloads_limit'] ?? 0;
      final hasFullHistory = normalized['has_full_history'] == true;
      final hasAdvancedAi = normalized['has_advanced_ai'] == true;
      final aiModel = (normalized['ai_model'] ?? 'gpt-3.5-turbo').toString();

      normalized['features'] = _buildFeatures(
        searches: searches is int ? searches : int.tryParse(searches.toString()) ?? 0,
        analyses: analyses is int ? analyses : int.tryParse(analyses.toString()) ?? 0,
        downloads: downloads is int ? downloads : int.tryParse(downloads.toString()) ?? 0,
        hasFullHistory: hasFullHistory,
        hasAdvancedAi: hasAdvancedAi,
        aiModel: aiModel,
      );
    }

    normalized['limits'] = limitsMap;

    return normalized;
  }

  // Build feature list from limits and flags (used for fallbacks only)
  List<String> _buildFeatures({
    required int searches,
    required int analyses,
    required int downloads,
    bool hasFullHistory = false,
    bool hasAdvancedAi = false,
    String aiModel = 'gpt-3.5-turbo',
  }) {
    final features = <String>[];

    features.add(searches == -1
        ? 'Recherches illimitées'
        : '$searches recherches par mois');

    features.add(analyses == -1
        ? 'Analyses IA illimitées'
        : '$analyses analyses IA par mois');

    if (downloads == -1) {
      features.add('Téléchargements PDF illimités');
    } else if (downloads > 0) {
      features.add('$downloads téléchargements PDF par mois');
    } else {
      features.add('Pas de téléchargements PDF');
    }

    if (hasFullHistory) {
      features.add('Historique complet des conversations');
    }

    features.add(hasAdvancedAi
        ? 'IA avancée (${aiModel.toUpperCase()})'
        : 'IA standard (${aiModel.toUpperCase()})');

    return features;
  }

  // Default plans as fallback (never used when API returns data)
  List<SubscriptionPlan> _getDefaultPlans() {
    return [
      () {
        const searches = 5;
        const analyses = 2;
        const downloads = 0;
        return SubscriptionPlan(
          id: 'gratuit',
          name: 'Gratuit',
          price: 0,
          currency: 'XAF',
          duration: 'Permanent',
          features: _buildFeatures(
            searches: searches,
            analyses: analyses,
            downloads: downloads,
            hasFullHistory: false,
            hasAdvancedAi: false,
            aiModel: 'gpt-3.5-turbo',
          ),
          limits: {
            'searches': searches,
            'analyses': analyses,
            'downloads': downloads,
          },
        );
      }(),
      () {
        const searches = 50;
        const analyses = 20;
        const downloads = 30;
        return SubscriptionPlan(
          id: 'etudiant',
          name: 'Étudiant',
          price: 2000,
          currency: 'XAF',
          duration: 'Par mois',
          features: _buildFeatures(
            searches: searches,
            analyses: analyses,
            downloads: downloads,
            hasFullHistory: true,
            hasAdvancedAi: false,
            aiModel: 'gpt-3.5-turbo',
          ),
          limits: {
            'searches': searches,
            'analyses': analyses,
            'downloads': downloads,
          },
        );
      }(),
      () {
        const searches = 200;
        const analyses = 100;
        const downloads = 50;
        return SubscriptionPlan(
          id: 'professionnel',
          name: 'Professionnel',
          price: 5000,
          currency: 'XAF',
          duration: 'Par mois',
          features: _buildFeatures(
            searches: searches,
            analyses: analyses,
            downloads: downloads,
            hasFullHistory: true,
            hasAdvancedAi: true,
            aiModel: 'gpt-4',
          ),
          limits: {
            'searches': searches,
            'analyses': analyses,
            'downloads': downloads,
          },
        );
      }(),
      () {
        const searches = -1;
        const analyses = -1;
        const downloads = -1;
        return SubscriptionPlan(
          id: 'cabinet',
          name: 'Cabinet/Entreprise',
          price: 15000,
          currency: 'XAF',
          duration: 'Par mois',
          features: _buildFeatures(
            searches: searches,
            analyses: analyses,
            downloads: downloads,
            hasFullHistory: true,
            hasAdvancedAi: true,
            aiModel: 'gpt-4-turbo',
          ),
          limits: {
            'searches': searches,
            'analyses': analyses,
            'downloads': downloads,
          },
        );
      }(),
    ];
  }

  // Subscription Plans Configuration (deprecated - kept for backward compatibility)
  List<Map<String, dynamic>> get plansAsMap {
    return _plans
        .map((plan) => {
              'id': plan.id,
              'name': plan.name,
              'price': plan.price,
              'currency': plan.currency,
              'duration': plan.duration,
              'features': plan.features,
              'limits': plan.limits,
            })
        .toList();
  }

  // Get Plan Details
  SubscriptionPlan? getPlanDetails(String planName) {
    try {
      return _plans.firstWhere(
        (plan) => plan.name == planName || plan.id == planName,
      );
    } catch (e) {
      return _plans.isNotEmpty ? _plans[0] : null;
    }
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
    required String token,
    String? couponCode,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.initiatePayment(
        token: token,
        planId: planId,
        duration: duration,
        couponCode: couponCode,
      );

      if (response['success'] == true) {
        final plan = _plans.firstWhere((p) => p.id == planId);
        _currentPlan = plan.name;
        _subscriptionEnd = DateTime.now().add(
          duration == 'annual'
              ? const Duration(days: 365)
              : const Duration(days: 30),
        );

        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'] ?? 'Échec du paiement';
        _isLoading = false;
        notifyListeners();
        return false;
      }
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

  // Fetch Current Subscription from API
  Future<void> fetchCurrentSubscription({required String token}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      print('DEBUG: Fetching current subscription with token');
      final response = await _apiService.getCurrentSubscription(token: token);
      print('DEBUG: Current subscription response: $response');

      if (response['success'] == true && response['subscription'] != null) {
        final subscription = response['subscription'];
        final planName = subscription['plan_name']?.toString() ?? 'Gratuit';
        final endDate = subscription['end_date'] != null
            ? DateTime.tryParse(subscription['end_date'].toString())
            : null;

        _currentPlan = planName;
        _subscriptionEnd = endDate;
        print('DEBUG: Updated current plan to: $_currentPlan, expires: $_subscriptionEnd');
      } else {
        // No active subscription, default to free
        _currentPlan = 'Gratuit';
        _subscriptionEnd = null;
        print('DEBUG: No active subscription, defaulting to Gratuit');
      }
    } catch (e) {
      print('DEBUG: Error fetching current subscription: $e');
      _error = e.toString();
      _currentPlan = 'Gratuit';
      _subscriptionEnd = null;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Clear Error
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
