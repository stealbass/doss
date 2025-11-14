@extends('layouts.app')

@section('page-title', __('Motions Type'))

@section('action-button')
    @can('create motions')
        <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
            <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md"
                data-title="{{ __('Create Motions Type') }}" data-url="{{ route('motions.create') }}" data-bs-toggle="tooltip"
                title="{{ __('Create') }}">
                <i class="ti ti-plus"></i>
            </a>
        </div>
    @endcan
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Motions Type') }}</li>
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
                                    <th>{{ __('Motions Type') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($motions as $motion)
                                    <tr>
                                        <td>
                                            <a href="#" class="btn btn-sm d-inline-flex align-items-center "
                                                data-url="{{ route('motions.edit', $motion->id) }}" data-size="md"
                                                data-ajax-popup="true" data-title="{{ __('Update Motion Type') }}">
                                                {{ ucfirst($motion->type) ?? '-' }}
                                            </a>
                                        </td>
                                        <td> {{ $motion->description ?? '-' }} </td>
                                        <td>
                                            @can('edit motions')
                                                <div class="action-btn me-2">
                                                    <a href="#" class="mx-3 btn btn-sm btn-info align-items-center "
                                                        data-url="{{ route('motions.edit', $motion->id) }}" data-size="md"
                                                        data-ajax-popup="true" data-title="{{ __('Update Motion Type') }}"
                                                        title="{{ __('Edit') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top">
                                                        <i class="ti ti-pencil "></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete motions')
                                                <div class="action-btn me-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                        data-confirm="{{ __('Are You Sure?') }}"
                                                        data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="delete-form-{{ $motion->id }}"
                                                        title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                                {!! Form::open([
                                                    'method' => 'DELETE',
                                                    'route' => ['motions.destroy', $motion->id],
                                                    'id' => 'delete-form-' . $motion->id,
                                                ]) !!}
                                                {!! Form::close() !!}
                                            @endcan
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
