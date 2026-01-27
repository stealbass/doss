# BIBLIOTHÈQUE JURIDIQUE - CATÉGORIES & PAGINATION

## Fichiers modifiés à uploader sur le serveur:

### BACKEND (Laravel)

1. **app/Http/Controllers/Api/Mobile/DocumentController.php**
   - Ajout pagination (page, per_page, total_pages)
   - Ajout support category_id
   - Nouvelle méthode `getLegalCategories()`

2. **routes/api.php**
   - Ajout route: `GET /api/mobile/documents/categories`

3. **app/Models/User.php**
   - Ajout alias `mobileAppSubscription()` → `activeMobileSubscription()`

### FLUTTER

4. **dossy_chat_ia/lib/screens/legal_library/legal_library_screen_new.dart** (NOUVEAU)
   - Nouvel écran avec catégories et pagination
   - Style similaire à templates_list_screen

5. **dossy_chat_ia/lib/providers/legal_library_provider.dart**
   - Ajout propriétés: total, currentPage, totalPages
   - Support pagination et categoryId

6. **dossy_chat_ia/lib/data/services/search_service.dart**
   - Ajout paramètre categoryId
   - Augmentation limit par défaut à 20

7. **dossy_chat_ia/lib/data/models/document_model.dart**
   - Fix parsing String → int pour id et fileSize

## FILTRAGE PAR PAYS

### Actuellement implémenté:

✅ **Templates**: Filtré par pays dans backend
```php
$userCountry = $user->country; // Via accessor
$templates = DocumentTemplate::byCountry($userCountry)->get();
```

✅ **Fiscal Resources**: Filtré par pays
```php
$userCountry = $user->country;
$resources = FiscalSocialResource::where('country', $userCountry)->get();
```

✅ **Legal Library**: Jurisdiction passée au backend
```dart
jurisdiction: auth.user?.jurisdiction ?? 'CM'
```

### À vérifier:

1. Les **legal_documents** ont-ils un champ `country` ou `jurisdiction`?
2. Les **legal_categories** sont-elles liées à un pays?

Si NON, il faut ajouter:
- Migration pour ajouter colonne `country` à `legal_documents`
- Ou utiliser une table pivot `legal_category_country`

## PROCHAINES ÉTAPES

1. **Uploader les fichiers modifiés sur le serveur**

2. **Nettoyer cache Laravel**:
   ```bash
   php artisan route:clear
   php artisan cache:clear
   ```

3. **Remplacer l'ancien écran dans Flutter**:
   - Renommer `legal_library_screen.dart` → `legal_library_screen_old.dart`
   - Renommer `legal_library_screen_new.dart` → `legal_library_screen.dart`

4. **Rebuild Flutter**:
   ```bash
   flutter clean
   flutter pub get
   flutter run
   ```

5. **Ajouter le filtrage par pays aux legal_documents** (si nécessaire):
   
   Option A - Si table a déjà `country`:
   ```php
   // Dans DocumentController.php, ligne ~220
   if ($jurisdiction && $jurisdiction !== 'ALL') {
       $documentsQuery->where('country', $jurisdiction);
   }
   ```

   Option B - Si pas de colonne:
   ```bash
   php artisan make:migration add_country_to_legal_documents
   ```
   ```php
   $table->string('country', 10)->default('CM')->after('category_id');
   $table->index('country');
   ```

## FEATURES

✅ Affichage par catégories (comme templates)
✅ Pagination (20 docs par page)
✅ Bouton "Charger plus"
✅ Compteur de documents par catégorie
✅ Filtrage par catégorie
✅ Recherche textuelle
✅ Design cohérent avec templates

🔄 Filtrage par pays (à finaliser selon structure DB)

## TEST

1. Ouvrir "Bibliothèque juridique"
2. Voir les catégories en haut (comme templates)
3. Cliquer sur une catégorie → filtre les docs
4. Scroll en bas → bouton "Charger plus"
5. Chercher un mot → filtre en temps réel
6. Télécharger un document
