@php
    $chatgpt_enable = App\Models\Utility::getChatGPTSettings();
@endphp

{{ Form::model($type, ['route' => ['doctype.update', $type->id], 'method' => 'put', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        @if ($chatgpt_enable)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['document_type']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                    class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>{{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Type'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Document Type'),
                'required' => 'required',
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea('description', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Description'),
                'rows' => '3',
                'maxlength' => '150',
            ]) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
