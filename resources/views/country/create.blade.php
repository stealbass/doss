{{ Form::open(['route' => 'country.store', 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('country', __('Country'), ['class' => 'form-label', 'id' => 'adv_label']) !!}<x-required></x-required>
            {{ Form::text('country', null, ['class' => 'form-control ', 'placeholder' => __('Enter Country Name'), 'required' => 'required']) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
