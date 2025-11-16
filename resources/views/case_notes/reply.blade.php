{{ Form::open(['route' => 'case-notes.reply', 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('note', __('Répondre'), ['class' => 'form-label']) }}
            {{ Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3, 'required' => 'required', 'placeholder' => __('Saisissez votre réponse...')]) }}
        </div>
        <input type="hidden" name="case_id" value="{{ $note->case_id }}">
        <input type="hidden" name="parent_id" value="{{ $note->id }}">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Répondre') }}</button>
</div>
{{ Form::close() }}
