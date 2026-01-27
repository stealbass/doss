<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Facture Créée</title>
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
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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
        .bill-info-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
            border-radius: 5px;
        }
        .bill-details-box {
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
            display: flex;
            justify-content: space-between;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #28a745;
            flex: 0 0 auto;
        }
        .value {
            color: #333;
            text-align: right;
            flex: 1;
            padding-left: 10px;
        }
        .amount-row {
            font-weight: bold;
            font-size: 16px;
            color: #28a745;
        }
        .subtotal-section {
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            margin-top: 15px;
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
        .bill-summary {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
        }
        .summary-label {
            font-weight: 500;
            color: #333;
        }
        .summary-value {
            text-align: right;
            color: #333;
            min-width: 100px;
        }
        .total-amount {
            background: #28a745;
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        .icon {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">💰 Nouvelle Facture Créée</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $adminName }}</strong>,</p>
        
        <p>Une nouvelle facture a été créée dans le système :</p>
        
        <!-- Facture Info Box -->
        <div class="bill-info-box">
            <div class="info-row">
                <span class="label"><span class="icon">📄</span>Numéro de Facture:</span>
                <span class="value"><strong style="color: #28a745; font-size: 18px;">{{ $billNumber }}</strong></span>
            </div>
            <div class="info-row">
                <span class="label"><span class="icon">📋</span>Titre:</span>
                <span class="value"><strong>{{ $billTitle }}</strong></span>
            </div>
            <div class="info-row">
                <span class="label"><span class="icon">👥</span>De:</span>
                <span class="value">{{ $billFromName }}</span>
            </div>
            <div class="info-row">
                <span class="label"><span class="icon">👤</span>À:</span>
                <span class="value">{{ $billToName }}</span>
            </div>
            <div class="info-row">
                <span class="label"><span class="icon">📅</span>Date de Création:</span>
                <span class="value">{{ $receiptDate }}</span>
            </div>
            <div class="info-row">
                <span class="label"><span class="icon">⏰</span>Date d'Échéance:</span>
                <span class="value"><strong style="color: #dc3545;">{{ $dueDate }}</strong></span>
            </div>
        </div>

        <!-- Détails Financiers -->
        <div class="bill-details-box">
            <h3 style="margin-top: 0; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">💵 Détails Financiers</h3>
            
            <div class="bill-summary">
                <div class="summary-row">
                    <span class="summary-label">Sous-total :</span>
                    <span class="summary-value">{{ $subtotal }} {{ $currency }}</span>
                </div>
                
                @if($totalDiscount > 0)
                <div class="summary-row">
                    <span class="summary-label">Remise :</span>
                    <span class="summary-value" style="color: #28a745;">-{{ $totalDiscount }} {{ $currency }}</span>
                </div>
                @endif
                
                @if($totalTax > 0)
                <div class="summary-row">
                    <span class="summary-label">Taxe/TVA :</span>
                    <span class="summary-value">{{ $totalTax }} {{ $currency }}</span>
                </div>
                @endif
                
                <div class="total-amount">
                    Montant Total: {{ $totalAmount }} {{ $currency }}
                </div>
            </div>

            @if(!empty($billDescription))
            <div class="info-row" style="border-bottom: none; margin-top: 15px;">
                <span class="label"><span class="icon">📝</span>Remarques:</span>
            </div>
            <p style="background: #f0f8ff; padding: 10px; border-left: 4px solid #007bff; border-radius: 4px; margin: 10px 0;">
                {{ $billDescription }}
            </p>
            @endif
        </div>
        
        <p style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>⚠️ Action Requise:</strong> Veuillez vérifier les détails de cette facture. Une notification sera envoyée au destinataire selon votre configuration.
        </p>
        
        <center>
            <a href="{{ url('/bills') }}" class="button">📊 Voir toutes les Factures</a>
        </center>
    </div>
    
    <div class="footer">
        <p>Ceci est un email automatique de <strong>Dossy Pro</strong></p>
        <p>© {{ date('Y') }} Dossy Pro. Tous droits réservés.</p>
        <p style="margin-top: 20px; color: #999; font-size: 12px;">
            Cet email contient des informations confidentielles destinées uniquement à l'administrateur du système.
        </p>
    </div>
</body>
</html>
