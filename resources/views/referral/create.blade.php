{{ Form::open(['route' => 'payout.store', 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('request_amount', __('Request Amount'), ['class' => 'form-label']) !!}
            {!! Form::text('request_amount', !isset($valideamount) || is_null($valideamount) ? 0 : $valideamount, [
                'rows' => 4,
                'class' => 'form-control',
                'required' => 'required',
            ]) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Send') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
