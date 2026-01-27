# 🌐 Configuration Cloudflare R2 pour Dossy Pro

## 📋 Vue d'ensemble

Cloudflare R2 est maintenant intégré à Dossy Pro comme 4ème option de stockage cloud, aux côtés de Local, AWS S3 et Wasabi.

### ✅ Avantages de Cloudflare R2

- **0€ de frais de sortie (egress)** - Pas de surprise de facturation
- **Prix compétitif** : $0.015/GB/mois ($15/TB)
- **Compatible S3** - API identique à AWS S3
- **Réseau mondial** - Bonne latence pour l'Afrique via CDN Cloudflare
- **Fiabilité** - Infrastructure Cloudflare de classe mondiale

---

## 🚀 Étape 1 : Créer un Compte Cloudflare R2

### 1️⃣ Inscription Cloudflare
1. Allez sur https://dash.cloudflare.com/sign-up
2. Créez un compte ou connectez-vous
3. Accédez au dashboard Cloudflare

### 2️⃣ Activer R2 Storage
1. Dans le menu latéral, cliquez sur **R2**
2. Cliquez sur **Purchase R2** (plan gratuit disponible : 10GB/mois)
3. Acceptez les conditions

### 3️⃣ Créer un Bucket
1. Cliquez sur **Create bucket**
2. Nom du bucket : `dossy-pro-documents` (ou autre nom de votre choix)
3. Location : Choisissez **Automatic** (recommandé)
4. Cliquez sur **Create bucket**

### 4️⃣ Générer les Credentials API
1. Allez dans **R2** → **Manage R2 API Tokens**
2. Cliquez sur **Create API Token**
3. Nom du token : `Dossy Pro Storage`
4. Permissions : 
   - ✅ **Object Read & Write** (cochez)
   - Ou sélectionnez **Admin Read & Write** pour accès complet
5. Cliquez sur **Create API Token**
6. **IMPORTANT** : Copiez et sauvegardez immédiatement :
   - **Access Key ID** (ex: `abc123def456...`)
   - **Secret Access Key** (ex: `xyz789uvw456...`)
   - ⚠️ Vous ne pourrez plus voir le Secret après cette étape !

### 5️⃣ Trouver votre Endpoint
1. Retournez dans **R2** → **Overview**
2. Cliquez sur votre bucket `dossy-pro-documents`
3. Dans les **Settings**, trouvez le **S3 API**
4. Copiez le **Endpoint** (format : `https://[account-id].r2.cloudflarestorage.com`)

---

## ⚙️ Étape 2 : Configuration dans Dossy Pro

### 1️⃣ Accéder aux Paramètres de Stockage

1. Connectez-vous à Dossy Pro en tant qu'administrateur
2. Allez dans **Settings** → **Storage Settings**
3. Vous verrez maintenant **4 options** :
   - Local
   - AWS S3
   - Wasabi
   - **Cloudflare R2** ✨ (nouvelle option)

### 2️⃣ Sélectionner Cloudflare R2

Cliquez sur le bouton **Cloudflare R2**

### 3️⃣ Remplir les Champs

| Champ | Valeur | Exemple |
|-------|--------|---------|
| **R2 Access Key ID** | Votre Access Key ID de l'étape 1 | `abc123def456ghi789` |
| **R2 Secret Access Key** | Votre Secret Access Key de l'étape 1 | `xyz789uvw456rst123` |
| **R2 Bucket** | Nom du bucket créé | `dossy-pro-documents` |
| **R2 Endpoint** | Endpoint R2 de votre compte | `https://[account-id].r2.cloudflarestorage.com` |
| **R2 Public URL** (Optionnel) | Domaine personnalisé si configuré | Laissez vide si vous n'en avez pas |
| **R2 Region** | Région | `auto` (recommandé) |
| **Only Upload Files** | Types de fichiers autorisés | Sélectionnez : `pdf`, `jpg`, `png`, `docx`, etc. |
| **Max upload size (In KB)** | Taille maximale par fichier | `51200` (50MB) |

### 4️⃣ Enregistrer

Cliquez sur **Save Changes** en bas de la page.

---

## 📁 Étape 3 : Configuration du Domaine Personnalisé (Optionnel)

Si vous voulez utiliser votre propre domaine pour accéder aux fichiers :

### 1️⃣ Ajouter un Domaine Personnalisé dans R2

1. Dans Cloudflare R2, ouvrez votre bucket
2. Allez dans **Settings** → **Custom Domains**
3. Cliquez sur **Connect Domain**
4. Entrez votre domaine : `files.dossypro.com` (exemple)
5. Cloudflare configure automatiquement le DNS

### 2️⃣ Mettre à Jour Dossy Pro

Dans **Storage Settings** → **Cloudflare R2** :
- **R2 Public URL** : `https://files.dossypro.com`

---

## 🧪 Étape 4 : Tester la Configuration

### Test Upload

1. Allez dans **Bibliothèque Juridique** (ou toute section avec upload)
2. Uploadez un document PDF
3. Vérifiez que l'upload réussit
4. Le fichier devrait être visible dans votre bucket R2

### Vérification dans Cloudflare

1. Ouvrez Cloudflare Dashboard → **R2** → Votre bucket
2. Cliquez sur **Browse**
3. Vous devriez voir vos fichiers uploadés

---

## 🔧 Résolution de Problèmes

### ❌ Erreur : "Credentials invalid"

**Cause** : Access Key ID ou Secret Access Key incorrect

**Solution** :
1. Vérifiez que vous avez copié exactement les credentials (pas d'espace)
2. Générez de nouveaux credentials API si nécessaire
3. Mettez à jour les paramètres dans Dossy Pro

### ❌ Erreur : "Bucket not found"

**Cause** : Nom du bucket incorrect ou bucket non créé

**Solution** :
1. Vérifiez l'orthographe exacte du nom du bucket
2. Assurez-vous que le bucket existe dans R2
3. Le nom est sensible à la casse

### ❌ Erreur : "Access Denied"

**Cause** : Permissions insuffisantes sur le token API

**Solution** :
1. Retournez dans R2 → **Manage R2 API Tokens**
2. Créez un nouveau token avec permissions **Admin Read & Write**
3. Mettez à jour les credentials dans Dossy Pro

### ❌ Fichiers uploadés mais non accessibles

**Cause** : Endpoint incorrect ou domaine personnalisé mal configuré

**Solution** :
1. Vérifiez l'endpoint dans Cloudflare R2
2. Si vous utilisez un domaine personnalisé, vérifiez qu'il est bien connecté
3. Laissez le champ **R2 Public URL** vide pour utiliser l'endpoint par défaut

---

## 📊 Estimation des Coûts

### Tarification Cloudflare R2

| Service | Prix |
|---------|------|
| **Stockage** | $0.015/GB/mois |
| **Opérations Class A** (write) | $4.50 / million |
| **Opérations Class B** (read) | $0.36 / million |
| **Sortie (Egress)** | **GRATUIT ♾️** |

### Exemple pour Dossy Pro

**Scénario** : 100 cabinets, 5000 documents PDF (moyenne 2MB/document)

| Métrique | Calcul | Coût mensuel |
|----------|--------|--------------|
| Stockage | 5000 × 2MB = 10GB | 10 × $0.015 = **$0.15** |
| Uploads | ~1000/mois | ~$0.005 |
| Downloads | ~10,000/mois | ~$0.004 |
| Egress (sortie) | Illimité | **$0** |
| **TOTAL** | | **~$0.16/mois** |

**Pour 1TB de stockage** : ~$15/mois avec sortie illimitée GRATUITE ! 🎉

---

## 🔐 Sécurité et Bonnes Pratiques

### ✅ Recommandations

1. **Tokens API** :
   - Créez des tokens séparés pour chaque environnement (dev, prod)
   - Renouvelez les tokens tous les 6-12 mois
   - Ne partagez JAMAIS vos credentials

2. **Permissions** :
   - Utilisez le principe du moindre privilège
   - Pour production : **Object Read & Write** suffit
   - Pour admin : **Admin Read & Write**

3. **Backup** :
   - Activez le versioning des objets dans R2 (optionnel)
   - Configurez des snapshots réguliers si données critiques

4. **Monitoring** :
   - Surveillez l'utilisation dans Cloudflare Analytics
   - Configurez des alertes pour dépassements

---

## 📝 Variables d'Environnement (.env)

Si vous préférez configurer via `.env` plutôt que l'interface :

```env
# Cloudflare R2 Configuration
R2_ACCESS_KEY_ID=your_access_key_id_here
R2_SECRET_ACCESS_KEY=your_secret_access_key_here
R2_REGION=auto
R2_BUCKET=dossy-pro-documents
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
R2_URL=  # Optionnel : votre domaine personnalisé
```

---

## 🌍 Migration depuis Wasabi/S3 vers R2

Si vous utilisez déjà Wasabi ou S3 et voulez migrer vers R2 :

### Méthode 1 : Migration Manuelle (Petits volumes)

1. Téléchargez les fichiers depuis Wasabi/S3
2. Changez le storage vers R2 dans Dossy Pro
3. Re-uploadez les fichiers

### Méthode 2 : Migration Automatisée (Gros volumes)

Contactez le support pour un script de migration automatique qui :
- Copie tous les fichiers de l'ancien storage vers R2
- Met à jour les chemins dans la base de données
- Vérifie l'intégrité des fichiers

---

## ✅ Checklist de Configuration

- [ ] Compte Cloudflare créé
- [ ] R2 activé avec plan choisi
- [ ] Bucket créé (`dossy-pro-documents`)
- [ ] Token API généré et sauvegardé
- [ ] Endpoint R2 copié
- [ ] Configuration dans Dossy Pro complétée
- [ ] Test d'upload réussi
- [ ] Fichiers visibles dans R2 bucket
- [ ] (Optionnel) Domaine personnalisé configuré

---

## 📞 Support

Pour toute assistance :
- Documentation Cloudflare R2 : https://developers.cloudflare.com/r2/
- Support Dossy Pro : [votre email support]

---

## 🎯 Prochaines Étapes Recommandées

Une fois R2 configuré et testé :

1. **✅ Monitorer les coûts** dans le dashboard Cloudflare
2. **✅ Configurer des backups** si données critiques
3. **✅ Optimiser les uploads** pour connexions lentes (chunked upload)
4. **✅ Configurer un CDN** si besoin de distribution mondiale rapide

---

**Cloudflare R2 est maintenant prêt pour Dossy Pro ! 🚀**

Profitez du stockage cloud sans frais de sortie pour votre application SaaS ! 💰
