{{ Form::model($grp, ['route' => ['groups.update', $grp->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Name')) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Group Name'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('members', __('Select Team Member'), ['class' => 'form-label']) !!}<x-required></x-required>
                {!! Form::select('members[]', $users, $my_members, [
                    'class' => 'form-control multi-select',
                    'id' => 'choices-multiple',
                    'multiple',
                    'data-role' => 'tagsinput',
                    'required' => 'required',
                ]) !!}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" id="saverating" class="btn btn-primary">
</div>
{{ Form::close() }}
