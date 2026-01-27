@extends('layouts.app')

@section('page-title', __('Bank Payment Receipt'))

@section('action-button')
    <div class="row justify-content-between align-items-center">
        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
            <div class="action-btn mx-1">
                <a data-bs-toggle="tooltip" onclick="saveBankReceiptAsPDF()" class="btn btn-sm btn-primary" data-bs-placement="top"
                    title="{{ __('Download Receipt') }}" href="#!">
                    <i class="ti ti-download"></i>
                </a>
            </div>

            <div class="action-btn mx-1">
                <a href="{{ route('bills.show', $bill->id) }}" class="btn btn-sm btn-secondary" title="{{ __('Back to Bill') }}"
                    data-bs-toggle="tooltip" data-bs-placement="top">
                    <i class="ti ti-arrow-left"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Bank Payment Receipt') }}</li>
@endsection

@php
    $user = App\Models\User::getUser($bill->bill_to);
    $userDetail = App\Models\UserDetail::getUserDetail($user->id);
@endphp

@section('content')
    <div class="row" id="bankReceiptPrintableArea">
        <div class="col-md-2"></div>
        <div class="col-sm-12 col-md-8">
            <div class="card border rounded-0 card-body shadow-none">
                <div class="receipt">
                    <div class="row receipt-header mt-2">
                        <div class="col-12 text-center mb-3">
                            @if(!empty($logoBase64))
                                <img src="{{ $logoBase64 }}" style="height: 60px; max-width: 250px;" alt="Logo">
                            @elseif($company_logo)
                                <img src="{{ $logo . '/' . $company_logo }}?t={{ time() }}"
                                    style="height: 60px; max-width: 250px;" alt="Logo" crossorigin="anonymous">
                            @else
                                <img src="{{ $logo }}/logo-dark.png?t={{ time() }}"
                                    style="height: 60px; max-width: 250px;" alt="Logo" onerror="this.style.display='none';">
                            @endif
                        </div>
                        <div class="col-12 text-center">
                            <h2 class="text-primary">{{ __('BANK TRANSFER RECEIPT') }}</h2>
                        </div>
                        <div class="col-12">
                            <hr>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <strong>{{ __('Receipt #') }}</strong> {{ sprintf('%05d', $bankPayment->transaction_id) }}<br>
                            <strong>{{ __('Date') }}:</strong> {{ date('d/m/Y', strtotime($bankPayment->date)) }}
                        </div>
                        <div class="col-6 text-end">
                            <strong>{{ __('Invoice #') }}</strong> {{ $bill->bill_number }}<br>
                            <strong>{{ __('Payment Method') }}:</strong> Bank Transfer
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-6">
                            <strong>{{ __('Company Information') }}</strong><br>
                            <small>
                                @if (!empty(App\Models\Utility::getcompanydetailValByName('company_name')))
                                    {{ App\Models\Utility::getcompanydetailValByName('company_name') }}<br>
                                @endif
                                @if (!empty(App\Models\Utility::getcompanydetailValByName('address')))
                                    {{ App\Models\Utility::getcompanydetailValByName('address') }},
                                @endif
                                @if (!empty(App\Models\Utility::getcompanydetailValByName('city')))
                                    {{ App\Models\Utility::getcompanydetailValByName('city') }},
                                @endif
                                @if (!empty(App\Models\Utility::getcompanydetailValByName('state')))
                                    {{ App\Models\Utility::getcompanydetailValByName('state') }}
                                @endif
                            </small>
                        </div>
                        <div class="col-6 text-end">
                            <strong>{{ __('Received From') }}</strong><br>
                            <small>
                                {{ $user->name }}<br>
                                @if (!empty($userDetail->address))
                                    {{ $userDetail->address }},
                                @endif
                                @if (!empty($userDetail->city))
                                    {{ $userDetail->city }},
                                @endif
                                @if (!empty($userDetail->state))
                                    {{ $userDetail->state }}
                                @endif
                            </small>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-dark">{{ __('Description') }}</th>
                                            <th class="text-dark text-right" width="30%">{{ __('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <strong>{{ __('Bank Transfer for Invoice') }} {{ $bill->bill_number }}</strong><br>
                                                @if(!empty($bankPayment->notes))
                                                    <small class="text-muted">{{ $bankPayment->notes }}</small>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                <strong>{{ number_format($bankPayment->amount, 0, '', ' ') }}</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <th class="text-dark">{{ __('Total Amount Paid') }}</th>
                                            <th class="text-dark text-right">
                                                {{ number_format($bankPayment->amount, 0, '', ' ') }} FCFA
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>{{ __('Invoice Summary') }}:</strong><br>
                                {{ __('Invoice Total') }}: {{ number_format($bill->total_amount, 0, '', ' ') }} FCFA<br>
                                {{ __('Amount Due') }}: {{ number_format($bill->due_amount, 0, '', ' ') }} FCFA<br>
                                {{ __('Status') }}: <span class="badge bg-{{ $bill->status == 'PAID' ? 'success' : 'warning' }}">{{ $bill->status }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-6">
                            <div style="border-top: 1px solid #333; padding-top: 10px; width: 200px;">
                                <small>{{ __('Authorized Signature') }}</small>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <div style="border-top: 1px solid #333; padding-top: 10px; width: 200px; margin-left: auto;">
                                <small>{{ __('Received By') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <small class="text-muted">{{ __('Thank you for your payment') }}</small><br>
                            <small class="text-muted">{{ __('This is an electronically generated receipt') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2"></div>
    </div>
@endsection

@push('custom-script')
    <style>
        /* PDF mode styles */
        #bankReceiptPrintableArea.pdf-mode .col-md-2 {
            display: none !important;
        }
        
        #bankReceiptPrintableArea.pdf-mode .col-md-8 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        
        #bankReceiptPrintableArea.pdf-mode .card {
            margin: 0 !important;
            padding: 20px !important;
        }
        
        #bankReceiptPrintableArea.pdf-mode table {
            font-size: 11px !important;
            width: 100% !important;
        }
        
        #bankReceiptPrintableArea.pdf-mode th,
        #bankReceiptPrintableArea.pdf-mode td {
            padding: 10px !important;
        }
    </style>
    <script src="{{ asset('public/assets/js/html2pdf.bundle.js') }}"></script>
    <script>
        var filename = 'BANK-RECEIPT-{{ sprintf("%05d", $bankPayment->transaction_id) }}-{{ $bill->bill_number }}';

        function saveBankReceiptAsPDF() {
            var element = document.getElementById('bankReceiptPrintableArea');
            element.classList.add('pdf-mode');
            
            var opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 2,
                    dpi: 96,
                    letterRendering: true,
                    useCORS: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };
            html2pdf().set(opt).from(element).save();
            
            setTimeout(function(){
                element.classList.remove('pdf-mode');
            }, 500);
        }
    </script>
@endpush
