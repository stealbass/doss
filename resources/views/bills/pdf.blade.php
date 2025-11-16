<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $bill->bill_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 5px;
        }
        .header .bill-number {
            color: #666;
            font-size: 14px;
        }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .col-6 {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .info-box h3 {
            color: #495057;
            font-size: 14px;
            margin-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 3px 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table thead {
            background-color: #007bff;
            color: white;
        }
        table th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
            font-size: 11px;
        }
        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            margin-top: 20px;
            float: right;
            width: 50%;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .total-row.final {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            font-size: 14px;
            border: none;
            margin-top: 5px;
        }
        .badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 3px;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-warning {
            background-color: #ffc107;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <h1>FACTURE</h1>
            <div class="bill-number">Numéro: {{ $bill->bill_number }}</div>
        </div>

        <!-- Informations Émetteur et Destinataire -->
        <div class="row">
            <div class="col-6">
                <div class="info-box">
                    <h3>Facturé par :</h3>
                    @if ($bill->bill_from == 'advocate' && $advocate)
                        <p><strong>{{ App\Models\Advocate::getAdvocates($bill->advocate) }}</strong></p>
                        @if (!empty($advocate->ofc_address_line_1))
                            <p>{{ $advocate->ofc_address_line_1 }}</p>
                        @endif
                        @if (!empty($advocate->ofc_city))
                            <p>{{ $advocate->ofc_city }}</p>
                        @endif
                        @if (!empty($advocate->ofc_state))
                            <p>{{ App\Models\State::StatebyId($advocate->ofc_state) }}</p>
                        @endif
                    @elseif ($bill->bill_from == 'company')
                        <p><strong>{{ App\Models\Utility::getcompanyValByName('name') }}</strong></p>
                        @if (!empty(App\Models\Utility::getcompanydetailValByName('address')))
                            <p>{{ App\Models\Utility::getcompanydetailValByName('address') }}</p>
                        @endif
                        @if (!empty(App\Models\Utility::getcompanydetailValByName('city')))
                            <p>{{ App\Models\Utility::getcompanydetailValByName('city') }}</p>
                        @endif
                        @if (!empty(App\Models\Utility::getcompanydetailValByName('state')))
                            <p>{{ App\Models\Utility::getcompanydetailValByName('state') }}</p>
                        @endif
                    @else
                        <p><strong>{{ $bill->custom_advocate }}</strong></p>
                        <p>{{ $bill->custom_address }}</p>
                    @endif
                </div>
            </div>

            <div class="col-6">
                <div class="info-box">
                    <h3>Facturé à :</h3>
                    <p><strong>{{ $user->name }}</strong></p>
                    @if ($user->email)
                        <p>Email: {{ $user->email }}</p>
                    @endif
                    @if (!empty($userDetail->address))
                        <p>{{ $userDetail->address }}</p>
                    @endif
                    @if (!empty($userDetail->city))
                        <p>{{ $userDetail->city }}</p>
                    @endif
                    @if (!empty($userDetail->state))
                        <p>{{ $userDetail->state }}</p>
                    @endif
                </div>

                <div class="info-box">
                    <p><strong>Date d'échéance :</strong> {{ date('d/m/Y', strtotime($bill->due_date)) }}</p>
                    <p><strong>Statut :</strong> 
                        @if ($bill->status == 'PENDING')
                            <span class="badge badge-danger">{{ $bill->status }}</span>
                        @elseif ($bill->status == 'Partialy Paid')
                            <span class="badge badge-warning">{{ $bill->status }}</span>
                        @else
                            <span class="badge badge-success">{{ $bill->status }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Détails des articles -->
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%">DESCRIPTION</th>
                    <th width="15%" class="text-center">QTÉ</th>
                    <th width="15%" class="text-right">PRIX UNIT.</th>
                    <th width="15%">TAXE</th>
                    <th width="15%" class="text-right">MONTANT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $item['particulars'] }}</td>
                    <td class="text-center">{{ $item['numbers'] }}</td>
                    <td class="text-right">{{ number_format($item['cost'], 0, ',', ' ') }} FCFA</td>
                    <td>
                        {{ App\Models\Tax::getTax($item['tax'])->name }}
                        ({{ App\Models\Tax::getTax($item['tax'])->rate }}%)
                    </td>
                    <td class="text-right">
                        @php
                            $subtotal = $item['numbers'] * $item['cost'];
                            $taxRate = App\Models\Tax::getTax($item['tax'])->rate;
                            $amount = $subtotal + ($subtotal * $taxRate / 100) - ($item['discount'] ?? 0);
                        @endphp
                        {{ number_format($amount, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="clearfix">
            <div class="total-section">
                <div class="total-row">
                    <span>Sous-total :</span>
                    <span>{{ number_format($bill->subtotal, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="total-row">
                    <span>Total Taxe :</span>
                    <span>{{ number_format($bill->total_tax, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="total-row">
                    <span>Total Remise :</span>
                    <span>{{ number_format($bill->total_disc, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="total-row final">
                    <span>MONTANT TOTAL :</span>
                    <span>{{ number_format($bill->total_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="total-row">
                    <span>Montant Dû :</span>
                    <span><strong>{{ number_format($bill->due_amount, 0, ',', ' ') }} FCFA</strong></span>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>Merci de votre confiance</p>
            <p>{{ config('app.name', 'Dossy Pro') }} - Document généré automatiquement le {{ date('d/m/Y') }}</p>
        </div>
    </div>
</body>
</html>
