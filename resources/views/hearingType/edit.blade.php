{{ Form::model($HearingType, ['route' => ['hearingType.update', $HearingType->id], 'method' => 'put', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('type', __('Type'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('type', null, ['class' => 'form-control', 'required' => 'required']) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'maxlength' => '150']) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
