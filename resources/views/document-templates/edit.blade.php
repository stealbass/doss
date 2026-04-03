@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit"></i> Modifier le Template
            </h1>
            <a href="{{ route('document-templates.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Erreurs de validation :</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('document-templates.update', $template->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" maxlength="500" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $template->description) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Type de Document *</label>
                        <select name="template_type" class="form-select" required>
                            <option value="contract" {{ old('template_type', $template->template_type) == 'contract' ? 'selected' : '' }}>Contrat</option>
                            <option value="act" {{ old('template_type', $template->template_type) == 'act' ? 'selected' : '' }}>Acte</option>
                            <option value="form" {{ old('template_type', $template->template_type) == 'form' ? 'selected' : '' }}>Formulaire</option>
                            <option value="letter" {{ old('template_type', $template->template_type) == 'letter' ? 'selected' : '' }}>Lettre</option>
                            <option value="calculator" {{ old('template_type', $template->template_type) == 'calculator' ? 'selected' : '' }}>Calculateur</option>
                            <option value="checklist" {{ old('template_type', $template->template_type) == 'checklist' ? 'selected' : '' }}>Checklist</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Catégorie *</label>
                        <select name="category_id" class="form-select" required>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}" {{ (int) old('category_id', $template->category_id) === (int) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pays *</label>
                        <select name="country" class="form-select" required>
                            <option value="">Sélectionner...</option>
                            @foreach(($countries ?? []) as $code => $country)
                                <option value="{{ $code }}" {{ old('country', $template->country) == $code ? 'selected' : '' }}>
                                    {{ $country['flag'] ?? '' }} {{ $code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Plan Requis *</label>
                        <select name="allowed_plans" class="form-select" required>
                            @php
                                $allowed = is_array($template->allowed_plans) ? ($template->allowed_plans[0] ?? null) : $template->allowed_plans;
                            @endphp
                            <option value="free" {{ old('allowed_plans', $allowed) == 'free' ? 'selected' : '' }}>Gratuit</option>
                            <option value="student" {{ old('allowed_plans', $allowed) == 'student' ? 'selected' : '' }}>Étudiant</option>
                            <option value="professional" {{ old('allowed_plans', $allowed) == 'professional' ? 'selected' : '' }}>Professionnel</option>
                            <option value="enterprise" {{ old('allowed_plans', $allowed) == 'enterprise' ? 'selected' : '' }}>Cabinet/Entreprise</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fichier (optionnel)</label>
                        <input type="file" name="file" class="form-control" accept=".doc,.docx,.pdf,.xlsx,.xls">
                        @if($template->file_name)
                            <small class="text-muted d-block mt-1">Fichier actuel: {{ $template->file_name }}</small>
                        @endif
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input type="hidden" name="is_mobile_visible" value="0">
                            <input type="checkbox" name="is_mobile_visible" value="1" class="form-check-input" id="mobileVisible" {{ old('is_mobile_visible', $template->is_mobile_visible) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mobileVisible">Visible sur mobile</label>
                        </div>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check mt-4">
                            <input type="hidden" name="is_premium" value="0">
                            <input type="checkbox" name="is_premium" value="1" class="form-check-input" id="isPremium" {{ old('is_premium', $template->is_premium) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isPremium">Template Premium</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('document-templates.index') }}" class="btn btn-light">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
