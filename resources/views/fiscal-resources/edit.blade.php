@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit"></i> Modifier Ressource Fiscale
            </h1>
            <a href="{{ route('fiscal-resources.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('fiscal-resources.update', $resource->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" class="form-control" value="{{ $resource->title }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de ressource *</label>
                        <select name="resource_type" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            <option value="cgi" {{ $resource->resource_type == 'cgi' ? 'selected' : '' }}>Code Général des Impôts</option>
                            <option value="finance_law" {{ $resource->resource_type == 'finance_law' ? 'selected' : '' }}>Loi de Finance</option>
                            <option value="tax_procedure" {{ $resource->resource_type == 'tax_procedure' ? 'selected' : '' }}>Procédure Fiscale</option>
                            <option value="circular" {{ $resource->resource_type == 'circular' ? 'selected' : '' }}>Circulaire</option>
                            <option value="doctrine" {{ $resource->resource_type == 'doctrine' ? 'selected' : '' }}>Doctrine Administrative</option>
                            <option value="convention" {{ $resource->resource_type == 'convention' ? 'selected' : '' }}>Convention Fiscale</option>
                            <option value="labor_code" {{ $resource->resource_type == 'labor_code' ? 'selected' : '' }}>Code du Travail</option>
                            <option value="social_code" {{ $resource->resource_type == 'social_code' ? 'selected' : '' }}>Code Sécurité Sociale</option>
                            <option value="collective_agreement" {{ $resource->resource_type == 'collective_agreement' ? 'selected' : '' }}>Convention Collective</option>
                            <option value="salary_grid" {{ $resource->resource_type == 'salary_grid' ? 'selected' : '' }}>Grille Salariale</option>
                            <option value="administrative_form" {{ $resource->resource_type == 'administrative_form' ? 'selected' : '' }}>Formulaire Administratif</option>
                            <option value="other" {{ $resource->resource_type == 'other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catégorie *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Sélectionner une catégorie...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $resource->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pays *</label>
                        <select name="country" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            @foreach($countries as $code => $label)
                                <option value="{{ $code }}" {{ $resource->country == $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Année *</label>
                        <select name="year" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            @forelse(($years ?? []) as $year)
                                <option value="{{ $year }}" {{ $resource->year == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-control" value="{{ $resource->version ?? '1.0' }}" placeholder="1.0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $resource->description }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Fichier (PDF/Word/Excel)</label>
                        @if($resource->file_path)
                            <p class="mb-2">
                                <strong>Fichier actuel :</strong> {{ $resource->file_name }}
                                <a href="{{ route('fiscal-resources.download', $resource->id) }}" target="_blank" class="ms-2">
                                    <i class="fas fa-download"></i> Télécharger
                                </a>
                            </p>
                        @endif
                        <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xlsx,.xls">
                        <small class="text-muted">Laisser vide pour conserver le fichier actuel</small>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input type="hidden" name="is_mobile_visible" value="0">
                            <input type="checkbox" name="is_mobile_visible" class="form-check-input" id="mobileVisible" value="1" 
                                {{ $resource->is_mobile_visible ? 'checked' : '' }}>
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
