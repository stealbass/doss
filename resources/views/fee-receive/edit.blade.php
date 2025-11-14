{{ Form::model($expense, ['route' => ['fee-receive.update', $expense->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('case', __('Case'), ['class' => 'form-label']) !!}<x-required></x-required>
            <select class="form-control multi-select" name="case" id="case" required>
                <option value="">{{ __('Select Case') }}</option>
                @foreach ($cases as $case)
                    <option value="{{ $case->id }}" {{ $expense->case == $case->id ? 'selected' : '' }}>
                        {{ $case->title }}
                    </option>
                @endforeach
            </select>
            <div class="text-xs mt-1">
                Create case. <a class="dash-link" href="{{ route('cases.index') }}"><b>Click here</b></a>
            </div>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}<x-required></x-required>
            <input id="timesheet_date" placeholder="DD/MM/YYYY" data-input class="form-control text-center"
                name="date" required value="{{ $expense->date }}" required>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('particulars', __('Particulars'), ['class' => 'form-label']) !!}<
            {!! Form::text('particulars', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Particulars'),
                
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('money', __('Money Spent'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::number('money', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Received Fee'),
                'required' => 'required',
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('method', __('Payment Method'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::select('method', $payTypes, null, ['class' => 'form-control multi-select', 'required' => 'required']) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('member', __('Client'), ['class' => 'form-label']) !!}<x-required></x-required>
            <div id="advocate_div">
                {!! Form::select('member', $members, $expense->member, [
                    'class' => 'form-control multi-select',
                    'id' => 'member',
                    'required' => 'required',
                ]) !!}
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
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
