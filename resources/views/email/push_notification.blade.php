<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
    <div style="max-width: 700px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        
        <!-- En-tête -->
        <div style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); padding: 40px 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: bold; letter-spacing: 1px;">
                {{ $notification->title }}
            </h1>
            <p style="color: rgba(255,255,255,0.9); margin: 15px 0 0 0; font-size: 14px;">
                {{ now()->format('j F Y à H:i') }}
            </p>
        </div>
        
        <!-- Contenu principal -->
        <div style="padding: 40px 30px;">
            
            <!-- Message principal -->
            <div style="margin-bottom: 30px; padding: 25px; background: linear-gradient(to right, #f5fff8, #ffffff); border-radius: 8px;">
                <div style="margin: 0; font-size: 16px; color: #333; line-height: 1.8;">
                    {!! $notification->body !!}
                </div>
            </div>
            
            <!-- Image si présente -->
            @if($notification->image_url)
                <div style="margin: 30px 0; text-align: center;">
                    <img src="{{ $notification->image_url }}" alt="Notification Image" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                </div>
            @endif
            
            <!-- Bouton d'action si présent -->
            @if($notification->action_url)
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $notification->action_url }}" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: #ffffff; padding: 12px 40px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; transition: transform 0.2s;">
                        {{ __('Consulter') }}
                    </a>
                </div>
            @endif
            
            <!-- Informations supplémentaires -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <table style="width: 100%; font-size: 14px; color: #6c757d;">
                    <tr>
                        <td style="padding: 8px 0;"><strong>{{ __('Type') }}:</strong></td>
                        <td style="padding: 8px 0; text-align: right;">
                            @if($notification->type === 'promotion')
                                🎉 {{ __('Promotion') }}
                            @elseif($notification->type === 'alert')
                                ⚠️ {{ __('Alerte') }}
                            @elseif($notification->type === 'update')
                                📢 {{ __('Mise à jour') }}
                            @else
                                📬 {{ __('Général') }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;"><strong>{{ __('Audience') }}:</strong></td>
                        <td style="padding: 8px 0; text-align: right;">
                            @switch($notification->target_audience)
                                @case('all')
                                    👥 {{ __('Tous les utilisateurs') }}
                                    @break
                                @case('students')
                                    🎓 {{ __('Étudiants') }}
                                    @break
                                @case('lawyers')
                                    👨‍⚖️ {{ __('Avocats') }}
                                    @break
                                @case('enterprises')
                                    🏢 {{ __('Entreprises') }}
                                    @break
                                @case('plan_specific')
                                    📋 {{ __('Plan spécifique') }}
                                    @break
                                @case('specific_users')
                                    👤 {{ __('Utilisateurs spécifiques') }}
                                    @break
                                @default
                                    {{ $notification->target_audience }}
                            @endswitch
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Footer avec note -->
            <div style="margin-top: 30px; padding-top: 20px; text-align: center; border-top: 1px solid #e9ecef;">
                <p style="margin: 0; font-size: 12px; color: #999;">
                    {{ __('Cet email a été généré automatiquement. Veuillez ne pas répondre à cet email.') }}
                </p>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                    © {{ date('Y') }} {{ env('APP_NAME', 'Dossy Pro') }}. {{ __('Tous droits réservés.') }}
                </p>
            </div>
            
        </div>
        
    </div>
</body>
</html>
