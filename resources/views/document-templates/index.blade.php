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
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="country" class="form-select">
                            <option value="">Tous les pays</option>
                            @foreach(config('mobile_countries.countries') as $code => $country)
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
                                        <button type="button" 
                                                class="btn btn-outline-danger delete-template" 
                                                data-id="{{ $template->id }}"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catégorie *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pays *</label>
                            <select name="country" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                @foreach(config('mobile_countries.countries') as $code => $country)
                                    <option value="{{ $code }}">{{ $country['flag'] }} {{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan Requis *</label>
                            <select name="required_plan" class="form-select" required>
                                <option value="Gratuit">Gratuit</option>
                                <option value="Étudiant">Étudiant</option>
                                <option value="Professionnel">Professionnel</option>
                                <option value="Cabinet/Entreprise">Cabinet/Entreprise</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fichier *</label>
                            <input type="file" name="file" class="form-control" accept=".doc,.docx,.pdf,.xlsx" required>
                            <small class="text-muted">Formats: Word, PDF, Excel</small>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_mobile_visible" class="form-check-input" id="mobileVisible" checked>
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

@push('scripts')
<script>
$(document).ready(function() {
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
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("document-templates.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1500);
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(err => toastr.error(err[0]));
                } else {
                    toastr.error('Erreur lors de l\'upload');
                }
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
