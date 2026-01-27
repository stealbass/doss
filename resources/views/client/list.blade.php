@extends('layouts.app')

@section('page-title', __('Client'))

@section('action-button')
    <div class="row align-items-end">
        <div class="col-md-12 d-flex justify-content-sm-end">
            <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
                <a href="{{ route('client.index') }}" class="btn btn-sm btn-primary mx-1" title="{{ __('Grid View') }}"
                    data-bs-original-title="{{ __('Grid View') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                    <i class="ti ti-border-all"></i>
                </a>

                @can('create client')
                    <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md"
                        data-title="{{ __('Create Client') }}" data-url="{{ route('client.create') }}"
                        data-bs-original-title="{{ __('Create') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                        <i class="ti ti-plus"></i>
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Client') }}</li>
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
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Mobile Number') }}</th>
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $key => $user)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user_details[$key]->mobile_number ?? '-' }}</td>
                                        <td>
                                            @if (($user->is_active == 0 || $user->is_disable == 0) && Auth::user()->type != 'super admin')
                                                <i class="ti ti-lock"></i>
                                            @else
                                                @if (Auth::user()->type == 'company')
                                                    <div class="action-btn me-2">
                                                        <a href="#"
                                                            data-url="{{ route('company.reset', \Crypt::encrypt($user->id)) }}"
                                                            class="mx-3 btn btn-sm btn-light-blue-subtitle align-items-center "
                                                            data-tooltip="Edit" data-ajax-popup="true"
                                                            data-title="{{ __('Reset Password') }}"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Reset Password') }}">
                                                            <i class="ti ti-key text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif


                                                @can('manage client')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('client.show', [$user->id]) }}"
                                                            class="mx-3 btn btn-sm btn-warning align-items-center"
                                                            data-bs-toggle="tooltip" title="{{ __('View Details') }}">
                                                            <i class="ti ti-eye "></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                                @can('edit client')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('users.edit', $user->id) }}"
                                                            class="mx-3 btn btn-sm btn-info align-items-center "
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil "></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                                @can('delete client')
                                                    <div class="action-btn me-2">
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para "
                                                            data-confirm="{{ __('Are You Sure?') }}"
                                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="delete-form-{{ $user->id }}"
                                                            title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top">
                                                            <i class="ti ti-trash"></i>
                                                        </a>
                                                    </div>
                                                    {!! Form::open([
                                                        'method' => 'DELETE',
                                                        'route' => ['users.destroy', $user->id],
                                                        'id' => 'delete-form-' . $user->id,
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
