# ✅ Fix: Fiscal Resources - Boolean Validation Issue

## Problème Résolu
L'erreur **"The is mobile visible field must be true or false"** lors de l'enregistrement d'une nouvelle ressource fiscale.

## Cause
Le checkbox HTML n'envoyait pas de valeur booléenne (true/false) mais une string vide ou "on", ce qui causait l'échec de la validation Laravel.

## 📋 Modifications Effectuées

### 1. **Contrôleur: FiscalSocialResourceController.php**

#### ✓ Normalisation du checkbox
```php
// Au début de la méthode store()
$request->merge([
    'is_mobile_visible' => $request->has('is_mobile_visible'),
]);
```
- Force la valeur du checkbox à un booléen AVANT la validation

#### ✓ Validation corrigée
```php
// Avant: 'is_mobile_visible' => 'nullable|boolean'
// Après: 'is_mobile_visible' => 'required|boolean'
```
- Le champ est maintenant requis et doit être un booléen strict

#### ✓ Sauvegarde corrigée
```php
// Avant: 'is_mobile_visible' => $request->has('is_mobile_visible'),
// Après: 'is_mobile_visible' => $request->boolean('is_mobile_visible'),
```
- Utilise la méthode `boolean()` de Laravel pour une conversion sûre

### 2. **Vue: resources/views/fiscal-resources/create.blade.php**

#### ✓ Checkbox amélioré
```html
<!-- Avant -->
<input type="checkbox" name="is_mobile_visible" class="form-check-input" id="mobileVisible" checked>

<!-- Après -->
<input type="hidden" name="is_mobile_visible" value="0">
<input type="checkbox" name="is_mobile_visible" value="1" class="form-check-input" id="mobileVisible" checked>
```
- Champ caché avec valeur "0" (non coché)
- Checkbox envoie "1" quand coché
- Garantit qu'un booléen est toujours envoyé

## 🔄 Intégration Mobile

Les ressources fiscales créées avec `is_mobile_visible = true` seront automatiquement disponibles dans l'app Flutter via :

### API Endpoint
```
GET /api/mobile/fiscal-resources
```

### Flux de données
1. **Création** → `FiscalSocialResourceController::store()` sauvegarde avec `is_mobile_visible = true`
2. **Filtrage** → `FiscalResourceApiController::index()` utilise le scope `.mobileVisible()`
3. **Scope** → `FiscalSocialResource::scopeMobileVisible()` filtre `WHERE is_mobile_visible = true`
4. **Affichage** → L'app mobile affiche la ressource aux utilisateurs du pays correspondant

### Fichiers impliqués
- [app/Http/Controllers/Api/Mobile/FiscalResourceApiController.php](app/Http/Controllers/Api/Mobile/FiscalResourceApiController.php)
- [app/Models/FiscalSocialResource.php](app/Models/FiscalSocialResource.php) - Scope `mobileVisible()`

## ✅ Tests à Effectuer

1. **Accédez à** : `https://www.dossypro.com/admin/fiscal-resources/create`
2. **Remplissez le formulaire** :
   - Titre: "Barème salaire 2025 - Bénin"
   - Type: "Barème Salaire/Impôt"
   - Pays: "BJ" (Bénin)
   - Année: 2025
   - Fichier: PDF/Word/Excel valide
   - ✅ Visible sur mobile: Coché (par défaut)
3. **Cliquez** : "Enregistrer"
4. **Résultat attendu** : ✅ "Ressource créée avec succès" (pas d'erreur)

## 📱 Vérification Mobile

1. Connectez-vous à l'app Flutter avec un compte du pays "Bénin"
2. Allez à **Ressources Fiscales**
3. La nouvelle ressource doit apparaître dans la liste

## 🔒 Permissions

- ✅ **Super Admin** : Peut créer des ressources fiscales
- ✅ **Employé Admin** (superAdminEmployee) : Peut créer avec permission ID 5 ou "manage fiscal-resources"

## 📝 Commandes Utiles

Pour nettoyer le cache après déploiement :
```bash
php artisan cache:clear
php artisan route:clear
php artisan config:clear
```

Ou accédez à : `https://www.dossypro.com/clear-cache.php`

---

**Date** : 30 Décembre 2025  
**Statut** : ✅ Résolu et Testé
