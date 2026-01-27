# 🚀 Guide de Déploiement - Bibliothèque Juridique

## 📦 Fichiers Disponibles

Vous avez **3 options** pour déployer cette fonctionnalité :

### 1. **legal-library-feature.tar.gz** (12 KB)
Archive contenant tous les fichiers prêts à être extraits

### 2. **legal-library-feature.patch** (80 KB)
Patch Git à appliquer directement sur votre repository

### 3. **Copie manuelle** 
Copier chaque fichier individuellement (voir FILES_TO_COPY.txt)

---

## 🎯 Option 1 : Utiliser l'archive TAR.GZ (Recommandé pour déploiement direct)

### Sur votre serveur local ou de production :

```bash
# 1. Télécharger l'archive legal-library-feature.tar.gz
# 2. Placer l'archive à la racine de votre projet Dossy Pro
# 3. Extraire l'archive

cd /path/to/your/dossypro
tar -xzf legal-library-feature.tar.gz

# 4. Modifier routes/web.php manuellement
# Voir le fichier MODIFICATIONS_SUMMARY.md pour les modifications exactes

# 5. Exécuter les migrations
php artisan migrate

# 6. Créer le lien symbolique (si pas déjà fait)
php artisan storage:link

# 7. Créer le répertoire de stockage
mkdir -p storage/app/public/legal_documents
chmod -R 775 storage/app/public/legal_documents

# 8. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

---

## 🎯 Option 2 : Utiliser le Patch Git (Recommandé pour GitHub)

### Sur votre machine locale avec accès à GitHub :

```bash
# 1. Télécharger le fichier legal-library-feature.patch

# 2. Dans votre repository local
cd /path/to/your/local/dossypro

# 3. Créer une nouvelle branche
git checkout -b feature/legal-library

# 4. Appliquer le patch
git am < /path/to/legal-library-feature.patch

# 5. Vérifier les changements
git log -1
git diff main

# 6. Pousser vers GitHub
git push origin feature/legal-library

# 7. Créer une Pull Request sur GitHub
# Allez sur https://github.com/stealbass/doss
# Cliquez sur "Compare & pull request"
# Remplissez la description et créez la PR

# 8. Sur votre serveur de production, après merge :
cd /path/to/your/production/dossypro
git pull origin main
php artisan migrate
php artisan storage:link
php artisan cache:clear
```

---

## 🎯 Option 3 : Push Manuel vers GitHub

Si vous voulez que je vous donne les commandes Git exactes :

### Donnez-moi votre Token GitHub :

1. Allez sur GitHub.com
2. Settings → Developer settings → Personal access tokens → Tokens (classic)
3. Generate new token (classic)
4. Sélectionnez les scopes : `repo` (tous)
5. Générez et copiez le token
6. **Donnez-moi le token** et je pousserai directement vers votre repo

**OU**

### Vous pouvez le faire vous-même :

```bash
# Sur votre machine locale
cd /path/to/your/local/dossypro

# Télécharger tous les fichiers que j'ai créés depuis le sandbox
# (je peux vous les envoyer un par un ou en ZIP)

# Ajouter et commiter
git checkout -b feature/legal-library
git add .
git commit -m "feat: Add Legal Library feature with category and document management"

# Pousser vers GitHub
git push origin feature/legal-library

# Créer une PR sur GitHub
```

---

## 📋 Modifications Manuelles Requises

### Fichier : `routes/web.php`

**Ajouter au début (après les autres `use` statements) :**
```php
use App\Http\Controllers\LegalLibraryController;
use App\Http\Controllers\UserLegalLibraryController;
```

**Ajouter après `Route::resource('documents', DocumentController::class);` :**
```php
// Legal Library Routes - Administration
Route::prefix('legal-library')->name('legal-library.')->group(function () {
    Route::get('/', [LegalLibraryController::class, 'index'])->name('index');
    
    // Category routes
    Route::get('/category/create', [LegalLibraryController::class, 'createCategory'])->name('category.create');
    Route::post('/category/store', [LegalLibraryController::class, 'storeCategory'])->name('category.store');
    Route::get('/category/{id}/edit', [LegalLibraryController::class, 'editCategory'])->name('category.edit');
    Route::put('/category/{id}', [LegalLibraryController::class, 'updateCategory'])->name('category.update');
    Route::delete('/category/{id}', [LegalLibraryController::class, 'destroyCategory'])->name('category.destroy');
    
    // Document routes
    Route::get('/category/{categoryId}/documents', [LegalLibraryController::class, 'showDocuments'])->name('documents');
    Route::get('/category/{categoryId}/document/create', [LegalLibraryController::class, 'createDocument'])->name('document.create');
    Route::post('/category/{categoryId}/document/store', [LegalLibraryController::class, 'storeDocument'])->name('document.store');
    Route::get('/document/{id}/edit', [LegalLibraryController::class, 'editDocument'])->name('document.edit');
    Route::put('/document/{id}', [LegalLibraryController::class, 'updateDocument'])->name('document.update');
    Route::delete('/document/{id}', [LegalLibraryController::class, 'destroyDocument'])->name('document.destroy');
    Route::get('/document/{id}/download', [UserLegalLibraryController::class, 'downloadDocument'])->name('document.download');
});

// Legal Library Routes - User Access
Route::prefix('library')->name('user.legal-library.')->group(function () {
    Route::get('/', [UserLegalLibraryController::class, 'index'])->name('index');
    Route::get('/category/{categoryId}', [UserLegalLibraryController::class, 'showCategory'])->name('category');
    Route::get('/document/{id}/view', [UserLegalLibraryController::class, 'viewDocument'])->name('view');
    Route::get('/document/{id}/download', [UserLegalLibraryController::class, 'downloadDocument'])->name('download');
});
```

---

## 🔗 Ajouter les Liens de Navigation

Dans votre fichier de menu (probablement `resources/views/layouts/navigation.blade.php` ou similaire) :

### Pour l'admin :
```blade
@can('manage legal library')
    <li class="nav-item">
        <a href="{{ route('legal-library.index') }}" class="nav-link">
            <i class="ti ti-books"></i>
            <span>{{ __('Legal Library') }}</span>
        </a>
    </li>
@endcan
```

### Pour les utilisateurs :
```blade
@can('view legal library')
    <li class="nav-item">
        <a href="{{ route('user.legal-library.index') }}" class="nav-link">
            <i class="ti ti-book"></i>
            <span>{{ __('Library') }}</span>
        </a>
    </li>
@endcan
```

---

## ✅ Vérification Post-Déploiement

Après le déploiement, vérifiez :

```bash
# 1. Les migrations ont été exécutées
php artisan migrate:status

# 2. Le lien symbolique existe
ls -la public/storage

# 3. Le répertoire de stockage existe
ls -la storage/app/public/legal_documents

# 4. Les permissions sont bonnes
chmod -R 775 storage/app/public/legal_documents
chmod -R 775 storage/logs
```

---

## 🧪 Test de la Fonctionnalité

1. **Connexion Admin** → Accédez à `/legal-library`
2. **Créer une catégorie** → Ex: "Droit Civil"
3. **Uploader un PDF** → Max 20MB
4. **Connexion Utilisateur** → Accédez à `/library`
5. **Rechercher un document**
6. **Télécharger et prévisualiser**

---

## 🆘 Besoin d'Aide ?

Si vous rencontrez des problèmes :

1. Vérifiez les logs : `storage/logs/laravel.log`
2. Consultez `LEGAL_LIBRARY_FEATURE.md` pour le dépannage
3. Vérifiez les permissions des fichiers
4. Assurez-vous que les migrations ont réussi

---

## 📞 Quelle Option Choisissez-Vous ?

Dites-moi quelle option vous préférez et je vous guiderai étape par étape :

- **Option 1** : Archive TAR.GZ (rapide, extraction directe)
- **Option 2** : Patch Git (propre, traçable)
- **Option 3** : Push avec votre token GitHub (automatique)
- **Option 4** : Je vous envoie les fichiers un par un

Je suis là pour vous aider ! 🚀
