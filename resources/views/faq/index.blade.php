@extends('layouts.app')

@section('page-title', __('Manage FAQ'))

@section('action-button')
    @if (Auth::user()->super_admin_employee == '1')
        <div class="row justify-content-end">
            <div class="col-auto">
                <a href="{{ route('faqs.create') }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Create') }}" class="btn btn-sm btn-primary btn-icon m-1 float-end">
                    <i class="ti ti-plus text-white"></i>
                </a>
            </div>
        </div>
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('FAQ') }}</li>
@endsection

@section('content')
    <div class="col-lg-12 col-md-12">
        <div class="card shadow-none rounded-0 border-bottom ">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pc-dt-simple" class="table dataTable data-table support-ticket-faq">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th class="w-25">{{ __('Title') }}</th>
                                <th>{{ __('Description') }}</th>
                                @if (\Auth::user()->super_admin_employee == 1)
                                    <th class="text-end me-3">{{ __('Action') }}</th>
                                @else
                                    <th class="text-end me-3"></th>
                                @endif

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($faqs as $index => $faq)
                                <tr>
                                    <th scope="row">{{ ++$index }}</th>
                                    <td><span class="font-weight-bold white-space">{{ $faq->title ?? '-' }}</span></td>
                                    <td class="faq_desc">{!! $faq->description ?? '-' !!}</td>
                                    <td class="text-end" width="100px">
                                        @if (\Auth::user()->super_admin_employee == 1)
                                            <div class="action-btn me-2">
                                                <a href="{{ route('faqs.edit', $faq->id) }}"
                                                    class="mx-3 btn btn-sm btn-info align-items-center"
                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                    <span class=""><i class="ti ti-pencil"></i></span>
                                                </a>
                                            </div>
                                        @endif
                                        @if (\Auth::user()->super_admin_employee == 1)
                                            <div class="action-btn me-2">
                                                <a href="#"
                                                    class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                    data-confirm="{{ __('Are You Sure?') }}"
                                                    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="delete-form-{{ $faq->id }}"
                                                    title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                    data-bs-placement="top">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['faqs.destroy', $faq->id], 'id' => 'delete-form-' . $faq->id]) !!}
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
@endsection
