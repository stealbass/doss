# CORRECTIONS À APPLIQUER SUR LE SERVEUR PRODUCTION

## PROBLÈME: Routes 404 et méthode canSearch() manquante

### 1. BACKEND - Ajouter méthode canSearch() au modèle MobileAppSubscription
**Fichier**: `app/Models/MobileAppSubscription.php`
**Localisation**: Avant la dernière accolade fermante `}` de la classe
**Code à ajouter**:

```php
    /**
     * Check if subscription can search
     */
    public function canSearch()
    {
        if (!$this->plan) {
            return false;
        }
        if ($this->plan->searches_limit === -1) {
            return true; // unlimited
        }
        return $this->searches_used < $this->plan->searches_limit;
    }

    /**
     * Increment search usage
     */
    public function incrementSearch()
    {
        $this->increment('searches_used');
    }
```

### 2. BACKEND - Nettoyer le cache Laravel
**Commandes à exécuter sur le serveur**:
```bash
cd /home/threesixty/yyy/Dossy
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan optimize
```

### 3. FLUTTER - Corriger les URLs (déjà fait localement, à rebuild)
Ces modifications sont déjà dans le code Flutter local, il faut juste rebuild l'app:

**Fichier modifié 1**: `dossy_chat_ia/lib/providers/template_provider.dart`
- Ligne ~92: `Uri.parse('${ApiConstants.baseUrl}/templates/$templateId/download')`
  (SANS /mobile/ car baseUrl contient déjà /mobile)

**Fichier modifié 2**: `dossy_chat_ia/lib/providers/fiscal_resource_provider.dart`
- Ligne ~157: `Uri.parse('${ApiConstants.baseUrl}/fiscal-resources')`
  (SANS /mobile/ car baseUrl contient déjà /mobile)

**Fichier modifié 3**: `dossy_chat_ia/lib/data/services/search_service.dart`
- Ligne ~45: `Uri.parse('$baseUrl/documents/search')`
  (SANS /mobile/ car baseUrl contient déjà /mobile)

### 4. VÉRIFICATION DES ROUTES
Les routes suivantes DOIVENT exister dans `routes/api.php` (déjà vérifiées présentes):

```php
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    
    // Templates
    Route::prefix('templates')->group(function () {
        Route::get('/', [TemplateApiController::class, 'index']);
        Route::get('/{id}', [TemplateApiController::class, 'show']);
        Route::get('/{id}/download', [TemplateApiController::class, 'download']);
    });
    
    // Fiscal Resources
    Route::prefix('fiscal-resources')->group(function () {
        Route::get('/', [FiscalResourceApiController::class, 'index']);
        Route::get('/salary-grids', [FiscalResourceApiController::class, 'salaryGrids']);
        Route::get('/tax-parameters', [FiscalResourceApiController::class, 'taxParameters']);
    });
    
    // Documents (Legal Library)
    Route::prefix('documents')->group(function () {
        Route::post('/search', [DocumentController::class, 'searchLegalDocuments']);
    });
});
```

## URLS FINALES ATTENDUES

Après corrections:
- Templates: `https://dossypro.com/api/mobile/templates/1/download`
- Fiscal: `https://dossypro.com/api/mobile/fiscal-resources?year=2026`
- Legal: `https://dossypro.com/api/mobile/documents/search` (POST)

## PROCÉDURE D'APPLICATION

1. **Sur le serveur**:
   - Éditer `app/Models/MobileAppSubscription.php`
   - Ajouter les 2 méthodes `canSearch()` et `incrementSearch()`
   - Exécuter les commandes de cache clear
   - Vérifier que les routes existent dans `routes/api.php`

2. **En local (Flutter)**:
   - Les modifications sont déjà faites
   - Compiler l'app: `flutter build apk` ou `flutter run`
   - Installer sur le téléphone

3. **Tester**:
   - Templates: cliquer sur un template, puis "Télécharger"
   - Fiscal: ouvrir "Ressources Fiscales & Sociales"
   - Legal: ouvrir "Bibliothèque juridique", chercher (ou laisser vide)

## DIAGNOSTIC SI ÉCHEC

Si ça ne marche toujours pas, exécuter sur le serveur:

```bash
php artisan route:list | grep mobile
```

Cela listera toutes les routes mobile et confirmera qu'elles existent.
