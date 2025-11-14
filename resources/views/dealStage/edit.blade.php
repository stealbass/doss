{{ Form::model($dealStage, ['route' => ['dealStage.update', $dealStage->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group">
            {{ Form::label('name', __('Name'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Deal Stage Name'), 'required' => 'required']) }}
        </div>
        <div class="form-group">
            {{ Form::label('name', __('Pipeline'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::select('pipeline_id', $pipelines, null, ['class' => 'form-control multi-select', 'id' => 'pipeline_id', 'data-toggle' => 'select', 'required' => 'required']) }}
            <div class="text-xs mt-1">
                Create Pipeline. <a class="dash-link" href="{{ route('pipeline.index') }}"><b>Click here</b></a>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Update'), ['class' => 'btn  btn-primary']) }}
</div>
{{ Form::close() }}