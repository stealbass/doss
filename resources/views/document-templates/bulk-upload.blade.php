@extends('layouts.app')

@section('page-title', __('Import Multiple Templates'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('document-templates.index') }}">{{ __('Document Templates') }}</a></li>
    <li class="breadcrumb-item">{{ __('Import Multiple Templates') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ __('Import Multiple Templates') }}</h5>
                            <small class="text-muted">{{ __('Upload several templates at once') }}</small>
                        </div>
                        <div>
                            <span class="badge bg-info" id="fileCount">0 {{ __('file(s) selected') }}</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('document-templates.bulk-upload.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      id="bulkUploadForm">
                    @csrf

                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-info-circle me-2" style="font-size: 24px;"></i>
                                <div>
                                    <strong>{{ __('Instructions:') }}</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>{{ __('You can select multiple files at once') }}</li>
                                        <li>{{ __('Maximum file size: 10MB per file') }}</li>
                                        <li>{{ __('Accepted formats: Word, PDF, Excel') }}</li>
                                        <li>{{ __('The template title will be the filename (without extension)') }}</li>
                                        <li>{{ __('You can edit titles and descriptions later') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label"><strong>{{ __('Category') }} <span class="text-danger">*</span></strong></label>
                                    <div class="input-group">
                                        <select name="category_id" id="templateCategorySelect" class="form-control" required>
                                            <option value="">{{ __('Select a category') }}</option>
                                            @foreach(($categories ?? collect()) as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Ajouter une catégorie">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label"><strong>{{ __('Document Type') }} <span class="text-danger">*</span></strong></label>
                                    <select name="template_type" class="form-control" required>
                                        <option value="">{{ __('Select...') }}</option>
                                        <option value="contract">Contrat</option>
                                        <option value="act">Acte</option>
                                        <option value="form">Formulaire</option>
                                        <option value="letter">Lettre</option>
                                        <option value="calculator">Calculateur</option>
                                        <option value="checklist">Checklist</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label"><strong>{{ __('Country') }} <span class="text-danger">*</span></strong></label>
                                    <select name="country" class="form-control" required>
                                        <option value="">{{ __('Select a country') }}</option>
                                        @foreach(($countries ?? []) as $code => $country)
                                            <option value="{{ $code }}">{{ $country['flag'] ?? '' }} {{ $code }} - {{ $country['name'] ?? $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><strong>{{ __('Required Plan') }} <span class="text-danger">*</span></strong></label>
                                    <select name="allowed_plans" class="form-control" required>
                                        <option value="free">Gratuit</option>
                                        <option value="student">Étudiant</option>
                                        <option value="professional">Professionnel</option>
                                        <option value="enterprise">Cabinet/Entreprise</option>
                                    </select>
                                    <small class="text-muted">{{ __('This plan will be applied to all uploaded templates') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><strong>{{ __('Description (optional)') }}</strong></label>
                                    <textarea name="description" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="hidden" name="is_mobile_visible" value="0">
                                    <input type="checkbox" name="is_mobile_visible" value="1" class="form-check-input" id="mobileVisible" checked>
                                    <label class="form-check-label" for="mobileVisible">{{ __('Visible on mobile') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="drop-zone" id="dropZone">
                            <div class="drop-zone-content">
                                <i class="ti ti-cloud-upload" style="font-size: 64px; color: #6c757d;"></i>
                                <h4 class="mt-3 mb-2">{{ __('Drag & Drop Files Here') }}</h4>
                                <p class="text-muted mb-3">{{ __('or') }}</p>
                                <button type="button" class="btn btn-primary btn-lg" id="browseBtn" onclick="document.getElementById('fileInput').click(); return false;">
                                    <i class="ti ti-file-upload"></i> {{ __('Browse Files') }}
                                </button>
                                <input type="file"
                                       name="files[]"
                                       id="fileInput"
                                       style="display: none;"
                                       accept=".pdf,.doc,.docx,.xlsx,.xls"
                                       multiple>
                            </div>
                        </div>

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
                                    <tbody id="fileList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('document-templates.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-success btn-lg" id="uploadBtn" disabled>
                                <i class="ti ti-upload"></i> {{ __('Upload All Templates') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

<!-- Modal Ajout Catégorie -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addCategoryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom de la catégorie *</label>
                        <input type="text" name="name" id="templateCategoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="templateCategoryDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> La catégorie sera disponible immédiatement dans la liste.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const bulkUploadForm = document.getElementById('bulkUploadForm');
        const fileListContainer = document.getElementById('fileListContainer');
        const fileList = document.getElementById('fileList');
        const fileCount = document.getElementById('fileCount');
        const uploadBtn = document.getElementById('uploadBtn');
        let selectedFiles = [];
        let customTitles = {};

        function getFileKey(file) {
            return `${file.name}__${file.size}__${file.lastModified}`;
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function syncInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        function appendUniqueFiles(filesToAdd) {
            Array.from(filesToAdd).forEach((newFile) => {
                const exists = selectedFiles.some((existingFile) => {
                    return existingFile.name === newFile.name &&
                           existingFile.size === newFile.size &&
                           existingFile.lastModified === newFile.lastModified;
                });
                if (!exists) {
                    selectedFiles.push(newFile);
                    const key = getFileKey(newFile);
                    const baseTitle = newFile.name.replace(/\.[^/.]+$/, '');
                    customTitles[key] = baseTitle;
                }
            });
            syncInputFiles();
            updateFileList();
        }

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function() {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            appendUniqueFiles(e.dataTransfer.files);
        });

        dropZone.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            appendUniqueFiles(fileInput.files);
        });

        function updateFileList() {
            fileList.innerHTML = '';

            if (selectedFiles.length > 0) {
                fileListContainer.style.display = 'block';
                uploadBtn.disabled = false;
                fileCount.textContent = selectedFiles.length + ' {{ __('file(s) selected') }}';

                selectedFiles.forEach((file, index) => {
                    const row = document.createElement('tr');
                    row.classList.add('file-item');
                    const fileKey = getFileKey(file);
                    const title = customTitles[fileKey] || file.name.replace(/\.[^/.]+$/, '');

                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${file.name}</td>
                        <td class="file-size">${formatFileSize(file.size)}</td>
                        <td>
                            <input
                                type="text"
                                class="form-control form-control-sm template-title-input"
                                name="titles[${index}]"
                                data-file-key="${escapeHtml(fileKey)}"
                                value="${escapeHtml(title)}"
                                maxlength="500"
                                required
                            >
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                                <i class="ti ti-x"></i>
                            </button>
                        </td>
                    `;
                    fileList.appendChild(row);
                });
            } else {
                fileListContainer.style.display = 'none';
                uploadBtn.disabled = true;
                fileCount.textContent = '0 {{ __('file(s) selected') }}';
            }
        }

        window.removeFile = function(index) {
            const removedFile = selectedFiles[index];
            selectedFiles.splice(index, 1);
            if (removedFile) {
                delete customTitles[getFileKey(removedFile)];
            }
            syncInputFiles();
            updateFileList();
        }

        fileList.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('template-title-input')) {
                const fileKey = e.target.getAttribute('data-file-key');
                customTitles[fileKey] = e.target.value;
            }
        });

        bulkUploadForm.addEventListener('submit', function(e) {
            if (selectedFiles.length === 0) {
                e.preventDefault();
                uploadBtn.disabled = true;
                fileCount.textContent = '0 {{ __('file(s) selected') }}';
                return;
            }
            syncInputFiles();
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="ti ti-loader"></i> {{ __('Uploading...') }}';
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    });
</script>
<script>
$(document).ready(function() {
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            name: $('#templateCategoryName').val(),
            description: $('#templateCategoryDescription').val(),
            _token: $('input[name="_token"]').val()
        };

        $.ajax({
            url: '{{ route("document-templates.categories.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success && response.category) {
                    const newOption = $('<option></option>')
                        .attr('value', response.category.id)
                        .text(response.category.name)
                        .prop('selected', true);

                    $('#templateCategorySelect').append(newOption);
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryForm')[0].reset();
                    showNotification('success', response.message || 'Catégorie créée avec succès');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Erreur lors de la création de la catégorie';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showNotification('error', errorMsg);
            }
        });
    });

    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';

        const notification = $('<div></div>')
            .addClass(`alert ${alertClass} alert-dismissible fade show`)
            .html(`
                <i class="fas fa-${icon}"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `)
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                minWidth: '300px'
            });

        $('body').append(notification);
        setTimeout(() => notification.alert('close'), 4000);
    }
});
</script>
@endpush
