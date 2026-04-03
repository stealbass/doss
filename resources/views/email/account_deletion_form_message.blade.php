<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demande de suppression de compte</title>
</head>
<body>
    <h2>Nouvelle demande de suppression de compte</h2>

    <p><strong>Nom:</strong> {{ $payload['name'] ?? 'N/A' }}</p>
    <p><strong>Email:</strong> {{ $payload['email'] ?? 'N/A' }}</p>
    <p><strong>Telephone:</strong> {{ $payload['phone'] ?? 'N/A' }}</p>
    <p><strong>Sujet:</strong> {{ $payload['subject'] ?? 'N/A' }}</p>

    <p><strong>Message:</strong></p>
    <p>{!! nl2br(e($payload['message'] ?? '')) !!}</p>

    <hr>

    <p><strong>Page source:</strong> {{ $payload['source_url'] ?? 'N/A' }}</p>
    <p><strong>IP:</strong> {{ $payload['ip'] ?? 'N/A' }}</p>
    <p><strong>User-Agent:</strong> {{ $payload['user_agent'] ?? 'N/A' }}</p>
    <p><strong>Date:</strong> {{ $payload['submitted_at'] ?? 'N/A' }}</p>
</body>
</html>
