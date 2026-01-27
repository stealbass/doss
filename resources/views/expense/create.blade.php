{{ Form::open(['route' => 'expenses.store', 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('type', __('Type'), ['class' => 'form-label']) !!}
            <select class="form-control" name="type" id="type">
                <option value="expense">{{ __('Dépense') }}</option>
                <option value="income">{{ __('Encaissement') }}</option>
            </select>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('case', __('Case'), ['class' => 'form-label']) !!}
            <select class="form-control multi-select" name="case" id="case" placeholder="Select Case">
                <option value="">{{ __('Select Case') }}</option>
                @foreach ($cases as $case)
                    <option value="{{ $case->id }}">{{ $case->title }}</option>
                @endforeach
            </select>
            <div class="text-xs mt-1">
                Create case. <a class="dash-link" href="{{ route('cases.index') }}"><b>Click here</b></a>
            </div>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <input id="timesheet_date" placeholder="DD/MM/YYYY" data-input class="form-control text-center"
                name="date" />
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('particulars', __('Particulars'), ['class' => 'form-label']) !!}
            {!! Form::text('particulars', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Particulars'),
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('money', __('Amount'), ['class' => 'form-label']) !!}
            {!! Form::number('money', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Amount'),
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('method', __('Payment Method'), ['class' => 'form-label']) !!}
            {!! Form::select('method', $payTypes, null, ['class' => 'form-control multi-select']) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('member', __('Advocate/Member'), ['class' => 'form-label']) !!}
            <div id="advocate_div">
                <select class="form-control multi-select" name="member" id="member">
                </select>
            </div>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('notes', __('Notes'), ['class' => 'form-label']) !!}
            {!! Form::text('notes', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Notes'),
            ]) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
