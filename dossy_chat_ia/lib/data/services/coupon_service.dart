import 'dart:convert';
import 'package:http/http.dart' as http;

/// Service pour gérer les coupons de réduction
class CouponService {
  static const String baseUrl = 'https://dossypro.com/api/mobile';

  /// Valider un code coupon pour un plan spécifique
  /// 
  /// Returns:
  /// - success: bool
  /// - message: String
  /// - coupon: Map avec détails du coupon
  /// - pricing: Map avec calculs de prix
  Future<Map<String, dynamic>> validateCoupon({
    required String couponCode,
    required String planId,
    required String token,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/coupons/validate'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'coupon_code': couponCode.toUpperCase(),
          'plan_id': int.tryParse(planId) ?? 0,
        }),
      );

      final data = jsonDecode(response.body);
      
      if (response.statusCode == 200) {
        return data;
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Erreur lors de la validation du coupon',
          'coupon_valid': false,
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur réseau: $e',
        'coupon_valid': false,
      };
    }
  }

  /// Marquer un coupon comme utilisé après paiement réussi
  Future<Map<String, dynamic>> markCouponAsUsed({
    required String couponCode,
    required String orderId,
    required String token,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/coupons/mark-used'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'coupon_code': couponCode.toUpperCase(),
          'order_id': orderId,
        }),
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur lors du marquage du coupon: $e',
      };
    }
  }

  /// Récupérer l'historique des coupons utilisés par l'utilisateur
  Future<Map<String, dynamic>> getMyCouponsHistory({
    required String token,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/coupons/my-history'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur lors de la récupération de l\'historique: $e',
      };
    }
  }

  /// Récupérer tous les coupons disponibles
  Future<Map<String, dynamic>> getAvailableCoupons({
    required String token,
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/coupons/available'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      final data = jsonDecode(response.body);
      return data;
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur lors de la récupération des coupons: $e',
      };
    }
  }
}
