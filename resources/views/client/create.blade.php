{{ Form::open(['route' => 'client.store', 'method' => 'post', 'autocomplete' => 'off', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Client Name'),
                'required' => 'required',
            ]) !!}
        </div>

        <div class="form-group col-md-12">
            {{ Form::label('Email', __('Email'), ['class' => 'form-label']) }}
            {!! Form::email('email', $randomEmail ?? 'client@exemple.com', [
                'class' => 'form-control',
                'placeholder' => __('Enter Client Email'),
                
            ]) !!}
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('password', __('Password'), ['class' => 'form-label']) !!}
            {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter Client Password'), 'minlength' => '8', 'autocomplete' => 'new-password']) }}
            <span class="small">{{ __('Minimum 8 characters') }}</span>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
