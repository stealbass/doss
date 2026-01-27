@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Alertes Juridiques</h1>
                    <p class="text-muted">Gérez les alertes envoyées aux utilisateurs (Email, WhatsApp, In-App)</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlertModal">
                    <i class="fas fa-plus"></i> Nouvelle Alerte
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Priorité</th>
                            <th>Pays Ciblés</th>
                            <th>Plans Ciblés</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alerts ?? [] as $alert)
                        <tr>
                            <td>{{ $alert->title ?? 'N/A' }}</td>
                            <td><span class="badge bg-info">{{ $alert->alert_type ?? 'N/A' }}</span></td>
                            <td>
                                <span class="badge bg-{{ $alert->priority == 'urgent' ? 'danger' : ($alert->priority == 'high' ? 'warning' : 'secondary') }}">
                                    {{ $alert->priority ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ is_array($alert->target_countries ?? null) ? count($alert->target_countries) : 0 }} pays</td>
                            <td>{{ is_array($alert->target_plans ?? null) ? count($alert->target_plans) : 0 }} plans</td>
                            <td>{{ $alert->created_at ? $alert->created_at->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                <button class="btn btn-sm btn-success send-alert" data-id="{{ $alert->id ?? 0 }}">
                                    <i class="fas fa-paper-plane"></i> Envoyer
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Aucune alerte. Créez votre première alerte juridique.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Alert Modal -->
<div class="modal fade" id="createAlertModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="createAlertForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Créer une Alerte Juridique</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Titre *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Contenu *</label>
                            <textarea name="content" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type d'Alerte</label>
                            <select name="alert_type" class="form-select">
                                <option value="legal_modification">Modification Légale</option>
                                <option value="new_law">Nouvelle Loi</option>
                                <option value="jurisprudence">Jurisprudence</option>
                                <option value="fiscal">Fiscal</option>
                                <option value="social">Social</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priorité</label>
                            <select name="priority" class="form-select">
                                <option value="low">Faible</option>
                                <option value="medium">Moyenne</option>
                                <option value="high">Élevée</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Pays Ciblés (laisser vide pour tous)</label>
                            <select name="target_countries[]" class="form-select" multiple size="5">
                                @foreach(config('mobile_countries.countries', []) as $code => $country)
                                    <option value="{{ $code }}">{{ $country['flag'] ?? '' }} {{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Plans Ciblés</label>
                            <select name="target_plans[]" class="form-select" multiple>
                                <option value="Étudiant">Étudiant</option>
                                <option value="Professionnel" selected>Professionnel</option>
                                <option value="Cabinet/Entreprise" selected>Cabinet/Entreprise</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer l'Alerte</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#createAlertForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("admin.legal-alerts.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                toastr.success('Alerte créée avec succès');
                setTimeout(() => location.reload(), 1500);
            }
        });
    });

    $('.send-alert').on('click', function() {
        const id = $(this).data('id');
        if (confirm('Envoyer cette alerte à tous les utilisateurs ciblés ?')) {
            $.ajax({
                url: `/admin/legal-alerts/${id}/send`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    toastr.success('Alerte envoyée avec succès');
                }
            });
        }
    });
});
</script>
@endpush
@endsection
