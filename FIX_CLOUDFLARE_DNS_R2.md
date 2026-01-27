# 🔧 Fix Cloudflare DNS + R2 Configuration pour dossypro.com

## 🚨 Problème Actuel

**Symptômes** :
- ✅ Site accessible sur WiFi bureau
- ❌ Site inaccessible sur réseau mobile / autres clients
- ❌ Images et documents bibliothèque ne s'affichent pas
- ❌ CNAME `files` mal configuré

**Cause** :
- Configuration DNS Cloudflare incorrecte pour R2
- CNAME `files` pointe vers `dossy-pro-documents` au lieu de l'endpoint R2

---

## ✅ Solution Étape par Étape

### **ÉTAPE 1 : Corriger le CNAME pour R2**

#### 1.1 Supprimer l'ancien CNAME `files`
1. Aller sur Cloudflare Dashboard
2. Sélectionner `dossypro.com`
3. Aller dans **DNS** → **Records**
4. Trouver le CNAME `files` qui pointe vers `dossy-pro-documents`
5. Cliquer sur **Edit** → **Delete**

#### 1.2 Créer le nouveau CNAME pour R2
1. Cliquer sur **Add record**
2. **Type** : `CNAME`
3. **Name** : `files`
4. **Target** : `<VOTRE_BUCKET_R2_ID>.r2.cloudflarestorage.com`
   
   **OU** si vous avez un custom domain R2 :
   
   **Target** : Le domaine R2 custom (exemple: `pub-xxxxxxxxxxxxxx.r2.dev`)

5. **Proxy status** : 🟠 **DNS only** (très important !)
6. **TTL** : `Auto`
7. Cliquer sur **Save**

---

### **ÉTAPE 2 : Configurer R2 Custom Domain (Recommandé)**

#### 2.1 Aller dans R2 Dashboard
1. Cloudflare Dashboard → **R2** → **Object Storage**
2. Sélectionner votre bucket `dossy-pro-documents`
3. Cliquer sur **Settings**

#### 2.2 Ajouter Custom Domain
1. Aller dans **Custom Domains**
2. Cliquer sur **Connect Domain**
3. Entrer : `files.dossypro.com`
4. Cloudflare va automatiquement créer le CNAME correct
5. Cliquer sur **Continue**
6. Attendre la validation (1-2 minutes)

#### 2.3 Activer Public Access
1. Dans **Settings** du bucket
2. Aller dans **Public access**
3. Activer **Allow Access** (si pas déjà fait)
4. Confirmer

---

### **ÉTAPE 3 : Vérifier la Configuration DNS**

Après avoir créé le custom domain R2, vérifier que le CNAME est correct :

```
Type    Name    Target
CNAME   files   <GENERATED_BY_CLOUDFLARE>.r2.cloudflarestorage.com
```

**OU**

```
Type    Name    Target
CNAME   files   pub-xxxxxxxxxxxxxx.r2.dev
```

**⚠️ IMPORTANT** : Le **Proxy status** doit être **DNS only** (nuage gris ⛅)

---

### **ÉTAPE 4 : Configurer CORS pour R2**

#### 4.1 Aller dans R2 Settings
1. Cloudflare R2 → Bucket `dossy-pro-documents`
2. **Settings** → **CORS Policy**

#### 4.2 Ajouter CORS Rules
```json
[
  {
    "AllowedOrigins": [
      "https://dossypro.com",
      "https://www.dossypro.com",
      "https://dossy.alwaysdata.net"
    ],
    "AllowedMethods": [
      "GET",
      "HEAD"
    ],
    "AllowedHeaders": [
      "*"
    ],
    "ExposeHeaders": [],
    "MaxAgeSeconds": 3600
  }
]
```

3. Sauvegarder

---

### **ÉTAPE 5 : Vérifier les URLs dans la Base de Données**

#### 5.1 SSH sur AlwaysData
```bash
ssh utilisateur@ssh-utilisateur.alwaysdata.net
cd ~/www/
```

#### 5.2 Vérifier Configuration R2
```bash
php artisan tinker
```

```php
// Vérifier r2_url
>>> Setting::where('key', 'r2_url')->first()->value;
// Doit retourner: "https://files.dossypro.com"

// Vérifier r2_endpoint
>>> Setting::where('key', 'r2_endpoint')->first()->value;
// Format attendu: "https://<ACCOUNT_ID>.r2.cloudflarestorage.com"

// Tester génération URL
>>> Utility::get_file('legal_documents/test.pdf');
// Doit retourner: "https://files.dossypro.com/legal_documents/test.pdf"
```

#### 5.3 Corriger si Nécessaire
Si `r2_url` n'est pas `https://files.dossypro.com` :

```php
>>> Setting::where('key', 'r2_url')->update(['value' => 'https://files.dossypro.com']);
>>> exit
```

Vider le cache :
```bash
php artisan config:clear
php artisan cache:clear
```

---

### **ÉTAPE 6 : Tester depuis Réseau Mobile**

#### 6.1 Tester DNS Propagation
Ouvrir terminal et exécuter :

```bash
# Tester résolution DNS
nslookup files.dossypro.com

# Devrait retourner l'adresse IP Cloudflare ou l'endpoint R2
```

#### 6.2 Tester URLs R2
Ouvrir navigateur mobile et tester :

```
https://files.dossypro.com/legal_documents/VOTRE_FICHIER.pdf
```

Si l'image/PDF s'affiche → ✅ Configuration correcte

#### 6.3 Tester Site Web
```
https://dossypro.com
https://www.dossypro.com
```

Vérifier que :
- ✅ Site s'ouvre
- ✅ Images s'affichent
- ✅ Documents bibliothèque s'affichent

---

## 🔍 Diagnostic Complet

### Test 1 : DNS Resolution
```bash
# Test DNS depuis ligne de commande
dig files.dossypro.com

# OU
nslookup files.dossypro.com
```

**Résultat attendu** : Doit pointer vers un endpoint Cloudflare ou R2

---

### Test 2 : URL R2 Directe
Ouvrir navigateur (mode navigation privée) :

```
https://files.dossypro.com/
```

**Résultat attendu** :
- Page blanche (OK)
- OU liste des fichiers (si bucket list activé)
- ❌ PAS d'erreur 404 ou "DNS not found"

---

### Test 3 : Image/PDF Spécifique
Tester une URL complète d'un fichier existant :

```
https://files.dossypro.com/legal_documents/example.pdf
```

**Résultat attendu** : Le PDF s'ouvre correctement

---

## 🚨 Problèmes Courants et Solutions

### ❌ Problème 1 : "DNS not found" pour files.dossypro.com

**Cause** : CNAME pas créé ou mal configuré

**Solution** :
1. Vérifier que le CNAME `files` existe dans Cloudflare DNS
2. Vérifier qu'il pointe vers le bon endpoint R2
3. Attendre 5-10 minutes pour propagation DNS
4. Vider cache DNS local :
   ```bash
   # Windows
   ipconfig /flushdns
   
   # Mac
   sudo dscacheutil -flushcache
   
   # Linux
   sudo systemd-resolve --flush-caches
   ```

---

### ❌ Problème 2 : "Access Denied" sur files.dossypro.com

**Cause** : Public Access R2 désactivé

**Solution** :
1. Cloudflare R2 → Bucket `dossy-pro-documents`
2. Settings → **Public access**
3. Activer **Allow Access**
4. Sauvegarder

---

### ❌ Problème 3 : CORS Error dans la Console

**Cause** : CORS non configuré pour R2

**Solution** :
1. Appliquer la configuration CORS (voir ÉTAPE 4)
2. Vérifier que les origines incluent votre domaine

---

### ❌ Problème 4 : Site s'ouvre seulement sur WiFi bureau

**Cause** : Cache DNS local ou configuration proxy

**Solution** :
1. Vérifier Cloudflare **Proxy status** :
   - `dossypro.com` → 🟠 **Proxied** (nuage orange)
   - `www` → 🟠 **Proxied** (nuage orange)
   - `files` → ⛅ **DNS only** (nuage gris)

2. Désactiver VPN/Proxy sur WiFi bureau
3. Tester depuis réseau mobile 4G/5G
4. Tester depuis réseau différent (hotspot, autre WiFi)

---

### ❌ Problème 5 : Images s'affichent sur bureau mais pas mobile

**Cause** : Cache navigateur ou URLs hardcodées

**Solution** :
1. Ouvrir site en **mode navigation privée** sur mobile
2. Vérifier URLs générées :
   ```bash
   php artisan tinker
   >>> Utility::get_file('images/logo.png');
   ```
   Doit retourner : `https://files.dossypro.com/images/logo.png`

3. Vider cache Laravel :
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. Vérifier dans l'Inspector du navigateur (F12) :
   - Onglet **Network**
   - Vérifier que les URLs des images sont `https://files.dossypro.com/...`
   - Vérifier qu'il n'y a pas d'erreur 404 ou CORS

---

## ✅ Configuration DNS Recommandée Finale

Voici la configuration DNS optimale pour Cloudflare :

| Type | Name | Target/Content | Proxy Status | TTL |
|------|------|----------------|--------------|-----|
| **A** | `@` (dossypro.com) | `185.31.40.25` | 🟠 Proxied | Auto |
| **A** | `www` | `185.31.40.25` | 🟠 Proxied | Auto |
| **CNAME** | `files` | `<R2_ENDPOINT>` | ⛅ DNS only | Auto |
| **MX** | `@` | `mx1.alwaysdata.com` (10) | DNS only | Auto |
| **MX** | `@` | `mx2.alwaysdata.com` (20) | DNS only | Auto |

**Notes** :
- 🟠 **Proxied** = Cloudflare proxy activé (protection DDoS, cache, SSL)
- ⛅ **DNS only** = Pas de proxy Cloudflare (direct vers serveur)

**⚠️ IMPORTANT** : `files` doit être en **DNS only** pour R2

---

## 📋 Checklist Complète

### DNS & Cloudflare
- [ ] CNAME `files` créé et pointe vers endpoint R2 correct
- [ ] CNAME `files` en mode **DNS only** (nuage gris)
- [ ] Custom Domain R2 `files.dossypro.com` configuré
- [ ] Public Access R2 activé
- [ ] CORS configuré pour R2
- [ ] DNS propagation complète (5-10 min)

### Base de Données
- [ ] `r2_url` = `https://files.dossypro.com`
- [ ] `r2_endpoint` = `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`
- [ ] `storage_setting` = `r2`

### Laravel
- [ ] Cache vidé (`php artisan cache:clear`)
- [ ] Config vidée (`php artisan config:clear`)
- [ ] Test `Utility::get_file()` retourne URLs R2

### Tests
- [ ] `nslookup files.dossypro.com` → Résout correctement
- [ ] `https://files.dossypro.com/` → Accessible
- [ ] Images site s'affichent sur mobile
- [ ] Documents bibliothèque s'affichent sur mobile
- [ ] Site accessible depuis réseau 4G/5G

---

## 🆘 Si Problème Persiste

### Option 1 : Recréer Custom Domain R2
1. Supprimer custom domain `files.dossypro.com` dans R2
2. Supprimer CNAME `files` dans Cloudflare DNS
3. Attendre 5 minutes
4. Recréer custom domain dans R2 (il va recréer le CNAME)

### Option 2 : Utiliser Endpoint R2 Direct (Temporaire)
Si custom domain ne fonctionne pas, utiliser endpoint R2 direct :

```bash
php artisan tinker
```

```php
// Trouver votre endpoint R2 public
// Format: https://pub-xxxxxxxxxxxxxx.r2.dev

// Mettre à jour r2_url
>>> Setting::where('key', 'r2_url')->update(['value' => 'https://pub-YOUR_ID.r2.dev']);
>>> exit
```

Vider cache :
```bash
php artisan config:clear
php artisan cache:clear
```

**Note** : Cette solution fonctionne mais l'URL sera moins jolie.

---

## 📞 Support

**Cloudflare Support** : https://dash.cloudflare.com/  
**AlwaysData Support** : https://admin.alwaysdata.com/support/  
**GitHub Issues** : https://github.com/stealbass/doss/issues

---

**Date** : 2025-11-27  
**Problème** : DNS + R2 Configuration  
**Solution** : Custom Domain R2 + CORS + DNS DNS-only
