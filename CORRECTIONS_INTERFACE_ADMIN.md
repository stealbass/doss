# 🔧 CORRECTIONS INTERFACE ADMIN - DOSSY PRO

Date : 19/12/2024
Statut : À corriger

## 📋 PROBLÈMES IDENTIFIÉS

### 1. ❌ Bouton "Créer une audience" ne fonctionne pas toujours
   - **Localisation** : Page Affaires (Cases)
   - **Problème** : Bouton non fonctionnel
   - **Action** : Vérifier le JavaScript et les routes

### 2. ❌ Manque champ "Assigner à" dans formulaire Créer une audience
   - **Fichier** : `resources/views/hearings/create.blade.php`
   - **Action** : Ajouter un champ de sélection d'utilisateur

### 3. ❌ Accès Admin Legal Library non visible
   - **Menu** : Admin → Legal Library
   - **Problème** : Lien pour accéder aux pays et catégories par pays non visible
   - **Action** : Ajouter les routes et menus pour la gestion par pays

### 4. ❌ Accès Admin Document Templates non visible
   - **Route attendue** : `/admin/document-templates`
   - **Action** : Créer/vérifier la route et le contrôleur

### 5. ❌ Accès Admin Fiscal Resources non visible
   - **Route attendue** : `/admin/fiscal-resources`
   - **Action** : Créer/vérifier la route et le contrôleur

### 6. ❌ Accès Admin Calculators non visible
   - **Route attendue** : `/admin/calculators`
   - **Action** : Créer/vérifier la route et le contrôleur

## 🎯 PLAN DE CORRECTION

### Étape 1 : Vérifier les contrôleurs existants
```bash
# Chercher les contrôleurs
ls -la app/Http/Controllers/ | grep -i "document\|fiscal\|calculator"
```

### Étape 2 : Vérifier les routes
```bash
# Voir toutes les routes admin
php artisan route:list | grep -i "document\|fiscal\|calculator"
```

### Étape 3 : Ajouter au menu sidebar
**Fichier** : `resources/views/partision/sidebar.blade.php`

Ajouter une nouvelle section pour Super Admin :

```php
@if (\Auth::user()->type == 'super admin')
    <li class="dash-item dash-hasmenu">
        <a href="#!" class="dash-link">
            <span class="dash-micon"><i class="ti ti-database"></i></span>
            <span class="dash-mtext">{{ __('Mobile App Data') }}</span>
            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
        </a>
        <ul class="dash-submenu">
            <li class="dash-item">
                <a class="dash-link" href="{{ route('document-templates.index') }}">
                    {{ __('Document Templates') }}
                </a>
            </li>
            <li class="dash-item">
                <a class="dash-link" href="{{ route('fiscal-resources.index') }}">
                    {{ __('Fiscal Resources') }}
                </a>
            </li>
            <li class="dash-item">
                <a class="dash-link" href="{{ route('calculators.index') }}">
                    {{ __('Calculators') }}
                </a>
            </li>
            <li class="dash-item">
                <a class="dash-link" href="{{ route('legal-library.countries') }}">
                    {{ __('Legal Library by Country') }}
                </a>
            </li>
        </ul>
    </li>
@endif
```

### Étape 4 : Créer les routes manquantes
**Fichier** : `routes/web.php`

```php
// Routes pour Super Admin uniquement
Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:super admin'])->group(function () {
        // Document Templates
        Route::resource('document-templates', DocumentTemplateController::class);
        
        // Fiscal Resources
        Route::resource('fiscal-resources', FiscalResourceController::class);
        
        // Calculators
        Route::resource('calculators', CalculatorController::class);
        
        // Legal Library by Country
        Route::get('legal-library/countries', [LegalDocumentController::class, 'countries'])
            ->name('legal-library.countries');
        Route::get('legal-library/country/{country}', [LegalDocumentController::class, 'byCountry'])
            ->name('legal-library.by-country');
    });
});
```

### Étape 5 : Ajouter "Assigner à" dans formulaire hearing
**Fichier** : `resources/views/hearings/create.blade.php`

Ajouter ce champ après le champ de date :

```php
<div class="form-group">
    {{ Form::label('assigned_to', __('Assign To'), ['class' => 'form-label']) }}
    {{ Form::select('assigned_to', $users, null, [
        'class' => 'form-control select2',
        'placeholder' => __('Select User')
    ]) }}
</div>
```

### Étape 6 : Modifier le contrôleur Hearing
**Fichier** : `app/Http/Controllers/HearingController.php`

Dans la méthode `create()`, ajouter :
```php
$users = User::where('created_by', Auth::user()->creatorId())
    ->where('type', '!=', 'client')
    ->pluck('name', 'id');
    
return view('hearings.create', compact('users', ...));
```

Dans la méthode `store()`, ajouter :
```php
$hearing->assigned_to = $request->assigned_to;
```

## 📝 FICHIERS À MODIFIER

1. ✅ `resources/views/partision/sidebar.blade.php` - Ajouter menu
2. ✅ `routes/web.php` - Ajouter routes
3. ✅ `resources/views/hearings/create.blade.php` - Ajouter champ "Assigner à"
4. ✅ `app/Http/Controllers/HearingController.php` - Gérer assignation
5. ✅ `database/migrations/xxxx_add_assigned_to_hearings.php` - Ajouter colonne

## 🗄️ MIGRATION À CRÉER

```php
Schema::table('hearings', function (Blueprint $table) {
    $table->unsignedBigInteger('assigned_to')->nullable()->after('case_id');
    $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
});
```

## ✅ VÉRIFICATIONS POST-CORRECTION

- [ ] Le bouton "Créer une audience" fonctionne
- [ ] Le champ "Assigner à" est visible et fonctionne
- [ ] Le menu "Mobile App Data" est visible pour Super Admin
- [ ] Les 4 sous-menus sont accessibles et fonctionnels
- [ ] La page Legal Library par pays est accessible
