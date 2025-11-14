@extends('layouts.app')

@section('page-title', __('Create Advocate'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Create Advocate') }}</li>
@endsection

@section('content')
    {{ Form::open(['route' => 'advocate.store', 'method' => 'post', 'id' => 'frmTarget', 'enctype' => 'multipart/form-data', 'autocomplete' => 'off', 'class' => 'needs-validation', 'novalidate']) }}
    <div class="row py-3">
        <div class="col-md-1"></div>
        <div class="col-lg-10">
            <div class="card shadow-none rounded-0 border">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-6">
                            <div class="form-group">
                                {{ Form::label('name', __('Firm/Advocate Name'), ['class' => 'col-form-label']) }}<x-required></x-required>
                                {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Firm/Advocate Name'), 'required' => 'required']) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <div class="form-group">
                                {{ Form::label('email', __('Email Address'), ['class' => 'col-form-label']) }}<x-required></x-required>
                                {{ Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('Enter Email'), 'required' => 'required']) }}
                            </div>
                        </div>
                        <!-- <div class="col-md-6 col-sm-6">
                            <div class="form-group">
                                {{ Form::label('password', __('Password'), ['class' => 'col-form-label']) }}<x-required></x-required>
                                {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter password'), 'required' => 'required', 'pattern' => '.{8,}', 'minlength' => '8', 'autocomplete' => 'new-password']) }}
                                <span class="small">{{ __('Minimum 8 characters') }}</span>
                            </div>
                        </div> -->
                        <div class="col-md-6 col-sm-6">
                            <div class="form-group">
                                {{ Form::label('phone_number', __('Phone Number'), ['class' => 'col-form-label']) }}<x-required></x-required>
                                {{ Form::text('phone_number', null, ['class' => 'form-control', 'placeholder' => __('Enter Phone Number'), 'required' => 'required']) }}
                                
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('age', __('Age'), ['class' => 'col-form-label']) }}
                                {{ Form::number('age', null, ['class' => 'form-control', 'placeholder' => __('Enter Age')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('company_name', __('Company Name'), ['class' => 'col-form-label']) }}
                                {{ Form::text('company_name', null, ['class' => 'form-control', 'placeholder' => __('Enter Company Name')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {!! Form::label('bank_details', __('Bank Details'), ['class' => 'col-form-label']) !!}
                                {{ Form::textarea('bank_details', null, ['class' => 'form-control', 'rows' => '3']) }}
                                <small class="text-xs">
                                    {{ __('Example : Bank : Bank Name <br> Account Number : 0000 0000 <br>') }}.
                                </small>
                            </div>
                        </div>
                        <div class="card-header">
                            <div class="row flex-grow-1">
                                <div class="col-md d-flex align-items-center">
                                    <h5 class="card-header-title">
                                        {{ __('Office Address') }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('ofc_address_line_1', __('Address Line 1'), ['class' => 'col-form-label']) }}
                                {{ Form::text('ofc_address_line_1', null, ['class' => 'form-control', 'placeholder' => __('Enter Address Line 1')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('ofc_address_line_2', __('Address Line 2'), ['class' => 'col-form-label']) }}
                                {{ Form::text('ofc_address_line_2', null, ['class' => 'form-control', 'placeholder' => __('Enter Address Line 2')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}
                                <select class="form-control" id="country" name="ofc_country">
                                    <option value="">{{ __('Select Country') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('state', __('State'), ['class' => 'col-form-label']) }}
                                <select class="form-control" id="state" name="ofc_state">
                                    <option value="">{{ __('Select State') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('ofc_city', __('City'), ['class' => 'col-form-label']) }}
                                {{ Form::text('ofc_city', null, ['class' => 'form-control', 'placeholder' => __('Enter City')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('ofc_zip_code', __('Zip/Postal Code'), ['class' => 'col-form-label']) }}
                                {{ Form::number('ofc_zip_code', null, ['class' => 'form-control', 'placeholder' => __('Enter Zip/Postal Code')]) }}
                            </div>
                        </div>
                        <div class="card-header">
                            <div class="row flex-grow-1">
                                <div class="col-md d-flex align-items-center">
                                    <h5 class="card-header-title">
                                        {{ __('Chamber Address') }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('home_address_line_1', __('Address Line 1'), ['class' => 'col-form-label']) }}
                                {{ Form::text('home_address_line_1', null, ['class' => 'form-control', 'placeholder' => __('Enter Address Line 1')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('home_address_line_2', __('Address Line 2'), ['class' => 'col-form-label']) }}
                                {{ Form::text('home_address_line_2', null, ['class' => 'form-control', 'placeholder' => __('Enter Address Line 2')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}
                                <select class="form-control" id="home_country" name="home_country">
                                    <option value="">{{ __('Select Country') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('state', __('State'), ['class' => 'col-form-label']) }}
                                <select class="form-control" id="home_state" name="home_state">
                                    <option value="">{{ __('Select State') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('home_city', __('City'), ['class' => 'col-form-label']) }}
                                {{ Form::text('home_city', null, ['class' => 'form-control', 'placeholder' => __('Enter City')]) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="form-group">
                                {{ Form::label('home_zip_code', __('Zip/Postal Code'), ['class' => 'col-form-label']) }}
                                {{ Form::number('home_zip_code', null, ['class' => 'form-control', 'placeholder' => __('Enter Zip/Postal Code')]) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
        <div class="col-md-1"></div>
        <div class="col-lg-10">
            <div class="card shadow-none rounded-0 border ">
                <div class="card-body p-2">
                    <div class="form-group col-12 d-flex justify-content-end col-form-label mb-0">
                        <a href="{{ route('advocate.index') }}" class="btn btn-secondary mx-1">{{ __('Cancel') }}</a>
                        <input type="submit" value="{{ __('Create') }}" id="advocate-store" class="btn btn-primary">
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{ Form::close() }}
@endsection

@push('custom-script')
    <script>
        $(document).ready(function() {
            $.ajax({
                url: "{{ route('get.country') }}",
                type: "GET",
                success: function(result) {
                    $.each(result.data, function(key, value) {
                        $("#country,#home_country").append('<option value="' + value.id + '">' +
                            value
                            .country + "</option>");

                    });
                },
            });

            $("#country").on("change", function() {
                var country_id = this.value;
                $("#state").html("");
                $.ajax({
                    url: "{{ route('get.state') }}",
                    type: "POST",
                    data: {
                        country_id: country_id,
                        _token: "{{ csrf_token() }}",
                    },
                    dataType: "json",
                    success: function(result) {
                        $.each(result.data, function(key, value) {
                            $("#state").append('<option value="' + value.id + '">' +
                                value.region + "</option>");
                        });
                        $("#city").html('<option value="">Select State First</option>');
                    },
                });
            });

            $("#home_country").on("change", function() {
                var country_id = this.value;
                $("#home_state").html("");
                $.ajax({
                    url: "{{ route('get.state') }}",
                    type: "POST",
                    data: {
                        country_id: country_id,
                        _token: "{{ csrf_token() }}",
                    },
                    dataType: "json",
                    success: function(result) {
                        $.each(result.data, function(key, value) {
                            $("#home_state").append('<option value="' + value.id +
                                '">' +
                                value.region + "</option>");
                        });
                        $("#home_city").html('<option value="">Select State First</option>');
                    },
                });
            });

            $("#state").on("change", function() {
                var state_id = this.value;
                $("#city").html("");
                $.ajax({
                    url: "{{ route('get.city') }}",
                    type: "POST",
                    data: {
                        state_id: state_id,
                        _token: "{{ csrf_token() }}",
                    },
                    dataType: "json",
                    success: function(result) {
                        $.each(result.data, function(key, value) {
                            $("#city").append('<option value="' + value.id + '">' +
                                value.city + "</option>");
                        });
                    },
                });
            });

            $("#home_state").on("change", function() {
                var state_id = this.value;
                $("#home_city").html("");
                $.ajax({
                    url: "{{ route('get.city') }}",
                    type: "POST",
                    data: {
                        state_id: state_id,
                        _token: "{{ csrf_token() }}",
                    },
                    dataType: "json",
                    success: function(result) {
                        $.each(result.data, function(key, value) {
                            $("#home_city").append('<option value="' + value.id + '">' +
                                value.city + "</option>");
                        });
                    },
                });
            });
        });
    </script>
@endpush
