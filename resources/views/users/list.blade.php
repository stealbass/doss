@extends('layouts.app')

@if (Auth::user()->type == 'super admin')
    @section('page-title', __('Companies'))
@else
    @section('page-title', __('Employees'))
@endif

@section('action-button')
    <div class="row align-items-end">
        <div class="col-md-12 d-flex justify-content-sm-end">
            <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary mx-1" data-toggle="tooltip"
                    title="{{ __('Grid View') }}" data-bs-original-title="{{ __('Grid View') }}" data-bs-placement="top"
                    data-bs-toggle="tooltip">
                    <i class="ti ti-border-all"></i>
                </a>
            </div>

            @canany(['create member', 'create user'])
                <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
                    <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="lg"
                        data-title="{{ Auth::user()->type == 'super admin' ? __('Create Company') : __('Create Employee') }}"
                        data-url="{{ route('users.create') }}" data-bs-original-title="{{ __('Create') }}"
                        data-bs-placement="top" data-bs-toggle="tooltip">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            @endcanany
        </div>
    </div>
@endsection

@section('breadcrumb')
    @if (Auth::user()->type == 'super admin')
        <li class="breadcrumb-item">{{ __('Companies') }}</li>
    @else
        <li class="breadcrumb-item">{{ __('Employees') }}</li>
    @endif
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table dataTable data-table user-datatable ">
                            <thead>
                                <tr>
                                    <th>{{ __('#') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    @if (Auth::user()->type == 'company')
                                        <th>{{ __('Designation') }}</th>
                                    @endif
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Phone Number') }}</th>
                                    @if (Auth::user()->type == 'super admin')
                                        <th>{{ __('Due Date') }}</th>
                                    @endif
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($users as $key => $user)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $user->name }}</td>
                                        @if (Auth::user()->type == 'company')
                                            <td>{{ $user->type }}</td>
                                        @endif
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->user_detail->mobile_number ?? '-' }}</td>
                                        @if (Auth::user()->type == 'super admin')
                                            <td>
                                                {{ $user->plan_expire_date ? date('d-m-Y', strtotime($user->plan_expire_date)) : '-' }}
                                            </td>
                                        @endif
                                        <td>
                                            @if (($user->is_active == 0 || $user->is_disable == 0) && Auth::user()->type != 'super admin')
                                                <i class="ti ti-lock"></i>
                                            @else
                                                @if (Auth::user()->type == 'super admin' || Auth::user()->type == 'company')
                                                    @if ($user->email_verified_at == null || $user->email_verified_at == '')
                                                        <div class="action-btn me-2">
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm btn-light-secondary align-items-center bs-pass-para "
                                                                data-confirm="{{ __('Are You Sure?') }}"
                                                                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="verify-form-{{ $user->id }}"
                                                                title="{{ __('Verify Email') }}" data-bs-toggle="tooltip"
                                                                data-bs-placement="top">
                                                                <i class="ti ti-checks"></i>
                                                            </a>
                                                            {!! Form::open([
                                                                'method' => 'POST',
                                                                'route' => ['users.verify', $user->id],
                                                                'id' => 'verify-form-' . $user->id,
                                                            ]) !!}
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @else
                                                        <div class="action-btn me-2">
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm btn-warning-subtle align-items-center"
                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                title="{{ __('verified Email') }}">
                                                                <i class="ti ti-checks text-white"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                @endif

                                                @if (Auth::user()->type == 'super admin')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('login.with.admin', $user->id) }}"
                                                            class="mx-3 btn btn-sm btn-primary-subtle align-items-center"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Login as company') }}">
                                                            <i class="ti ti-replace text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn me-2">
                                                        <a href="#" data-url="{{ route('plan.upgrade', $user->id) }}"
                                                            class="mx-3 btn btn-sm btn-brown-subtitle align-items-center"
                                                            data-ajax-popup="true" data-title="{{ __('Upgrade Plan') }}"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Upgrade Plan') }}" data-size="lg">
                                                            <i class="ti ti-trophy text-white"></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                @if (Auth::user()->type == 'super admin' || Auth::user()->type == 'company')
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

                                                    <div class="action-btn me-2">
                                                        @if ($user->is_enable_login == 1)
                                                            <a href="{{ route('users.login', \Crypt::encrypt($user->id)) }}"
                                                                data-bs-toggle="tooltip" data-tooltip="Login Disable"
                                                                title="{{ __('Login Disable') }}"
                                                                class="mx-3 btn btn-sm btn-blue-subtitle align-items-center">
                                                                <i class="ti ti-road-sign text-white"></i>
                                                            </a>
                                                        @elseif ($user->is_enable_login == 0 && $user->password == null)
                                                            <a href="#"
                                                                data-url="{{ route('users.reset', \Crypt::encrypt($user->id)) }}"
                                                                data-bs-toggle="tooltip" data-tooltip="Login Enable"
                                                                title="{{ __('Login Enable') }}" data-ajax-popup="true"
                                                                data-size="md"
                                                                class="mx-3 btn btn-sm btn-danger align-items-center login_enable"
                                                                data-title="{{ __('New Password') }}"
                                                                class="mx-3 btn btn-sm align-items-center">
                                                                <i class="ti ti-road-sign"></i>
                                                            </a>
                                                        @else
                                                            <a href="{{ route('users.login', \Crypt::encrypt($user->id)) }}"
                                                                data-bs-toggle="tooltip" data-tooltip="Login Enable"
                                                                title="{{ __('Login Enable') }}"
                                                                class="mx-3 btn btn-sm btn-danger align-items-center">
                                                                <i class="ti ti-road-sign text-white"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if (Auth::user()->type == 'super admin')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('users.detail', $user->id) }}" href="#"
                                                            class="mx-3 btn btn-sm btn-warning align-items-center"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Details') }}">
                                                            <i class="ti ti-eye "></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                @can('show member')
                                                    <div class="action-btn me-2">
                                                        <a data-url="{{ route('users.show', $user->id) }}" href="#"
                                                            class="mx-3 btn btn-sm btn-warning align-items-center"
                                                            data-ajax-popup="true" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="{{ __('View Groups') }}"
                                                            data-size="lg" data-title="{{ $user->name . __("'s Group") }}">
                                                            <i class="ti ti-users "></i>
                                                        </a>
                                                    </div>
                                                @endcan

                                                @canany(['edit member', 'edit user'])
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('users.edit', $user->id) }}"
                                                            class="mx-3 btn btn-sm btn-info align-items-center "
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil"></i>
                                                        </a>
                                                    </div>
                                                @endcanany

                                                @canany(['delete member', 'delete user'])
                                                    @if ($user->id != 2)
                                                        <div class="action-btn me-2">
                                                            <a href="#"
                                                                class="mx-3 btn btn-sm btn-danger align-items-center {{ Auth::user()->type == 'super admin' ? 'bs-pass-para-user-delete' : 'bs-pass-para' }} "
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
                                                    @endif
                                                @endcanany
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

@push('custom-script')
    <script>
        $(document).on('change', '#password_switch', function() {
            if ($(this).is(':checked')) {
                $('.ps_div').removeClass('d-none');
                $('#password').attr("required", true);
            } else {
                $('.ps_div').addClass('d-none');
                $('#password').val(null);
                $('#password').removeAttr("required");
            }
        });
        $(document).on('click', '.login_enable', function() {
            setTimeout(function() {
                $('.modal-body').append($('<input>', {
                    type: 'hidden',
                    val: 'true',
                    name: 'login_enable'
                }));
            }, 2000);
        });
    </script>
@endpush
