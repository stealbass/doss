{{ Form::model($cause, ['route' => ['cause.update', $cause->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('court', __('Courts/Tribunal'), ['class' => 'form-label']) !!}
            {{ Form::select('court', $courts, null, ['class' => 'form-control  item multi-select', 'id' => 'court', 'required' => 'required']) }}
             <div class="text-xs mt-1">
                Create Courts/Tribunal. <a class="dash-link" href="{{ route('courts.index') }}"><b>Click here</b></a>
            </div>
        </div>

        <div class="form-group col-md-12 d-none" id="highcourt_div">
            {!! Form::label('highcourt', __('High Court'), ['class' => 'form-label']) !!}
        </div>

        <div class="form-group col-md-12 d-none" id="bench_div">
            {!! Form::label('court', __('Circuit/Devision'), ['class' => 'form-label']) !!}
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('causelist_by', __('Causelist By'), ['class' => 'form-label']) !!}
            {{ Form::select('causelist_by', ['Advocate Name' => 'Advocate Name', 'Keyword' => 'Keyword', 'Party Name' => 'Party Name', 'Judge Name' => 'Judge Name'], '', ['class' => 'form-control  item multi-select', 'required' => 'required']) }}
        </div>

        <div class="form-group col-md-12">
            {!! Form::label('advocate_name', __('Advocate Name'), ['class' => 'form-label', 'id' => 'adv_label']) !!}
            {{ Form::text('advocate_name', null, ['class' => 'form-control ', 'placeholder' => __('Enter Advocate Name'), 'required' => 'required']) }}
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
