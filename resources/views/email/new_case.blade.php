<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Affaire - {{ $case->title }}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
    <div style="max-width: 700px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <!-- En-tête avec nom de l'émetteur -->
        <div style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); padding: 40px 30px; text-align: center;">
            <div style="background-color: rgba(255,255,255,0.95); display: inline-block; padding: 15px 30px; border-radius: 8px; margin-bottom: 20px;">
                <h1 style="color: #28a745; margin: 0; font-size: 28px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">
                    {{ $recipientName }}
                </h1>
            </div>
            <h2 style="color: #ffffff; margin: 15px 0 5px 0; font-size: 24px; font-weight: 300; text-transform: uppercase; letter-spacing: 2px;">
                📂 Nouvelle Affaire Créée
            </h2>
            <p style="color: rgba(255,255,255,0.95); margin: 10px 0 0 0; font-size: 14px;">
                📅 {{ date('d/m/Y à H:i') }}
            </p>
        </div>
        
        <!-- Contenu principal -->
        <div style="padding: 30px;">
            
            <!-- Message d'introduction -->
            <div style="margin: 0 0 25px 0; padding: 20px; background: linear-gradient(to right, #f8fff9, #ffffff); border-left: 5px solid #28a745; border-radius: 8px; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);">
                <p style="margin: 0; font-size: 15px; color: #495057; line-height: 1.8;">
                    ✅ Une nouvelle affaire a été créée avec succès dans votre système.
                </p>
            </div>
            
            <!-- Titre de l'affaire -->
            <div style="margin: 25px 0; padding: 20px; background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%); border-radius: 8px; border: 2px solid #e8f5e9;">
                <h3 style="margin: 0 0 15px 0; color: #28a745; font-size: 18px; font-weight: bold; border-bottom: 3px solid #28a745; padding-bottom: 10px;">
                    📋 Titre de l'Affaire
                </h3>
                <p style="margin: 0; font-size: 16px; color: #212529; font-weight: bold; line-height: 1.6;">
                    {{ $case->title }}
                </p>
                @if($case->description)
                    <p style="margin: 15px 0 0 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
                        {{ $case->description }}
                    </p>
                @endif
            </div>
            
            <!-- Informations du client (plaignant) -->
            <div style="margin: 25px 0;">
                <h3 style="margin: 0 0 20px 0; color: #28a745; font-size: 18px; font-weight: bold; border-bottom: 3px solid #28a745; padding-bottom: 12px;">
                    👤 Informations du Client
                </h3>
                <table style="width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #28a745 0%, #20923d 100%); color: white;">
                            <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: bold; width: 40%;">Nom du Client</th>
                            <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: bold; width: 30%;">Type de Partie</th>
                            <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: bold; width: 30%;">Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $index => $client)
                        <tr style="background-color: {{ $index % 2 == 0 ? '#f8fff9' : '#ffffff' }}; border-bottom: 1px solid #e8f5e9;">
                            <td style="padding: 14px 15px; font-size: 14px; color: #212529; font-weight: bold;">
                                {{ $client['name'] }}
                            </td>
                            <td style="padding: 14px 15px; font-size: 13px; color: #495057;">
                                <span style="background-color: #e8f5e9; padding: 4px 12px; border-radius: 15px; display: inline-block; font-weight: 600;">
                                    Plaignant
                                </span>
                            </td>
                            <td style="padding: 14px 15px; font-size: 13px; color: #6c757d;">
                                Partie principale
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($clients) == 0)
                        <tr>
                            <td colspan="3" style="padding: 20px; text-align: center; color: #6c757d; font-style: italic;">
                                Aucun client associé
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            <!-- Détails supplémentaires -->
            <div style="margin: 25px 0; padding: 20px; background: linear-gradient(to right, #e8f5e9, #f1f8f4); border-radius: 8px; border-left: 5px solid #28a745;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; color: #28a745; font-size: 14px; width: 40%;">
                            📅 Date de dépôt:
                        </td>
                        <td style="padding: 10px 0; font-size: 14px; color: #212529;">
                            {{ date('d/m/Y', strtotime($case->filing_date)) }}
                        </td>
                    </tr>
                    @if($case->year)
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; color: #28a745; font-size: 14px;">
                            📆 Année:
                        </td>
                        <td style="padding: 10px 0; font-size: 14px; color: #212529;">
                            {{ $case->year }}
                        </td>
                    </tr>
                    @endif
                    @if($case->casenumber)
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; color: #28a745; font-size: 14px;">
                            🔢 Numéro d'affaire:
                        </td>
                        <td style="padding: 10px 0; font-size: 14px; color: #212529;">
                            {{ $case->casenumber }}
                        </td>
                    </tr>
                    @endif
                    @if($courtName)
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; color: #28a745; font-size: 14px;">
                            ⚖️ Tribunal:
                        </td>
                        <td style="padding: 10px 0; font-size: 14px; color: #212529;">
                            {{ $courtName }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
            
            <!-- Bouton d'action -->
            <div style="margin: 30px 0; text-align: center;">
                <a href="{{ $caseUrl }}" 
                   style="display: inline-block; 
                          padding: 15px 40px; 
                          background: linear-gradient(135deg, #28a745 0%, #20923d 100%); 
                          color: #ffffff; 
                          text-decoration: none; 
                          border-radius: 25px; 
                          font-size: 16px; 
                          font-weight: bold; 
                          box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
                          transition: all 0.3s ease;">
                    📂 Voir l'Affaire Complète
                </a>
            </div>
            
            <!-- Information supplémentaire -->
            <div style="margin: 25px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                <p style="margin: 0; font-size: 13px; color: #856404; line-height: 1.6;">
                    💡 <strong>Astuce:</strong> Cliquez sur le bouton ci-dessus pour accéder aux détails complets de l'affaire, 
                    ajouter des documents, planifier des audiences et suivre la progression.
                </p>
            </div>
            
            <!-- Pied de page -->
            <div style="margin-top: 40px; padding: 25px 20px; background: linear-gradient(to right, #f8fff9, #e8f5e9); border-radius: 8px; text-align: center; border-top: 3px solid #28a745;">
                <p style="margin: 0 0 15px 0; font-size: 16px; color: #28a745; font-weight: bold;">
                    Bonne gestion de votre affaire! ⚖️
                </p>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #6c757d;">
                    📅 Notification envoyée le {{ date('d/m/Y à H:i') }}
                </p>
            </div>
        </div>
        
        <!-- Footer externe avec copyright Dossy Pro -->
        <div style="padding: 20px; text-align: center; background-color: #f4f4f4; border-top: 1px solid #dee2e6;">
            <p style="margin: 5px 0; color: #6c757d; font-size: 12px;">
                💡 Cet email a été envoyé automatiquement depuis votre système de gestion.
            </p>
            <p style="margin: 10px 0 5px 0; color: #999; font-size: 11px;">
                © {{ date('Y') }} <a href="https://www.dossypro.com" style="color: #28a745; text-decoration: none; font-weight: bold;">Dossy Pro</a> - Tous droits réservés
            </p>
        </div>
    </div>
</body>
</html>
