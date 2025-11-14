{{ Form::model($employee, ['route' => ['member.change.password', $employee->id], 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="form-group col-md-12">
        {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}<x-required></x-required>
        <input id="password" type="password" placeholder="{{ __('Enter Password') }}"
            class="form-control @error('password') is-invalid @enderror" name="password" required
            autocomplete="new-password" minlength="8">
        @error('password')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>
    <div class="form-group col-md-12">
        {{ Form::label('password-confirm', __('Confirm Password'), ['class' => 'form-label']) }}<x-required></x-required>
        <input id="password-confirm" type="password" class="form-control" name="confirm_password"
            placeholder="{{ __('Enter Confirm Password') }}" required autocomplete="new-password" minlength="8">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal"> {{ __('Cancel') }} </button>
    {{ Form::submit(__('Reset'), ['class' => 'btn btn-primary']) }}
</div>
{{ Form::close() }}
