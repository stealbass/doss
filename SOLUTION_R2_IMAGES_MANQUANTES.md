# 🔧 SOLUTION: Images manquantes sur R2

## 🎯 Diagnostic du problème

Tu as dit :
- ✅ **Storage local** : Logo s'affiche → `https://dossypro.com/legal//storage/uploads/landing_page_image/site_logo.png`
- ❌ **Storage R2** : Logo ne s'affiche pas → `https://files.dossypro.com/uploads/landing_page_image/site_logo.png`

**Cause identifiée :**  
Le fichier `site_logo.png` existe en **stockage local** mais **PAS sur R2**.

Quand tu changes le Storage setting à R2, Laravel génère l'URL R2, mais le fichier n'a jamais été uploadé sur R2.

---

## ✅ Solution 1: Migrer les fichiers existants vers R2 (RECOMMANDÉ)

### Étape 1: Télécharger les scripts sur ton serveur

Tu as maintenant 2 scripts dans le repo :
- `test_r2_connection.php` - Test connexion R2
- `migrate_local_to_r2.php` - Migration fichiers local → R2

### Étape 2: Sur ton serveur AlwaysData

```bash
# Se connecter
ssh dossypro@ssh-dossypro.alwaysdata.net

# Aller dans le répertoire
cd ~/public_html

# Pull les nouveaux scripts
git pull origin main

# Test connexion R2
php test_r2_connection.php
```

**Résultat attendu :**
```
=== TEST CONNEXION R2 ===

Configuration R2:
- r2_bucket: dossy-storage
- r2_endpoint: https://xxxxx.r2.cloudflarestorage.com
- r2_url: https://files.dossypro.com
- r2_key: CONFIGURÉ (****)
- r2_secret: CONFIGURÉ (****)

Test listage fichiers R2...
⚠️  AUCUN fichier trouvé dans uploads/landing_page_image
```

Si tu vois `⚠️ AUCUN fichier`, c'est normal : les fichiers ne sont pas encore sur R2.

### Étape 3: Migrer les fichiers

```bash
php migrate_local_to_r2.php
```

Le script va :
1. ✅ Vérifier connexion R2
2. ✅ Lister tous les fichiers locaux
3. ✅ Copier chaque fichier vers R2
4. ✅ Conserver les fichiers locaux (backup)

**Exemple de sortie :**
```
🚀 Début migration...

📁 Traitement: uploads/landing_page_image
   Trouvé 3 fichier(s)
   ✅ Uploadé: site_logo.png (45.23 KB)
   ✅ Uploadé: favicon.png (12.45 KB)
   ✅ Uploadé: banner.jpg (234.56 KB)

📁 Traitement: uploads/profile
   Trouvé 15 fichier(s)
   ✅ Uploadé: avatar_1.jpg (23.45 KB)
   ...

═══════════════════════════════════════════════════
  RÉSUMÉ MIGRATION
═══════════════════════════════════════════════════
📊 Fichiers traités: 45
✅ Uploadés sur R2: 45
⏭️  Déjà existants (ignorés): 0
❌ Erreurs: 0

🎉 Migration réussie !
```

### Étape 4: Vérifier que les fichiers sont sur R2

**Test direct dans le navigateur :**

Ouvre cette URL : `https://files.dossypro.com/uploads/landing_page_image/site_logo.png`

- ✅ Si l'image s'affiche → **Migration OK !**
- ❌ Si erreur 404 → Problème de bucket public (voir plus bas)

### Étape 5: Activer R2 dans les paramètres

Si les images s'affichent via l'URL R2 :

1. Aller dans **Admin → Paramètres → Stockage**
2. Changer **Storage Setting** à **Cloudflare R2**
3. Vider le cache : `php artisan cache:clear`
4. Actualiser le site

**Le logo devrait maintenant s'afficher !** 🎉

---

## ✅ Solution 2: Bucket R2 non public (si erreur 403/404)

Si tu vois **403 Forbidden** ou **404 Not Found** sur `https://files.dossypro.com/...` :

### Vérifier que le bucket est public

1. **Cloudflare Dashboard** → **R2**
2. Clique sur ton bucket (ex: `dossy-storage`)
3. **Settings** → **Public Access**
4. Vérifie que **Allow Access** est **Enabled**

**Si pas activé :**
1. Clique sur **Allow Access**
2. Cloudflare va te donner une URL publique : `https://pub-xxxxxxxxxxxxx.r2.dev`
3. **Copie cette URL**

### Mettre à jour r2_url dans la base de données

**Option A: Via PhpMyAdmin**
```sql
UPDATE settings 
SET value = 'https://pub-xxxxxxxxxxxxx.r2.dev' 
WHERE name = 'r2_url';
```

**Option B: Via l'interface admin**
1. Admin → Paramètres → Stockage
2. Section **Cloudflare R2**
3. **R2 Public URL** : Colle l'URL publique
4. Sauvegarde

### Vider le cache

```bash
php artisan cache:clear
php artisan config:clear
```

---

## ✅ Solution 3: Domaine personnalisé R2 (si tu veux garder files.dossypro.com)

Si tu veux utiliser `https://files.dossypro.com` au lieu de `pub-xxxxx.r2.dev` :

### Étape 1: Configurer le domaine personnalisé sur Cloudflare

1. **Cloudflare Dashboard** → **R2** → Ton bucket
2. **Settings** → **Custom Domains**
3. **Connect Domain** → Entre `files.dossypro.com`
4. Cloudflare va te demander de créer un enregistrement CNAME

### Étape 2: Ajouter le CNAME DNS

1. **Cloudflare Dashboard** → **DNS**
2. **Add record** :
   - Type: **CNAME**
   - Name: **files** (pour files.dossypro.com)
   - Target: **Celui donné par R2** (ex: `bucket-name.r2.cloudflarestorage.com`)
   - Proxy status: **Proxied** (orange cloud)
3. **Save**

### Étape 3: Attendre propagation DNS (quelques minutes)

Test : Ouvre `https://files.dossypro.com/uploads/landing_page_image/site_logo.png`

Si ça fonctionne, **c'est bon !**

---

## 🔍 Diagnostic avancé

### Problème: Le script de migration échoue

**Erreur: "Credentials are not configured correctly"**

→ Vérifie dans PhpMyAdmin :
```sql
SELECT name, value FROM settings WHERE name LIKE 'r2_%';
```

Vérifie que :
- `r2_key` = Ton Access Key ID (30+ caractères)
- `r2_secret` = Ton Secret Key (40+ caractères)
- `r2_endpoint` = `https://xxxxx.r2.cloudflarestorage.com`
- `r2_bucket` = Nom exact du bucket

**Si manquant :**
1. Admin → Paramètres → Stockage
2. Reconfigure Cloudflare R2
3. Sauvegarde

### Problème: Images uploadées sur R2 mais toujours pas visibles

**Vérifier CORS du bucket :**

1. Cloudflare Dashboard → R2 → Ton bucket
2. Settings → CORS Policy
3. Ajoute cette configuration :

```json
[
  {
    "AllowedOrigins": [
      "https://dossypro.com",
      "https://www.dossypro.com",
      "https://files.dossypro.com"
    ],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

### Problème: Double slash dans l'URL locale

Tu as dit que l'URL locale est : `https://dossypro.com/legal//storage/...` (double slash)

C'est probablement un bug dans la configuration. Pour le fixer :

**Vérifie dans `.env` ou settings :**
```bash
APP_URL=https://dossypro.com
# PAS: https://dossypro.com/
```

Ou vérifie dans `config/filesystems.php` :
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',  // Pas de slash avant /storage
],
```

---

## 📋 Checklist finale

Après avoir suivi les étapes ci-dessus :

- [ ] Script `test_r2_connection.php` exécuté → Connexion OK
- [ ] Script `migrate_local_to_r2.php` exécuté → Fichiers migrés
- [ ] URL directe R2 testée → `https://files.dossypro.com/uploads/landing_page_image/site_logo.png` s'affiche
- [ ] Storage setting changé à R2 dans l'admin
- [ ] Cache vidé : `php artisan cache:clear`
- [ ] Site actualisé → Logo s'affiche correctement

---

## 🚀 Commandes rapides (résumé)

```bash
# Sur le serveur
ssh dossypro@ssh-dossypro.alwaysdata.net
cd ~/public_html

# 1. Test connexion R2
php test_r2_connection.php

# 2. Migrer fichiers
php migrate_local_to_r2.php

# 3. Vider cache
php artisan cache:clear
php artisan config:clear

# 4. Test URL R2
curl -I https://files.dossypro.com/uploads/landing_page_image/site_logo.png
# Si retourne 200 OK → Fichier accessible ✅
# Si retourne 404 → Fichier manquant ou bucket pas public ❌
```

---

## ❓ Besoin d'aide ?

Si après toutes ces étapes les images ne s'affichent toujours pas :

1. Exécute ces commandes et envoie-moi les résultats :

```bash
php test_r2_connection.php
php migrate_local_to_r2.php
curl -I https://files.dossypro.com/uploads/landing_page_image/site_logo.png
```

2. Envoie-moi aussi une capture d'écran de :
   - Cloudflare R2 → Ton bucket → Settings → Public Access
   - Cloudflare R2 → Ton bucket → Settings → Custom Domains (si configuré)

Je t'aiderai à diagnostiquer !

---

**Date:** 2024-11-22  
**Auteur:** Claude AI  
**Statut:** Guide complet prêt
