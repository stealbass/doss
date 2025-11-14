{{ Form::model($state, ['route' => ['state.update', $state->id], 'method' => 'put', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('country', __('Country'), ['class' => 'col-form-label']) }}<x-required></x-required>
            <select class="form-control" id="" name="country" required>
                <option value="" disabled selected>{{ __('Select Country') }}</option>
                @foreach ($countries as $key => $count)
                    <option value="{{ $key }}" {{ $country->country == $count ? 'selected' : '' }}>
                        {{ $count }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('state', __('State'), ['class' => 'form-label', 'id' => 'adv_label']) !!}<x-required></x-required>
            {{ Form::text('state', !empty($state->region) ? $state->region : '', ['class' => 'form-control ', 'placeholder' => __('Enter State Name'), 'required' => 'required', 'state' => 'state']) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
