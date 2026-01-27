<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type === 'new' ? 'Nouvel Abonnement' : 'Abonnement Expirant' }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, {{ $type === 'new' ? '#28a745 0%, #218838 100%' : '#ff6b6b 0%, #ee5a6f 100%' }}); padding: 40px 30px; text-align: center;">
                            <div style="font-size: 50px; margin-bottom: 10px;">{{ $type === 'new' ? '🎉' : '⚠️' }}</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700;">
                                {{ $type === 'new' ? 'NOUVEL ABONNEMENT' : 'ABONNEMENT EXPIRANT' }}
                            </h1>
                            <h2 style="color: #ffffff; margin: 15px 0 0 0; font-size: 20px; font-weight: 400;">
                                Notification Administrateur
                            </h2>
                            <p style="color: {{ $type === 'new' ? '#e8f5e9' : '#ffe0e0' }}; margin: 10px 0 0 0; font-size: 14px;">
                                📅 {{ date('d/m/Y à H:i') }}
                            </p>
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0;">
                                Bonjour Administrateur,
                            </p>
                            <p style="color: #666666; font-size: 15px; line-height: 1.6; margin: 15px 0 0 0;">
                                @if($type === 'new')
                                    Un nouvel abonnement vient d'être souscrit sur la plateforme Dossy Pro.
                                @else
                                    Un abonnement arrive bientôt à expiration sur la plateforme Dossy Pro.
                                @endif
                            </p>
                        </td>
                    </tr>

                    <!-- Détails -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background: linear-gradient(to right, #e8f5e9, #f8fff9); border-left: 4px solid #28a745; padding: 20px; border-radius: 5px;">
                                <h3 style="color: #28a745; margin: 0 0 15px 0; font-size: 18px;">
                                    📋 Informations
                                </h3>
                                
                                <table width="100%">
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">👤 Abonné:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #333333; font-size: 14px;">{{ $userName }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">📧 Email:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <a href="mailto:{{ $userEmail }}" style="color: #28a745; text-decoration: none;">{{ $userEmail }}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">📦 Plan:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #333333; font-size: 14px;">{{ $planName }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">💰 Montant:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #28a745; font-size: 16px;">{{ $planPrice }}</strong>
                                        </td>
                                    </tr>
                                    @if($type === 'new')
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">📅 Date souscription:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #333333; font-size: 14px;">{{ date('d/m/Y') }}</strong>
                                        </td>
                                    </tr>
                                    @if($paymentMethod)
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">💳 Paiement:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #333333; font-size: 14px;">{{ $paymentMethod }}</strong>
                                        </td>
                                    </tr>
                                    @endif
                                    @endif
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #666666; font-size: 14px;">📆 Expiration:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right;">
                                            <strong style="color: {{ $type === 'new' ? '#333333' : '#dc3545' }}; font-size: 14px;">
                                                {{ date('d/m/Y', strtotime($expirationDate)) }}
                                            </strong>
                                        </td>
                                    </tr>
                                    @if($type !== 'new' && isset($daysLeft))
                                    <tr>
                                        <td colspan="2" style="padding: 15px 0;">
                                            <div style="background: #fff3cd; padding: 10px; border-radius: 5px; text-align: center;">
                                                <strong style="color: #dc3545; font-size: 16px;">
                                                    ⏰ Expire dans {{ $daysLeft }} {{ $daysLeft > 1 ? 'jours' : 'jour' }}
                                                </strong>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Call to Action -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <a href="{{ $adminUrl }}" 
                               style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20923d 100%); 
                                      color: #ffffff; text-decoration: none; padding: 15px 40px; 
                                      border-radius: 25px; font-size: 16px; font-weight: 600; 
                                      box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);">
                                👥 Voir dans le Dashboard
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                © {{ date('Y') }} 
                                <a href="https://www.dossypro.com" style="color: #28a745; text-decoration: none;">
                                    Dossy Pro
                                </a> - Admin Dashboard
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
