<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement sur le point d'expirer</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header avec gradient rouge/orange -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700;">
                                ⚠️ ALERTE ABONNEMENT
                            </h1>
                            <h2 style="color: #ffffff; margin: 15px 0 0 0; font-size: 20px; font-weight: 400;">
                                Votre abonnement expire bientôt
                            </h2>
                            <p style="color: #ffe0e0; margin: 10px 0 0 0; font-size: 14px;">
                                📅 {{ date('d/m/Y à H:i') }}
                            </p>
                        </td>
                    </tr>

                    <!-- Salutation -->
                    <tr>
                        <td style="padding: 30px 30px 20px 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0;">
                                Bonjour <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="color: #666666; font-size: 15px; line-height: 1.6; margin: 15px 0 0 0;">
                                Nous vous informons que votre abonnement Dossy Pro arrive bientôt à expiration.
                            </p>
                        </td>
                    </tr>

                    <!-- Compte à rebours -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background: linear-gradient(to right, #fff3cd, #fff8e1); border-left: 4px solid #ffc107; padding: 20px; border-radius: 5px; text-align: center;">
                                <p style="color: #856404; font-size: 14px; margin: 0 0 10px 0; font-weight: 600;">
                                    ⏰ Expiration dans
                                </p>
                                <h3 style="color: #dc3545; margin: 0; font-size: 36px; font-weight: 700;">
                                    {{ $daysLeft }} {{ $daysLeft > 1 ? 'jours' : 'jour' }}
                                </h3>
                                <p style="color: #856404; font-size: 14px; margin: 10px 0 0 0;">
                                    📅 Date d'expiration: <strong>{{ date('d/m/Y', strtotime($expirationDate)) }}</strong>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Détails de l'abonnement -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 5px;">
                                <h3 style="color: #28a745; margin: 0 0 15px 0; font-size: 18px;">
                                    📋 Détails de votre abonnement actuel
                                </h3>
                                
                                <table width="100%">
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">Plan:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #333333; font-size: 14px;">{{ $planName }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 14px;">Montant:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #28a745; font-size: 16px;">{{ $planPrice }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0;">
                                            <span style="color: #666666; font-size: 14px;">Durée:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right;">
                                            <strong style="color: #333333; font-size: 14px;">{{ $planDuration }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Call to Action -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <p style="color: #666666; font-size: 15px; margin: 0 0 20px 0;">
                                Pour continuer à profiter de tous les avantages de Dossy Pro, renouvelez votre abonnement dès maintenant !
                            </p>
                            <a href="{{ $renewUrl }}" 
                               style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20923d 100%); 
                                      color: #ffffff; text-decoration: none; padding: 15px 40px; 
                                      border-radius: 25px; font-size: 16px; font-weight: 600; 
                                      box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);">
                                💳 Renouveler Mon Abonnement
                            </a>
                        </td>
                    </tr>

                    <!-- Avantages du renouvellement -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background: linear-gradient(to right, #e8f5e9, #f8fff9); padding: 20px; border-radius: 5px;">
                                <h4 style="color: #28a745; margin: 0 0 15px 0; font-size: 16px; text-align: center;">
                                    ✨ En renouvelant, vous conservez :
                                </h4>
                                <ul style="color: #555555; font-size: 14px; line-height: 1.8; margin: 0; padding-left: 20px;">
                                    <li>Accès complet à toutes vos affaires et dossiers</li>
                                    <li>Gestion illimitée de vos clients</li>
                                    <li>Bibliothèque juridique complète</li>
                                    <li>Toutes les fonctionnalités premium</li>
                                    <li>Support prioritaire</li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Message de fermeture -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; border-top: 2px solid #e8f5e9;">
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0; text-align: center;">
                                Merci de votre confiance ! 🙏
                            </p>
                            <p style="color: #999999; font-size: 12px; margin: 10px 0 0 0; text-align: center;">
                                📅 Email envoyé le {{ date('d/m/Y à H:i') }}
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                © {{ date('Y') }} 
                                <a href="https://www.dossypro.com" style="color: #28a745; text-decoration: none;">
                                    Dossy Pro
                                </a> - Tous droits réservés
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
