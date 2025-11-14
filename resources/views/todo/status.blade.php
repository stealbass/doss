{{ Form::model($todo, ['route' => ['to-do.status.update', $todo->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}
@csrf
@method('put')
<div class="modal-body">
    @if ($todo->status == 1)
        <p>{{ __('You can\'t edit to-do after marking as complete. Are you sure?') }}</p>
    @else
        <p>{{ __('To-do already complete') }}</p>
    @endif
</div>
<div class="form-group text-right">
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    @if ($todo->status == 1)
        <button class="btn btn-primary ms-2" value="{{ $todo->status }}" type="submit">{{ __('Yes') }}</button>
    @else
        <input type="button" value="{{ __('Ok') }}" class="btn btn-primary ms-2" data-bs-dismiss="modal">
    @endif
</div>

{{ Form::close() }}
