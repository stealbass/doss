# ✅ CONFIGURATION BACKEND REQUISE POUR L'APP FLUTTER

## 🎯 RÉPONSE RAPIDE

**OUI**, vous devez faire quelques vérifications/modifications dans le backend **AVANT** de tester l'application Flutter :

1. ✅ Vérifier le fichier `.env`
2. ✅ Déployer les nouveaux fichiers backend
3. ✅ Vider le cache Laravel
4. ✅ Tester les endpoints API

---

## 📋 CHECKLIST COMPLÈTE

### ☑️ Étape 1: Configuration `.env` (Obligatoire)

**Fichier:** `/home/dossypro/public_html/.env`

**Vérifications nécessaires:**

```env
# 1. APP_URL doit pointer vers votre domaine
APP_URL=https://dossypro.com

# 2. APP_ENV (production ou local)
APP_ENV=production

# 3. APP_DEBUG (false en production)
APP_DEBUG=false

# 4. Base de données (vérifier que c'est correct)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nom_de_votre_base
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# 5. SANCTUM pour l'authentification mobile (IMPORTANT!)
SANCTUM_STATEFUL_DOMAINS=dossypro.com,*.dossypro.com
SESSION_DOMAIN=.dossypro.com

# 6. CORS Origins (pour autoriser les requêtes depuis l'app)
# Pas nécessaire de modifier, mais vérifier que c'est présent
```

**⚠️ PARAMÈTRE CRITIQUE:** `SANCTUM_STATEFUL_DOMAINS`

Si ce paramètre n'existe pas ou est mal configuré, l'authentification mobile ne fonctionnera pas !

---

### ☑️ Étape 2: Déployer les Fichiers Backend Modifiés (Obligatoire)

**Fichiers à uploader via FTP/cPanel :**

#### Fichier 1: `routes/api.php`
- **Source:** https://raw.githubusercontent.com/stealbass/doss/genspark_ai_developer/routes/api.php
- **Destination:** `/home/dossypro/public_html/routes/api.php`
- **Raison:** Contient les 42 routes API pour l'app mobile

#### Fichier 2: `app/Http/Controllers/Api/Mobile/AuthController.php`
- **Source:** https://raw.githubusercontent.com/stealbass/doss/genspark_ai_developer/app/Http/Controllers/Api/Mobile/AuthController.php
- **Destination:** `/home/dossypro/public_html/app/Http/Controllers/Api/Mobile/AuthController.php`
- **Raison:** Support `passwordConfirmation` (Flutter camelCase)

**Comment uploader:**

1. **Via cPanel File Manager:**
   - Connectez-vous à cPanel
   - File Manager → public_html
   - Upload les fichiers aux emplacements indiqués

2. **Via FTP (FileZilla):**
   - Connectez-vous avec vos identifiants FTP
   - Naviguez vers `/home/dossypro/public_html/`
   - Glissez-déposez les fichiers

---

### ☑️ Étape 3: Vider le Cache Laravel (OBLIGATOIRE après modifications)

**URL à visiter:**
```
https://dossypro.com/super-clear-cache.php?token=DOSSY2024CLEAR
```

**Résultat attendu:**
```
✅ Config cache cleared
✅ Application cache cleared
✅ Route cache cleared
✅ View cache cleared
✅ Compiled views cleared
✅ Optimize cleared
✅ Events cache cleared
✅ Schedule cache cleared
✅ Bootstrap cache cleared
✅ Queue cache cleared
✅ Session cache cleared

11/11 tasks successful
```

**⚠️ IMPORTANT:** Ne passez pas à l'étape suivante si vous n'avez pas "11/11 tasks successful"

---

### ☑️ Étape 4: Tester les Endpoints API (Avant de tester Flutter)

**Ces tests se font depuis votre ordinateur ou Postman**

#### Test 1: Health Check
```bash
curl https://dossypro.com/api/mobile
```

**Résultat attendu:**
```json
{
  "success": true,
  "message": "DOSSY CHAT IA API - Mobile endpoint",
  "version": "1.0.0",
  "timestamp": "2025-12-26T...",
  "endpoints": [...]
}
```

**❌ Si erreur 404:** Les routes ne sont pas déployées correctement

---

#### Test 2: Register (Inscription)
```bash
curl -X POST https://dossypro.com/api/mobile/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test Flutter User",
    "email": "testflutter@example.com",
    "password": "test123456",
    "passwordConfirmation": "test123456",
    "phone": "+237600000000",
    "jurisdiction": "CM"
  }'
```

**Résultat attendu:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 123,
      "name": "Test Flutter User",
      "email": "testflutter@example.com",
      ...
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "subscription": {
      "plan_name": "Gratuit",
      ...
    }
  }
}
```

**❌ Si erreur 422:** Validation échoue (vérifier les champs)
**❌ Si erreur 500:** Problème serveur (vérifier logs Laravel)

---

#### Test 3: Login (Connexion)
```bash
curl -X POST https://dossypro.com/api/mobile/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "testflutter@example.com",
    "password": "test123456"
  }'
```

**Résultat attendu:**
```json
{
  "success": true,
  "data": {
    "user": {...},
    "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "subscription": {...}
  }
}
```

**❌ Si "Email ou mot de passe incorrect":** Normal si le compte n'existe pas (faire Register avant)

---

### ☑️ Étape 5: Configuration Admin Panel (Optionnel mais recommandé)

**Dans l'admin web de DOSSY Pro:**

#### 5.1. Vérifier les Plans d'Abonnement

**Menu:** Configuration → Plans d'Abonnement

**Plans requis:**
1. **Gratuit** - 0 FCFA
   - Searches: 10/mois
   - AI analyses: 5/mois
   - Downloads: 10/mois

2. **Étudiant** - 2500 FCFA/mois
   - Searches: 50/mois
   - AI analyses: 30/mois
   - Downloads: 50/mois

3. **Professionnel** - 5000 FCFA/mois
   - Searches: illimité
   - AI analyses: 100/mois
   - Templates: ✅
   - Ressources fiscales: ✅

4. **Cabinet/Entreprise** - 15000 FCFA/mois
   - Tout illimité
   - Multi-comptes: 10 sous-comptes

**Action:** Vérifier que ces plans existent dans la base de données

---

#### 5.2. Vérifier les Pays Supportés

**Menu:** Configuration → Pays / Juridictions

**14 pays requis:**
- 🇧🇯 Bénin (BJ)
- 🇧🇫 Burkina Faso (BF)
- 🇨🇲 Cameroun (CM)
- 🇨🇮 Côte d'Ivoire (CI)
- 🇨🇩 RD Congo (CD)
- 🇬🇦 Gabon (GA)
- 🇬🇼 Guinée-Bissau (GW)
- 🇲🇬 Madagascar (MG)
- 🇲🇱 Mali (ML)
- 🇲🇦 Maroc (MA)
- 🇳🇪 Niger (NE)
- 🇸🇳 Sénégal (SN)
- 🇹🇬 Togo (TG)
- 🇹🇳 Tunisie (TN)

**Action:** Vérifier dans la table `mobile_countries` ou config

---

### ☑️ Étape 6: Permissions CORS (Si erreurs de connexion)

**Fichier:** `/home/dossypro/public_html/config/cors.php`

**Configuration recommandée:**

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // En production, spécifier les domaines

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

**⚠️ Si vous modifiez ce fichier, n'oubliez pas de vider le cache !**

---

## 🚀 PROCÉDURE COMPLÈTE DANS L'ORDRE

### Ordre d'exécution (Important !) :

```
1. ✅ Vérifier/modifier .env
   ↓
2. ✅ Uploader routes/api.php
   ↓
3. ✅ Uploader AuthController.php
   ↓
4. ✅ Vider le cache (super-clear-cache.php)
   ↓
5. ✅ Tester Health Check (curl)
   ↓
6. ✅ Tester Register (curl)
   ↓
7. ✅ Tester Login (curl)
   ↓
8. ✅ Compiler l'APK Flutter
   ↓
9. ✅ Installer et tester sur téléphone
```

---

## ❌ ERREURS COURANTES ET SOLUTIONS

### Erreur 1: "404 Not Found" sur /api/mobile

**Cause:** Routes pas déployées ou cache pas vidé

**Solution:**
```bash
1. Re-uploader routes/api.php
2. https://dossypro.com/super-clear-cache.php?token=DOSSY2024CLEAR
3. Attendre 1 minute
4. Réessayer
```

---

### Erreur 2: "CSRF token mismatch" ou "Unauthenticated"

**Cause:** SANCTUM mal configuré

**Solution dans .env:**
```env
SANCTUM_STATEFUL_DOMAINS=dossypro.com,*.dossypro.com
SESSION_DOMAIN=.dossypro.com
```

Puis vider le cache.

---

### Erreur 3: "CORS policy" dans la console

**Cause:** Headers CORS manquants

**Solution:**
1. Vérifier `config/cors.php`
2. Ajouter `'allowed_origins' => ['*']` temporairement
3. Vider le cache

---

### Erreur 4: "Password confirmation does not match"

**Cause:** Backend attend `password_confirmation` mais Flutter envoie `passwordConfirmation`

**Solution:**
✅ Déjà corrigé dans le nouveau AuthController !
Assurez-vous d'avoir uploadé la dernière version.

---

### Erreur 5: "Connection refused" ou "No route to host"

**Cause:** Firewall ou serveur inaccessible

**Solution:**
1. Vérifier que https://dossypro.com est accessible dans un navigateur
2. Vérifier que le serveur est en ligne
3. Contacter l'hébergeur si nécessaire

---

## 📊 CHECKLIST FINALE AVANT TEST FLUTTER

Cochez chaque élément avant de compiler l'APK :

- [ ] `.env` vérifié et configuré (SANCTUM_STATEFUL_DOMAINS)
- [ ] `routes/api.php` uploadé sur le serveur
- [ ] `AuthController.php` uploadé sur le serveur
- [ ] Cache vidé (11/11 tasks successful)
- [ ] Health Check fonctionne (curl)
- [ ] Register fonctionne (curl)
- [ ] Login fonctionne (curl)
- [ ] Plans d'abonnement créés dans la base
- [ ] Pays configurés (14 pays)

**✅ Si tous cochés → Vous pouvez compiler et tester l'APK Flutter !**

---

## 🔗 FICHIERS À UPLOADER

**Téléchargez ces fichiers depuis GitHub:**

1. **routes/api.php**
   ```
   https://raw.githubusercontent.com/stealbass/doss/genspark_ai_developer/routes/api.php
   ```

2. **AuthController.php**
   ```
   https://raw.githubusercontent.com/stealbass/doss/genspark_ai_developer/app/Http/Controllers/Api/Mobile/AuthController.php
   ```

**Destinations:**
```
1. /home/dossypro/public_html/routes/api.php
2. /home/dossypro/public_html/app/Http/Controllers/Api/Mobile/AuthController.php
```

---

## 💡 CONSEIL PROFESSIONNEL

**Testez TOUJOURS le backend avec cURL avant de compiler l'APK !**

Cela vous évitera :
- ❌ De compiler plusieurs fois
- ❌ De perdre du temps à debugger Flutter
- ❌ Des erreurs mystérieuses "Server Error"

**Règle d'or:** Backend OK (cURL) → Alors seulement → Compiler Flutter

---

## 📞 BESOIN D'AIDE ?

Si vous êtes bloqué à une étape :

1. **Copiez le message d'erreur exact**
2. **Notez l'étape où vous êtes bloqué**
3. **Faites un screenshot si possible**
4. **Envoyez-moi ces informations**

Je vous débloquerai rapidement ! 🚀

---

**Date:** 26 décembre 2025  
**Version:** 1.0  
**Status:** ✅ **GUIDE COMPLET**  
**Fichier:** `CONFIGURATION_BACKEND_POUR_FLUTTER.md`
