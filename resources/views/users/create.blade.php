{{ Form::open(['route' => 'users.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter User Name'), 'required' => 'required']) !!}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('Email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
            {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('Enter User Email'), 'required' => 'required']) !!}
        </div>

        @if (Auth::user()->type != 'super admin')
        <div class="form-group col-md-6">
            {{ Form::label('role', __('Role'), ['class' => 'form-label']) }}<x-required></x-required>
            {!! Form::select('role', $roles, null, ['class' => 'form-control multi-select', 'required' => 'required']) !!}
            <div class="text-xs mt-1">
                Create role. <a class="dash-link" href="{{ route('roles.index') }}"><b>Click here</b></a>
            </div>
        </div>
        @endif

        <div class="col-md-6 mb-3 form-group mt-4">
            <label for="password_switch">{{ __('Login is enable') }}</label>
            <div class="form-check form-switch custom-switch-v1 float-end">
                <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer"
                    value="on" id="password_switch">
                <label class="form-check-label" for="password_switch"></label>
            </div>
        </div>

        <div class="form-group col-md-6 ps_div d-none">
            {!! Form::label('password', __('Password'), ['class' => 'form-label']) !!}<x-required></x-required>
            {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter User Password'), 'minlength' => '8']) }}
            <span class="small">{{ __('Minimum 8 characters') }}</span>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}