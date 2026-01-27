@extends('layouts.app')

@section('page-title')
    {{ __('Notification Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('push-notifications.index') }}">{{ __('Push Notifications') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Details') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Notification Information') }}</h3>
                    <div class="card-actions">
                        @if($notification->status === 'draft')
                            <form action="{{ route('push-notifications.send', $notification) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('{{ __('Send this notification now?') }}')">
                                    <i class="ti ti-send"></i> {{ __('Send Now') }}
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('push-notifications.edit', $notification) }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-edit"></i> {{ __('Edit') }}
                        </a>
                        
                        <form action="{{ route('push-notifications.destroy', $notification) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Are you sure?') }}')">
                                <i class="ti ti-trash"></i> {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Title') }}:</div>
                        <div class="col-md-9"><strong>{{ $notification->title }}</strong></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Title (FR)') }}:</div>
                        <div class="col-md-9">{{ $notification->title_fr ?? '-' }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Message') }}:</div>
                        <div class="col-md-9">{{ $notification->message }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Message (FR)') }}:</div>
                        <div class="col-md-9">{{ $notification->message_fr ?? '-' }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Type') }}:</div>
                        <div class="col-md-9">
                            @php
                                $typeColors = [
                                    'general' => 'info',
                                    'update' => 'primary',
                                    'promotion' => 'success',
                                    'alert' => 'warning',
                                    'system' => 'secondary'
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$notification->type] ?? 'secondary' }}">
                                {{ ucfirst($notification->type) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Target') }}:</div>
                        <div class="col-md-9">
                            <span class="badge bg-purple">{{ ucfirst($notification->target) }}</span>
                        </div>
                    </div>
                    
                    @if($notification->target === 'specific_users' && $notification->user_ids)
                        <div class="row mb-3">
                            <div class="col-md-3 text-muted">{{ __('Target Users') }}:</div>
                            <div class="col-md-9">
                                <span class="badge bg-secondary">{{ count($notification->user_ids) }} {{ __('users') }}</span>
                            </div>
                        </div>
                    @endif
                    
                    @if($notification->target === 'plan' && $notification->plan_id)
                        <div class="row mb-3">
                            <div class="col-md-3 text-muted">{{ __('Target Plan') }}:</div>
                            <div class="col-md-9">
                                <span class="badge bg-indigo">{{ $notification->plan->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @endif
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Status') }}:</div>
                        <div class="col-md-9">
                            @php
                                $statusColors = [
                                    'draft' => 'secondary',
                                    'scheduled' => 'warning',
                                    'sent' => 'success',
                                    'failed' => 'danger'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$notification->status] ?? 'secondary' }}">
                                {{ ucfirst($notification->status) }}
                            </span>
                        </div>
                    </div>
                    
                    @if($notification->scheduled_at)
                        <div class="row mb-3">
                            <div class="col-md-3 text-muted">{{ __('Scheduled At') }}:</div>
                            <div class="col-md-9">{{ $notification->scheduled_at->format('Y-m-d H:i') }}</div>
                        </div>
                    @endif
                    
                    @if($notification->sent_at)
                        <div class="row mb-3">
                            <div class="col-md-3 text-muted">{{ __('Sent At') }}:</div>
                            <div class="col-md-9">{{ $notification->sent_at->format('Y-m-d H:i') }}</div>
                        </div>
                    @endif
                    
                    @if($notification->action_url)
                        <div class="row mb-3">
                            <div class="col-md-3 text-muted">{{ __('Action URL') }}:</div>
                            <div class="col-md-9">
                                <code>{{ $notification->action_url }}</code>
                            </div>
                        </div>
                    @endif
                    
                    @if($notification->image_url)
                        <div class="row mb-3">
                            <div class="col-md-3 text-muted">{{ __('Image') }}:</div>
                            <div class="col-md-9">
                                <img src="{{ $notification->image_url }}" alt="Notification Image" class="img-thumbnail" style="max-width: 300px;">
                            </div>
                        </div>
                    @endif
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Created') }}:</div>
                        <div class="col-md-9">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 text-muted">{{ __('Updated') }}:</div>
                        <div class="col-md-9">{{ $notification->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Delivery Statistics') }}</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">{{ __('Total Recipients') }}</span>
                            <strong>{{ number_format($notification->total_recipients ?? 0) }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">{{ __('Delivered') }}</span>
                            <strong class="text-success">{{ number_format($notification->delivered_count ?? 0) }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $notification->total_recipients > 0 ? ($notification->delivered_count / $notification->total_recipients * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">{{ __('Failed') }}</span>
                            <strong class="text-danger">{{ number_format($notification->failed_count ?? 0) }}</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" style="width: {{ $notification->total_recipients > 0 ? ($notification->failed_count / $notification->total_recipients * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Delivery Rate') }}</span>
                            <strong class="text-primary">
                                {{ $notification->total_recipients > 0 ? number_format(($notification->delivered_count / $notification->total_recipients * 100), 1) : 0 }}%
                            </strong>
                        </div>
                    </div>
                    
                    @if($notification->status === 'sent' && $notification->sent_at)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">{{ __('Sent') }}</span>
                                <span>{{ $notification->sent_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Actions') }}</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($notification->status === 'draft')
                            <form action="{{ route('push-notifications.send', $notification) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100" onclick="return confirm('{{ __('Send this notification now?') }}')">
                                    <i class="ti ti-send"></i> {{ __('Send Notification') }}
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('push-notifications.edit', $notification) }}" class="btn btn-primary">
                            <i class="ti ti-edit"></i> {{ __('Edit Notification') }}
                        </a>
                        
                        <a href="{{ route('push-notifications.create') }}?duplicate={{ $notification->id }}" class="btn btn-info">
                            <i class="ti ti-copy"></i> {{ __('Duplicate') }}
                        </a>
                        
                        <form action="{{ route('push-notifications.destroy', $notification) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('{{ __('Are you sure you want to delete this notification?') }}')">
                                <i class="ti ti-trash"></i> {{ __('Delete') }}
                            </button>
                        </form>
                        
                        <a href="{{ route('push-notifications.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
