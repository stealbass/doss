# Guide Complet - Configuration Pinecone Host dans Mobile App Settings

**Date:** 21 janvier 2026, 15:45
**Statut:** ✅ Implémentation complète

---

## 📋 Vue d'ensemble

Ce guide explique comment le système utilise la configuration Pinecone et comment déployer le nouveau champ `pinecone_host` dans l'interface admin.

---

## 🔍 Comment fonctionne la configuration actuelle

### Ordre de priorité (du plus au moins important) :

```php
// Dans AdvancedRagService.php - Ligne 30-63

$mobileSettings = MobileAppSetting::first();

// 1. PRIORITE MAXIMALE: Mobile App Settings (Base de données)
$this->pineconeHost = $this->sanitizePineconeHost(
    $mobileSettings?->pinecone_host ?? env('PINECONE_HOST')
);

// 2. FALLBACK: Fichier .env
if (empty($this->pineconeHost)) {
    // 3. DERNIER RECOURS: Construction automatique (INCORRECT)
    $this->pineconeHost = $this->sanitizePineconeHost(
        "{$this->pineconeIndex}.svc.{$this->pineconeEnvironment}.pinecone.io"
    );
}
```

### Flux de décision :

```
┌─────────────────────────────────────┐
│ 1. mobile_app_settings.pinecone_host│
│    Existe et non vide ?             │
└──────────┬──────────────────────────┘
           │
      ┌────┴────┐
      │   OUI   │────────► ✅ Utilise cette valeur (PRIORITE)
      └─────────┘
      ┌────┴────┐
      │   NON   │
      └────┬────┘
           │
           ▼
┌─────────────────────────────────────┐
│ 2. .env PINECONE_HOST               │
│    Existe et non vide ?             │
└──────────┬──────────────────────────┘
           │
      ┌────┴────┐
      │   OUI   │────────► ✅ Utilise .env (FALLBACK)
      └─────────┘
      ┌────┴────┐
      │   NON   │
      └────┬────┘
           │
           ▼
┌─────────────────────────────────────┐
│ 3. Construction automatique         │
│ {index}.svc.{env}.pinecone.io       │
└──────────┬──────────────────────────┘
           │
           ▼
      ❌ FORMAT INCORRECT
      (Manque suffixes -7udtg21 et -b74a)
```

---

## 📊 Configuration actuelle dans votre base de données

D'après votre screenshot phpMyAdmin, la table `mobile_app_settings` contient :

| Colonne | Valeur actuelle | Statut |
|---------|----------------|--------|
| `pinecone_api_key` | `pcsk_4RgfFc_4K9hBN5pP1YTnANe97uifPAYeqD1JpPQk6fwK5...` | ✅ Configuré |
| `pinecone_environment` | `aped-4627` | ✅ Configuré |
| `pinecone_index_name` | `dossy-legal-docs` | ✅ Configuré |
| `pinecone_host` | ❓ **Champ n'existe pas encore** | ⚠️ À créer |
| `pinecone_verify_ssl` | ❓ **Champ n'existe pas encore** | ⚠️ À créer |

**Résultat actuel :**
- Le système utilise le fallback `.env` (si configuré)
- Sinon : construction automatique → `dossy-legal-docs.svc.aped-4627.pinecone.io` ❌ INCORRECT
- Format attendu : `dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io` ✅ CORRECT

---

## 🚀 Déploiement - Étapes détaillées

### Étape 1 : Exécuter la migration (Alwaysdata)

```bash
# Connectez-vous en SSH à alwaysdata
ssh threesixty@ssh-threesixty.alwaysdata.net

# Naviguez vers le projet
cd ~/www/threesixty

# Exécutez la migration
php artisan migrate

# Vérifiez que les colonnes ont été ajoutées
php artisan tinker
Schema::hasColumn('mobile_app_settings', 'pinecone_host'); // Devrait retourner true
exit
```

**Résultat attendu :**
```
Migrating: 2026_01_21_000001_add_pinecone_host_to_mobile_app_settings
Migrated:  2026_01_21_000001_add_pinecone_host_to_mobile_app_settings (50.23ms)
```

---

### Étape 2 : Mettre à jour la configuration (phpMyAdmin ou SQL)

#### Option A - Via phpMyAdmin (Recommandé) :

1. Connectez-vous à phpMyAdmin : https://phpmyadmin.alwaysdata.com/
2. Base de données : `threesixty_dossypro_legal_new`
3. Table : `mobile_app_settings`
4. Cliquez sur **"Modifier"** (Edit) pour l'enregistrement unique
5. Remplissez les nouveaux champs :
   - **pinecone_host** : `dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io`
   - **pinecone_verify_ssl** : `1` (cochée)
6. Cliquez sur **"Enregistrer"**

#### Option B - Via SQL :

```sql
UPDATE mobile_app_settings 
SET 
    pinecone_host = 'dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io',
    pinecone_verify_ssl = 1
WHERE id = 1;

-- Vérification
SELECT 
    pinecone_api_key IS NOT NULL AS 'API Key OK',
    pinecone_environment,
    pinecone_index_name,
    pinecone_host,
    pinecone_verify_ssl
FROM mobile_app_settings;
```

---

### Étape 3 : Vérifier l'interface admin (Web)

1. Connectez-vous à l'admin : https://threesixty.alwaysdata.net/admin
2. Menu : **Mobile App Settings**
3. Section : **API Keys Configuration** → **Pinecone (Vector Database)**
4. Vous devriez maintenant voir :
   - ✅ **Pinecone API Key** (existant)
   - ✅ **Environment** (existant)
   - ✅ **Index Name** (existant)
   - 🆕 **Host (Optional)** (nouveau champ)
   - 🆕 **Verify SSL Certificate** (nouveau toggle)

**Screenshot attendu :**

```
┌─────────────────────────────────────────────────────────────┐
│ Pinecone (Vector Database)                                  │
├─────────────────────────────────────────────────────────────┤
│ Pinecone API Key       Environment                          │
│ [pcsk_4RgfFc_...]      [aped-4627]                          │
│                                                              │
│ Index Name             Host (Optional)                      │
│ [dossy-legal-docs]     [dossy-legal-docs-7udtg21.svc...]    │
│                        Leave empty to auto-generate         │
│                                                              │
│ ☑ Verify SSL Certificate                                   │
│   Recommended: Keep enabled for production                  │
└─────────────────────────────────────────────────────────────┘
```

---

### Étape 4 : Tester la configuration

#### Test 1 - Vérifier la lecture de la config :

```bash
php artisan tinker

use App\Models\MobileAppSetting;
$settings = MobileAppSetting::first();

echo "Pinecone API Key: " . ($settings->pinecone_api_key ? 'Configured' : 'NULL') . "\n";
echo "Environment: " . $settings->pinecone_environment . "\n";
echo "Index Name: " . $settings->pinecone_index_name . "\n";
echo "Host: " . ($settings->pinecone_host ?? 'NULL') . "\n";
echo "Verify SSL: " . ($settings->pinecone_verify_ssl ? 'true' : 'false') . "\n";

exit
```

**Résultat attendu :**
```
Pinecone API Key: Configured
Environment: aped-4627
Index Name: dossy-legal-docs
Host: dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io
Verify SSL: true
```

#### Test 2 - Tester la connexion Pinecone :

```bash
# Depuis l'app mobile, posez une question :
"Quelles sont les obligations fiscales d'une SARL?"

# Vérifiez les logs :
tail -f storage/logs/laravel.log | grep -i pinecone
```

**Logs attendus (AVANT le fix) :**
```
❌ cURL error 6: Could not resolve host: dossy-legal-docs.svc.aped-4627.pinecone.io
⚠️  Pinecone returned no results, using database fallback
```

**Logs attendus (APRÈS le fix) :**
```
✅ Pinecone query successful
ℹ️  Pinecone returned 0 results (index empty, needs population)
⚠️  Using database fallback
```

---

## 📱 Impact sur l'application Flutter

### Avant la mise à jour :

```dart
// L'app Flutter ne voit PAS pinecone_host
// Elle reçoit seulement :
{
  "pinecone_api_key": "pcsk_4RgfFc_...",
  "pinecone_environment": "aped-4627",
  "pinecone_index_name": "dossy-legal-docs"
  // pinecone_host: null ou absent
}

// Résultat: Le backend utilise construction automatique → ❌ ECHEC DNS
```

### Après la mise à jour :

```dart
// L'app Flutter reçoit maintenant :
{
  "pinecone_api_key": "pcsk_4RgfFc_...",
  "pinecone_environment": "aped-4627",
  "pinecone_index_name": "dossy-legal-docs",
  "pinecone_host": "dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io", // ✅ NOUVEAU
  "pinecone_verify_ssl": true // ✅ NOUVEAU
}

// Résultat: Le backend utilise le HOST correct → ✅ DNS résolu
```

**Note importante :**
- L'app Flutter **n'a PAS besoin d'être mise à jour**
- Elle envoie toujours les mêmes requêtes au backend Laravel
- C'est le **backend** qui gère la connexion Pinecone
- L'app reçoit juste la réponse finale du chat

---

## 🔧 Configuration recommandée

### Pour votre environnement de production :

| Champ | Valeur recommandée | Raison |
|-------|-------------------|--------|
| `pinecone_api_key` | `pcsk_4RgfFc_4K9hBN5pP1YTnANe97uifPAYeqD1JpPQk6fwK5...` | Votre clé actuelle (déjà configurée) |
| `pinecone_environment` | `aped-4627` | Votre environnement actuel |
| `pinecone_index_name` | `dossy-legal-docs` | Votre index actuel |
| `pinecone_host` | `dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io` | ✅ FORMAT CORRECT avec suffixes |
| `pinecone_verify_ssl` | `true` (coché) | Sécurité production |

### Pourquoi le host doit contenir les suffixes ?

Pinecone génère des hosts **uniques par index** avec :
- **Suffixe index** : `-7udtg21` (identifiant unique de votre index)
- **Suffixe environnement** : `-b74a` (sous-région du cloud)

**Format générique (INCORRECT) :**
```
dossy-legal-docs.svc.aped-4627.pinecone.io
❌ Manque -7udtg21 et -b74a
```

**Format spécifique (CORRECT) :**
```
dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io
✅ Contient les suffixes uniques
```

---

## 📁 Fichiers modifiés

### 1. Migration (Nouveau fichier)
**Fichier :** `database/migrations/2026_01_21_000001_add_pinecone_host_to_mobile_app_settings.php`
- Ajoute : `pinecone_host` (text, nullable)
- Ajoute : `pinecone_verify_ssl` (boolean, default true)

### 2. Modèle (Mis à jour)
**Fichier :** `app/Models/MobileAppSetting.php`
- **Ligne 12-43** : Ajout de `'pinecone_host'` et `'pinecone_verify_ssl'` dans `$fillable`

### 3. Controller (Mis à jour)
**Fichier :** `app/Http/Controllers/MobileAppSettingsController.php`
- **Ligne 97-125** : Méthode `updateApiKeys()`
  - Ajout validation : `'pinecone_host' => 'nullable|string'`
  - Ajout validation : `'pinecone_verify_ssl' => 'nullable|boolean'`
  - Ajout dans `update()` : `'pinecone_host'` et `'pinecone_verify_ssl'`

### 4. Vue Admin (Mise à jour)
**Fichier :** `resources/views/mobile-app-settings/index.blade.php`
- **Ligne 197-230** : Section Pinecone
  - Nouveau champ : **Host (Optional)**
  - Placeholder : `dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io`
  - Aide : "Leave empty to auto-generate from index and environment"
  - Nouveau toggle : **Verify SSL Certificate**
  - Checkbox avec label : "Recommended: Keep enabled for production"

---

## ✅ Checklist de déploiement

- [ ] **Étape 1** : Upload des fichiers via Git/FTP
  - [ ] Migration : `2026_01_21_000001_add_pinecone_host_to_mobile_app_settings.php`
  - [ ] Modèle : `MobileAppSetting.php` (mis à jour)
  - [ ] Controller : `MobileAppSettingsController.php` (mis à jour)
  - [ ] Vue : `mobile-app-settings/index.blade.php` (mise à jour)

- [ ] **Étape 2** : Exécuter la migration
  ```bash
  php artisan migrate
  ```

- [ ] **Étape 3** : Mettre à jour la config dans phpMyAdmin
  ```sql
  UPDATE mobile_app_settings 
  SET pinecone_host = 'dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io',
      pinecone_verify_ssl = 1;
  ```

- [ ] **Étape 4** : Vider les caches Laravel
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  ```

- [ ] **Étape 5** : Vérifier l'interface admin
  - [ ] Les nouveaux champs apparaissent
  - [ ] Les valeurs sont correctement affichées
  - [ ] La sauvegarde fonctionne

- [ ] **Étape 6** : Tester la connexion Pinecone
  - [ ] Poser une question via l'app mobile
  - [ ] Vérifier les logs : plus d'erreur DNS
  - [ ] Confirmer : `Pinecone query successful`

- [ ] **Étape 7** : Reindexer les documents (si 0 records)
  ```bash
  php artisan tinker
  $docs = App\Models\SubmittedDocument::where('processing_status', 'completed')->get();
  foreach ($docs as $doc) {
      App\Jobs\ProcessDocumentForRAG::dispatch($doc);
  }
  ```

---

## ❓ FAQ

### Q1 : Dois-je mettre à jour l'app Flutter ?
**R :** Non, l'app Flutter n'a pas besoin d'être mise à jour. Elle communique avec le backend Laravel qui gère la connexion Pinecone.

### Q2 : Que se passe-t-il si je laisse `pinecone_host` vide ?
**R :** Le système utilisera le fallback `.env` (si configuré), sinon la construction automatique (qui est incorrecte dans votre cas).

### Q3 : Puis-je désactiver `pinecone_verify_ssl` ?
**R :** Oui, mais **non recommandé en production**. Ne le faites que pour du debug en développement local.

### Q4 : Comment savoir si Pinecone fonctionne maintenant ?
**R :** 
1. Plus d'erreur "cURL error 6" dans les logs
2. Log affiche "Pinecone query successful"
3. Si l'index est vide (0 records), vous verrez "Pinecone returned 0 results" (normal, besoin de réindexation)

### Q5 : Pourquoi mon index Pinecone est vide (0 records) ?
**R :** Les documents n'ont pas encore été indexés. Vous devez :
1. Lancer le job ProcessDocumentForRAG pour chaque document
2. Ou attendre que les nouveaux documents soient uploadés (indexation automatique)
3. Ou ré-uploader les documents existants via l'UI

---

## 🎯 Résultat final attendu

Après déploiement complet :

✅ **Configuration complète dans admin :**
- Pinecone API Key : Configuré
- Environment : `aped-4627`
- Index Name : `dossy-legal-docs`
- Host : `dossy-legal-docs-7udtg21.svc.aped-4627-b74a.pinecone.io` 🆕
- Verify SSL : Activé 🆕

✅ **Logs Laravel (après une question) :**
```
[2026-01-21 16:00:00] local.INFO: Pinecone query successful
[2026-01-21 16:00:00] local.INFO: Pinecone returned 0 results (index empty)
[2026-01-21 16:00:00] local.INFO: Using database fallback
```

✅ **Plus d'erreurs DNS**
✅ **Système prêt pour la réindexation**
✅ **RAG fonctionnel avec fallback database**

---

**Auteur :** GitHub Copilot  
**Date :** 21 janvier 2026  
**Version :** 1.0  
**Statut :** Prêt pour déploiement
