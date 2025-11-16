@component('email.common')
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Facture') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
        <div style="background-color: #ffffff; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2 style="color: #2c3e50; margin-top: 0;">{{ $subject ?? __('Facture') }}</h2>
            
            <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
                <p style="margin: 0; font-size: 14px; color: #555;">
                    {{ $messageContent ?? __('Veuillez trouver ci-joint votre facture.') }}
                </p>
            </div>
            
            <div style="margin: 25px 0; padding: 20px; background-color: #e9ecef; border-radius: 5px;">
                <h3 style="margin-top: 0; color: #495057; font-size: 16px;">{{ __('Détails de la facture') }}</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold; color: #6c757d;">{{ __('Numéro de facture') }}:</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $bill->bill_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: bold; color: #6c757d;">{{ __('Date d\'échéance') }}:</td>
                        <td style="padding: 8px 0; text-align: right;">{{ date('d/m/Y', strtotime($bill->due_date)) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #dee2e6;">
                        <td style="padding: 12px 0; font-weight: bold; color: #495057; font-size: 16px;">{{ __('Montant Total') }}:</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: bold; color: #28a745; font-size: 18px;">
                            {{ number_format($bill->total_amount, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style="margin: 25px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                <p style="margin: 0; font-size: 13px; color: #856404;">
                    <strong>{{ __('Note') }}:</strong> {{ __('La facture complète est jointe à cet email au format PDF.') }}
                </p>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; text-align: center; color: #6c757d; font-size: 12px;">
                <p style="margin: 5px 0;">{{ __('Merci de votre confiance') }}</p>
                <p style="margin: 5px 0;">{{ config('app.name', 'Dossy Pro') }}</p>
            </div>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: #999; font-size: 11px;">
            <p style="margin: 5px 0;">{{ __('Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.') }}</p>
        </div>
    </div>
</body>
</html>
@endcomponent
