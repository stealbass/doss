@extends('layouts.app')

@section('page-title', __('Client'))

@section('action-button')
    <div class="row align-items-center">
        <div class="col-md-12 d-flex align-items-center  justify-content-end">
            <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
                <a href="{{ route('client.list') }}" class="btn btn-sm btn-primary mx-1" data-toggle="tooltip"
                    title="{{ __('List View') }}" data-bs-original-title="{{ __('List View') }}" data-bs-placement="top"
                    data-bs-toggle="tooltip">
                    <i class="ti ti-menu-2"></i>
                </a>
            </div>

            @can('create client')
                <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
                    <a href="#" class="btn btn-sm btn-primary mx-1" data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ __('Import') }}" data-size="md" data-ajax-popup="true" data-title="{{ __('Import Client') }}"
                        data-url="{{ route('clients.file.import') }}">
                        <i class="ti ti-file-import text-white"></i>
                    </a>
                </div>

                <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
                    <a href="{{ route('clients.export') }}" class="btn btn-sm btn-primary mx-1" data-bs-toggle="tooltip"
                        data-title=" {{ __('Export') }}" title="{{ __('Export') }}">
                        <i class="ti ti-file-export"></i>
                    </a>
                </div>

                <div class="text-end d-flex all-button-box justify-content-md-end justify-content-center">
                    <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md"
                        data-title="{{ __('Create Client') }}" data-url="{{ route('client.create') }}"
                        data-bs-original-title="{{ __('Create') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            @endcan
        </div>
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Client') }}</li>
@endsection

@section('content')
    <div class="row g-0 pt-0">
        <div class="col-xxl-12">
            <div class="row g-0">
                @foreach ($users as $user)
                    <div class="col-md-6 col-xxl-3 col-lg-4 col-sm-6 border-end border-bottom">
                        <div class="card user-card shadow-none bg-transparent border h-100 text-center rounded-0">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <div class="badge p-2 px-3 bg-primary">{{ ucfirst($user->type) }}</div>
                                    </h6>
                                </div>

                                @if (Gate::check('delete client') || Gate::check('edit client'))
                                    <div class="card-header-right">
                                        <div class="btn-group card-option">
                                            @if ($user->is_active == 1 && $user->is_disable == 1)
                                                <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>

                                                <div class="dropdown-menu dropdown-menu-end">
                                                    @if (Auth::user()->type == 'company')
                                                        <a href="#!"
                                                            data-url="{{ route('company.reset', \Crypt::encrypt($user->id)) }}"
                                                            data-ajax-popup="true" data-size="md" class="dropdown-item"
                                                            data-bs-original-title="{{ __('Reset Password') }}"
                                                            data-title="{{ __('Reset Password') }}">
                                                            <i class="ti ti-key"></i>
                                                            <span> {{ __('Reset Password') }}</span>
                                                        </a>
                                                    @endif

                                                    @can('manage client')
                                                        <a href="{{ route('client.show', [$user->id]) }}" class="dropdown-item"
                                                            class="mx-3 btn btn-sm btn-warning align-items-center">
                                                            <i class="ti ti-eye"></i>
                                                            <span>{{ __('View Details') }}</span>
                                                        </a>
                                                    @endcan

                                                    @can('edit client')
                                                        <a href="{{ route('users.edit', $user->id) }}" class="dropdown-item"
                                                            data-bs-original-title="{{ __('Edit User') }}">
                                                            <i class="ti ti-pencil"></i>
                                                            <span>{{ __('Edit') }}</span>
                                                        </a>
                                                    @endcan

                                                    @can('delete client')
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['users.destroy', $user->id],
                                                            'id' => 'delete-form-' . $user->id,
                                                        ]) !!}
                                                        <a href="#" class="dropdown-item bs-pass-para"
                                                            data-id="{{ $user['id'] }}"
                                                            data-confirm="{{ __('Are You Sure?') }}"
                                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="delete-form-{{ $user->id }}">
                                                            <i class="ti ti-trash"></i>
                                                            <span class="text-danger"> {{ __('Delete') }}</span>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    @endcan
                                                </div>
                                            @else
                                                <a href="#" class="action-item"><i class="ti ti-lock"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body full-card">
                                <div class="user-image rounded border-2 border border-primary m-auto">
                                    <img src="{{ !empty($user->avatar)
                                        ? asset('storage/uploads/profile/' . $user->avatar)
                                        : asset('storage/uploads/profile/avatar.png') }}"
                                        class="h-100 w-100">
                                </div>
                                <h4 class=" mt-3 text-primary">{{ $user->name }}</h4>
                                <small class="text-primary">{{ $user->email }}</small>
                                <p></p>
                                <div class="text-center" data-bs-toggle="tooltip" title="{{ __('Last Login') }}">
                                    {{ !empty($user->last_login_at) ? $user->last_login_at : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-md-6 col-xxl-3 col-lg-4 col-sm-6 border-end border-bottom">
                    <div class="card  shadow-none bg-transparent border h-100 text-center rounded-0">
                        <div class="card-body border-0 pb-0">
                            <a href="#" class="btn-addnew-project border-0" data-ajax-popup="true" data-size="md"
                                data-title="{{ __('Create Client') }}" data-url="{{ route('client.create') }}">
                                <div class="bg-primary proj-add-icon" data-bs-original-title="{{ __('Create') }}"
                                    data-bs-placement="top" data-bs-toggle="tooltip">
                                    <i class="ti ti-plus my-2"></i>
                                </div>
                                <h6 class="mt-4 mb-2">{{ __('New Client') }}</h6>
                                <p class="text-muted text-center">{{ __('Click here to add New Client') }}</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
