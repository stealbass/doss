@extends('layouts.app')

@section('page-title', __('Employees'))

@section('action-button')
    <div class="row align-items-center">
        <div class="col-md-12 d-flex align-items-center  justify-content-end">
            @canany(['create member', 'create user'])
                <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
                    <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="lg"
                        data-title="{{ __('Create Employee') }}" data-url="{{ route('employee.create') }}"
                        data-bs-original-title="{{ __('Create') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            @endcanany
        </div>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Employees') }}</li>
@endsection

@section('content')
    <div class="row g-0 pt-0">
        <div class="col-xxl-12">
            <div class="row g-0">
                @foreach ($employee as $employee)
                    <div class="col-md-6 col-xxl-3 col-lg-4 col-sm-6 border-end border-bottom">
                        <div class="card user-card shadow-none bg-transparent border h-100 text-center rounded-0">
                            <div class="card-header border-0 pb-0">
                                @if (Gate::check('delete member') || Gate::check('delete user'))
                                    <div class="card-header-right">
                                        <div class="btn-group card-option">
                                            @if (Auth::user()->type == 'super admin')
                                                <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    @canany(['edit member', 'edit user'])
                                                        <a href="#" class="dropdown-item" data-ajax-popup="true"
                                                            data-size="lg" data-title="{{ __('Update Employee') }}"
                                                            data-url="{{ route('employee.edit', $employee->id) }}"
                                                            data-toggle="tooltip">
                                                            <i class="ti ti-pencil"></i>
                                                            <span>{{ __('Edit') }}</span>
                                                        </a>
                                                    @endcanany
                                                    @canany(['delete member', 'delete user'])
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['employee.destroy', $employee->id],
                                                            'id' => 'delete-form-' . $employee->id,
                                                        ]) !!}
                                                        <a href="#" class="dropdown-item bs-pass-para-user-delete"
                                                            data-id="{{ $employee['id'] }}"
                                                            data-confirm="{{ __('Are You Sure?') }}"
                                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="delete-form-{{ $employee->id }}">
                                                            <i class="ti ti-trash"></i>
                                                            <span class="text-danger"> {{ __('Delete') }}</span>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    @endcanany
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body full-card">
                                <div class="user-image rounded border-2 border border-primary m-auto">
                                    <img src="{{ !empty($employee->avatar)
                                        ? asset('storage/uploads/profile/' . $employee->avatar)
                                        : asset('storage/uploads/profile/avatar.png') }}"
                                        class="h-100 w-100">
                                </div>
                                <h4 class=" mt-3 text-primary">{{ $employee->name }}</h4>
                                <small class="text-primary">{{ $employee->email }}</small>
                                <p></p>
                                <div class="text-center" data-bs-toggle="tooltip" title="{{ __('Last Login') }}">
                                    {{ !empty($employee->last_login_at) ? $employee->last_login_at : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-md-6 col-xxl-3 col-lg-4 col-sm-6 border-end border-bottom">
                    <div class="card  shadow-none bg-transparent border h-100 text-center rounded-0">
                        <div class="card-body border-0 pb-0">
                            <a href="#" class="btn-addnew-project border-0" data-ajax-popup="true" data-size="lg"
                                data-title="{{ __('Create Employee') }}" data-url="{{ route('employee.create') }}">
                                <div class="bg-primary proj-add-icon" data-bs-original-title="{{ __('Create') }}"
                                    data-bs-placement="top" data-bs-toggle="tooltip">
                                    <i class="ti ti-plus"></i>
                                </div>
                                <h6 class="mt-4 mb-2">{{ __('New Employee') }}</h6>
                                <p class="text-muted text-center">{{ __('Click here to add New Employee') }}</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
