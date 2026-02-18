@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-file-invoice-dollar"></i> Ressources Fiscales & Sociales
                </h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('fiscal-resources.bulk-upload.form') }}" class="btn btn-outline-primary">
                        <i class="fas fa-file-upload"></i> Import Multiple
                    </a>
                    <a href="{{ route('fiscal-resources.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle Ressource
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Ressources</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-resources">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Visibles Mobile</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-resources">{{ $stats['mobile_visible'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Vues Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-views">{{ $stats['total_views'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pays</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['countries'] ?? 0 }} supportés</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('fiscal-resources.index') }}" class="form-inline">
                <div class="form-group mr-3">
                    <select name="resource_type" class="form-control" onchange="this.form.submit()">
                        <option value="">Tous les types</option>
                        <option value="cgi" {{ request('resource_type') == 'cgi' ? 'selected' : '' }}>CGI</option>
                        <option value="finance_law" {{ request('resource_type') == 'finance_law' ? 'selected' : '' }}>Loi de Finances</option>
                        <option value="tax_procedure" {{ request('resource_type') == 'tax_procedure' ? 'selected' : '' }}>Procédure Fiscale</option>
                        <option value="circular" {{ request('resource_type') == 'circular' ? 'selected' : '' }}>Circulaire</option>
                        <option value="doctrine" {{ request('resource_type') == 'doctrine' ? 'selected' : '' }}>Doctrine</option>
                        <option value="convention" {{ request('resource_type') == 'convention' ? 'selected' : '' }}>Convention</option>
                        <option value="labor_code" {{ request('resource_type') == 'labor_code' ? 'selected' : '' }}>Code du Travail</option>
                        <option value="social_code" {{ request('resource_type') == 'social_code' ? 'selected' : '' }}>Code de Sécurité Sociale</option>
                        <option value="collective_agreement" {{ request('resource_type') == 'collective_agreement' ? 'selected' : '' }}>Convention Collective</option>
                        <option value="salary_grid" {{ request('resource_type') == 'salary_grid' ? 'selected' : '' }}>Grilles Salariales</option>
                        <option value="administrative_form" {{ request('resource_type') == 'administrative_form' ? 'selected' : '' }}>Formulaire Administratif</option>
                        <option value="other" {{ request('resource_type') == 'other' ? 'selected' : '' }}>Autres</option>
                    </select>
                </div>

                <div class="form-group mr-3">
                    <select name="country" class="form-control" onchange="this.form.submit()">
                        <option value="">Tous les pays</option>
                        @foreach(['BJ' => 'Bénin', 'BF' => 'Burkina Faso', 'CM' => 'Cameroun', 'CI' => 'Côte d\'Ivoire', 'CD' => 'RD Congo', 'GA' => 'Gabon', 'GW' => 'Guinée-Bissau', 'MG' => 'Madagascar', 'ML' => 'Mali', 'MA' => 'Maroc', 'NE' => 'Niger', 'SN' => 'Sénégal', 'TG' => 'Togo', 'TN' => 'Tunisie'] as $code => $name)
                            <option value="{{ $code }}" {{ request('country') == $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mr-3">
                    <select name="year" class="form-control" onchange="this.form.submit()">
                        <option value="">Toutes les années</option>
                        @for($y = date('Y') + 5; $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="form-group mr-3">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
                </div>

                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-search"></i> Rechercher
                </button>

                @if(request()->hasAny(['resource_type', 'country', 'year', 'search']))
                    <a href="{{ route('fiscal-resources.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Resources Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Ressources</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Pays</th>
                            <th>Année</th>
                            <th>Version</th>
                            <th>Vues</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resources as $resource)
                        <tr>
                            <td>{{ $resource->id }}</td>
                            <td>
                                <strong>{{ $resource->title }}</strong><br>
                                <small class="text-muted">{{ Str::limit($resource->description, 50) }}</small>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ Str::title(str_replace('_', ' ', $resource->resource_type)) }}</span>
                            </td>
                            <td>
                                <img src="https://flagcdn.com/24x18/{{ strtolower($resource->country) }}.png" alt="{{ $resource->country }}" class="mr-1">
                                {{ $resource->country }}
                            </td>
                            <td>{{ $resource->year ?? '-' }}</td>
                            <td>
                                @if($resource->version)
                                    <span class="badge badge-primary">v{{ $resource->version }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <i class="fas fa-eye"></i> {{ $resource->views_count }}
                            </td>
                            <td>
                                @if($resource->is_mobile_visible)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('fiscal-resources.download', $resource->id) }}" class="btn btn-sm btn-primary" title="Télécharger" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="{{ route('fiscal-resources.show', $resource->id) }}" class="btn btn-sm btn-info" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('fiscal-resources.edit', $resource->id) }}" class="btn btn-sm btn-warning" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @php
                                        $fiscalPermissions = json_decode(\Auth::user()->permission_json ?? '[]', true) ?? [];
                                        $canDeleteFiscal = \Auth::user()->type == 'super admin' || (\Auth::user()->type == 'superAdminEmployee' && (in_array('manage fiscal-resources', $fiscalPermissions) || in_array(5, $fiscalPermissions)));
                                    @endphp
                                    @if($canDeleteFiscal)
                                        <form action="{{ route('fiscal-resources.destroy', $resource->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette ressource ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">Aucune ressource fiscale trouvée</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($resources->hasPages())
            <div class="mt-3">
                {{ $resources->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('custom-script')
<script>
    $(document).ready(function() {
        // Initialize DataTable if needed
        // $('#dataTable').DataTable();
    });
</script>
@endpush