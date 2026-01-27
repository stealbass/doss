{{ Form::model($bench, ['route' => ['bench.update', $bench->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Circuit/Devision Name'),
                'required' => 'required',
            ]) !!}
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('court', __('High Court'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::select('highcourt_id', $highcourts, null, [
                'class' => 'form-control multi-select',
                'id' => 'highcourt_id',
                'placeholder' => __('Select Highcourt'),
                'required' => 'required',
            ]) !!}
            <div class="text-xs mt-1">
                Create High Court. <a class="dash-link" href="{{ route('highcourts.index') }}"><b>Click here</b></a>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
