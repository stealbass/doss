# Correction de l'Envoi d'Email pour Mot de Passe Oublié

## Problème Identifié

L'erreur SMTP suivante apparaissait lors de la tentative de réinitialisation du mot de passe depuis l'application Flutter :

```
Failed to authenticate on SMTP server with username "contact@dossupro.com" using the following authenticators: "LOGIN", "PLAIN". 
Authenticator "LOGIN" returned "Expected response code "235" but got code "535"
```

## Cause du Problème

La fonction `forgotPassword` dans `AuthController.php` n'appelait pas la configuration SMTP depuis la base de données avant d'envoyer l'email de réinitialisation. Les paramètres SMTP n'étaient donc pas correctement configurés.

## Solution Appliquée

### 1. Ajout de l'import Utility

```php
use App\Models\Utility;
```

### 2. Configuration SMTP avant l'envoi

La fonction `forgotPassword` a été modifiée pour inclure :

```php
try {
    // Configurer les paramètres SMTP depuis la base de données
    // Utiliser l'ID 1 pour les paramètres de l'admin principal
    Utility::getSMTPDetails(1);
    
    Log::info('SMTP configured for password reset', ['email' => $request->email]);
    
    $status = Password::sendResetLink(
        $request->only('email')
    );
    // ... rest of the code
} catch (\Exception $e) {
    Log::error('Password reset error', [
        'email' => $request->email,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return response()->json([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'envoi de l\'email.',
        'error' => $e->getMessage(),
    ], 500);
}
```

## Inspiration de la Solution

Cette correction s'inspire directement de la méthode utilisée dans `PushNotificationsController.php` (lignes 365-370) :

```php
// Configurer les paramètres SMTP depuis la base de données
Utility::getSMTPDetails(Auth::user()->created_by);

foreach ($recipients as $user) {
    try {
        if ($user->email) {
            Mail::to($user->email)->send(new SendPushNotificationEmail($notification, $user));
            // ...
        }
    } catch (\Exception $e) {
        // Error handling
    }
}
```

## Vérifications à Effectuer

### 1. Paramètres SMTP dans l'Admin

Assurez-vous que les paramètres SMTP sont correctement configurés dans le panneau d'administration :

1. Accédez à **Paramètres > Email Settings**
2. Vérifiez les paramètres suivants :
   - **Mail Driver** : smtp
   - **Mail Host** : smtp.votre-serveur.com
   - **Mail Port** : 587 (pour TLS) ou 465 (pour SSL)
   - **Mail Username** : contact@dossupro.com
   - **Mail Password** : [Mot de passe correct]
   - **Mail Encryption** : tls ou ssl
   - **Mail From Address** : contact@dossupro.com
   - **Mail From Name** : DossuPro

### 2. Test de l'Email

1. Dans le panneau admin, utilisez le bouton "Test Mail" pour vérifier que les paramètres SMTP fonctionnent
2. Si le test échoue, vérifiez :
   - Le mot de passe SMTP
   - Les paramètres du serveur SMTP
   - Les autorisations du compte email
   - Les règles de pare-feu

### 3. Vérification des Logs

Après la correction, les logs suivants seront générés :

**Succès :**
```
SMTP configured for password reset
Password reset link sent successfully
```

**Échec :**
```
Password reset error
```

## Messages Retournés à Flutter

### Succès (200)
```json
{
    "success": true,
    "message": "Un email de réinitialisation a été envoyé à votre adresse."
}
```

### Erreur - Email Invalide (422)
```json
{
    "success": false,
    "message": "Impossible d'envoyer l'email de réinitialisation. Veuillez vérifier votre adresse email."
}
```

### Erreur - Exception SMTP (500)
```json
{
    "success": false,
    "message": "Une erreur est survenue lors de l'envoi de l'email. Veuillez réessayer plus tard.",
    "error": "[Détails de l'erreur]"
}
```

## Configuration Recommandée pour DossuPro

Si vous utilisez un service d'email professionnel, voici les paramètres typiques :

### Gmail
```
Mail Host: smtp.gmail.com
Mail Port: 587
Mail Encryption: tls
```

### Office 365
```
Mail Host: smtp.office365.com
Mail Port: 587
Mail Encryption: tls
```

### OVH
```
Mail Host: ssl0.ovh.net
Mail Port: 465
Mail Encryption: ssl
```

### Autre hébergeur
Contactez votre hébergeur pour obtenir les paramètres SMTP corrects.

## Test depuis Flutter

Après cette correction, testez la fonctionnalité "Mot de passe oublié" :

1. Ouvrez l'application Flutter
2. Sur l'écran de connexion, cliquez sur "Mot de passe oublié"
3. Entrez une adresse email valide
4. Vérifiez la réception de l'email
5. Cliquez sur le lien de réinitialisation
6. Définissez un nouveau mot de passe

## Notes Importantes

- La fonction utilise `Utility::getSMTPDetails(1)` pour récupérer les paramètres SMTP de l'admin principal (ID = 1)
- Les logs détaillés permettent de déboguer facilement les problèmes SMTP
- La gestion d'erreur est améliorée avec des messages en français pour l'utilisateur
- Cette approche est cohérente avec le reste de l'application (notifications push, etc.)

## Fichiers Modifiés

- `app/Http/Controllers/Api/Mobile/AuthController.php`
  - Ajout de `use App\Models\Utility;`
  - Modification de la fonction `forgotPassword()`
  - Ajout de la configuration SMTP
  - Amélioration de la gestion d'erreur et des logs
