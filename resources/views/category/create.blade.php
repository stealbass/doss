<form method="post" class="needs-validation" action="{{ route('category.store') }}" novalidate>
    @csrf
    <div class="modal-body">
        <div class="row">

            <div class="form-group col-md-6">
                <label class="form-label">{{ __('Name') }}</label><x-required></x-required>
                <div class="col-sm-12 col-md-12">
                    <input type="text" placeholder="{{ __('Name of the Category') }}" name="name"
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
                        value="255ff7" id="exampleColorInput" required>
                    <div class="invalid-feedback">
                        {{ $errors->first('color') }}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 form-group">
                    {{ Form::label('users', __('User'), ['class' => 'col-form-label']) }}
                    {{ Form::select('users[]', $users, null, ['class' => 'form-control multi-select ', 'id' => 'choices-multiple', 'multiple' => '']) }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        {{ Form::submit(__('Create'), ['class' => 'btn  btn-primary']) }}
    </div>
</form>
@push('custom-script')
    <script src="{{ asset('public/libs/select2/dist/js/select2.min.js') }}"></script>
    <script>
        if ($(".multi-select").length > 0) {
            $($(".multi-select")).each(function(index, element) {
                var id = $(element).attr('id');
                var multipleCancelButton = new Choices(
                    '#' + id, {
                        removeItemButton: true,
                    }
                );
            });
        }
        if ($(".select2").length) {
            $('.select2').select2({
                "language": {
                    "noResults": function() {
                        return "No result found";
                    }
                },
            });
        }
    </script>
@endpush
