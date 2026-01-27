{{ Form::model($timesheet, ['route' => ['timesheet.update', $timesheet->id], 'method' => 'put', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('case', __('Case'), ['class' => 'form-label']) !!}<x-required></x-required>
            <select class="form-control multi-select" name="case" id="case" required>
                <option value="">{{ __('Select Case') }}</option>
                @foreach ($cases as $case)
                    <option value="{{ $case->id }}" {{ $timesheet->case == $case->id ? 'selected' : '' }}>
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
                name="date" required value="{{ $timesheet->date }}">
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('particulars', __('Particulars'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('particulars', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter particulars'),
                'required' => 'required',
            ]) !!}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('time', __('Time Spent (in Hours)'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::time('time', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'Select time']) }}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('member', __('Advocate'), ['class' => 'form-label']) !!}<x-required></x-required>
            <div id="advocate_div">
                {!! Form::select('member', $members, $timesheet->member, [
                    'class' => 'form-control multi-select',
                    'id' => 'member',
                ]) !!}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
