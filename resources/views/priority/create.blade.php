<form action="{{ route('priority.store') }}" method="post" class="needs-validation" novalidate>
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('Name') }}</label><x-required></x-required>
                <div class="col-sm-12 col-md-12">
                    <input type="text" placeholder="{{ __('Name of the Priority') }}" name="name"
                        class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ old('name') }}"
                        required>
                    <div class="invalid-feedback">
                        {{ $errors->first('name') }}
                    </div>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="exampleColorInput" class="form-label">{{ __('Color') }}</label><x-required></x-required>
                <div class="col-sm-12 col-md-12">
                    <input name="color" type="color"
                        class=" form-control  form-control-color {{ $errors->has('color') ? ' is-invalid' : '' }}"
                        value="255ff7" id="exampleColorInput">
                    <div class="invalid-feedback">
                        {{ $errors->first('color') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button class="btn btn-primary" type="submit">{{ __('Create') }}</button>
    </div>
</form>
