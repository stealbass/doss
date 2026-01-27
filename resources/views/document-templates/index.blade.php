@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Banque de Modèles d'Actes et Contrats</h1>
                    <p class="text-muted">Gérez les templates de documents pour l'application mobile</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadTemplateModal">
                    <i class="fas fa-plus"></i> Ajouter un Template
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Templates</div>
                            <div class="h4 mb-0">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-mobile-alt fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Visibles Mobile</div>
                            <div class="h4 mb-0">{{ $stats['mobile_visible'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-download fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Téléchargements</div>
                            <div class="h4 mb-0">{{ $stats['total_downloads'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-folder fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Catégories</div>
                            <div class="h4 mb-0">{{ $stats['categories'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('document-templates.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">Toutes les catégories</option>
                            @forelse(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="country" class="form-select">
                            <option value="">Tous les pays</option>
                            @foreach($countries ?? [] as $code => $country)
                                <option value="{{ $code }}" {{ request('country') == $code ? 'selected' : '' }}>
                                    {{ $country['flag'] }} {{ $code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="plan" class="form-select">
                            <option value="">Tous les plans</option>
                            <option value="Gratuit" {{ request('plan') == 'Gratuit' ? 'selected' : '' }}>Gratuit</option>
                            <option value="Étudiant" {{ request('plan') == 'Étudiant' ? 'selected' : '' }}>Étudiant</option>
                            <option value="Professionnel" {{ request('plan') == 'Professionnel' ? 'selected' : '' }}>Professionnel</option>
                            <option value="Cabinet/Entreprise" {{ request('plan') == 'Cabinet/Entreprise' ? 'selected' : '' }}>Cabinet/Entreprise</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Liste des Templates</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Pays</th>
                            <th>Type</th>
                            <th>Plan</th>
                            <th>Mobile</th>
                            <th>Téléchargements</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $template->title }}</div>
                                    <small class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                </td>
                                <td>{{ $template->category->name ?? 'N/A' }}</td>
                                <td>
                                    @if($template->country)
                                        {{ config("mobile_countries.countries.{$template->country}.flag", '') }} 
                                        {{ $template->country }}
                                    @else
                                        <span class="text-muted">Non défini</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ strtoupper($template->file_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $template->required_plan == 'Gratuit' ? 'success' : ($template->required_plan == 'Cabinet/Entreprise' ? 'primary' : 'info') }}">
                                        {{ $template->required_plan }}
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-mobile-visibility" 
                                               type="checkbox" 
                                               data-id="{{ $template->id }}"
                                               {{ $template->is_mobile_visible ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $template->downloads_count }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('document-templates.download', $template->id) }}" 
                                           class="btn btn-outline-primary" 
                                           title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @if(\Auth::user()->type == 'super admin')
                                            <button type="button" 
                                                    class="btn btn-outline-danger delete-template" 
                                                    data-id="{{ $template->id }}"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Aucun template trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
            <div class="card-footer bg-white">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Upload Template Modal -->
<div class="modal fade" id="uploadTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="uploadTemplateForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Titre *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de Document *</label>
                            <select name="template_type" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                <option value="contract">Contrat</option>
                                <option value="act">Acte</option>
                                <option value="form">Formulaire</option>
                                <option value="letter">Lettre</option>
                                <option value="calculator">Calculateur</option>
                                <option value="checklist">Checklist</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catégorie *</label>
                            <div class="input-group">
                                <select name="category_id" id="templateCategorySelect" class="form-select" required>
                                    <option value="">Sélectionner...</option>
                                    @foreach(($categories ?? collect()) as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="Ajouter une catégorie">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pays *</label>
                            <select name="country" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                @php
                                $countries = [
                                    'BJ' => ['flag' => '🇧🇯', 'name' => 'Bénin'],
                                    'BF' => ['flag' => '🇧🇫', 'name' => 'Burkina Faso'],
                                    'CM' => ['flag' => '🇨🇲', 'name' => 'Cameroun'],
                                    'CI' => ['flag' => '🇨🇮', 'name' => 'Côte d\'Ivoire'],
                                    'CD' => ['flag' => '🇨🇩', 'name' => 'RD Congo'],
                                    'GA' => ['flag' => '🇬🇦', 'name' => 'Gabon'],
                                    'GW' => ['flag' => '🇬🇼', 'name' => 'Guinée-Bissau'],
                                    'MG' => ['flag' => '🇲🇬', 'name' => 'Madagascar'],
                                    'ML' => ['flag' => '🇲🇱', 'name' => 'Mali'],
                                    'MA' => ['flag' => '🇲🇦', 'name' => 'Maroc'],
                                    'NE' => ['flag' => '🇳🇪', 'name' => 'Niger'],
                                    'SN' => ['flag' => '🇸🇳', 'name' => 'Sénégal'],
                                    'TG' => ['flag' => '🇹🇬', 'name' => 'Togo'],
                                    'TN' => ['flag' => '🇹🇳', 'name' => 'Tunisie']
                                ];
                                @endphp
                                @foreach($countries as $code => $country)
                                    <option value="{{ $code }}">{{ $country['flag'] }} {{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan Requis *</label>
                            <select name="allowed_plans" class="form-select" required>
                                <option value="free">Gratuit</option>
                                <option value="student">Étudiant</option>
                                <option value="professional">Professionnel</option>
                                <option value="enterprise">Cabinet/Entreprise</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fichier *</label>
                            <input type="file" name="file" class="form-control" accept=".doc,.docx,.pdf,.xlsx" required>
                            <small class="text-muted">Formats: Word, PDF, Excel</small>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="hidden" name="is_mobile_visible" value="0">
                                <input type="checkbox" name="is_mobile_visible" value="1" class="form-check-input" id="mobileVisible" checked>
                                <label class="form-check-label" for="mobileVisible">
                                    Visible sur mobile
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Uploader
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
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
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="categoryDescription" class="form-control" rows="3"></textarea>
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

@push('custom-script')
<script>
$(document).ready(function() {
    // Add new category
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#categoryName').val(),
            description: $('#categoryDescription').val(),
            _token: $('input[name="_token"]').val()
        };
        
        $.ajax({
            url: '{{ route("document-templates.categories.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Add new category to dropdown
                    const newOption = $('<option></option>')
                        .attr('value', response.category.id)
                        .text(response.category.name)
                        .prop('selected', true);
                    
                    $('#templateCategorySelect').append(newOption);
                    
                    // Close modal and reset form
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryForm')[0].reset();
                    
                    // Show success message
                    showNotification('success', response.message || 'Catégorie créée avec succès!');
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
    
    // Helper function to show notifications
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
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Toggle mobile visibility
    $('.toggle-mobile-visibility').on('change', function() {
        const templateId = $(this).data('id');
        const isVisible = $(this).is(':checked');
        
        $.ajax({
            url: `/admin/document-templates/${templateId}/toggle-mobile`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                is_mobile_visible: isVisible
            },
            success: function(response) {
                toastr.success(response.message);
            },
            error: function() {
                toastr.error('Erreur lors de la mise à jour');
            }
        });
    });

    // Upload template
    $('#uploadTemplateForm').on('submit', function(e) {
        
            console.log('Form submitted!');
        e.preventDefault();
        
        const formData = new FormData(this);
        
                // Log form data for debugging
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Disable button and show loading
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Upload en cours...');
        
        $.ajax({
            url: '{{ route("document-templates.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message || 'Template créé avec succès!');
                    $('#uploadTemplateModal').modal('hide');
                    $('#uploadTemplateForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                }
            },
            error: function(xhr) {
                console.error('Upload error:', xhr);
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(err => showNotification('error', err[0]));
                } else {
                    const errorMsg = xhr.responseJSON?.message || 'Erreur lors de l\'upload';
                    showNotification('error', errorMsg);
                }
            },
            complete: function() {
                // Re-enable button
                submitBtn.prop('disabled', false).html('<i class="fas fa-upload"></i> Uploader');
            }
        });
    });

    // Delete template
    $('.delete-template').on('click', function() {
        const templateId = $(this).data('id');
        
        if (confirm('Êtes-vous sûr de vouloir supprimer ce template ?')) {
            $.ajax({
                url: `/admin/document-templates/${templateId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1500);
                },
                error: function() {
                    toastr.error('Erreur lors de la suppression');
                }
            });
        }
    });
});
</script>
@endpush
@endsection