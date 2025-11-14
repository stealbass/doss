@extends('layouts.app')

@section('page-title', __('Case'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Case') }}</li>
@endsection

@php
    $docfile = \App\Models\Utility::get_file('uploads/case_docs/');
    $filing_date = '';
    if (!empty($case->filing_date)) {
        $filing_date = date('d-m-Y', strtotime($case->filing_date));
    }
    $documentsfile = \App\Models\Utility::get_file('uploads/documents/');
@endphp

@php
    // Récupère la liste des clients (id => nom)
    $clients_list = \App\Models\User::where('type', 'client')->pluck('name', 'id')->toArray();
@endphp

@section('content')
    <div class="row p-0 g-0">
        <div class="col-xl-12">
            <div class="card shadow-none shadow-none rounded-0 border">
                <div class="row">
                    <div class="col-lg-12">

                        <div class="row p-0">
                            <dl class="row col-md-6 p-5 py-2">
                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Courts/Tribunal:') }}</span></dt>
                                <dd class="col-md-7">
                                    <span
                                        class="text-md">{{ App\Models\CauseList::getCourtById($case->court) ?? '-' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Case no.:') }}</span></dt>
                                <dd class="col-md-7">
                                    <span class="text-md">{{ !empty($case->case_number) ? $case->case_number : '-' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Case Type:') }}</span></dt>
                                <dd class="col-md-7">
                                    <span class="text-md">{{ !empty($case->casenumber) ? $case->casenumber : '-' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Year:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->year ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Title:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->title ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Date of filing:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $filing_date ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Judge name:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->judge ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Court Room no.:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->court_room ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Under Acts:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->under_acts ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Under Sections:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->under_sections ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('FIR Police Station:') }}</span>
                                </dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->police_station ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('FIR No:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->FIR_number ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('FIR Year:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->FIR_year ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Stage:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->stage ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Your Party:') }}</span></dt>
                                <dd class="col-md-7"><span
                                        class="text-md">{{ $case->your_party == 0 ? 'Petitioner/Plaintiff' : 'Respondent/Defendant' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Advocates:') }}</span></dt>
                                <dd class="col-md-7"><span
                                        class="text-md">{{ App\Models\Advocate::getAdvocates($case->advocates) ?? '-' }}</span>
                                </dd>
                            </dl>

                            <dl class="row col-md-6 p-5 py-2">
                                @if (!empty($case->your_party_name))
                                    <div class="col-12">
                                        <h5>{{ __('Your Party Name') }}</h5>
                                        <hr class="mt-2 mb-2">
                                    </div>
                                    @foreach (json_decode($case->your_party_name, true) as $key => $opp)
                                        <dt class="col-md-12">
                                            <span class="h6 text-md mb-0">{{ __('Party Name ' . $key + 1) }}</span>
                                        </dt>
                                        <dt class="col-md-2"><span class="h6 text-md mb-0">{{ __('Name:') }}</span></dt>
                                        <dd class="col-md-10">
                                            <span class="text-md">
                                                {{ $clients_list[$opp['clients']] ?? '-' }}
                                            </span>
                                        </dd>
                                    @endforeach
                                @endif

                                @if (!empty($case->opp_party_name))
                                    <div class="col-12">
                                        <h5>{{ __('Opposite Party') }}</h5>
                                        <hr class="mt-2 mb-2">
                                    </div>
                                    @foreach (json_decode($case->opp_party_name, true) as $key => $opp)
                                        <dt class="col-md-12"><span
                                                class="h6 text-md mb-0">{{ __('Opposite Party ' . $key + 1) }}</span>
                                        </dt>
                                        <dt class="col-md-2">
                                            <span class="h6 text-md mb-0">{{ __('Name:') }}</span>
                                        </dt>
                                        <dd class="col-md-10">
                                            <span class="text-md">{{ $opp['name'] ?? '-' }}</span>
                                        </dd>
                                    @endforeach
                                @endif
                                <div class="col-12">
                                    <hr class="mt-2 mb-2">
                                    <h5>{{ __('Description') }}</h5>
                                </div>
                                <dd class="col-md-12"><span class="text-md">{!! $case->description ?? '-' !!}</span></dd>
                            </dl>
                            @if ($documents > 0)
                                @foreach ($documents as $key => $document)
                                    <div class="row col-md-6 p-5 py-2">
                                        <hr>
                                        <h5>{{ $document ?? '-' }}</h5>
                                        <dt class="col-md-4"><span class="h6 text-md mb-0">{{ __('View:') }}</span></dt>
                                        <dd class="col-md-8">
                                            <span class="text-md">
                                                <a
                                                    href="{{ $docfile . $document }}"target="_blank">{{ __('Click here') }}</a>
                                            </span>
                                        </dd>
                                        <dt class="col-md-4"><span class="h6 text-md mb-0">{{ __('Download:') }}</span>
                                        </dt>
                                        <dd class="col-md-8">
                                            <span class="text-md">
                                                <a href="{{ $docfile . $document }}" target="_blank"
                                                    download>{{ __('Click here') }}</a>
                                            </span>
                                        </dd>
                                        <dd class="col-md-4">
                                            <div class="action-btn mx-1">
                                                <a href="#"
                                                    class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                    data-confirm="{{ __('Are You Sure?') }}"
                                                    data-confirm-yes="delete-form-{{ $key }}"
                                                    title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                    data-bs-placement="top">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                        </dd>
                                        {!! Form::open([
                                            'method' => 'GET',
                                            'route' => ['cases.docs.destroy', [$case->id, $key]],
                                            'id' => 'delete-form-' . $key,
                                        ]) !!}
                                        {!! Form::close() !!}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-none rounded-0 border">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="row p-0 ">
                            <div class="col-xl-12">
                                <div class="card-header">
                                    <div class="row px-3">
                                        <div class="col-xl-2">
                                            <h5> {{ __('Document') }} </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class=" row">
                                        @foreach ($docs as $key => $document)
                                            <div class=" row col-12 col-sm-6 px-lg-5 px-4 py-2">
                                                <hr>
                                                <h5>{{ $document->name ?? '-' }}</h5>

                                                <dd class="col-xxl-12 col-12">
                                                    {{ $document->purpose ?? '-' }}
                                                </dd>

                                                <dt class="col-md-4">
                                                    <span class="h6 text-md mb-0">{{ __('View:') }}</span>
                                                </dt>
                                                <dd class="col-md-8">
                                                    <span class="text-md">
                                                        <a
                                                            href="{{ $documentsfile . $document->file }}"target="_blank">{{ __('Click here') }}</a>
                                                    </span>
                                                </dd>

                                                <dt class="col-md-4">
                                                    <span class="h6 text-md mb-0">{{ __('Download:') }}</span>
                                                </dt>
                                                <dd class="col-md-8">
                                                    <span class="text-md">
                                                        <a href="{{ $documentsfile . $document->file }}" target="_blank"
                                                            download>{{ __('Click here') }}</a>
                                                    </span>
                                                </dd>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-none rounded-0 border">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="row p-0 ">
                            <div class="col-xl-12">
                                <div class="card-header">
                                    <div class="row align-items-center justify-content-between gap-2">
                                        <div class="col-auto">
                                            <h5> {{ __('Hearing') }} </h5>
                                        </div>
                                        @if (Auth::user()->type != 'client')
                                            <div class="col-auto">
                                                <a href="#" class="btn btn-sm btn-primary mx-1"
                                                    data-ajax-popup="true" data-title="{{ __('Import Hearing') }}"
                                                    data-url="{{ route('hearing.file.import', $case->id) }}"
                                                    data-bs-original-title="{{ __('Import') }}" data-bs-placement="top"
                                                    data-bs-toggle="tooltip">
                                                    <i class="ti ti-file-import"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true"
                                                    data-size="md" data-title="{{ __('Create Hearing') }}"
                                                    data-url="{{ route('hearings.create', $case->id) }}"
                                                    data-bs-original-title="{{ __('Create') }}" data-bs-placement="top"
                                                    data-bs-toggle="tooltip">
                                                    <i class="ti ti-plus"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body table-border-style">
                                    <div class="table-responsive">
                                        <table class="table dataTable data-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('#') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Remarks') }}</th>
                                                    <th>{{ __('Order Sheet') }}</th>
                                                    <th width="100px">{{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($hearings as $key => $hearing)
                                                    <tr>
                                                        <td> {{ $key + 1 }} </td>
                                                        <td> {{ date('d-m-Y ', strtotime($hearing->date)) ?? '-' }} </td>
                                                        <td> {{ $hearing->remarks ?? '-' }} </td>
                                                        <td>
                                                            @if (!empty($hearing->order_seet))
                                                                <a href="{{ $documentsfile . $hearing->order_seet }}"
                                                                    target="_blank">{{ __('View') }}
                                                                </a>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if (Auth::user()->type != 'client')
                                                                <div class="action-btn me-2">
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm btn-info align-items-center "
                                                                        data-url="{{ route('hearing.edit', $hearing->id) }}"
                                                                        data-size="md" data-ajax-popup="true"
                                                                        data-title="{{ __('Update Hearing') }}"
                                                                        title="{{ __('Edit') }}"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top">
                                                                        <i class="ti ti-pencil "></i>
                                                                    </a>
                                                                </div>
                                                                <div class="action-btn me-2">
                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                                        data-confirm="{{ __('Are You Sure?') }}"
                                                                        data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                        data-confirm-yes="delete-form-{{ $hearing->id }}"
                                                                        title="{{ __('Delete') }}"
                                                                        data-bs-toggle="tooltip" data-bs-placement="top">
                                                                        <i class="ti ti-trash"></i>
                                                                    </a>
                                                                </div>
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['hearing.destroy', $hearing->id],
                                                                    'id' => 'delete-form-' . $hearing->id,
                                                                ]) !!}
                                                                {!! Form::close() !!}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
