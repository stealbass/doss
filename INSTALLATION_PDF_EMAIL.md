# Installation de la Fonctionnalité d'Envoi de Facture par Email avec PDF

## 📋 Vue d'ensemble

Cette fonctionnalité permet d'envoyer les factures par email avec un fichier PDF en pièce jointe directement depuis l'application.

## ✅ Fonctionnalités Implémentées

1. **Bouton "Envoyer par Email"** dans la page de détail de la facture
2. **Formulaire popup** pour saisir :
   - Email du destinataire (pré-rempli avec l'email du client si disponible)
   - Objet de l'email
   - Message personnalisé
3. **Génération automatique du PDF** de la facture
4. **Envoi par email** avec le PDF en pièce jointe
5. **Utilisation des paramètres email** configurés dans l'application

## 🔧 Installation Requise

### Étape 1 : Installer le package DomPDF

Pour générer les PDF, vous devez installer le package `barryvdh/laravel-dompdf` :

```bash
composer require barryvdh/laravel-dompdf
```

### Étape 2 : Publier la configuration (Optionnel)

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### Étape 3 : Vérifier la configuration Email

Assurez-vous que vos paramètres email sont correctement configurés dans :
- **Paramètres > Paramètres d'e-mail**

Les paramètres nécessaires :
- Nom de l'expéditeur
- Email de l'expéditeur
- Configuration SMTP (serveur, port, encryption, authentification)

## 📁 Fichiers Créés/Modifiés

### Nouveaux Fichiers

1. **resources/views/bills/send_email.blade.php**
   - Formulaire popup pour l'envoi d'email

2. **resources/views/bills/pdf.blade.php**
   - Template PDF de la facture

3. **resources/views/email/bill_send.blade.php**
   - Template email pour l'envoi de facture

### Fichiers Modifiés

1. **app/Http/Controllers/BillController.php**
   - Ajout de `sendEmail()` : Affiche le formulaire d'envoi
   - Ajout de `postSendEmail()` : Traite l'envoi de l'email avec PDF

2. **resources/views/bills/show.blade.php**
   - Ajout du bouton "Envoyer par Email" (icône enveloppe)

3. **routes/web.php**
   - Ajout des routes :
     - `GET bill/{id}/send-email` : Affiche le formulaire
     - `POST bill/{id}/send-email` : Envoie l'email

## 🚀 Utilisation

### Pour l'utilisateur :

1. Ouvrir une facture (cliquer sur "Voir" dans la liste des factures)
2. Cliquer sur le bouton "Envoyer par Email" (icône enveloppe ✉️)
3. Le formulaire s'affiche avec :
   - Email du client pré-rempli (si disponible dans son profil)
   - Objet de l'email pré-rempli avec le numéro de facture
   - Message par défaut (modifiable)
4. Vérifier/modifier les informations
5. Cliquer sur "Envoyer"
6. La facture est envoyée par email avec le PDF en pièce jointe

### Fonctionnalités automatiques :

- **Récupération automatique de l'email du client** depuis son profil
- **Génération automatique du PDF** de la facture
- **Format professionnel** du PDF avec :
  - Logo de l'entreprise
  - Informations émetteur et destinataire
  - Détails des articles avec calculs
  - Totaux (sous-total, taxes, remises, montant total)
  - Statut de la facture
- **Template email élégant** avec mise en forme professionnelle

## 🧪 Tests Recommandés

### Test 1 : Envoi avec email client existant
1. Créer un client avec un email valide
2. Créer une facture pour ce client
3. Ouvrir la facture
4. Cliquer sur "Envoyer par Email"
5. ✅ Vérifier que l'email du client est pré-rempli
6. Envoyer et vérifier la réception

### Test 2 : Envoi sans email client
1. Créer une facture pour un client sans email
2. Ouvrir la facture
3. Cliquer sur "Envoyer par Email"
4. ✅ Le champ email doit être vide
5. Saisir un email manuellement
6. Envoyer et vérifier la réception

### Test 3 : Modification du message
1. Ouvrir une facture
2. Cliquer sur "Envoyer par Email"
3. Modifier l'objet et le message
4. ✅ Vérifier que l'email reçu contient le message personnalisé

### Test 4 : Vérification du PDF
1. Envoyer une facture par email
2. Ouvrir l'email reçu
3. ✅ Vérifier la présence de la pièce jointe PDF
4. ✅ Ouvrir le PDF et vérifier son contenu
5. ✅ Vérifier que toutes les informations sont correctes

## 🔒 Permissions

La fonctionnalité d'envoi d'email respecte les permissions Laravel :
- Seuls les utilisateurs avec la permission `view bill` peuvent envoyer des factures par email
- Super Admin a accès par défaut

## 📧 Configuration Email

### Paramètres à vérifier

Dans **Paramètres > Paramètres d'e-mail** :

```
Mail Driver: SMTP
Mail Host: smtp.votre-serveur.com
Mail Port: 587 (ou 465 pour SSL)
Mail Username: votre-email@domaine.com
Mail Password: ****************
Mail Encryption: TLS (ou SSL)
Mail From Address: noreply@votre-domaine.com
Mail From Name: Dossy Pro
```

### Test de configuration

Pour tester la configuration email :
1. Aller dans Paramètres > Paramètres d'e-mail
2. Utiliser la fonction "Test Email" si disponible
3. Ou envoyer une facture test

## 🐛 Dépannage

### Problème : PDF non généré

**Symptôme** : Email envoyé mais sans PDF en pièce jointe

**Solution** :
```bash
# Installer le package DomPDF
composer require barryvdh/laravel-dompdf

# Vider le cache
php artisan config:clear
php artisan cache:clear
```

### Problème : Email non envoyé

**Symptôme** : Erreur lors de l'envoi

**Solutions possibles** :
1. Vérifier la configuration SMTP dans Paramètres
2. Vérifier que le serveur SMTP est accessible
3. Vérifier les logs Laravel : `storage/logs/laravel.log`
4. Tester avec un autre serveur SMTP (Gmail, SendGrid, etc.)

### Problème : Email du client non pré-rempli

**Symptôme** : Le champ email est vide même si le client a un email

**Solution** :
1. Vérifier que l'email est bien saisi dans le profil du client
2. Éditer le client et ajouter/vérifier son email
3. L'email doit être dans le champ `email` du modèle User

## 📝 Structure du PDF

Le PDF généré contient :

### En-tête
- Titre "FACTURE"
- Numéro de facture

### Section Émetteur/Destinataire
- **Facturé par** : Informations de l'entreprise ou de l'avocat
- **Facturé à** : Informations du client (nom, adresse, email)
- **Date d'échéance**
- **Statut** : PENDING / Partialy Paid / PAID (avec badge coloré)

### Tableau des Articles
- Numéro de ligne
- Description
- Quantité
- Prix unitaire
- Taxe (nom et pourcentage)
- Montant par ligne

### Section Totaux
- Sous-total
- Total Taxe
- Total Remise
- **MONTANT TOTAL** (mis en évidence)
- Montant Dû

### Pied de page
- Message de remerciement
- Nom de l'application
- Date de génération

## 🎨 Personnalisation

### Modifier le template email

Éditer : `resources/views/email/bill_send.blade.php`

### Modifier le template PDF

Éditer : `resources/views/bills/pdf.blade.php`

### Modifier le formulaire d'envoi

Éditer : `resources/views/bills/send_email.blade.php`

## 📚 Documentation Technique

### Routes

```php
// Afficher le formulaire d'envoi
GET /bill/{id}/send-email
Route: bill.send.email

// Traiter l'envoi
POST /bill/{id}/send-email
Route: bill.post.send.email
```

### Méthodes du Contrôleur

```php
// BillController@sendEmail
// Affiche le formulaire popup avec email du client pré-rempli

// BillController@postSendEmail
// Valide, génère le PDF et envoie l'email
```

### Génération du PDF

Le PDF est généré en utilisant :
- Package : `barryvdh/laravel-dompdf`
- Template : `resources/views/bills/pdf.blade.php`
- Moteur : DomPDF (conversion HTML vers PDF)

### Envoi de l'email

L'email utilise :
- Système Laravel Mail
- Configuration SMTP depuis les paramètres de l'application
- Template : `resources/views/email/bill_send.blade.php`
- Pièce jointe : PDF généré dynamiquement

## ✨ Améliorations Futures Possibles

1. **Historique des envois** : Enregistrer les emails envoyés
2. **Envoi groupé** : Envoyer plusieurs factures en une fois
3. **Rappels automatiques** : Envoi automatique avant échéance
4. **Templates personnalisables** : Plusieurs modèles d'email
5. **Aperçu avant envoi** : Voir le PDF avant d'envoyer
6. **CC/BCC** : Copie à d'autres destinataires
7. **Suivi** : Savoir si l'email a été ouvert

## 📞 Support

Pour toute question ou problème :
1. Vérifier ce fichier de documentation
2. Consulter les logs : `storage/logs/laravel.log`
3. Vérifier la configuration email
4. Tester avec une facture simple

---

**Date de création** : {{ date('Y-m-d') }}  
**Version** : 1.0  
**Statut** : Production Ready (après installation de DomPDF)
