@php
    $docfile = \App\Models\Utility::get_file('uploads/documents/');
    $file_validation = App\Models\Utility::file_upload_validation();
@endphp
{{ Form::model($doc, ['route' => ['documents.update', $doc->id], 'method' => 'put', 'enctype' => 'multipart/form-data', 'id' => 'documentForm', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('name', __('Name of the Document'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Document Name'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('purpose', __('Purpose'), ['class' => 'form-label']) }}
                {{ Form::text('purpose', null, ['class' => 'form-control', 'placeholder' => __('Enter Purpose')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('type', __('Document Type'), ['class' => 'form-label']) }}<x-required></x-required>
                <select name="type" id="type" class="form-control multi-select" required>
                    <option value="" disabled selected>{{ __('Please select') }}</option>
                    @foreach ($types as $key => $typ)
                        <option value="{{ $key }}" {{ $key == $doc_typ ? 'selected' : '' }}>
                            {{ $typ }}
                        </option>
                    @endforeach
                </select>
                <div class="text-xs mt-1">
                    Create documenttype. <a class="dash-link" href="{{ route('doctype.index') }}"><b>Click here</b></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('cases', __('Cases'), ['class' => 'form-label']) }}
                <select class="form-control multi-select" name="cases" id="cases">
                    <option value="">{{ __('Select Case') }}</option>
                    @foreach ($cases as $case)
                        <option value="{{ $case->id }}" {{ $doc->cases == $case->id ? 'selected' : '' }}>
                            {{ $case->title }}
                        </option>
                    @endforeach
                </select>
                <div class="text-xs mt-1">
                    Create case. <a class="dash-link" href="{{ route('cases.index') }}"><b>Click here</b></a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => '1', 'maxlength' => '250', 'placeholder' => __('Enter Description')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('doc_link', __('Document Link'), ['class' => 'form-label']) }}
                {{ Form::url('doc_link', null, ['class' => 'form-control', 'placeholder' => __('Enter Document Link')]) }}
            </div>
        </div>

        <div class="col-md-6 choose-files fw-semibold">
            <label for="profile_pic">
                {{ Form::label('profile_pic', __('Document Upload'), ['class' => 'form-label']) }}
                <div class="bg-primary profile_update" style="max-width: 100% !important;">
                    <i class="ti ti-upload px-1"></i>
                    {{ __('Choose file here') }}
                </div>

                <input type="file" class="file" name="file[]" id="profile_pic" multiple style="width: 0px !important"
    onchange="
        if(this.files.length > 0) {
            document.getElementById('blah').src = window.URL.createObjectURL(this.files[0]);
            $('#fileName').html(Array.from(this.files).map(f => f.name).join(', '));
            image_upload_bar(this.files[0].name.split('.')[1]);
        }
    ">
                <span id="fileError" class="text-danger"
                    style="display: none;">{{ __('*Please select a file.') }}</span><br>
                <p><span
                        class="text-muted m-0">{{ __('Allowed file extension : ') }}{{ $file_validation['mimes'] }}</span>
                    <span class="text-muted">({{ __('Max Size In KB : ') }}{{ $file_validation['max_size'] }})</span>
                </p>
                <div id="progressContainer" class="p-0" style="display: none;">
                    <progress class="bg-primary progress rounded" id="progressBar" value="0"
                        max="100"></progress>
                    <span id="progressText">0%</span>
                </div>
                <img class="img_setting" id="blah" src="{{ $docfile . $doc->file }}" width="200px"
                    class="big-logo">
                <p id="fileName"></p>
            </label>
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>

{{ Form::close() }}
<script>
    $(document).ready(function() {

        $("#documentForm").submit(function(event) {
            $("#fileError").hide();

            var profile_pic = $("#profile_pic")[0];
            var file = profile_pic.files[0];

            if (file) {

                var maxSize = {{ $file_validation['max_size'] * 1024 }};
                if (file.size > maxSize) {
                    $("#fileError").text(
                            "*The file size should be less than {{ $file_validation['max_size'] }}KB.")
                        .show();
                    return false;
                }

                var allowedTypes = {!! json_encode(explode(',', $file_validation['mimes'])) !!};
                avar allowedTypes = {!! json_encode(explode(',', $file_validation['mimes'])) !!};
                allowedTypes = allowedTypes.map(function(type) {
                    return '.' + type; // Add a dot in front of each file type
                });

                var fileType = '.' + file.name.split('.').pop().toLowerCase();
                if ($.inArray(fileType, allowedTypes) === -1) {
                    $("#fileError").text(
                            "*Please upload a valid file type ({{ $file_validation['mimes'] }}).")
                        .show();
                    return false;
                }
                return true;
            }
            return true;
        });
    });
</script>
