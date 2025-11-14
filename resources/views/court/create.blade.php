{{ Form::open(['route' => 'courts.store', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('name', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Court Name'),
                'required' => 'required',
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('type', __('Type'), ['class' => 'form-label']) !!}
            {!! Form::text('type', null, ['class' => 'form-control', 'placeholder' => __('Enter Court Type')]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('location', __('Location'), ['class' => 'form-label']) !!}
            {!! Form::text('location', null, ['class' => 'form-control', 'placeholder' => __('Enter Location')]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('address', __('Address'), ['class' => 'form-label']) !!}
            {!! Form::text('address', null, ['class' => 'form-control', 'placeholder' => __('Enter Address')]) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
