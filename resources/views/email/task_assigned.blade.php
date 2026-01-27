<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Tâche Assignée</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header avec gradient vert -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; text-transform: uppercase;">
                                {{ $assignedByName }}
                            </h1>
                            <h2 style="color: #ffffff; margin: 15px 0 0 0; font-size: 20px; font-weight: 400;">
                                ✅ Nouvelle Tâche Assignée
                            </h2>
                            <p style="color: #e8f5e9; margin: 10px 0 0 0; font-size: 14px;">
                                📅 {{ date('d/m/Y à H:i') }}
                            </p>
                        </td>
                    </tr>

                    <!-- Salutation -->
                    <tr>
                        <td style="padding: 30px 30px 20px 30px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0;">
                                Bonjour <strong>{{ $assignedToName }}</strong>,
                            </p>
                            <p style="color: #666666; font-size: 15px; line-height: 1.6; margin: 15px 0 0 0;">
                                <strong>{{ $assignedByName }}</strong> vous a assigné une nouvelle tâche.
                            </p>
                        </td>
                    </tr>

                    <!-- Détails de la tâche -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background: linear-gradient(to right, #e8f5e9, #f8fff9); border-left: 4px solid #28a745; padding: 20px; border-radius: 5px;">
                                <h3 style="color: #28a745; margin: 0 0 15px 0; font-size: 18px;">
                                    📋 {{ $task->title }}
                                </h3>
                                
                                @if($task->description)
                                <div style="color: #555555; font-size: 14px; line-height: 1.6; margin-bottom: 15px; background: #ffffff; padding: 15px; border-radius: 5px;">
                                    {{ $task->description }}
                                </div>
                                @endif

                                <table width="100%" style="margin-top: 15px;">
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 13px;">📅 Date de début:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #333333; font-size: 14px;">{{ date('d/m/Y', strtotime($task->start_date)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 13px;">📆 Date d'échéance:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            <strong style="color: #dc3545; font-size: 14px;">{{ date('d/m/Y', strtotime($task->due_date)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
                                            <span style="color: #666666; font-size: 13px;">⚡ Priorité:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right; border-bottom: 1px solid #e0e0e0;">
                                            @if($task->priority == 'high')
                                                <span style="background-color: #dc3545; color: #ffffff; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">Haute</span>
                                            @elseif($task->priority == 'medium')
                                                <span style="background-color: #ffc107; color: #ffffff; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">Moyenne</span>
                                            @else
                                                <span style="background-color: #17a2b8; color: #ffffff; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">Basse</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($caseName)
                                    <tr>
                                        <td style="padding: 8px 0;">
                                            <span style="color: #666666; font-size: 13px;">⚖️ Affaire liée:</span>
                                        </td>
                                        <td style="padding: 8px 0; text-align: right;">
                                            <strong style="color: #333333; font-size: 14px;">{{ $caseName }}</strong>
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
                            <a href="{{ $taskUrl }}" 
                               style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20923d 100%); 
                                      color: #ffffff; text-decoration: none; padding: 15px 40px; 
                                      border-radius: 25px; font-size: 16px; font-weight: 600; 
                                      box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);">
                                📋 Voir la Tâche
                            </a>
                        </td>
                    </tr>

                    <!-- Message de fermeture -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; border-top: 2px solid #e8f5e9;">
                            <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0; text-align: center;">
                                Bonne chance avec cette tâche! 💪
                            </p>
                            <p style="color: #999999; font-size: 12px; margin: 10px 0 0 0; text-align: center;">
                                📅 Notification envoyée le {{ date('d/m/Y à H:i') }}
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
