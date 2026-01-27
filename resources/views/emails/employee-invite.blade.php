<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Employé</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .btn { display: inline-block; background: #2563eb; color: #fff; padding: 10px 16px; text-decoration: none; border-radius: 6px; }
        .muted { color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Bienvenue, {{ $name }} !</h2>
            <p>Votre compte employé a été créé sur la plateforme.</p>
            <p><strong>Identifiants de connexion :</strong></p>
            <ul>
                <li>Email : {{ $email }}</li>
                <li>Mot de passe provisoire : {{ $plainPassword }}</li>
            </ul>
            <p>Pour accéder à votre espace, cliquez sur le bouton ci-dessous :</p>
            <p>
                <a class="btn" href="{{ $loginUrl }}" target="_blank" rel="noopener">Se connecter</a>
            </p>
            <p class="muted">Par mesure de sécurité, changez votre mot de passe après la première connexion.</p>
        </div>
    </div>
</body>
</html>
