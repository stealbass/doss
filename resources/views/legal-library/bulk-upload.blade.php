@extends('layouts.app')

@section('page-title', __('Import Multiple Documents'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('legal-library.index') }}">{{ __('Legal Library') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('legal-library.documents', $category->id) }}">{{ $category->name }}</a></li>
    <li class="breadcrumb-item">{{ __('Import Multiple Documents') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ __('Import Multiple Documents') }}</h5>
                            <small class="text-muted">{{ __('Category') }}: <strong>{{ $category->name }}</strong></small>
                        </div>
                        <div>
                            <span class="badge bg-info" id="fileCount">0 {{ __('file(s) selected') }}</span>
                        </div>
                    </div>
                </div>
                
                <form action="{{ route('legal-library.bulk-upload.store', $category->id) }}" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      id="bulkUploadForm">
                    @csrf
                    
                    <div class="card-body">
                        <!-- Info Alert -->
                        <div class="alert alert-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-info-circle me-2" style="font-size: 24px;"></i>
                                <div>
                                    <strong>{{ __('Instructions:') }}</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>{{ __('You can select multiple PDF files at once') }}</li>
                                        <li>{{ __('Maximum file size: 50MB per file') }}</li>
                                        <li>{{ __('Only PDF files are accepted') }}</li>
                                        <li>{{ __('The document title will be the filename (without extension)') }}</li>
                                        <li>{{ __('You can edit titles and descriptions later') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Drag & Drop Zone -->
                        <div class="drop-zone" id="dropZone">
                            <div class="drop-zone-content">
                                <i class="ti ti-cloud-upload" style="font-size: 64px; color: #6c757d;"></i>
                                <h4 class="mt-3 mb-2">{{ __('Drag & Drop PDF Files Here') }}</h4>
                                <p class="text-muted mb-3">{{ __('or') }}</p>
                                <button type="button" class="btn btn-primary btn-lg" id="browseBtn" onclick="document.getElementById('fileInput').click(); return false;">
                                    <i class="ti ti-file-upload"></i> {{ __('Browse Files') }}
                                </button>
                                <input type="file" 
                                       name="files[]" 
                                       id="fileInput" 
                                       style="display: none;" 
                                       accept=".pdf" 
                                       multiple>
                            </div>
                        </div>

                        <!-- File List -->
                        <div id="fileListContainer" class="mt-4" style="display: none;">
                            <h6 class="mb-3">{{ __('Selected Files:') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="fileListTable">
                                    <thead>
                                        <tr>
                                            <th width="50px">#</th>
                                            <th>{{ __('File Name') }}</th>
                                            <th width="120px">{{ __('Size') }}</th>
                                            <th width="200px">{{ __('Will be titled as') }}</th>
                                            <th width="80px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fileList">
                                        <!-- Files will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('legal-library.documents', $category->id) }}" 
                               class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-success btn-lg" id="uploadBtn" disabled>
                                <i class="ti ti-upload"></i> {{ __('Upload All Documents') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    .drop-zone {
        border: 3px dashed #dee2e6;
        border-radius: 10px;
        padding: 60px 20px;
        text-align: center;
        background-color: #f8f9fa;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .drop-zone:hover {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    
    .drop-zone.drag-over {
        border-color: #28a745;
        background-color: #d4edda;
        transform: scale(1.02);
    }
    
    .drop-zone-content {
        /* Allow clicks on button - removed pointer-events: none */
    }
    
    .file-item {
        transition: all 0.3s;
    }
    
    .file-item:hover {
        background-color: #f8f9fa;
    }
    
    .file-size {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .file-title {
        font-size: 0.875rem;
        color: #28a745;
        font-weight: 500;
    }
</style>
@endpush

@push('custom-script')
<script>
    console.log('🚀 Bulk upload script loaded!');
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('📋 DOMContentLoaded event fired');
        
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const browseBtn = document.getElementById('browseBtn');
        const fileListContainer = document.getElementById('fileListContainer');
        const fileList = document.getElementById('fileList');
        const fileCount = document.getElementById('fileCount');
        const uploadBtn = document.getElementById('uploadBtn');
        
        console.log('✅ Elements found:', {
            dropZone: !!dropZone,
            fileInput: !!fileInput,
            browseBtn: !!browseBtn,
            fileListContainer: !!fileListContainer,
            fileList: !!fileList,
            fileCount: !!fileCount,
            uploadBtn: !!uploadBtn
        });
        
        if (!browseBtn || !fileInput) {
            console.error('❌ Required elements not found!');
            return;
        }
        
        let selectedFiles = [];
        
        // Click on browse button to open file browser
        browseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Browse button clicked');
            fileInput.click();
        });
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('drag-over');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('drag-over');
        }, false);
    });
    
    // Handle dropped files
    dropZone.addEventListener('drop', function(e) {
        console.log('Drop event triggered');
        const dt = e.dataTransfer;
        const files = dt.files;
        console.log('Files dropped:', files.length);
        handleFiles(files);
    });
    
    // Handle selected files from input
    fileInput.addEventListener('change', function(e) {
        console.log('File input changed, files:', e.target.files.length);
        handleFiles(e.target.files);
    });
    
    function handleFiles(files) {
        console.log('handleFiles called with', files.length, 'files');
        
        // Convert FileList to Array and filter only PDFs
        const pdfFiles = Array.from(files).filter(file => {
            if (file.type === 'application/pdf') {
                return true;
            } else {
                console.warn('Skipped non-PDF file:', file.name);
                return false;
            }
        });
        
        console.log('PDF files filtered:', pdfFiles.length);
        
        if (pdfFiles.length === 0) {
            alert('{{ __("Please select at least one PDF file.") }}');
            return;
        }
        
        // Check file sizes (50MB max)
        const oversizedFiles = pdfFiles.filter(file => file.size > 50 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            alert('{{ __("Some files exceed the maximum size of 50MB:") }}\n' + 
                  oversizedFiles.map(f => f.name).join('\n'));
            return;
        }
        
        selectedFiles = pdfFiles;
        console.log('selectedFiles updated:', selectedFiles.length);
        updateFileList();
        updateUI();
    }
    
    function updateFileList() {
        console.log('updateFileList called, files:', selectedFiles.length);
        fileList.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const title = file.name.replace('.pdf', '');
            const sizeKB = (file.size / 1024).toFixed(2);
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            const displaySize = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';
            
            const row = document.createElement('tr');
            row.className = 'file-item';
            row.innerHTML = `
                <td class="text-center">${index + 1}</td>
                <td>
                    <i class="ti ti-file-text text-danger me-2"></i>
                    <strong>${file.name}</strong>
                </td>
                <td class="file-size">${displaySize}</td>
                <td class="file-title">${title}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-file-btn" data-index="${index}">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            `;
            fileList.appendChild(row);
        });
    }
    
    // Event delegation for remove buttons
    fileList.addEventListener('click', function(e) {
        if (e.target.closest('.remove-file-btn')) {
            const btn = e.target.closest('.remove-file-btn');
            const index = parseInt(btn.getAttribute('data-index'));
            console.log('Removing file at index:', index);
            removeFile(index);
        }
    });
    
    function removeFile(index) {
        console.log('removeFile called for index:', index);
        selectedFiles.splice(index, 1);
        updateFileList();
        updateUI();
        
        // Update file input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    function updateUI() {
        const count = selectedFiles.length;
        console.log('updateUI called, count:', count);
        fileCount.textContent = count + ' {{ __("file(s) selected") }}';
        
        if (count > 0) {
            console.log('Showing file list container');
            fileListContainer.style.display = 'block';
            uploadBtn.disabled = false;
        } else {
            console.log('Hiding file list container');
            fileListContainer.style.display = 'none';
            uploadBtn.disabled = true;
        }
    }
    
    // Form submission with progress indication
    const bulkForm = document.getElementById('bulkUploadForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('{{ __("Please select at least one file.") }}');
                return;
            }
            
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __("Uploading...") }}';
        });
    }
    
    }); // End DOMContentLoaded
</script>
@endpush
