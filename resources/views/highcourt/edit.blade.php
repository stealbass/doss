{{ Form::model($highcourt, ['route' => ['highcourts.update', $highcourt->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Highcourt Name'),
                'required' => 'required',
            ]) !!}
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('court', __('Courts/Tribunal'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::select('court_id', $courts, null, [
                'class' => 'form-control multi-select',
                'placeholder' => __('Select Court'),
                'id' => 'member',
                'required' => 'required',
            ]) !!}
            <div class="text-xs mt-1">
                Create Courts/Tribunal. <a class="dash-link" href="{{ route('courts.index') }}"><b>Click here</b></a>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
