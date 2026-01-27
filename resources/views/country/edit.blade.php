{{ Form::model($country, ['route' => ['country.update', $country->id], 'method' => 'put', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('country', __('Country'), ['class' => 'form-label']) !!}<x-required></x-required>
            {{ Form::text('country', null, ['class' => 'form-control ', 'required' => 'required', 'placeholder' => __('Enter Country Name'), 'id' => 'country']) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
