{{ Form::open(['route' => 'case-notes.store', 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('note', __('Note / Commentaire'), ['class' => 'form-label']) }}
            {{ Form::textarea('note', null, ['class' => 'form-control', 'rows' => 4, 'required' => 'required', 'placeholder' => __('Saisissez votre note ou commentaire...')]) }}
        </div>
        <input type="hidden" name="case_id" value="{{ $case->id }}">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Ajouter la note') }}</button>
</div>
{{ Form::close() }}
