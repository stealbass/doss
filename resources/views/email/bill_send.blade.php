<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Facture') }} - {{ $bill->bill_number }}</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;">
    <div style="max-width: 700px; margin: 20px auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <!-- En-tête avec nom de l'émetteur -->
        <div style="background: linear-gradient(135deg, #28a745 0%, #218838 100%); padding: 40px 30px; text-align: center;">
            <div style="background-color: rgba(255,255,255,0.95); display: inline-block; padding: 15px 30px; border-radius: 8px; margin-bottom: 20px;">
                <h1 style="color: #28a745; margin: 0; font-size: 32px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;">
                    @if($billFrom == 'company')
                        {{ $companyName }}
                    @else
                        {{ $advocateName }}
                    @endif
                </h1>
            </div>
            <h2 style="color: #ffffff; margin: 15px 0 5px 0; font-size: 24px; font-weight: 300; text-transform: uppercase; letter-spacing: 2px;">Facture</h2>
            <p style="color: rgba(255,255,255,0.95); margin: 0; font-size: 18px; font-weight: bold;">{{ $bill->bill_number }}</p>
        </div>
        
        <!-- Contenu principal -->
        <div style="padding: 30px;"
            
            <!-- Message personnalisé -->
            <div style="margin: 0 0 25px 0; padding: 20px; background: linear-gradient(to right, #f8fff9, #ffffff); border-left: 5px solid #28a745; border-radius: 8px; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);">
                <p style="margin: 0; font-size: 15px; color: #495057; line-height: 1.8;">
                    {{ $messageContent ?? __('Veuillez trouver ci-dessous le détail de votre facture.') }}
                </p>
            </div>
            
            <!-- Informations générales -->
            <div style="margin: 25px 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 48%; padding: 0 1% 0 0; vertical-align: top;">
                            <div style="background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%); padding: 20px; border-radius: 8px; border: 2px solid #e8f5e9; height: 100%;">
                                <h3 style="margin: 0 0 15px 0; color: #28a745; font-size: 16px; font-weight: bold; border-bottom: 3px solid #28a745; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Facturé par') }}</h3>
                                <p style="margin: 0; font-size: 14px; line-height: 1.8; color: #495057;">
                                    @if($billFrom == 'company')
                                        <strong style="color: #28a745; font-size: 16px;">{{ $companyName }}</strong><br>
                                        <span style="color: #6c757d;">{{ $companyAddress }}</span>
                                    @else
                                        <strong style="color: #28a745; font-size: 16px;">{{ $advocateName }}</strong><br>
                                        <span style="color: #6c757d;">{{ $advocateAddress }}</span>
                                    @endif
                                </p>
                            </div>
                        </td>
                        <td style="width: 4%;"></td>
                        <td style="width: 48%; padding: 0 0 0 1%; vertical-align: top;">
                            <div style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); padding: 20px; border-radius: 8px; border: 2px solid #dee2e6; height: 100%;">
                                <h3 style="margin: 0 0 15px 0; color: #495057; font-size: 16px; font-weight: bold; border-bottom: 3px solid #6c757d; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Facturé à') }}</h3>
                                <p style="margin: 0; font-size: 14px; line-height: 1.8; color: #495057;">
                                    <strong style="color: #212529; font-size: 16px;">{{ $clientName }}</strong><br>
                                    @if($clientEmail)
                                        <span style="color: #6c757d;">📧 {{ $clientEmail }}</span><br>
                                    @endif
                                    <span style="color: #6c757d;">{{ $clientAddress }}</span>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Informations de la facture -->
            <div style="margin: 25px 0; padding: 20px; background: linear-gradient(to right, #e8f5e9, #f1f8f4); border-radius: 8px; border-left: 5px solid #28a745;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; color: #28a745; font-size: 14px;">📅 {{ __('Date d\'échéance') }}:</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 14px; font-weight: bold; color: #212529;">{{ date('d/m/Y', strtotime($bill->due_date)) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; font-weight: bold; color: #28a745; font-size: 14px;">📊 {{ __('Statut') }}:</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 14px;">
                            @if($bill->status == 'PENDING')
                                <span style="background-color: #dc3545; color: white; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $bill->status }}</span>
                            @elseif($bill->status == 'Partialy Paid')
                                <span style="background-color: #ffc107; color: #000; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $bill->status }}</span>
                            @else
                                <span style="background-color: #28a745; color: white; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;">{{ $bill->status }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Détails des articles -->
            <div style="margin: 30px 0;">
                <h3 style="margin: 0 0 20px 0; color: #28a745; font-size: 20px; font-weight: bold; border-bottom: 3px solid #28a745; padding-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">📋 {{ __('Détails des articles') }}</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #28a745 0%, #20923d 100%); color: white;">
                            <th style="padding: 15px 10px; text-align: left; font-size: 13px; font-weight: bold; border-right: 1px solid rgba(255,255,255,0.2);">#</th>
                            <th style="padding: 15px 10px; text-align: left; font-size: 13px; font-weight: bold; border-right: 1px solid rgba(255,255,255,0.2);">{{ __('Description') }}</th>
                            <th style="padding: 15px 10px; text-align: center; font-size: 13px; font-weight: bold; border-right: 1px solid rgba(255,255,255,0.2);">{{ __('Qté') }}</th>
                            <th style="padding: 15px 10px; text-align: right; font-size: 13px; font-weight: bold; border-right: 1px solid rgba(255,255,255,0.2);">{{ __('Prix Unit.') }}</th>
                            <th style="padding: 15px 10px; text-align: right; font-size: 13px; font-weight: bold; border-right: 1px solid rgba(255,255,255,0.2);">{{ __('Remise') }}</th>
                            <th style="padding: 15px 10px; text-align: center; font-size: 13px; font-weight: bold; border-right: 1px solid rgba(255,255,255,0.2);">{{ __('Taxe') }}</th>
                            <th style="padding: 15px 10px; text-align: right; font-size: 13px; font-weight: bold;">{{ __('Montant') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $key => $item)
                        <tr style="background-color: {{ $key % 2 == 0 ? '#f8fff9' : '#ffffff' }}; transition: background-color 0.2s;">
                            <td style="padding: 14px 10px; border-bottom: 1px solid #e8f5e9; font-size: 13px; font-weight: bold; color: #28a745;">{{ $key + 1 }}</td>
                            <td style="padding: 14px 10px; border-bottom: 1px solid #e8f5e9; font-size: 13px; color: #495057;">{{ $item['particulars'] }}</td>
                            <td style="padding: 14px 10px; text-align: center; border-bottom: 1px solid #e8f5e9; font-size: 13px; font-weight: bold; color: #28a745;">{{ $item['numbers'] }}</td>
                            <td style="padding: 14px 10px; text-align: right; border-bottom: 1px solid #e8f5e9; font-size: 13px; color: #495057;">{{ number_format($item['cost'], 0, ',', ' ') }} FCFA</td>
                            <td style="padding: 14px 10px; text-align: right; border-bottom: 1px solid #e8f5e9; font-size: 13px; color: #dc3545;">{{ number_format($item['discount'] ?? 0, 0, ',', ' ') }} FCFA</td>
                            <td style="padding: 14px 10px; text-align: center; border-bottom: 1px solid #e8f5e9; font-size: 12px; color: #6c757d;">
                                <span style="background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                    {{ $taxes[$item['tax']]['name'] ?? '' }} ({{ $taxes[$item['tax']]['rate'] ?? 0 }}%)
                                </span>
                            </td>
                            <td style="padding: 14px 10px; text-align: right; border-bottom: 1px solid #e8f5e9; font-size: 14px; font-weight: bold; color: #28a745;">
                                @php
                                    $subtotal = $item['numbers'] * $item['cost'];
                                    $discount = $item['discount'] ?? 0;
                                    $taxRate = $taxes[$item['tax']]['rate'] ?? 0;
                                    $taxAmount = ($subtotal - $discount) * $taxRate / 100;
                                    $total = $subtotal - $discount + $taxAmount;
                                @endphp
                                {{ number_format($total, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Totaux -->
            <div style="margin: 30px 0;">
                <table style="width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="padding: 12px 20px; text-align: right; font-size: 15px; color: #6c757d; background-color: #f8fff9;" colspan="6">{{ __('Sous-total') }}:</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 15px; font-weight: bold; background-color: #f8fff9; color: #495057;">{{ number_format($bill->subtotal, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 20px; text-align: right; font-size: 15px; color: #6c757d; background-color: #ffffff;" colspan="6">{{ __('Total Taxe') }}:</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 15px; font-weight: bold; background-color: #ffffff; color: #495057;">{{ number_format($bill->total_tax, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 20px; text-align: right; font-size: 15px; color: #6c757d; background-color: #f8fff9;" colspan="6">{{ __('Total Remise') }}:</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 15px; font-weight: bold; background-color: #f8fff9; color: #dc3545;">-{{ number_format($bill->total_disc, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr style="background: linear-gradient(135deg, #28a745 0%, #20923d 100%);">
                        <td style="padding: 20px 20px; text-align: right; font-size: 18px; font-weight: bold; color: white; text-transform: uppercase; letter-spacing: 1px;" colspan="6">💰 {{ __('MONTANT TOTAL') }}:</td>
                        <td style="padding: 20px 20px; text-align: right; font-size: 22px; font-weight: bold; color: white;">{{ number_format($bill->total_amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @if($bill->due_amount > 0)
                    <tr style="background-color: #fff3cd; border-top: 3px solid #ffc107;">
                        <td style="padding: 15px 20px; text-align: right; font-size: 16px; font-weight: bold; color: #856404;" colspan="6">⚠️ {{ __('Montant Dû') }}:</td>
                        <td style="padding: 15px 20px; text-align: right; font-size: 18px; font-weight: bold; color: #dc3545;">{{ number_format($bill->due_amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endif
                </table>
            </div>
            
            <!-- Pied de page -->
            <div style="margin-top: 40px; padding: 25px 20px; background: linear-gradient(to right, #f8fff9, #e8f5e9); border-radius: 8px; text-align: center; border-top: 3px solid #28a745;">
                <p style="margin: 0 0 15px 0; font-size: 18px; color: #28a745; font-weight: bold;">{{ __('Merci de votre confiance') }} 🙏</p>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #6c757d;">📅 {{ __('Email envoyé le') }} {{ date('d/m/Y à H:i') }}</p>
            </div>
        </div>
        
        <!-- Footer externe avec copyright Dossy Pro -->
        <div style="padding: 20px; text-align: center; background-color: #f4f4f4; border-top: 1px solid #dee2e6;">
            <p style="margin: 5px 0; color: #6c757d; font-size: 12px;">
                💡 {{ __('Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.') }}
            </p>
            <p style="margin: 10px 0 5px 0; color: #999; font-size: 11px;">
                © {{ date('Y') }} <a href="https://www.dossypro.com" style="color: #28a745; text-decoration: none; font-weight: bold;">Dossy Pro</a> - {{ __('Tous droits réservés') }}
            </p>
        </div>
    </div>
</body>
</html>
