@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Calculateurs & Simulateurs</h1>
            <p class="text-muted">Gérez les 7 calculateurs de l'application</p>
        </div>
    </div>

    <div class="row mb-4">
        @foreach(['total' => 'Total', 'active' => 'Actifs', 'total_uses' => 'Utilisations', 'this_month' => 'Ce mois'] as $key => $label)
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h4 mb-0">{{ $stats[$key] ?? 0 }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Calculateur</th>
                            <th>Type</th>
                            <th>Pays</th>
                            <th>Plan Requis</th>
                            <th>Status</th>
                            <th>Utilisations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($calculators as $calc)
                        <tr>
                            <td>{{ $calc->name }}</td>
                            <td><span class="badge bg-info">{{ $calc->calculator_type }}</span></td>
                            <td>{{ $calc->country }}</td>
                            <td><span class="badge bg-primary">{{ $calc->required_plan }}</span></td>
                            <td>
                                <span class="badge bg-{{ $calc->is_active ? 'success' : 'secondary' }}">
                                    {{ $calc->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td>{{ $calc->uses_count ?? 0 }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary toggle-status" data-id="{{ $calc->id }}">
                                    <i class="fas fa-toggle-{{ $calc->is_active ? 'on' : 'off' }}"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Aucun calculateur</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.toggle-status').on('click', function() {
        const id = $(this).data('id');
        $.ajax({
            url: `/admin/calculators/${id}/toggle-active`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            }
        });
    });
});
</script>
@endpush
@endsection
