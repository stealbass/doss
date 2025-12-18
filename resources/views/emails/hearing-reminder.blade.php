<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel d'Audience</title>
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
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
        .alert-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        .countdown {
            font-size: 48px;
            font-weight: bold;
            color: #ff9800;
            margin: 10px 0;
        }
        .info-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
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
            color: #ff9800;
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
            background: #ffc107;
            color: #333 !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">⏰ Rappel d'Audience</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $userName }}</strong>,</p>
        
        <div class="alert-box">
            <h2 style="margin: 0; color: #ff9800;">📢 AUDIENCE PROCHAINE</h2>
            <div class="countdown">{{ $daysRemaining }}</div>
            <p style="margin: 0; font-size: 18px;"><strong>jour(s) restant(s)</strong></p>
        </div>
        
        <p>Nous vous rappelons que vous avez une audience prévue pour l'affaire suivante :</p>
        
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
                <span class="value"><strong style="color: #ff9800; font-size: 18px;">{{ $hearingDate }}</strong></span>
            </div>
            <div class="info-row">
                <span class="label">💬 Remarques:</span>
                <span class="value">{{ $remarks }}</span>
            </div>
        </div>
        
        <p style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; border-radius: 5px;">
            <strong>🚨 Action requise:</strong> Veuillez vous préparer pour cette audience et vous assurer d'avoir tous les documents nécessaires.
        </p>
        
        <center>
            <a href="{{ url('/') }}" class="button">Accéder à Dossy Pro</a>
        </center>
    </div>
    
    <div class="footer">
        <p>Ceci est un email automatique de rappel de <strong>Dossy Pro</strong></p>
        <p>© {{ date('Y') }} Dossy Pro. Tous droits réservés.</p>
    </div>
</body>
</html>
