# 🎟️ SYSTÈME DE COUPONS - INTÉGRATION FLUTTER

## 🎯 OBJECTIF

Intégrer le système de coupons (codes promo) existant dans l'admin avec l'application Flutter pour permettre aux utilisateurs d'appliquer des réductions lors de l'achat d'abonnements.

---

## ✅ IMPLÉMENTATION COMPLÈTE

### 1. 📱 API Endpoints Créés

#### **POST** `/api/mobile/coupons/validate`
Valider et appliquer un code coupon à un plan d'abonnement.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "coupon_code": "PROMO2024",
  "plan_id": 1
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Code promo appliqué avec succès",
  "coupon_valid": true,
  "coupon": {
    "id": 1,
    "code": "PROMO2024",
    "name": "Promotion 2024",
    "discount": 20,
    "description": "20% de réduction sur tous les plans"
  },
  "pricing": {
    "original_price": 5000,
    "discount_amount": 1000,
    "discount_percentage": 20,
    "final_price": 4000,
    "currency": "XOF"
  },
  "usage": {
    "used_count": 5,
    "limit": 100,
    "remaining": 95
  }
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Code promo invalide ou expiré",
  "coupon_valid": false
}
```

**Response Error (400) - Limite atteinte:**
```json
{
  "success": false,
  "message": "Ce code promo a atteint sa limite d'utilisation",
  "coupon_valid": false
}
```

**Response Error (400) - Déjà utilisé:**
```json
{
  "success": false,
  "message": "Vous avez déjà utilisé ce code promo",
  "coupon_valid": false
}
```

---

#### **POST** `/api/mobile/coupons/mark-used`
Marquer un coupon comme utilisé après un paiement réussi.

**Headers:**
```http
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "coupon_code": "PROMO2024",
  "order_id": "FLW-1234567890"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Coupon marqué comme utilisé avec succès"
}
```

---

#### **GET** `/api/mobile/coupons/my-history`
Récupérer l'historique des coupons utilisés par l'utilisateur connecté.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Historique des coupons récupéré avec succès",
  "data": {
    "total_used": 2,
    "coupons": [
      {
        "id": 1,
        "coupon_code": "PROMO2024",
        "coupon_name": "Promotion 2024",
        "discount": 20,
        "order_id": "FLW-1234567890",
        "used_at": "2026-01-05 19:30:00",
        "used_at_human": "il y a 2 heures"
      },
      {
        "id": 2,
        "coupon_code": "WELCOME10",
        "coupon_name": "Bienvenue",
        "discount": 10,
        "order_id": "FLW-9876543210",
        "used_at": "2026-01-01 10:15:00",
        "used_at_human": "il y a 4 jours"
      }
    ]
  }
}
```

---

#### **GET** `/api/mobile/coupons/available`
Récupérer tous les coupons actifs et disponibles.

**Headers:**
```http
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Coupons disponibles récupérés avec succès",
  "data": {
    "total_available": 3,
    "coupons": [
      {
        "id": 1,
        "code": "PROMO2024",
        "name": "Promotion 2024",
        "description": "20% de réduction sur tous les plans",
        "discount": 20,
        "usage": {
          "used_count": 5,
          "limit": 100,
          "remaining": 95
        }
      },
      {
        "id": 2,
        "code": "WELCOME10",
        "name": "Bienvenue",
        "description": "10% pour les nouveaux utilisateurs",
        "discount": 10,
        "usage": {
          "used_count": 50,
          "limit": 200,
          "remaining": 150
        }
      }
    ]
  }
}
```

---

### 2. 📂 Fichiers Créés/Modifiés

| Fichier | Type | Action | Description |
|---------|------|--------|-------------|
| `app/Http/Controllers/Api/Mobile/CouponApiController.php` | Créé | Contrôleur API | Gère validation, utilisation, historique |
| `app/Models/Coupon.php` | Modifié | Modèle | Ajout `is_active` dans fillable |
| `app/Models/UserCoupon.php` | Modifié | Modèle | Ajout `order` et relation `couponDetails` |
| `routes/api.php` | Modifié | Routes | Ajout 4 endpoints coupons |

---

### 3. 🔄 Flux Complet d'Utilisation

```
┌─────────────────────────────────────────────────────┐
│  1. ADMIN CRÉE UN COUPON                            │
│     - Code: PROMO2024                               │
│     - Réduction: 20%                                │
│     - Limite: 100 utilisations                      │
│     - Status: Actif                                 │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  2. FLUTTER - SÉLECTION PLAN                        │
│     - Utilisateur choisit un plan (5000 XOF)        │
│     - Clique sur "Appliquer un code promo"          │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  3. FLUTTER - SAISIE CODE COUPON                    │
│     - Utilisateur tape: PROMO2024                   │
│     - API: POST /api/mobile/coupons/validate        │
│     - Envoie: { coupon_code, plan_id }             │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  4. API - VALIDATION                                │
│     ✅ Coupon existe et actif                       │
│     ✅ Limite pas atteinte (5/100)                  │
│     ✅ Utilisateur ne l'a jamais utilisé            │
│     ✅ Calcul réduction: 5000 - 20% = 4000 XOF     │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  5. FLUTTER - AFFICHAGE PRIX RÉDUIT                 │
│     - Prix original: 5000 XOF                       │
│     - Réduction: -1000 XOF (20%)                    │
│     - Prix final: 4000 XOF ✅                       │
│     - Bouton "Payer 4000 XOF"                       │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  6. FLUTTER - PAIEMENT FLUTTERWAVE                  │
│     - Montant: 4000 XOF (avec réduction)            │
│     - Gateway: Flutterwave                          │
│     - Paiement réussi: transaction_id reçu          │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  7. FLUTTER - MARQUER COUPON UTILISÉ                │
│     - API: POST /api/mobile/coupons/mark-used       │
│     - Envoie: { coupon_code, order_id }            │
│     - Enregistre utilisation dans BD                │
└─────────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────────┐
│  8. RÉSULTAT FINAL                                  │
│     ✅ Abonnement activé                            │
│     ✅ Coupon marqué comme utilisé                  │
│     ✅ Utilisateur ne peut plus réutiliser          │
│     ✅ Compteur: 6/100 utilisations                 │
└─────────────────────────────────────────────────────┘
```

---

### 4. 📱 Exemple Code Flutter (Dart)

#### Service API Coupons
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class CouponService {
  final String baseUrl = 'https://dossypro.com/api/mobile';
  final String token; // Token d'authentification

  CouponService(this.token);

  /// Valider un code coupon
  Future<Map<String, dynamic>> validateCoupon({
    required String couponCode,
    required int planId,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/coupons/validate'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'coupon_code': couponCode,
        'plan_id': planId,
      }),
    );

    return jsonDecode(response.body);
  }

  /// Marquer un coupon comme utilisé
  Future<Map<String, dynamic>> markCouponUsed({
    required String couponCode,
    required String orderId,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/coupons/mark-used'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'coupon_code': couponCode,
        'order_id': orderId,
      }),
    );

    return jsonDecode(response.body);
  }

  /// Récupérer l'historique des coupons
  Future<List<dynamic>> getMyCoupons() async {
    final response = await http.get(
      Uri.parse('$baseUrl/coupons/my-history'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    final data = jsonDecode(response.body);
    return data['data']['coupons'];
  }

  /// Récupérer les coupons disponibles
  Future<List<dynamic>> getAvailableCoupons() async {
    final response = await http.get(
      Uri.parse('$baseUrl/coupons/available'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    final data = jsonDecode(response.body);
    return data['data']['coupons'];
  }
}
```

#### Widget Saisie Code Promo
```dart
class CouponInputWidget extends StatefulWidget {
  final int planId;
  final double planPrice;
  final Function(Map<String, dynamic>) onCouponApplied;

  CouponInputWidget({
    required this.planId,
    required this.planPrice,
    required this.onCouponApplied,
  });

  @override
  _CouponInputWidgetState createState() => _CouponInputWidgetState();
}

class _CouponInputWidgetState extends State<CouponInputWidget> {
  final TextEditingController _couponController = TextEditingController();
  bool _isValidating = false;
  Map<String, dynamic>? _appliedCoupon;

  Future<void> _validateCoupon() async {
    if (_couponController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Veuillez saisir un code promo')),
      );
      return;
    }

    setState(() => _isValidating = true);

    try {
      final couponService = CouponService(authToken);
      final result = await couponService.validateCoupon(
        couponCode: _couponController.text.trim(),
        planId: widget.planId,
      );

      setState(() => _isValidating = false);

      if (result['success']) {
        setState(() => _appliedCoupon = result);
        widget.onCouponApplied(result);
        
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      setState(() => _isValidating = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Champ saisie code promo
        TextField(
          controller: _couponController,
          decoration: InputDecoration(
            labelText: 'Code promo',
            hintText: 'Ex: PROMO2024',
            border: OutlineInputBorder(),
            suffixIcon: _isValidating
                ? CircularProgressIndicator()
                : IconButton(
                    icon: Icon(Icons.check),
                    onPressed: _validateCoupon,
                  ),
          ),
          textCapitalization: TextCapitalization.characters,
        ),
        
        SizedBox(height: 10),
        
        // Affichage réduction appliquée
        if (_appliedCoupon != null) ...[
          Container(
            padding: EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.green.shade50,
              border: Border.all(color: Colors.green),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.check_circle, color: Colors.green),
                    SizedBox(width: 8),
                    Text(
                      'Code promo appliqué',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                    ),
                  ],
                ),
                SizedBox(height: 8),
                Text(
                  'Réduction: ${_appliedCoupon!['pricing']['discount_percentage']}%',
                  style: TextStyle(fontSize: 16),
                ),
                Text(
                  'Prix original: ${_appliedCoupon!['pricing']['original_price']} XOF',
                  style: TextStyle(
                    decoration: TextDecoration.lineThrough,
                    color: Colors.grey,
                  ),
                ),
                Text(
                  'Nouveau prix: ${_appliedCoupon!['pricing']['final_price']} XOF',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.green,
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}
```

---

### 5. 🎯 Intégration avec Paiement

```dart
// Lors du paiement réussi
Future<void> _handlePaymentSuccess(String transactionId) async {
  // 1. Activer l'abonnement
  await subscriptionService.activateSubscription(
    planId: selectedPlanId,
    transactionId: transactionId,
  );

  // 2. Si un coupon a été utilisé, le marquer comme tel
  if (appliedCouponCode != null) {
    await couponService.markCouponUsed(
      couponCode: appliedCouponCode!,
      orderId: transactionId,
    );
  }

  // 3. Rediriger vers succès
  Navigator.pushReplacement(
    context,
    MaterialPageRoute(builder: (_) => SuccessScreen()),
  );
}
```

---

### 6. 🔒 Sécurité & Validations

#### Côté Backend (API):
✅ Vérifie que le coupon existe et est actif  
✅ Vérifie que la limite n'est pas atteinte  
✅ Vérifie que l'utilisateur ne l'a pas déjà utilisé  
✅ Vérifie que le plan_id existe  
✅ Calculs précis des réductions  

#### Côté Frontend (Flutter):
✅ Validation du format du code  
✅ Feedback visuel immédiat  
✅ Gestion des erreurs réseau  
✅ Double vérification avant paiement  

---

### 7. 🧪 Tests

#### Test 1: Valider un coupon valide
```bash
curl -X POST https://dossypro.com/api/mobile/coupons/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "coupon_code": "PROMO2024",
    "plan_id": 1
  }'
```

#### Test 2: Coupon invalide
```bash
curl -X POST https://dossypro.com/api/mobile/coupons/validate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "coupon_code": "INVALID123",
    "plan_id": 1
  }'
```

#### Test 3: Marquer comme utilisé
```bash
curl -X POST https://dossypro.com/api/mobile/coupons/mark-used \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "coupon_code": "PROMO2024",
    "order_id": "FLW-1234567890"
  }'
```

#### Test 4: Historique
```bash
curl -X GET https://dossypro.com/api/mobile/coupons/my-history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 8. 📊 Base de Données

#### Table `coupons`:
```sql
id | name           | code       | discount | limit | is_active | created_at
---|----------------|------------|----------|-------|-----------|------------
1  | Promotion 2024 | PROMO2024  | 20.00    | 100   | 1         | 2026-01-05
2  | Bienvenue      | WELCOME10  | 10.00    | 200   | 1         | 2026-01-05
```

#### Table `user_coupons`:
```sql
id | user | coupon | order             | created_at
---|------|--------|-------------------|------------
1  | 5    | 1      | FLW-1234567890    | 2026-01-05 19:30:00
2  | 8    | 1      | FLW-9876543210    | 2026-01-05 18:15:00
```

---

### 9. ✅ Checklist Finale

- [x] Contrôleur API créé
- [x] Routes API configurées
- [x] Modèles mis à jour
- [x] Validation backend implémentée
- [x] Gestion des erreurs
- [x] Documentation complète
- [ ] Tests unitaires
- [ ] Tests d'intégration Flutter
- [ ] Déploiement en production

---

### 10. 🚀 Déploiement

```bash
# 1. Vérifier les routes API
php artisan route:list | grep coupon

# 2. Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Tester les endpoints
curl https://dossypro.com/api/mobile/coupons/available \
  -H "Authorization: Bearer TOKEN"
```

---

## 📝 RÉSUMÉ

**Système complet de coupons intégré entre Admin et Flutter:**

1. ✅ Admin crée des coupons avec code, réduction, limite
2. ✅ Flutter valide le coupon via API
3. ✅ Calcul automatique de la réduction
4. ✅ Application au montant du paiement
5. ✅ Marquage comme utilisé après paiement
6. ✅ Historique des coupons pour chaque utilisateur
7. ✅ Protection contre réutilisation
8. ✅ Gestion des limites d'utilisation

**Date**: 5 janvier 2026  
**Status**: ✅ Prêt pour intégration Flutter
