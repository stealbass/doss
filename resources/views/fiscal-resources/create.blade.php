@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-medical"></i> Nouvelle Ressource Fiscale & Sociale
            </h1>
            <a href="{{ route('fiscal-resources.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('fiscal-resources.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de ressource *</label>
                        <select name="resource_type" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            <option value="cgi">Code Général des Impôts</option>
                            <option value="finance_law">Loi de Finance</option>
                            <option value="tax_procedure">Procédure Fiscale</option>
                            <option value="circular">Circulaire</option>
                            <option value="doctrine">Doctrine Administrative</option>
                            <option value="convention">Convention Fiscale</option>
                            <option value="labor_code">Code du Travail</option>
                            <option value="social_code">Code Sécurité Sociale</option>
                            <option value="collective_agreement">Convention Collective</option>
                            <option value="salary_grid">Grille Salariale</option>
                            <option value="administrative_form">Formulaire Administratif</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catégorie *</label>
                        <div class="input-group">
                            <select name="category_id" id="fiscalCategorySelect" class="form-select" required>
                                <option value="">Sélectionner une catégorie...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addFiscalCategoryModal" title="Ajouter une catégorie">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pays *</label>
                        <select name="country" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            @foreach($countries as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Année *</label>
                        <select name="year" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            @forelse(($years ?? []) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-control" placeholder="1.0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Fichier (PDF/Word/Excel) *</label>
                        <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.xls" required>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="hidden" name="is_mobile_visible" value="0">
                            <input type="checkbox" name="is_mobile_visible" class="form-check-input" id="mobileVisible" value="1" checked>
                            <label class="form-check-label" for="mobileVisible">Visible sur mobile</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('fiscal-resources.index') }}" class="btn btn-light">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- Modal Ajout Catégorie -->
<div class="modal fade" id="addFiscalCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addFiscalCategoryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une Catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom de la catégorie *</label>
                        <input type="text" name="name" id="fiscalCategoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="fiscalCategoryDescription" class="form-control" rows="3"></textarea>
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
    // Création d'une catégorie fiscale via AJAX
    $('#addFiscalCategoryForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            name: $('#fiscalCategoryName').val(),
            description: $('#fiscalCategoryDescription').val(),
            _token: $('input[name="_token"]').val()
        };

        $.ajax({
            url: '{{ route("fiscal-resources.categories.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success && response.category) {
                    const newOption = $('<option></option>')
                        .attr('value', response.category.id)
                        .text(response.category.name)
                        .prop('selected', true);

                    $('#fiscalCategorySelect').append(newOption);

                    $('#addFiscalCategoryModal').modal('hide');
                    $('#addFiscalCategoryForm')[0].reset();

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

    // Notification utilitaire
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
});
</script>
@endpush
