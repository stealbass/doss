<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Audience Créée</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20923d 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
            border-radius: 5px;
        }
        .bcc-recipients-box {
            background: #e8f5e9;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #4caf50;
            border-radius: 5px;
        }
        .bcc-recipients-box h3 {
            margin-top: 0;
            color: #2e7d32;
            font-size: 14px;
        }
        .recipient-list {
            font-size: 13px;
            color: #333;
        }
        .recipient-item {
            padding: 5px 0;
            display: flex;
            align-items: center;
        }
        .recipient-item:before {
            content: "✓";
            color: #4caf50;
            margin-right: 8px;
            font-weight: bold;
        }
        .info-row {
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #28a745;
            display: inline-block;
            width: 180px;
        }
        .value {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #28a745;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        /* WYSIWYG Formatting Styles */
        .value b, .value strong {
            font-weight: bold;
        }
        .value i, .value em {
            font-style: italic;
        }
        .value u {
            text-decoration: underline;
        }
        .value ul, .value ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .value li {
            margin: 5px 0;
        }
        .value p {
            margin: 5px 0;
        }
        .value a {
            color: #28a745;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">📅 Nouvelle Audience Créée</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $userName }}</strong>,</p>
        
        <p>Une nouvelle audience a été créée pour l'affaire suivante :</p>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">📋 Titre de l'affaire:</span>
                <span class="value">{{ $caseTitle }}</span>
            </div>
            <div class="info-row">
                <span class="label">🔢 Numéro d'affaire:</span>
                <span class="value">{{ $caseNumber }}</span>
            </div>
            <div class="info-row">
                <span class="label">📆 Date de l'audience:</span>
                <span class="value"><strong style="color: #28a745; font-size: 18px;">{{ $hearingDate }}</strong></span>
            </div>
            <div class="info-row">
                <span class="label">📍 Lieu:</span>
                <span class="value">{{ $hearingLocation }}</span>
            </div>
            <div class="info-row">
                <span class="label">💬 Remarques:</span>
                <span class="value">{!! $remarks !!}</span>
            </div>
        </div>

        @if($bccRecipients && $bccRecipients->count() > 0)
        <div class="bcc-recipients-box">
            <h3>📧 Destinataires en copie confidentielle (BCC):</h3>
            <div class="recipient-list">
                @foreach($bccRecipients as $recipient)
                <div class="recipient-item">
                    <strong>{{ $recipient->name }}</strong> ({{ $recipient->email }})
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <p style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>⚠️ Important:</strong> Veuillez noter cette date dans votre agenda et vous assurer de votre disponibilité.
        </p>
        
        <center>
            <a href="{{ url('/') }}" class="button">Accéder à Dossy Pro</a>
        </center>
    </div>
    
    <div class="footer">
        <p>Ceci est un email automatique de <strong>Dossy Pro</strong></p>
        <p>© {{ date('Y') }} Dossy Pro. Tous droits réservés.</p>
    </div>
</body>
</html>
