<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Tâche Créée</title>
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
            border-left: 4px solid #007bff;
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
            color: #007bff;
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
            background: #007bff;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">✅ Nouvelle Tâche Créée</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $userName }}</strong>,</p>
        
        <p>Une nouvelle tâche a été créée et vous a été assignée :</p>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">📝 Description:</span>
                <span class="value"><strong>{{ $taskDescription }}</strong></span>
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
                <span class="value"><strong style="color: #007bff; font-size: 18px;">{{ $dueDate }}</strong></span>
            </div>
            <div class="info-row">
                <span class="label">📊 Statut:</span>
                <span class="value">{{ ucfirst($taskStatus) }}</span>
            </div>
        </div>
        
        <p style="background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; border-radius: 5px;">
            <strong>💡 Conseil:</strong> N'oubliez pas de mettre à jour le statut de la tâche une fois que vous l'aurez complétée.
        </p>
        
        <center>
            <a href="{{ url('/') }}" class="button">Voir la Tâche</a>
        </center>
    </div>
    
    <div class="footer">
        <p>Ceci est un email automatique de <strong>Dossy Pro</strong></p>
        <p>© {{ date('Y') }} Dossy Pro. Tous droits réservés.</p>
    </div>
</body>
</html>
