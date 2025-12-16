@extends('layouts.app')

@section('page-title', __('Mobile App Settings'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Mobile App Settings') }}</li>
@endsection

@section('content')
    <div class="row p-0 g-0">
        <div class="col-sm-12">
            <div class="row g-0">
                <div class="col-xl-3 border-end border-bottom">
                    <div class="card shadow-none bg-transparent sticky-top" style="top:30px">
                        <div class="list-group list-group-flush rounded-0" id="mobile-settings-sidenav">
                            <a href="#version-settings" class="list-group-item list-group-item-action border-0">
                                <i class="ti ti-versions me-2"></i>{{ __('Versions & Updates') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#maintenance-settings" class="list-group-item list-group-item-action border-0">
                                <i class="ti ti-tool me-2"></i>{{ __('Maintenance Mode') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#api-keys-settings" class="list-group-item list-group-item-action border-0">
                                <i class="ti ti-key me-2"></i>{{ __('API Keys') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#features-settings" class="list-group-item list-group-item-action border-0">
                                <i class="ti ti-toggle-left me-2"></i>{{ __('Features Toggle') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#limits-settings" class="list-group-item list-group-item-action border-0">
                                <i class="ti ti-chart-bar me-2"></i>{{ __('Plan Limits') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#info-settings" class="list-group-item list-group-item-action border-0">
                                <i class="ti ti-info-circle me-2"></i>{{ __('App Info & Support') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9" data-bs-spy="scroll" data-bs-target="#mobile-settings-sidenav" data-bs-offset="0" tabindex="0">
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Version Settings -->
                    <div class="card shadow-none rounded-0 border" id="version-settings">
                        {{ Form::open(['route' => 'mobile-app-settings.update-version', 'method' => 'POST']) }}
                        <div class="card-header">
                            <h5><i class="ti ti-versions me-2"></i>{{ __('App Versions & Force Update') }}</h5>
                            <small class="text-muted">{{ __('Manage app versions and force update settings') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Android Settings -->
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="ti ti-brand-android text-success me-2"></i>Android</h6>
                                    <div class="form-group mb-3">
                                        {{ Form::label('android_version', __('Current Version'), ['class' => 'form-label']) }}
                                        {{ Form::text('android_version', $settings->android_version, ['class' => 'form-control', 'placeholder' => '1.0.0', 'required' => true]) }}
                                        <small class="text-muted">Format: X.Y.Z (ex: 1.2.3)</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        {{ Form::label('android_build_number', __('Build Number'), ['class' => 'form-label']) }}
                                        {{ Form::number('android_build_number', $settings->android_build_number, ['class' => 'form-control', 'min' => 1, 'required' => true]) }}
                                    </div>
                                    <div class="form-group mb-3">
                                        <div class="form-check form-switch">
                                            {{ Form::checkbox('force_update_android', 1, $settings->force_update_android, ['class' => 'form-check-input', 'id' => 'force_update_android']) }}
                                            {{ Form::label('force_update_android', __('Force Update'), ['class' => 'form-check-label']) }}
                                        </div>
                                        <small class="text-muted">{{ __('Users with older versions will be required to update') }}</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        {{ Form::label('min_android_version', __('Minimum Required Version'), ['class' => 'form-label']) }}
                                        {{ Form::text('min_android_version', $settings->min_android_version, ['class' => 'form-control', 'placeholder' => '1.0.0']) }}
                                        <small class="text-muted">{{ __('Only enforced when Force Update is enabled') }}</small>
                                    </div>
                                </div>

                                <!-- iOS Settings -->
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="ti ti-brand-apple text-dark me-2"></i>iOS</h6>
                                    <div class="form-group mb-3">
                                        {{ Form::label('ios_version', __('Current Version'), ['class' => 'form-label']) }}
                                        {{ Form::text('ios_version', $settings->ios_version, ['class' => 'form-control', 'placeholder' => '1.0.0', 'required' => true]) }}
                                        <small class="text-muted">Format: X.Y.Z (ex: 1.2.3)</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        {{ Form::label('ios_build_number', __('Build Number'), ['class' => 'form-label']) }}
                                        {{ Form::number('ios_build_number', $settings->ios_build_number, ['class' => 'form-control', 'min' => 1, 'required' => true]) }}
                                    </div>
                                    <div class="form-group mb-3">
                                        <div class="form-check form-switch">
                                            {{ Form::checkbox('force_update_ios', 1, $settings->force_update_ios, ['class' => 'form-check-input', 'id' => 'force_update_ios']) }}
                                            {{ Form::label('force_update_ios', __('Force Update'), ['class' => 'form-check-label']) }}
                                        </div>
                                        <small class="text-muted">{{ __('Users with older versions will be required to update') }}</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        {{ Form::label('min_ios_version', __('Minimum Required Version'), ['class' => 'form-label']) }}
                                        {{ Form::text('min_ios_version', $settings->min_ios_version, ['class' => 'form-control', 'placeholder' => '1.0.0']) }}
                                        <small class="text-muted">{{ __('Only enforced when Force Update is enabled') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!-- Maintenance Mode -->
                    <div class="card shadow-none rounded-0 border" id="maintenance-settings">
                        {{ Form::open(['route' => 'mobile-app-settings.update-maintenance', 'method' => 'POST']) }}
                        <div class="card-header">
                            <h5><i class="ti ti-tool me-2"></i>{{ __('Maintenance Mode') }}</h5>
                            <small class="text-muted">{{ __('Control app availability for maintenance') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="ti ti-info-circle me-2"></i>
                                {{ __('When maintenance mode is enabled, users will see a maintenance message and cannot access the app.') }}
                            </div>
                            
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    {{ Form::checkbox('maintenance_mode', 1, $settings->maintenance_mode, ['class' => 'form-check-input', 'id' => 'maintenance_mode']) }}
                                    {{ Form::label('maintenance_mode', __('Enable Maintenance Mode'), ['class' => 'form-check-label']) }}
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                {{ Form::label('maintenance_message', __('Maintenance Message'), ['class' => 'form-label']) }}
                                {{ Form::textarea('maintenance_message', $settings->maintenance_message, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Nous effectuons une maintenance pour améliorer votre expérience. L\'application sera de retour bientôt.']) }}
                                <small class="text-muted">{{ __('This message will be displayed to users') }}</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('maintenance_start', __('Start Time (Optional)'), ['class' => 'form-label']) }}
                                        {{ Form::datetimeLocal('maintenance_start', $settings->maintenance_start ? $settings->maintenance_start->format('Y-m-d\TH:i') : null, ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('maintenance_end', __('End Time (Optional)'), ['class' => 'form-label']) }}
                                        {{ Form::datetimeLocal('maintenance_end', $settings->maintenance_end ? $settings->maintenance_end->format('Y-m-d\TH:i') : null, ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            </div>

                            @if($settings->isInMaintenance())
                                <div class="alert alert-warning">
                                    <i class="ti ti-alert-triangle me-2"></i>
                                    <strong>{{ __('App is currently in maintenance mode!') }}</strong>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!-- API Keys -->
                    <div class="card shadow-none rounded-0 border" id="api-keys-settings">
                        {{ Form::open(['route' => 'mobile-app-settings.update-api-keys', 'method' => 'POST']) }}
                        <div class="card-header">
                            <h5><i class="ti ti-key me-2"></i>{{ __('API Keys Configuration') }}</h5>
                            <small class="text-muted">{{ __('Manage third-party API keys for mobile app') }}</small>
                        </div>
                        <div class="card-body">
                            <!-- OpenAI -->
                            <h6 class="mb-3 text-primary"><i class="ti ti-robot me-2"></i>OpenAI (Chat GPT)</h6>
                            <div class="form-group mb-4">
                                {{ Form::label('openai_api_key', __('OpenAI API Key'), ['class' => 'form-label']) }}
                                {{ Form::text('openai_api_key', $settings->openai_api_key, ['class' => 'form-control', 'placeholder' => 'sk-...']) }}
                                <small class="text-muted">{{ __('Required for AI chat functionality') }}</small>
                            </div>

                            <hr>

                            <!-- Pinecone -->
                            <h6 class="mb-3 text-primary"><i class="ti ti-database me-2"></i>Pinecone (Vector Database)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('pinecone_api_key', __('Pinecone API Key'), ['class' => 'form-label']) }}
                                        {{ Form::text('pinecone_api_key', $settings->pinecone_api_key, ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('pinecone_environment', __('Environment'), ['class' => 'form-label']) }}
                                        {{ Form::text('pinecone_environment', $settings->pinecone_environment, ['class' => 'form-control', 'placeholder' => 'gcp-starter']) }}
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                {{ Form::label('pinecone_index_name', __('Index Name'), ['class' => 'form-label']) }}
                                {{ Form::text('pinecone_index_name', $settings->pinecone_index_name, ['class' => 'form-control', 'placeholder' => 'dossy-legal-docs']) }}
                            </div>

                            <hr>

                            <!-- Flutterwave -->
                            <h6 class="mb-3 text-primary"><i class="ti ti-credit-card me-2"></i>Flutterwave (Payments)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('flutterwave_public_key', __('Public Key'), ['class' => 'form-label']) }}
                                        {{ Form::text('flutterwave_public_key', $settings->flutterwave_public_key, ['class' => 'form-control', 'placeholder' => 'FLWPUBK-...']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('flutterwave_secret_key', __('Secret Key'), ['class' => 'form-label']) }}
                                        {{ Form::text('flutterwave_secret_key', $settings->flutterwave_secret_key, ['class' => 'form-control', 'placeholder' => 'FLWSECK-...']) }}
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                {{ Form::label('flutterwave_environment', __('Environment'), ['class' => 'form-label']) }}
                                {{ Form::select('flutterwave_environment', ['test' => 'Test', 'live' => 'Live'], $settings->flutterwave_environment, ['class' => 'form-control']) }}
                            </div>

                            <hr>

                            <!-- Firebase -->
                            <h6 class="mb-3 text-primary"><i class="ti ti-bell me-2"></i>Firebase (Push Notifications)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('firebase_server_key', __('Server Key'), ['class' => 'form-label']) }}
                                        {{ Form::text('firebase_server_key', $settings->firebase_server_key, ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('firebase_messaging_sender_id', __('Sender ID'), ['class' => 'form-label']) }}
                                        {{ Form::text('firebase_messaging_sender_id', $settings->firebase_messaging_sender_id, ['class' => 'form-control']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!-- Features Toggle -->
                    <div class="card shadow-none rounded-0 border" id="features-settings">
                        {{ Form::open(['route' => 'mobile-app-settings.update-features', 'method' => 'POST']) }}
                        <div class="card-header">
                            <h5><i class="ti ti-toggle-left me-2"></i>{{ __('Features Toggle') }}</h5>
                            <small class="text-muted">{{ __('Enable or disable app features globally') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><i class="ti ti-message-circle me-2 text-success"></i>{{ __('AI Chat') }}</h6>
                                                    <small class="text-muted">{{ __('Allow users to chat with AI assistant') }}</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    {{ Form::checkbox('chat_enabled', 1, $settings->chat_enabled, ['class' => 'form-check-input', 'id' => 'chat_enabled']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><i class="ti ti-files me-2 text-primary"></i>{{ __('Documents Library') }}</h6>
                                                    <small class="text-muted">{{ __('Access to legal documents library') }}</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    {{ Form::checkbox('documents_enabled', 1, $settings->documents_enabled, ['class' => 'form-check-input', 'id' => 'documents_enabled']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><i class="ti ti-tools me-2 text-warning"></i>{{ __('Student Tools') }}</h6>
                                                    <small class="text-muted">{{ __('Tools for students (QCM, Flashcards, etc.)') }}</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    {{ Form::checkbox('tools_enabled', 1, $settings->tools_enabled, ['class' => 'form-check-input', 'id' => 'tools_enabled']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><i class="ti ti-gift me-2 text-info"></i>{{ __('Referral Program') }}</h6>
                                                    <small class="text-muted">{{ __('Users can refer friends and earn rewards') }}</small>
                                                </div>
                                                <div class="form-check form-switch">
                                                    {{ Form::checkbox('referral_enabled', 1, $settings->referral_enabled, ['class' => 'form-check-input', 'id' => 'referral_enabled']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!-- Plan Limits -->
                    <div class="card shadow-none rounded-0 border" id="limits-settings">
                        {{ Form::open(['route' => 'mobile-app-settings.update-limits', 'method' => 'POST']) }}
                        <div class="card-header">
                            <h5><i class="ti ti-chart-bar me-2"></i>{{ __('Subscription Plan Limits') }}</h5>
                            <small class="text-muted">{{ __('Define usage limits for each subscription plan') }}</small>
                        </div>
                        <div class="card-body">
                            @php
                                $plans = [
                                    'free_plan_limits' => ['name' => 'Gratuit', 'color' => 'secondary'],
                                    'student_plan_limits' => ['name' => 'Étudiant', 'color' => 'info'],
                                    'professional_plan_limits' => ['name' => 'Professionnel', 'color' => 'primary'],
                                    'cabinet_plan_limits' => ['name' => 'Cabinet/Entreprise', 'color' => 'success']
                                ];
                            @endphp

                            @foreach($plans as $planKey => $planInfo)
                                @php
                                    $limits = $settings->$planKey ?? [];
                                @endphp
                                <div class="card border mb-3">
                                    <div class="card-header bg-{{ $planInfo['color'] }} text-white">
                                        <h6 class="mb-0">{{ $planInfo['name'] }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    {{ Form::label($planKey.'[searches]', __('Searches'), ['class' => 'form-label']) }}
                                                    {{ Form::number($planKey.'[searches]', $limits['searches'] ?? 0, ['class' => 'form-control', 'min' => -1]) }}
                                                    <small class="text-muted">-1 = {{ __('Unlimited') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    {{ Form::label($planKey.'[analyses]', __('Analyses'), ['class' => 'form-label']) }}
                                                    {{ Form::number($planKey.'[analyses]', $limits['analyses'] ?? 0, ['class' => 'form-control', 'min' => -1]) }}
                                                    <small class="text-muted">-1 = {{ __('Unlimited') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    {{ Form::label($planKey.'[downloads]', __('Downloads'), ['class' => 'form-label']) }}
                                                    {{ Form::number($planKey.'[downloads]', $limits['downloads'] ?? 0, ['class' => 'form-control', 'min' => -1]) }}
                                                    <small class="text-muted">-1 = {{ __('Unlimited') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-3">
                                                    {{ Form::label($planKey.'[messages_per_day]', __('Messages/Day'), ['class' => 'form-label']) }}
                                                    {{ Form::number($planKey.'[messages_per_day]', $limits['messages_per_day'] ?? 0, ['class' => 'form-control', 'min' => -1]) }}
                                                    <small class="text-muted">-1 = {{ __('Unlimited') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>

                    <!-- App Info & Support -->
                    <div class="card shadow-none rounded-0 border" id="info-settings">
                        {{ Form::open(['route' => 'mobile-app-settings.update-info', 'method' => 'POST']) }}
                        <div class="card-header">
                            <h5><i class="ti ti-info-circle me-2"></i>{{ __('App Information & Support') }}</h5>
                            <small class="text-muted">{{ __('Configure app store links and support contacts') }}</small>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">{{ __('App Store Links') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('play_store_url', __('Google Play Store URL'), ['class' => 'form-label']) }}
                                        {{ Form::url('play_store_url', $settings->play_store_url, ['class' => 'form-control', 'placeholder' => 'https://play.google.com/store/apps/details?id=...']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('app_store_url', __('Apple App Store URL'), ['class' => 'form-label']) }}
                                        {{ Form::url('app_store_url', $settings->app_store_url, ['class' => 'form-control', 'placeholder' => 'https://apps.apple.com/app/...']) }}
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3">{{ __('Legal Pages') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('privacy_policy_url', __('Privacy Policy URL'), ['class' => 'form-label']) }}
                                        {{ Form::url('privacy_policy_url', $settings->privacy_policy_url, ['class' => 'form-control', 'placeholder' => 'https://dossypro.com/privacy']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        {{ Form::label('terms_of_service_url', __('Terms of Service URL'), ['class' => 'form-label']) }}
                                        {{ Form::url('terms_of_service_url', $settings->terms_of_service_url, ['class' => 'form-control', 'placeholder' => 'https://dossypro.com/terms']) }}
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3">{{ __('Support Contacts') }}</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        {{ Form::label('support_email', __('Support Email'), ['class' => 'form-label']) }}
                                        {{ Form::email('support_email', $settings->support_email, ['class' => 'form-control', 'placeholder' => 'support@dossypro.com']) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        {{ Form::label('support_phone', __('Support Phone'), ['class' => 'form-label']) }}
                                        {{ Form::text('support_phone', $settings->support_phone, ['class' => 'form-control', 'placeholder' => '+225 07 00 00 00 00']) }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        {{ Form::label('whatsapp_number', __('WhatsApp Number'), ['class' => 'form-label']) }}
                                        {{ Form::text('whatsapp_number', $settings->whatsapp_number, ['class' => 'form-control', 'placeholder' => '+225 07 00 00 00 00']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>{{ __('Save Changes') }}
                            </button>
                        </div>
                        {{ Form::close() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
