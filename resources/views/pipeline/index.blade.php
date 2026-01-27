@extends('layouts.app')

@section('page-title', __('Pipeline'))

@section('action-button')
    @if (Auth::user()->super_admin_employee == 1 || array_search('manage crm', $premission_arr))
        <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
            <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md"
                data-title="{{ __('Create Pipeline') }}" data-url="{{ route('pipeline.create') }}"
                data-bs-original-title="{{ __('Create') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                <i class="ti ti-plus"></i>
            </a>
        </div>
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Pipeline') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table dataTable data-table doc-type-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Pipeline') }}</th>
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pipelines as $pipeline)
                                    <tr>
                                        <td>{{ $pipeline->name ?? '-' }}</td>
                                        @if (Auth::user()->super_admin_employee == 1 || array_search('manage crm', $premission_arr))
                                            <td class="text-end">
                                                <div class="action-btn me-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm btn-info align-items-center "
                                                        data-url="{{ route('pipeline.edit', $pipeline->id) }}"
                                                        data-size="md" data-ajax-popup="true"
                                                        data-title="{{ __('Update Pipeline') }}" title="{{ __('Edit') }}"
                                                        data-bs-toggle="tooltip" data-bs-placement="top">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                </div>
                                                <div class="action-btn me-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                        data-confirm="{{ __('Are You Sure?') }}"
                                                        data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="delete-form-{{ $pipeline->id }}"
                                                        title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                                {!! Form::open([
                                                    'method' => 'DELETE',
                                                    'route' => ['pipeline.destroy', $pipeline->id],
                                                    'id' => 'delete-form-' . $pipeline->id,
                                                ]) !!}
                                                {!! Form::close() !!}
                                            </td>
                                        @endif
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
