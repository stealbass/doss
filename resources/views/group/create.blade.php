{{ Form::open(['route' => 'groups.store', 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Group Name'),
                'required' => 'required',
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('members', __('Select Team Member'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::select('members[]', $users, null, [
                'class' => 'form-control multi-select',
                'id' => 'choices-multiple',
                'multiple',
                'data-role' => 'tagsinput',
                'required' => 'required',
            ]) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
