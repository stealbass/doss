@php
    $chatgpt_enable = App\Models\Utility::getChatGPTSettings();
@endphp

{{ Form::open(['url' => 'deal', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if ($chatgpt_enable)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true" data-url="{{ route('generate', ['deal']) }}"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
                    data-title="{{ __('Generate content with AI') }}" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>{{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-6">
            {{ Form::label('name', __('Deal Name'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Deal Name'), 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('price', __('Price'), ['class' => 'col-form-label']) }}
            {{ Form::number('price', 0, ['class' => 'form-control', 'placeholder' => __('Enter Price'), 'min' => 0]) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('clients', __('Advocates'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::select('clients[]', $clients, null, ['class' => 'form-control multi-select', 'placeholder' => __('Select Advocates'), 'id' => 'choices-multiple', 'multiple' => '', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('phone_no', __('Phone No'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::tel('phone_no', null, ['class' => 'form-control', 'placeholder' => __('Enter Phone No'), 'pattern' => '^\+\d{1,3}\d{9,13}$', 'required' => 'required']) }}
            <div class=" text-xs text-danger">
                {{ __('Please use with country code. (ex. +91)') }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Create'), ['class' => 'btn  btn-primary']) }}
</div>
{{ Form::close() }}
@push('custom-script')
    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
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
    </script>
@endpush
