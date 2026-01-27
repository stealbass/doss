@extends('layouts.app')

@section('page-title', __('Push Notifications'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Push Notifications') }}</li>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="ti ti-bell text-primary" style="font-size: 32px;"></i>
                    <h3 class="mt-2 mb-0">{{ $stats['total'] }}</h3>
                    <small class="text-muted">{{ __('Total') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="ti ti-send text-success" style="font-size: 32px;"></i>
                    <h3 class="mt-2 mb-0">{{ $stats['sent'] }}</h3>
                    <small class="text-muted">{{ __('Sent') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="ti ti-clock text-info" style="font-size: 32px;"></i>
                    <h3 class="mt-2 mb-0">{{ $stats['scheduled'] }}</h3>
                    <small class="text-muted">{{ __('Scheduled') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="ti ti-file text-secondary" style="font-size: 32px;"></i>
                    <h3 class="mt-2 mb-0">{{ $stats['drafts'] }}</h3>
                    <small class="text-muted">{{ __('Drafts') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="ti ti-users text-warning" style="font-size: 32px;"></i>
                    <h3 class="mt-2 mb-0">{{ number_format($stats['total_recipients']) }}</h3>
                    <small class="text-muted">{{ __('Recipients') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="ti ti-chart-line text-primary" style="font-size: 32px;"></i>
                    <h3 class="mt-2 mb-0">{{ $stats['avg_open_rate'] }}%</h3>
                    <small class="text-muted">{{ __('Avg Open Rate') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions & Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group" role="group">
                    <a href="{{ route('push-notifications.index') }}" class="btn btn-sm {{ !request('status') && !request('type') ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ __('All') }}
                    </a>
                    <a href="{{ route('push-notifications.index', ['status' => 'sent']) }}" class="btn btn-sm {{ request('status') == 'sent' ? 'btn-success' : 'btn-outline-success' }}">
                        {{ __('Sent') }}
                    </a>
                    <a href="{{ route('push-notifications.index', ['status' => 'scheduled']) }}" class="btn btn-sm {{ request('status') == 'scheduled' ? 'btn-info' : 'btn-outline-info' }}">
                        {{ __('Scheduled') }}
                    </a>
                    <a href="{{ route('push-notifications.index', ['status' => 'draft']) }}" class="btn btn-sm {{ request('status') == 'draft' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        {{ __('Drafts') }}
                    </a>
                </div>
                <a href="{{ route('push-notifications.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>{{ __('New Notification') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Notifications Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%;">{{ __('Notification') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Audience') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Stats') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-bold">{{ $notification->title }}</div>
                                        <small class="text-muted">{{ Str::limit($notification->body, 80) }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $notification->type_color }}">
                                        {{ ucfirst($notification->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($notification->target_audience === 'all')
                                        <span class="badge bg-primary">{{ __('All Users') }}</span>
                                    @elseif($notification->target_audience === 'students')
                                        <span class="badge bg-info">{{ __('Students') }}</span>
                                    @elseif($notification->target_audience === 'lawyers')
                                        <span class="badge bg-warning">{{ __('Lawyers') }}</span>
                                    @elseif($notification->target_audience === 'enterprises')
                                        <span class="badge bg-primary">{{ __('Enterprises') }}</span>
                                    @elseif($notification->target_audience === 'plan_specific')
                                        <span class="badge bg-secondary">{{ __('Plan Specific') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $notification->status_color }}">
                                        {{ ucfirst($notification->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($notification->status === 'sent')
                                        <div class="small">
                                            <div><i class="ti ti-send"></i> {{ number_format($notification->successful_sends) }}</div>
                                            <div><i class="ti ti-eye"></i> {{ $notification->open_rate }}%</div>
                                            <div><i class="ti ti-click"></i> {{ $notification->click_rate }}%</div>
                                        </div>
                                    @elseif($notification->status === 'scheduled')
                                        <small class="text-muted">
                                            {{ $notification->scheduled_at->format('d/m/Y H:i') }}
                                        </small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $notification->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('push-notifications.show', $notification->id) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           data-bs-toggle="tooltip" 
                                           title="{{ __('View') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>

                                        @if(in_array($notification->status, ['draft', 'scheduled']))
                                            <a href="{{ route('push-notifications.edit', $notification->id) }}" 
                                               class="btn btn-sm btn-outline-secondary"
                                               data-bs-toggle="tooltip" 
                                               title="{{ __('Edit') }}">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endif

                                        @if($notification->canBeSent())
                                            <form action="{{ route('push-notifications.send', $notification->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ __('Send this notification now?') }}')">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-success"
                                                        data-bs-toggle="tooltip" 
                                                        title="{{ __('Send Now') }}">
                                                    <i class="ti ti-send"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('push-notifications.duplicate', $notification->id) }}" 
                                           class="btn btn-sm btn-outline-info"
                                           data-bs-toggle="tooltip" 
                                           title="{{ __('Duplicate') }}">
                                            <i class="ti ti-copy"></i>
                                        </a>

                                        @if($notification->status === 'scheduled')
                                            <form action="{{ route('push-notifications.cancel', $notification->id) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-warning"
                                                        data-bs-toggle="tooltip" 
                                                        title="{{ __('Cancel') }}">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if($notification->status === 'draft')
                                            <form action="{{ route('push-notifications.destroy', $notification->id) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ __('Delete this notification?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="tooltip" 
                                                        title="{{ __('Delete') }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="ti ti-bell-off" style="font-size: 48px; opacity: 0.3;"></i>
                                    <div class="mt-3 text-muted">{{ __('No notifications yet') }}</div>
                                    <a href="{{ route('push-notifications.create') }}" class="btn btn-primary mt-3">
                                        <i class="ti ti-plus me-1"></i>{{ __('Create First Notification') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($notifications->hasPages())
            <div class="card-footer">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection

@push('custom-script')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>
@endpush
