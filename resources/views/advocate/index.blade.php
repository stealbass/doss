@extends('layouts.app')

@section('page-title', __('Advocate'))

@section('action-button')
    @can('create advocate')
        <div class="row align-items-center">
            <div class="col-md-12 d-flex align-items-center  justify-content-end">
                <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
                    <a href="#" class="btn btn-sm btn-primary mx-1" data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ __('Import') }}" data-size="md" data-ajax-popup="true"
                        data-title="{{ __('Import Advocate CSV file') }}" data-url="{{ route('advocates.file.import') }}">
                        <i class="ti ti-file-import text-white"></i>
                    </a>
                    <a href="{{ route('advocates.export') }}" class="btn btn-sm btn-primary mx-1" data-bs-toggle="tooltip"
                        data-title=" {{ __('Export') }}" title="{{ __('Export') }}">
                        <i class="ti ti-file-export"></i>
                    </a>
                    <a href="{{ route('advocate.create') }}" class="btn btn-sm btn-primary mx-1" data-toggle="tooltip"
                        title="{{ __('Create') }}" data-bs-toggle="tooltip" data-bs-placement="top">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Advocate') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table dataTable data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('#') }}</th>
                                    <th>{{ __('Advocate Name') }}</th>
                                    <th>{{ __('Company Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Contact') }}</th>
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($advocates as $key => $advocate)
                                    <tr>
                                        <td>
                                            {{ $key + 1 }}
                                        </td>
                                        <td>{{ optional($advocate->getAdvUser)->name ?? '-' }}</td>
                                        <td>{{ $advocate->company_name ?? '-' }}</td>
                                        <td>{{ $advocate->getAdvUser->email ?? '-' }}</td>
                                        <td>{{ $advocate->phone_number ?? '-' }}</td>
                                        <td>
                                            @if ($advocate->getAdvUser->is_disable == 0 || $advocate->getAdvUser->is_active == 0)
                                                <i class="ti ti-lock"></i>
                                            @else
                                                @can('view advocate')
                                                    <div class="action-btn me-2">
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm btn-warning-subtle align-items-center "
                                                            data-url="{{ route('advocate.show', $advocate->user_id) }}"
                                                            data-size="xl" data-ajax-popup="true"
                                                            data-title="{{ $advocate->getAdvUser->name }}{{ __("'s Cases") }}"
                                                            title="{{ __('View Case') }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top">
                                                            <i class="ti ti-clipboard-list text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @if (Auth::user()->type == 'company')
                                                    <div class="action-btn me-2">
                                                        <a href="#"
                                                            data-url="{{ route('company.reset', \Crypt::encrypt($advocate->user_id)) }}"
                                                            class="mx-3 btn btn-sm btn-light-blue-subtitle align-items-center "
                                                            data-tooltip="Edit" data-ajax-popup="true" data-size="md"
                                                            data-title="{{ __('Reset Password') }}"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Reset Password') }}">
                                                            <i class="ti ti-key text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                                @can('view advocate')
                                                    <div class="action-btn me-2">
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm btn-warning align-items-center "
                                                            data-url="{{ route('advocate.view', $advocate->id) }}"
                                                            data-size="lg" data-ajax-popup="true"
                                                            data-title="{{ $advocate->getAdvUser->name }}"
                                                            title="{{ __('View') }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top">
                                                            <i class=" ti ti-eye "></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('edit advocate')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('advocate.edit', $advocate->id) }}"
                                                            class="mx-3 btn btn-sm btn-info align-items-center "
                                                            title="{{ __('Edit') }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top">
                                                            <i class="ti ti-pencil"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete advocate')
                                                    <div class="action-btn me-2">
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                            data-confirm="{{ __('Are You Sure?') }}"
                                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="delete-form-{{ $advocate->id }}"
                                                            title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top">
                                                            <i class="ti ti-trash"></i>
                                                        </a>
                                                    </div>
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['advocate.destroy', $advocate->id],
                                                        'id' => 'delete-form-' . $advocate->id,
                                                    ]) !!}
                                                    {!! Form::close() !!}
                                                @endcan
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
@endsection
