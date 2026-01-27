{{ Form::open(['route' => ['store.language'], 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-12">
            {{ Form::label('code', __('Language Code'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::text('code', '', ['class' => 'form-control','placeholder' => __('Enter Language Code'), 'required' => 'required']) }}
            @error('code')
                <span class="invalid-code" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group">
            {{ Form::label('fullname', __('Language Full Name'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::text('fullname', '', ['class' => 'form-control','placeholder' => __('Enter Language Full Name'), 'required' => 'required']) }}
            @error('fullname')
                <span class="invalid-code" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
