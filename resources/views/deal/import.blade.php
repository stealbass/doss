{{ Form::open(['route' => ['deals.import'], 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-6">
            {{ Form::label('file', __('Download sample deal CSV file'), ['class' => 'col-form-label']) }}
            <a href="{{ asset(Storage::url('uploads/sample')) . '/deal-client.csv' }}"
                class="btn btn-sm btn-primary btn-icon">
                <i class="fa fa-download"></i>
            </a>
        </div>
        <div class="col-md-12">
            {{ Form::label('file', __('Select CSV File'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file"
                        required>
                </label>
                <p class="upload_file"></p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn  btn-primary">{{ __('Upload') }}</button>
</div>
{{ Form::close() }}
