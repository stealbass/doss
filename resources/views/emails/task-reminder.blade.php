<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel de Tâche</title>
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
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
            border: 2px solid #ff6b6b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        .countdown {
            font-size: 48px;
            font-weight: bold;
            color: #dc3545;
            margin: 10px 0;
        }
        .info-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #ff6b6b;
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
            color: #dc3545;
            display: inline-block;
            width: 180px;
        }
        .value {
            color: #333;
        }
        .priority-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .priority-high {
            background: #dc3545;
            color: white;
        }
        .priority-medium {
            background: #ffc107;
            color: #333;
        }
        .priority-low {
            background: #17a2b8;
            color: white;
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
            background: #dc3545;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
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
            color: #dc3545;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">⏰ Rappel de Tâche Urgente</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $userName }}</strong>,</p>
        
        <div class="alert-box">
            <h2 style="margin: 0; color: #dc3545;">🔔 TÂCHE PROCHAINEMENT DUE</h2>
            <div class="countdown">{{ $daysRemaining }}</div>
            <p style="margin: 0; font-size: 18px;"><strong>jour(s) restant(s)</strong></p>
        </div>
        
        <p>Nous vous rappelons que vous avez une tâche à accomplir :</p>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">📝 Description:</span>
                <span class="value"><strong>{!! $taskDescription !!}</strong></span>
            </div>
            <div class="info-row">
                <span class="label">⚡ Priorité:</span>
                <span class="value">
                    @if($taskPriority == 'high')
                        <span class="priority-badge priority-high">HAUTE</span>
                    @elseif($taskPriority == 'medium')
                        <span class="priority-badge priority-medium">MOYENNE</span>
                    @else
                        <span class="priority-badge priority-low">BASSE</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="label">📅 Date d'échéance:</span>
                <span class="value"><strong style="color: #dc3545; font-size: 18px;">{{ $dueDate }}</strong></span>
            </div>
            <div class="info-row">
                <span class="label">📊 Statut actuel:</span>
                <span class="value">{{ ucfirst($taskStatus) }}</span>
            </div>
        </div>
        
        <p style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; border-radius: 5px;">
            <strong>🚨 Action urgente:</strong> Veuillez compléter cette tâche avant la date d'échéance pour éviter tout retard.
        </p>
        
        <center>
            <a href="{{ url('/') }}" class="button">Accéder à la Tâche</a>
        </center>
    </div>
    
    <div class="footer">
        <p>Ceci est un email automatique de rappel de <strong>Dossy Pro</strong></p>
        <p>© {{ date('Y') }} Dossy Pro. Tous droits réservés.</p>
    </div>
</body>
</html>
