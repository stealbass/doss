@php
    $chatgpt_enable = App\Models\Utility::getChatGPTSettings();
@endphp

@if (isset($task))
    {{ Form::model($task, ['route' => ['deal.tasks.update', $deal->id, $task->id], 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
@else
    {{ Form::open(['route' => ['deal.tasks.store', $deal->id], 'class' => 'needs-validation', 'novalidate']) }}
@endif
<div class="modal-body">
    <div class="row">
        @if ($chatgpt_enable)
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['deal task']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                    class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>{{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group">
            {{ Form::label('name', __('Name'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'Task Name']) }}
        </div>
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    {{ Form::label('date', __('Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    {{ Form::date('date', new \DateTime(), ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    {{ Form::label('time', __('Time'), ['class' => 'col-form-label']) }}<x-required></x-required>
                    {{ Form::time('time', null, ['class' => 'form-control', 'required' => 'required']) }}
                </div>
            </div>
        </div>
        <div class="form-group">
            {{ Form::label('priority', __('Priority'), ['class' => 'col-form-label']) }}<x-required></x-required>
            <select class="form-control multi-select" id="priority" name="priority" required data-toggle="select">
                @foreach ($priorities as $key => $priority)
                    <option value="{{ $key }}" @if (isset($task) && $task->priority == $key) selected @endif>
                        {{ __($priority) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            {{ Form::label('status', __('Status'), ['class' => 'col-form-label']) }}<x-required></x-required>
            <select class="form-control multi-select" id="status" name="status" data-toggle="select" required>
                @foreach ($status as $key => $st)
                    <option value="{{ $key }}" @if (isset($task) && $task->status == $key) selected @endif>
                        {{ __($st) }}</option>
                @endforeach
            </select>
        </div>

    </div>
</div>
@if (isset($task))
    <div class="modal-footer">
        <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        {{ Form::submit(__('Update'), ['class' => 'btn  btn-primary']) }}
    </div>
@else
    <div class="modal-footer">
        <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        {{ Form::submit(__('Create'), ['class' => 'btn  btn-primary']) }}
    </div>
@endif
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
