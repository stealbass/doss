@extends('layouts.app')

@section('page-title', __('Mobile Legal Library - Sync Logs'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('mobile-legal-library.index') }}">{{ __('Mobile Legal Library') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sync Logs') }}</li>
@endsection

@section('action-btn')
    <form action="{{ route('mobile-legal-library.clear-old-logs') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Clear logs older than 90 days?') }}')">
            <i class="ti ti-trash"></i> {{ __('Clear Old Logs') }}
        </button>
    </form>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="ti ti-history me-2"></i>{{ __('Sync Activity Logs') }}
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Date & Time') }}</th>
                                    <th>{{ __('Action') }}</th>
                                    <th>{{ __('Details') }}</th>
                                    <th>{{ __('User') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>
                                            <small>{{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}</small>
                                            <br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $actionBadges = [
                                                    'toggle_visibility' => 'info',
                                                    'bulk_toggle_visibility' => 'primary',
                                                    'sync_category' => 'success',
                                                    'force_full_sync' => 'warning',
                                                ];
                                                $badgeColor = $actionBadges[$log->action] ?? 'secondary';
                                                $actionLabel = str_replace('_', ' ', ucwords($log->action, '_'));
                                            @endphp
                                            <span class="badge bg-{{ $badgeColor }}">{{ $actionLabel }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $details = json_decode($log->details, true);
                                            @endphp
                                            @if($details)
                                                @if(isset($details['document_title']))
                                                    <strong>{{ $details['document_title'] }}</strong>
                                                    @if(isset($details['new_status']))
                                                        <br><small class="text-muted">{{ __('Status: :status', ['status' => $details['new_status']]) }}</small>
                                                    @endif
                                                @elseif(isset($details['category_name']))
                                                    <strong>{{ __('Category: :name', ['name' => $details['category_name']]) }}</strong>
                                                @elseif(isset($details['count']))
                                                    {{ __(':count documents', ['count' => $details['count']]) }}
                                                    @if(isset($details['action']))
                                                        <small class="text-muted">({{ $details['action'] }})</small>
                                                    @endif
                                                @elseif(isset($details['total_documents']))
                                                    {{ __('Total: :count documents', ['count' => $details['total_documents']]) }}
                                                @else
                                                    <small class="text-muted">{{ json_encode($details) }}</small>
                                                @endif
                                            @else
                                                <small class="text-muted">{{ __('No details') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->user_id)
                                                @php
                                                    $user = \App\Models\User::find($log->user_id);
                                                @endphp
                                                @if($user)
                                                    {{ $user->name }}
                                                    <br><small class="text-muted">{{ $user->email }}</small>
                                                @else
                                                    <small class="text-muted">{{ __('User #:id', ['id' => $log->user_id]) }}</small>
                                                @endif
                                            @else
                                                <small class="text-muted">{{ __('System') }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="ti ti-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <br>{{ __('No sync logs yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                        <div class="mt-3">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
