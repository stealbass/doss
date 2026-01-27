@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice-dollar"></i> Détail Ressource
            </h1>
            <div>
                <a href="{{ route('fiscal-resources.edit', $resource->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <a href="{{ route('fiscal-resources.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4>{{ $resource->title }}</h4>
                    <p class="text-muted">{{ $resource->description }}</p>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <label class="fw-bold">Catégorie</label>
                            <p>{{ $resource->category->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Type</label>
                            <p>{{ $resource->resource_type }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Pays</label>
                            <p>{{ $resource->country }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Année</label>
                            <p>{{ $resource->year }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Version</label>
                            <p>{{ $resource->version ?? '1.0' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Visible sur Mobile</label>
                            <p>
                                @if($resource->is_mobile_visible)
                                    <span class="badge bg-success">Oui</span>
                                @else
                                    <span class="badge bg-danger">Non</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="mt-4">
                        <label class="fw-bold">Fichier</label>
                        <p>
                            <a href="{{ route('fiscal-resources.download', $resource->id) }}" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-download"></i> {{ $resource->file_name ?? 'Télécharger' }}
                            </a>
                            <small class="text-muted">({{ $resource->file_type }}, {{ $resource->formatted_file_size }})</small>
                        </p>
                    </div>

                    <div class="mt-3">
                        <label class="fw-bold">Créé</label>
                        <p>{{ $resource->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Actions</h6>
                            <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#toggleMobileModal">
                                <i class="fas fa-mobile-alt"></i> 
                                {{ $resource->is_mobile_visible ? 'Masquer du mobile' : 'Afficher sur mobile' }}
                            </button>
                            @if(\Auth::user()->type == 'super admin')
                                <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title">Infos Fichier</h6>
                            <small>
                                <p><strong>Taille :</strong> {{ $resource->formatted_file_size }}</p>
                                <p><strong>Format :</strong> {{ strtoupper($resource->file_type) }}</p>
                                <p><strong>Téléchargements :</strong> {{ $resource->downloads_count ?? 0 }}</p>
                                <p><strong>Vues :</strong> {{ $resource->views_count ?? 0 }}</p>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Mobile Modal -->
<div class="modal fade" id="toggleMobileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Basculer Visibilité Mobile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir {{ $resource->is_mobile_visible ? 'masquer cette ressource du mobile ?' : 'afficher cette ressource sur mobile ?' }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('fiscal-resources.toggle-mobile', $resource->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">Confirmer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
@if(\Auth::user()->type == 'super admin')
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supprimer Ressource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger"><strong>⚠️ Cette action est irréversible.</strong></p>
                <p>Êtes-vous sûr de vouloir supprimer cette ressource ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('fiscal-resources.destroy', $resource->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@push('custom-script')
<script>
// Script removed - formatBytes now handled by model attribute
</script>
@endpush
@endsection
