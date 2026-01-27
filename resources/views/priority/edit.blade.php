<form method="post" action="{{ route('priority.update', $priority->id) }}" class="needs-validation" novalidate>
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('Name') }}</label><x-required></x-required>
                <div class="col-sm-12 col-md-12">
                    <input type="text" placeholder="{{ __('Name of the Category') }}" name="name"
                        class="form-control {{ $errors->has('name') ? ' is-invalid' : '' }}"
                        value="{{ $priority->name }}" autofocus required>
                    <div class="invalid-feedback">
                        {{ $errors->first('name') }}
                    </div>
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="exampleColorInput" class="form-label">{{ __('Color') }}</label><x-required></x-required>
                <div class="col-sm-12 col-md-12">
                    <input name="color" type="color"
                        class="form-control form-control-color {{ $errors->has('color') ? ' is-invalid' : '' }}"
                        value="{{ $priority->color }}" required>
                    <div class="invalid-feedback">
                        {{ $errors->first('color') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
    </div>
</form>
