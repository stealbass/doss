{{ Form::model($todo, ['route' => ['to-do.update', $todo->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
@csrf
@method('put')
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('title', __('Title'), ['class' => 'form-label']) !!}<x-required></x-required>
            {!! Form::text('title', null, [
            'rows' => 4,
            'class' => 'form-control',
            'placeholder' => __('Enter Title'),
            'required' => 'required',
            ]) !!}
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('relate_to', __('Relate to (Case(s))'), ['class' => 'form-label']) !!}<x-required></x-required>
            <select class="form-control multi-select" name="relate_to" id="relate_to" required>
                <option value="">{{ __('Select Case') }}</option>
                @foreach ($cases as $case)
                <option value="{{ $case->id }}" {{ $todo->relate_to == $case->id ? 'selected' : '' }}>
                    {{ $case->title }}
                </option>
                @endforeach
            </select>
            <div class="text-xs mt-1">
                Create Case. <a class="dash-link" href="{{ route('cases.index') }}"><b>Click here</b></a>
            </div>
        </div>

        <div class="form-group col-md-12">
            {{ Form::label('due_date', __('Assigned Date'), ['class' => 'form-label']) }}<x-required></x-required>
            <input value="{{ $todo->start_date }}" placeholder="DD/MM/YYYY" data-input
                class="form-control text-center single-date" name="assigned_date" required />
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('due_date', __('Due Date'), ['class' => 'form-label']) }}<x-required></x-required>
            <input value="{{ $todo->end_date }}" placeholder="DD/MM/YYYY" data-input
                class="form-control text-center single-date" name="due_date" required />
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('assign_to', __('Assign To (Advocates/Members)'), ['class' => 'form-label']) !!}
            <div id="advocate_div">
                {!! Form::select('assign_to[]', $members, $assign_to, [
                'class' => 'form-control multi-select',
                'id' => 'choices-multiple1',
                'multiple',
                ]) !!}
            </div>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('assign_to', __('Priority'), ['class' => 'form-label']) !!}<x-required></x-required>
            <select name="priority" id="priority" class="form-control multi-select" required>
                @foreach ($priorities as $priority)
                <option value="{{ $priority }}" {{ $todo->priority == $priority ? 'selected' : '' }}>
                    {{ $priority }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea('description', null, [
            'rows' => 2,
            'class' => 'form-control',
            'placeholder' => __('Enter Description'),
            'maxlength' => '150',
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