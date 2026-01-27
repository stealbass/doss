# ✅ Améliorations Push Notifications

## Fonctionnalités ajoutées

### 1. 📋 Sélection d'utilisateurs spécifiques

**Avant** : Seules les options génériques étaient disponibles (Tous, Étudiants, Avocats, Entreprises, Plan spécifique)

**Après** : Nouvelle option "Utilisateurs spécifiques" permettant de :
- ✅ Voir la liste complète des utilisateurs inscrits
- ✅ Sélectionner individuellement les destinataires
- ✅ Voir les informations de chaque utilisateur (nom, email, plan actuel, rôle)
- ✅ Boutons "Sélectionner tout" / "Désélectionner tout"
- ✅ Compteur dynamique des destinataires sélectionnés

### 2. 🖼️ Upload d'image depuis stockage local

**Avant** : Champ "Image URL" - fallait saisir une URL externe

**Après** : Champ "Upload d'image" permettant de :
- ✅ Sélectionner une image depuis l'ordinateur
- ✅ Formats acceptés : PNG, JPG, JPEG, WEBP
- ✅ Taille maximale : 2 MB
- ✅ Prévisualisation de l'image avant envoi
- ✅ Image automatiquement uploadée et stockée dans `storage/push-notifications/`

---

## Fichiers modifiés

### 1. Vue - create.blade.php
**Fichier** : `resources/views/push-notifications/create.blade.php`

**Changements** :
- Formulaire avec `enctype="multipart/form-data"` pour upload de fichiers
- Ajout section "Utilisateurs spécifiques" avec liste scrollable + checkboxes
- Remplacement du champ "Image URL" par input file
- Ajout prévisualisation d'image en temps réel
- JavaScript pour toggle entre les différentes options d'audience
- Fonctions `selectAllUsers()` pour cocher/décocher tous les utilisateurs

### 2. Contrôleur - PushNotificationsController.php
**Fichier** : `app/Http/Controllers/PushNotificationsController.php`

**Méthode `create()`** :
- Ajout de `$users` : charge tous les utilisateurs mobiles avec leurs abonnements
- Ajout de l'option `'specific_users' => 'Utilisateurs spécifiques'` dans `$targetAudiences`

**Méthode `store()`** :
- Validation ajoutée pour `specific_users` (array d'IDs)
- Validation ajoutée pour `image` (fichier image, max 2MB)
- Upload de l'image vers `storage/push-notifications/`
- Conversion des IDs utilisateurs en JSON pour stockage
- Génération automatique de l'URL publique de l'image

**Méthode `getRecipients()`** :
- Ajout du cas `specific_users` : retourne uniquement les utilisateurs sélectionnés
- Correction bug : `plan_id` → `mobile_app_plan_id` dans la requête plan_specific

### 3. Modèle - PushNotification.php
**Fichier** : `app/Models/PushNotification.php`

**Changements** :
- Ajout `'specific_users'` dans `$fillable`
- Ajout cast `'specific_users' => 'array'` pour conversion automatique JSON ↔ Array

### 4. Migration
**Fichier** : `database/migrations/2026_01_05_000001_add_specific_users_to_push_notifications.php`

**Changement** :
- Ajout colonne `specific_users` (JSON, nullable) dans table `push_notifications`

---

## Installation

### Étape 1 : Exécuter la migration
```bash
.\migrate-push-notifications.bat
```

Ou manuellement :
```bash
php artisan migrate --path=database/migrations/2026_01_05_000001_add_specific_users_to_push_notifications.php
```

### Étape 2 : Créer le lien symbolique storage (si pas déjà fait)
```bash
php artisan storage:link
```

Cette commande crée un lien symbolique de `storage/app/public` vers `public/storage`, permettant l'accès public aux fichiers uploadés.

### Étape 3 : Vider le cache
```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
```

---

## Utilisation

### Créer une notification avec utilisateurs spécifiques

1. **Aller sur** : Admin → Push Notifications → Create
2. **Remplir** : Titre, Message, Type
3. **Sélectionner** : Target Audience → "Utilisateurs spécifiques"
4. **Cocher** : Les utilisateurs souhaités dans la liste
5. **Uploader** : Une image (optionnel) depuis votre ordinateur
6. **Envoyer** : Immédiatement ou planifier

### Vérifier les destinataires

Le compteur "Recipients" en bas de la liste se met à jour automatiquement :
- Si vous cochez 3 utilisateurs → Affiche "3"
- Utilise "Sélectionner tout" → Affiche le nombre total
- Change d'audience → Recalcule automatiquement

### Format d'image recommandé

- **Dimensions** : 1200x600px (ratio 2:1)
- **Format** : PNG ou JPEG
- **Poids** : < 500 KB pour performances optimales
- **Contenu** : Éviter texte trop petit (illisible sur mobile)

---

## Données stockées

### Dans la base de données (table push_notifications)

```sql
-- Exemple avec utilisateurs spécifiques
target_audience: 'specific_users'
specific_users: '[202, 203, 205]'  -- JSON array des IDs
image_url: 'http://dossypro.com/storage/push-notifications/abc123.jpg'

-- Exemple avec plan spécifique (inchangé)
target_audience: 'plan_specific'
target_plan: '1'
specific_users: NULL
```

### Sur le serveur (fichiers)

```
storage/
  app/
    public/
      push-notifications/
        - abc123def456.jpg
        - ghi789jkl012.png
        
public/
  storage/ → lien symbolique vers storage/app/public
```

---

## Sécurité

### Upload d'images
✅ Validation stricte des types MIME (image/png, image/jpeg, etc.)
✅ Limite de taille 2 MB
✅ Stockage dans dossier dédié `push-notifications/`
✅ Noms de fichiers aléatoires (pas d'écrasement)

### Sélection d'utilisateurs
✅ Validation que les IDs existent dans la table `users`
✅ Seuls les utilisateurs avec abonnements mobiles sont affichés
✅ Backend vérifie que les IDs correspondent à de vrais utilisateurs

---

## Test

### Test 1 : Upload d'image
1. Créer notification
2. Sélectionner une image
3. Vérifier prévisualisation s'affiche
4. Envoyer
5. Vérifier fichier existe dans `storage/app/public/push-notifications/`
6. Vérifier URL accessible : `http://dossypro.com/storage/push-notifications/fichier.jpg`

### Test 2 : Utilisateurs spécifiques
1. Créer notification
2. Target Audience → "Utilisateurs spécifiques"
3. Cocher Emma Gui (ID 202)
4. Vérifier compteur affiche "1"
5. Cliquer "Sélectionner tout"
6. Vérifier compteur affiche le total
7. Envoyer
8. Vérifier dans BDD : `SELECT specific_users FROM push_notifications WHERE id=X;`
9. Résultat attendu : `[202]` ou `[202,203,205]`

### Test 3 : Envoi réel
1. Créer notification avec 1 utilisateur spécifique
2. Envoyer immédiatement
3. Vérifier dans Admin → Push Notifications :
   - Status = "Sent"
   - Total Recipients = 1
   - Successful Sends = 1

---

## Troubleshooting

### ❌ Image ne s'affiche pas après upload
**Problème** : Image uploadée mais URL renvoie 404

**Solution** :
```bash
php artisan storage:link
```

Vérifier que le lien symbolique existe : `public/storage` doit pointer vers `storage/app/public`

### ❌ Liste d'utilisateurs vide
**Problème** : Aucun utilisateur dans la liste de sélection

**Cause** : Pas d'utilisateurs avec abonnements mobiles actifs

**Solution** :
1. Vérifier qu'il y a des utilisateurs dans `mobile_app_subscriptions`
2. Vérifier que les utilisateurs ont `user_id` valide

### ❌ Erreur "specific_users column not found"
**Problème** : Migration pas exécutée

**Solution** :
```bash
php artisan migrate --path=database/migrations/2026_01_05_000001_add_specific_users_to_push_notifications.php
```

### ❌ Image trop volumineuse
**Problème** : Erreur validation "The image may not be greater than 2048 kilobytes"

**Solution** :
1. Compresser l'image avec un outil en ligne (TinyPNG, etc.)
2. Ou augmenter la limite dans `store()` : `'image' => 'nullable|image|max:5120'` (5MB)

---

## Améliorations futures possibles

1. **Filtres avancés** : Combiner plusieurs critères (Plan + Rôle + Pays)
2. **Groupes d'utilisateurs** : Sauvegarder des listes réutilisables
3. **Statistiques détaillées** : Voir qui a ouvert/cliqué par utilisateur
4. **Images multiples** : Carousel d'images dans la notification
5. **Aperçu réel mobile** : Simuler l'apparence sur iOS/Android
6. **Tests A/B** : Envoyer différentes variantes pour comparer performances

---

## Checklist déploiement

- [ ] Uploader tous les fichiers modifiés
- [ ] Exécuter migration `2026_01_05_000001_add_specific_users_to_push_notifications.php`
- [ ] Exécuter `php artisan storage:link` si jamais fait
- [ ] Vider cache Laravel (route, config, view)
- [ ] Tester création notification avec upload image
- [ ] Tester sélection utilisateurs spécifiques
- [ ] Vérifier compteur recipients fonctionne
- [ ] Tester envoi réel notification
- [ ] Vérifier image s'affiche dans notification envoyée

✅ **Tout est prêt !**
