{{ Form::model($deal, ['route' => ['deal.sources.update', $deal->id], 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group">
            <div class="row gutters-xs">
                @foreach ($sources as $source)
                    <div class="col-12 custom-control custom-checkbox mt-2 mb-2">
                        {{ Form::checkbox('sources[]', $source->id, $selected && array_key_exists($source->id, $selected) ? true : false, ['class' => 'form-check-input ', 'id' => 'sources_' . $source->id]) }}
                        {{ Form::label('sources_' . $source->id, ucfirst($source->name), ['class' => 'custom-control-label ml-4 bg-' . $source->color]) }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Add'), ['class' => 'btn  btn-primary']) }}
</div>
{{ Form::close() }}
