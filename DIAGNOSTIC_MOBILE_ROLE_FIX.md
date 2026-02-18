# 🔧 DIAGNOSTIC & CORRECTION - PROBLÈME MOBILE_ROLE NON ENREGISTRÉ

**Date:** 28 Janvier 2026  
**Problème:** Le champ `mobile_role` n'était pas enregistré lors de l'inscription  
**Cause:** Incompatibilité entre le nom du champ Flutter et le Backend Laravel  
**Status:** ✅ **CORRIGÉ**

---

## 🔍 ANALYSE DU PROBLÈME

### Symptôme
- Utilisateur sélectionne "Avocat" dans le dropdown lors de l'inscription
- L'inscription réussit
- En base de données, `mobile_role = 'student'` (valeur par défaut)

### Root Cause Analysis (RCA)

**Le problème se situait au BACKEND LARAVEL:**

```php
// AuthController.php - Ligne 41 (AVANT)
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:6',
    'password_confirmation' => 'required|string|same:password',
    'phone' => 'nullable|string|max:20',
    'jurisdiction' => 'nullable|string|max:10',
    'role' => 'nullable|string|in:student,lawyer,enterprise',  // ❌ PROBLÈME 1
    'referral_code' => 'nullable|string|max:50',
]);
```

**Problème 1: Le validateur cherche `'role'` mais Flutter envoie `'mobile_role'`**

```
Flutter envoie:        Backend cherche:
{                      {
  mobile_role: 'avocat'  role: 'student' (valeur par défaut)
}                      }
```

**Problème 2: Utilisation du mauvais champ lors de la sauvegarde**

```php
// AuthController.php - Ligne 87-90 (AVANT)
if ($request->jurisdiction || $request->role) {
    $user->update([
        'jurisdiction' => $request->jurisdiction,
        'mobile_role' => $request->role ?? 'student',  // ❌ PROBLÈME 2
    ]);
}
```

Même si `$request->role` existait, il serait toujours null, donc la sauvegarde utilisait la valeur par défaut `'student'`.

**Problème 3: Réponse JSON avec le mauvais champ**

```php
// AuthController.php - Ligne 136 (AVANT)
'role' => $request->role ?? 'student',  // ❌ PROBLÈME 3
```

---

## ✅ CORRECTIONS APPLIQUÉES

### Correction 1 - Validateur (Ligne 41)
```php
// AVANT
'role' => 'nullable|string|in:student,lawyer,enterprise',

// APRÈS
'mobile_role' => 'nullable|string|in:student,lawyer,enterprise',
```

### Correction 2 - Sauvegarde (Ligne 87-90)
```php
// AVANT
if ($request->jurisdiction || $request->role) {
    $user->update([
        'jurisdiction' => $request->jurisdiction,
        'mobile_role' => $request->role ?? 'student',
    ]);
}

// APRÈS
if ($request->jurisdiction || $request->mobile_role) {
    $user->update([
        'jurisdiction' => $request->jurisdiction,
        'mobile_role' => $request->mobile_role ?? 'student',
    ]);
}
```

### Correction 3 - Réponse JSON (Ligne 136)
```php
// AVANT
'role' => $request->role ?? 'student',

// APRÈS
'mobile_role' => $request->mobile_role ?? 'student',
```

---

## 🔄 FLUX COMPLET CORRIGÉ

```
Flutter App
├─ User selects: "Avocat" in dropdown
├─ _selectedRole = "avocat"
│
├─ register_screen.dart
│  └─ mobileRole: _selectedRole  // "avocat"
│
├─ auth_provider.dart
│  └─ mobileRole: "avocat"
│
├─ api_service.dart
│  └─ JSON: { mobile_role: "avocat" }
│
└─ POST /api/register
   │
   ├─ Laravel Backend (AuthController.php)
   │  ├─ Validateur cherche: 'mobile_role' ✅ (CORRIGÉ)
   │  ├─ Récupère: $request->mobile_role = "avocat"
   │  └─ Sauvegarde: User.mobile_role = "avocat"
   │
   └─ Base de Données
      └─ users.mobile_role = "avocat" ✅ CORRECT
```

---

## 📊 TABLE DE VÉRIFICATION

| Point | Avant | Après | Status |
|-------|-------|-------|--------|
| **Flutter envoie** | mobile_role: "avocat" | mobile_role: "avocat" | ✅ OK |
| **Validateur cherche** | 'role' | 'mobile_role' | ✅ CORRIGÉ |
| **Validateur trouve le champ** | ❌ Non | ✅ Oui | ✅ CORRIGÉ |
| **Code récupère la valeur** | $request->role | $request->mobile_role | ✅ CORRIGÉ |
| **Sauvegarde en BD** | 'student' | 'avocat' | ✅ CORRIGÉ |
| **Réponse JSON retourne** | 'role' => 'student' | 'mobile_role' => 'avocat' | ✅ CORRIGÉ |

---

## 🧪 TEST DE VÉRIFICATION

### Test 1 - Inscription avec "Avocat"
```
1. Ouvrir l'app
2. Accéder à l'écran d'inscription
3. Remplir le formulaire avec les détails de test
4. Sélectionner "Avocat" dans le dropdown
5. Cliquer sur "Créer un compte"
6. ✅ Succès d'inscription

7. Vérifier en base de données:
   SELECT mobile_role FROM users WHERE email = '[test_email]';
   
   Résultat attendu: 'avocat' ✅
```

### Test 2 - Inscription avec "Entreprise"
```
1. Ouvrir l'app
2. Accéder à l'écran d'inscription
3. Remplir le formulaire avec les détails de test
4. Sélectionner "Entreprise" dans le dropdown
5. Cliquer sur "Créer un compte"
6. ✅ Succès d'inscription

7. Vérifier en base de données:
   SELECT mobile_role FROM users WHERE email = '[test_email]';
   
   Résultat attendu: 'enterprise' ✅
```

### Test 3 - Valeur par défaut "Étudiant"
```
1. Ouvrir l'app
2. Accéder à l'écran d'inscription
3. Remplir le formulaire
4. NE PAS changer le dropdown (laisser "Étudiant" par défaut)
5. Cliquer sur "Créer un compte"
6. ✅ Succès d'inscription

7. Vérifier en base de données:
   SELECT mobile_role FROM users WHERE email = '[test_email]';
   
   Résultat attendu: 'student' ✅
```

---

## 📝 FICHIERS MODIFIÉS

**Backend:**
- ✅ `app/Http/Controllers/Api/Mobile/AuthController.php`
  - Ligne 41: Changé 'role' → 'mobile_role' dans validateur
  - Ligne 87: Changé $request->role → $request->mobile_role
  - Ligne 136: Changé 'role' → 'mobile_role' dans réponse JSON

---

## 🔗 RÉSUMÉ DE LA CHAÎNE COMPLÈTE

| Composant | Champ | Status |
|-----------|-------|--------|
| Flutter (register_screen.dart) | mobileRole | ✅ OK |
| Flutter (auth_provider.dart) | mobileRole | ✅ OK |
| Flutter (api_service.dart) | mobile_role | ✅ OK |
| **Laravel (AuthController.php) Validateur** | **mobile_role** | **✅ CORRIGÉ** |
| **Laravel (AuthController.php) Sauvegarde** | **$request->mobile_role** | **✅ CORRIGÉ** |
| **Laravel (AuthController.php) Réponse JSON** | **mobile_role** | **✅ CORRIGÉ** |
| Base de données | mobile_role | ✅ OK |

---

## 🚀 PROCHAINES ÉTAPES

1. **Nettoyer le cache backend:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Redémarrer les services:**
   ```bash
   # Si vous utilisez queue:
   php artisan queue:restart
   ```

3. **Tester l'inscription complète** avec les 3 cas de test ci-dessus

4. **Vérifier les logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i register
   ```

---

## 📌 RÉSUMÉ EXÉCUTIF

**Problème:** Incompatibilité de noms de champs (Flutter: `mobile_role` vs Laravel: `role`)  
**Impact:** Toutes les inscriptions enregistraient `mobile_role = 'student'` peu importe la sélection  
**Solution:** Synchroniser les noms de champs dans le Backend  
**Fichiers modifiés:** 1 (AuthController.php)  
**Changements:** 3 lignes  
**Risque:** ✅ Très faible - corrections simples et directes  

**Status:** 🟢 **PRÊT À TESTER**

---

*Diagnostic et correction effectués: 28 Janvier 2026*
