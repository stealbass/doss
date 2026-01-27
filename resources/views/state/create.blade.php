{{ Form::open(['route' => 'state.store', 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}<x-required></x-required>
            <select class="form-control" id="state_country" name="country" required>
                <option value="" disabled selected>{{ __('Select Country') }}</option>
            </select>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('state', __('State'), ['class' => 'form-label', 'id' => 'adv_label']) !!}<x-required></x-required>
            {{ Form::text('state', null, ['class' => 'form-control ', 'placeholder' => __('Enter State Name'), 'required' => 'required', 'state' => 'state']) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
