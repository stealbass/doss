@extends('layouts.app')

@section('page-title', __('Bill'))

@section('action-button')
    <div class="row justify-content-between align-items-center">
        <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
            @can('edit bill')
                <div class="action-btn mx-1">
                    <a href="{{ route('bills.edit', $bill->id) }}" class="btn btn-sm btn-primary" title="{{ __('Edit') }}"
                        data-bs-toggle="tooltip" data-bs-placement="top">
                        <i class="ti ti-pencil "></i>
                    </a>
                </div>
            @endcan

            @if ($bill->status != 'PAID' && Auth::check() && Auth::user()->type == 'company')
            <div class="action-btn mx-1">
                <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md"
                    data-title="{{ __('Bill: ') }} {{ $bill->bill_number }}"
                    data-url="{{ route('create.payment', $bill->id) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Add Payment') }}">
                    <i class="ti ti-report-money"></i>
                </a>
            </div>
        @endif


            <div class="action-btn mx-1">
                <a data-bs-toggle="tooltip" onclick="saveAsPDF2()" class="btn btn-sm btn-primary " data-bs-placement="top"
                    title="{{ __('Download') }}" href="#!" target="_blanks">
                    <i class="ti ti-download "></i>
                </a>
            </div>

            <div class="action-btn mx-1">
                <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true" data-size="md"
                    data-title="{{ __('Send Bill by Email') }}"
                    data-url="{{ route('bill.send.email', $bill->id) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Send by Email') }}">
                    <i class="ti ti-mail"></i>
                </a>
            </div>

            <div class="action-btn mx-1">
                <a href="#" class="btn btn-sm btn-primary  cp_link"
                    data-link="{{ route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($bill->id)) }}"
                    data-bs-toggle="tooltip" title="{{ __('Copy invoice link') }}">
                    <span class="btn-inner--icon text-white"><i class="ti ti-file"></i></span>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Bill') }}</li>
@endsection

@php
    use App\Models\Utility;
    $settings = App\Models\Utility::settings();
    $logo = App\Models\Utility::get_file('uploads/logo');
    $company_logo = App\Models\Utility::get_company_logo();
    $advocate = App\Models\Advocate::where('user_id', $bill->advocate)->first();
    $user = App\Models\User::getUser($bill->bill_to);
    $userDetail = App\Models\UserDetail::getUserDetail($user->id);
@endphp

@section('content')
    <div class="row" id="printableArea2">
        <div class="col-md-2 col-md-2"></div>
        <div class="col-sm-12 col-md-8 col-md-8  ">
            <div class="card border rounded-0 card-body shadow-none ">
                <div class="invoice">
                    <div class="invoice-print">
                        <div class="row invoice-title mt-2">
                            <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                <h2>{{ __('Bill') }}</h2>
                            </div>
                            <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 d-flex justify-content-end">
                                <h3 class="invoice-number">{{ $bill->bill_number }}</h3>
                            </div>
                            <div class="col-12">
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="page-header-title">
                                    @if(!empty($logoBase64))
                                        <img src="{{ $logoBase64 }}" id="navbar-logo" style="height: 50px; max-width: 200px;" alt="Logo">
                                    @elseif($company_logo)
                                        <img src="{{ $logo . '/' . $company_logo }}?t={{ time() }}"
                                            id="navbar-logo" style="height: 50px; max-width: 200px;" alt="Logo" crossorigin="anonymous">
                                    @else
                                        <img src="{{ $logo }}/logo-dark.png?t={{ time() }}"
                                            id="navbar-logo" style="height: 50px; max-width: 200px;" alt="Logo" onerror="this.style.display='none';">
                                    @endif
                                </div>
                            </div>
                            <div class="col text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <div>
                                        <small>
                                            <strong>{{ __('Due Date :') }}</strong><br>
                                            {{ date('M d, Y', strtotime($bill->due_date)) }}<br><br>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Status visible on screen, hidden in PDF download -->
                        <div class="row mt-3 hide-on-pdf">
                            <div class="col">
                                <small>
                                    <strong>{{ __('Status :') }}</strong><br>
                                    @if ($bill->status == 'PENDING')
                                        <span class="badge fix_badge p-1 px-3 bg-danger">{{ $bill->status }}</span>
                                    @elseif ($bill->status == 'Partialy Paid')
                                        <span class="badge fix_badge p-1 px-3 bg-warning">{{ $bill->status }}</span>
                                    @else
                                        <span class="badge fix_badge p-1 px-3 bg-success">{{ $bill->status }}</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <small class="font-style">
                                    <strong>{{ __('Bill From :') }}</strong><br>
                                    @if ($bill->bill_from == 'advocate')
                                        {{ App\Models\Advocate::getAdvocates($bill->advocate) }}
                                        <br>
                                        @if ($advocate)
                                            @if (!empty($advocate->ofc_address_line_1))
                                                {{ $advocate->ofc_address_line_1 }},
                                            @endif
                                            @if (!empty($advocate->ofc_city))
                                                {{ $advocate->ofc_city }},
                                            @endif
                                            @if (!empty($advocate->ofc_state))
                                                {{ App\Models\State::StatebyId($advocate->ofc_state) }}
                                            @endif
                                        @endif
                                    @elseif ($bill->bill_from == 'company')
                                        {{ App\Models\Utility::getcompanyValByName('name') }}
                                        <br>
                                        @if (!empty(App\Models\Utility::getcompanydetailValByName('address')))
                                            {{ App\Models\Utility::getcompanydetailValByName('address') }},
                                        @endif
                                        @if (!empty(App\Models\Utility::getcompanydetailValByName('city')))
                                            {{ App\Models\Utility::getcompanydetailValByName('city') }},
                                        @endif
                                        @if (!empty(App\Models\Utility::getcompanydetailValByName('state')))
                                            {{ App\Models\Utility::getcompanydetailValByName('state') }}
                                        @endif
                                    @else
                                        {{ $bill->custom_advocate }}
                                        <br>
                                        {{ $bill->custom_address }}
                                    @endif
                                </small>
                            </div>
                            <div class="col-6 text-end">
                                <small>
                                    <strong>{{ __('Bill To :') }}</strong><br>
                                    {{ $user->name }} <br>
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
                            <div class="col-md-12">
                                <div class="font-weight-bold"> {{ __('Summary') }} </div>
                                <div class="table-responsive mt-2">
                                    <table class="table mb-0 table-striped">
                                        <tbody>
                                            <tr>
                                                <th data-width="40" class="text-dark" style="width: 5%;"> {{ __('#') }} </th>
                                                <th class="text-dark" style="width: 30%;">{{ __('PARTICULARS') }}</th>
                                                <th class="text-dark" style="width: 10%;">{{ __('NUMBERS') }}</th>
                                                <th class="text-dark" style="width: 15%;">
                                                    {{ __('RATE/UNIT COST') . '(' . $settings['site_currency'] . ')' }}
                                                </th>
                                                <th class="text-dark" style="width: 20%;">{{ __('TAX') }}</th>
                                                <th class="text-right text-dark" style="width: 20%;">
                                                    {{ __('Amount') }}<br>
                                                </th>
                                            </tr>
                                            @foreach ($items as $key => $item)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $item['particulars'] }}</td>
                                                    <td class="numbers" data-value="{{ $item['numbers'] }}">{{ $item['numbers'] }}</td>
                                                    <td class="cost" data-value="{{ $item['cost'] }}">{{ number_format($item['cost'], 0, '', ' ') }}</td>
                                                    <td>
                                                        {{ App\Models\Tax::getTax($item['tax'])->name }}
                                                        {{ '(' . App\Models\Tax::getTax($item['tax'])->rate . '%)' }}
                                                        <span
                                                            class="d-none tax-rate">{{ App\Models\Tax::getTax($item['tax'])->rate }}</span>
                                                    </td>
                                                    <td class="amount text-right">
                                                        <b>0</b>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4"></td>
                                                <td class="text-right"><b>{{ __('Sub Total') }}</b></td>
                                                <td class="text-right"><b>{{ number_format($bill->subtotal, 0, '', ' ') }}</b></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4"></td>
                                                <td class="blue-text text-right"><b>{{ __('Total Tax') }}</b></td>
                                                <td class="blue-text text-right"><b>{{ number_format($bill->total_tax, 0, '', ' ') }}</b></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4"></td>
                                                <td class="blue-text text-right"><b>{{ __('Total Discount') }}</b></td>
                                                <td class="blue-text text-right"><b>{{ number_format($bill->total_disc, 0, '', ' ') }}</b></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4"></td>
                                                <td class="blue-text text-right"><b>{{ __('Total Amount') }}</b></td>
                                                <td class="blue-text text-right"><b>{{ number_format($bill->total_amount, 0, '', ' ') }}</b></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4"></td>
                                                <td class="blue-text text-right"><b>{{ __('Due Amount') }}</b></td>
                                                <td class="blue-text text-right"><b>{{ number_format($bill->due_amount, 0, '', ' ') }}</b></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments section: visible on screen, hidden in PDF download -->
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-xs-12 pl-0 mt-3 hide-on-pdf">
                <div class="card border rounded-0 card-body shadow-none p-0">
                    <div class="card-header">
                        <h5>{{ __('Payments') }}</h5>
                    </div>
                    <div class="card-body table-border-style pb-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th> {{ __('Date') }} </th>
                                        <th> {{ __('Amount') }} </th>
                                        <th> {{ __('Payment Type') }} </th>
                                        <th> {{ __('Description') }} </th>
                                        <th> {{ __('Receipt') }} </th>
                                        <th> {{ __('Transaction ID') }} </th>
                                        <th> {{ __('Action') }} </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payments as $payment)
                                        <tr>
                                            <td> {{ $payment->date }} </td>
                                            <td> {{ number_format($payment->amount, 0, '', ' ') }} </td>
                                            <td> {{ $payment->method }} </td>
                                            <td>
                                                {{ !empty($payment->note) ? $payment->note : ' --- ' }}
                                            </td>
                                            <td>{{ !empty($payment->reciept) ? __('Available') : ' --- ' }}</td>
                                            <td>
                                                {{ !empty($payment->txn_id) ? $payment->txn_id : ' --- ' }}
                                            </td>
                                            <td>
                                                <a href="{{ route('payment.receipt', $payment->id) }}" class="btn btn-outline-primary btn-sm" title="{{ __('View Receipt') }}" data-bs-toggle="tooltip" target="_blank">
                                                    <i class="ti ti-file-invoice"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($bankPayments as $bankPayment)
                                        <tr>
                                            <td>{{ $bankPayment->date }}</td>
                                            <td>{{ number_format($bankPayment->amount, 0, '', ' ') }}</td>
                                            <td>{{ 'Bank Transfer' }}</td>
                                            <td>{{ !empty($bankPayment->notes) ? $bankPayment->notes : '-' }}</td>
                                            <td>
                                                @if(!empty($bankPayment->receipt))
                                                    <span class="badge bg-success">{{ __('Available') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ sprintf('%05d', $bankPayment->transaction_id) }}</td>
                                            <td>
                                                <a href="{{ route('bank.payment.receipt', $bankPayment->id) }}" class="btn btn-outline-primary btn-sm" title="{{ __('View Receipt') }}" data-bs-toggle="tooltip" target="_blank">
                                                    <i class="ti ti-file-invoice"></i>
                                                </a>
                                                @if(!empty($bankPayment->receipt))
                                                    <a href="{{ \App\Models\Utility::get_file($bankPayment->receipt) }}" class="btn btn-outline-secondary btn-sm" target="_blank" title="{{ __('View Proof') }}" data-bs-toggle="tooltip">
                                                        <i class="ti ti-paperclip"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-md-2"></div>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <style>
        /* Hide elements marked for PDF when exporting */
        #printableArea2.pdf-mode .hide-on-pdf {
            display: none !important;
        }
        
        /* Make content full width in PDF mode */
        #printableArea2.pdf-mode .col-md-2 {
            display: none !important;
        }
        
        #printableArea2.pdf-mode .col-md-8 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        
        #printableArea2.pdf-mode .card {
            margin: 0 !important;
            padding: 20px !important;
        }
        
        /* Optimize table for PDF export */
        #printableArea2.pdf-mode table {
            font-size: 10px !important;
            width: 100% !important;
        }
        
        #printableArea2.pdf-mode th,
        #printableArea2.pdf-mode td {
            padding: 8px 6px !important;
            word-wrap: break-word;
        }
        
        #printableArea2.pdf-mode th {
            font-size: 9px !important;
            font-weight: 600 !important;
        }
        
        #printableArea2.pdf-mode .table-responsive {
            overflow: visible !important;
        }
        
        #printableArea2.pdf-mode tfoot td {
            font-size: 10px !important;
        }
    </style>
    <script src="{{ asset('public/assets/js/html2pdf.bundle.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.numbers').each(function() {
                var el = $(this).parent();
                var numbers = parseFloat($(el.find('.numbers')).attr('data-value'));
                var cost = parseFloat($(el.find('.cost')).attr('data-value'));
                var tax = parseFloat($(el.find('.tax-rate')).html());
                
                var totalItemPrice = (numbers * cost);
                totalItemPrice = totalItemPrice + totalItemPrice * tax / 100;
                
                // Format with space as thousand separator
                var formatted = Math.round(totalItemPrice).toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1 ');
                $(el.find('.amount b')).html(formatted);
            });
        })

        var filename = '#BILL-{{ $bill->bill_number }}';

        function saveAsPDF2() {
            var element = document.getElementById('printableArea2');
            // Mark PDF mode to hide elements with .hide-on-pdf
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
            
            // Remove PDF mode after a short delay
            setTimeout(function(){
                element.classList.remove('pdf-mode');
            }, 500);
        }

        $('.cp_link').on('click', function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            show_toastr('success', '{{ __('Link Copy on Clipboard') }}', 'success')
        });
    </script>
@endpush
