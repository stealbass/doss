@extends('layouts.app')

@section('page-title', __('Case'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Case') }}</li>
@endsection

@push('css')
<style>
    /* Style pour les tabs de l'affaire */
    #caseTabs .nav-link {
        color: #6c757d;
        border: 1px solid transparent;
        border-bottom: 3px solid transparent;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    #caseTabs .nav-link:hover {
        color: #28a745;
        border-bottom-color: #d4edda;
        background-color: #f8fff9;
    }
    
    #caseTabs .nav-link.active {
        color: #fff !important;
        background: linear-gradient(135deg, #28a745 0%, #20923d 100%) !important;
        border-color: #28a745 #28a745 #28a745 !important;
        border-radius: 0.375rem 0.375rem 0 0;
        box-shadow: 0 -2px 8px rgba(40, 167, 69, 0.3);
        font-weight: 600;
    }
    
    #caseTabs .nav-link.active i {
        color: #fff !important;
    }
    
    #caseTabs .nav-link i {
        margin-right: 5px;
        font-size: 1.1em;
    }
</style>
@endpush

@php
    $docfile = \App\Models\Utility::get_file('uploads/case_docs/');
    $filing_date = '';
    if (!empty($case->filing_date)) {
        $filing_date = date('d-m-Y', strtotime($case->filing_date));
    }
    $documentsfile = \App\Models\Utility::get_file('uploads/documents/');
@endphp

@php
    // Récupère la liste des clients (id => nom)
    $clients_list = \App\Models\User::where('type', 'client')->pluck('name', 'id')->toArray();
@endphp

@section('content')
    <div class="row p-0 g-0">
        <div class="col-xl-12">
            <!-- Section des informations de l'affaire (en haut) -->
            <div class="card shadow-none shadow-none rounded-0 border">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">{{ __('Détails de l\'affaire') }}</h5>
                        </div>
                        <div class="col-auto">
                            @can('edit case')
                                <a href="{{ route('cases.edit', $case->id) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-pencil"></i> {{ __('Modifier') }}
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="row p-0">
                            <dl class="row col-md-6 p-5 py-2">
                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Courts/Tribunal:') }}</span></dt>
                                <dd class="col-md-7">
                                    <span
                                        class="text-md">{{ App\Models\CauseList::getCourtById($case->court) ?? '-' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Case no.:') }}</span></dt>
                                <dd class="col-md-7">
                                    <span class="text-md">{{ !empty($case->case_number) ? $case->case_number : '-' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Case Type:') }}</span></dt>
                                <dd class="col-md-7">
                                    <span class="text-md">{{ !empty($case->casenumber) ? $case->casenumber : '-' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Year:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->year ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Title:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->title ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Date of filing:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $filing_date ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Judge name:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->judge ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Court Room no.:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->court_room ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Under Acts:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->under_acts ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Under Sections:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->under_sections ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('FIR Police Station:') }}</span>
                                </dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->police_station ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('FIR No:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->FIR_number ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('FIR Year:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->FIR_year ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Stage:') }}</span></dt>
                                <dd class="col-md-7"><span class="text-md">{{ $case->stage ?? '-' }}</span></dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Your Party:') }}</span></dt>
                                <dd class="col-md-7"><span
                                        class="text-md">{{ $case->your_party == 0 ? 'Petitioner/Plaintiff' : 'Respondent/Defendant' }}</span>
                                </dd>

                                <dt class="col-md-5"><span class="h6 text-md mb-0">{{ __('Advocates:') }}</span></dt>
                                <dd class="col-md-7"><span
                                        class="text-md">{{ App\Models\Advocate::getAdvocates($case->advocates) ?? '-' }}</span>
                                </dd>
                            </dl>

                            <dl class="row col-md-6 p-5 py-2">
                                @if (!empty($case->your_party_name))
                                    <div class="col-12">
                                        <h5>{{ __('Your Party Name') }}</h5>
                                        <hr class="mt-2 mb-2">
                                    </div>
                                    @foreach (json_decode($case->your_party_name, true) as $key => $opp)
                                        <dt class="col-md-12">
                                            <span class="h6 text-md mb-0">{{ __('Party Name ' . $key + 1) }}</span>
                                        </dt>
                                        <dt class="col-md-2"><span class="h6 text-md mb-0">{{ __('Name:') }}</span></dt>
                                        <dd class="col-md-10">
                                            <span class="text-md">
                                                {{ $clients_list[$opp['clients']] ?? '-' }}
                                            </span>
                                        </dd>
                                    @endforeach
                                @endif

                                @if (!empty($case->opp_party_name))
                                    <div class="col-12">
                                        <h5>{{ __('Opposite Party') }}</h5>
                                        <hr class="mt-2 mb-2">
                                    </div>
                                    @foreach (json_decode($case->opp_party_name, true) as $key => $opp)
                                        <dt class="col-md-12"><span
                                                class="h6 text-md mb-0">{{ __('Opposite Party ' . $key + 1) }}</span>
                                        </dt>
                                        <dt class="col-md-2">
                                            <span class="h6 text-md mb-0">{{ __('Name:') }}</span>
                                        </dt>
                                        <dd class="col-md-10">
                                            <span class="text-md">{{ $opp['name'] ?? '-' }}</span>
                                        </dd>
                                    @endforeach
                                @endif
                                <div class="col-12">
                                    <hr class="mt-2 mb-2">
                                    <h5>{{ __('Description') }}</h5>
                                </div>
                                <dd class="col-md-12"><span class="text-md">{!! $case->description ?? '-' !!}</span></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section à onglets (Audiences, Documents, Tâches, Notes) -->
            <div class="card shadow-none rounded-0 border">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs mb-3" id="caseTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="hearings-tab" data-bs-toggle="tab" 
                                data-bs-target="#hearings" type="button" role="tab" aria-controls="hearings" 
                                aria-selected="true">
                                <i class="ti ti-gavel"></i> {{ __('Audiences/Interventions') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" 
                                data-bs-target="#documents-content" type="button" role="tab" 
                                aria-controls="documents-content" aria-selected="false">
                                <i class="ti ti-file"></i> {{ __('Documents') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" 
                                data-bs-target="#tasks" type="button" role="tab" aria-controls="tasks" 
                                aria-selected="false">
                                <i class="ti ti-checkbox"></i> {{ __('Tâches') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notes-tab" data-bs-toggle="tab" 
                                data-bs-target="#notes-content" type="button" role="tab" 
                                aria-controls="notes-content" aria-selected="false">
                                <i class="ti ti-notes"></i> {{ __('Notes/Commentaires') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="caseTabsContent">
                        <!-- Tab 1: Audiences/Interventions -->
                        <div class="tab-pane fade show active" id="hearings" role="tabpanel" 
                            aria-labelledby="hearings-tab">
                            <div class="card-header border-0 bg-light">
                                <div class="row align-items-center justify-content-between gap-2">
                                    <div class="col-auto">
                                        <h5 class="mb-0"> {{ __('Audiences/Interventions') }} </h5>
                                    </div>
                                    @if (Auth::user()->type != 'client')
                                        <div class="col-auto">
                                            <a href="#" class="btn btn-sm btn-primary mx-1"
                                                data-ajax-popup="true" data-title="{{ __('Import Hearing') }}"
                                                data-url="{{ route('hearing.file.import', $case->id) }}"
                                                data-bs-original-title="{{ __('Import') }}" data-bs-placement="top"
                                                data-bs-toggle="tooltip">
                                                <i class="ti ti-file-import"></i> {{ __('Importer') }}
                                            </a>
                                            <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true"
                                                data-size="md" data-title="{{ __('Create Hearing') }}"
                                                data-url="{{ route('hearings.create', $case->id) }}"
                                                data-bs-original-title="{{ __('Create') }}" data-bs-placement="top"
                                                data-bs-toggle="tooltip">
                                                <i class="ti ti-plus"></i> {{ __('Créer une audience') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body table-border-style">
                                <div class="table-responsive">
                                    <table class="table dataTable data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('#') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Remarks') }}</th>
                                                <th>{{ __('Order Sheet') }}</th>
                                                <th width="100px">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($hearings as $key => $hearing)
                                                <tr>
                                                    <td> {{ $key + 1 }} </td>
                                                    <td> {{ date('d-m-Y ', strtotime($hearing->date)) ?? '-' }} </td>
                                                    <td> {{ $hearing->remarks ?? '-' }} </td>
                                                    <td>
                                                        @if (!empty($hearing->order_seet))
                                                            <a href="{{ $documentsfile . $hearing->order_seet }}"
                                                                target="_blank">{{ __('View') }}
                                                            </a>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (Auth::user()->type != 'client')
                                                            <div class="action-btn me-2">
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm btn-info align-items-center "
                                                                    data-url="{{ route('hearing.edit', $hearing->id) }}"
                                                                    data-size="md" data-ajax-popup="true"
                                                                    data-title="{{ __('Update Hearing') }}"
                                                                    title="{{ __('Edit') }}"
                                                                    data-bs-toggle="tooltip" data-bs-placement="top">
                                                                    <i class="ti ti-pencil "></i>
                                                                </a>
                                                            </div>
                                                            <div class="action-btn me-2">
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                                    data-confirm="{{ __('Are You Sure?') }}"
                                                                    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="delete-form-{{ $hearing->id }}"
                                                                    title="{{ __('Delete') }}"
                                                                    data-bs-toggle="tooltip" data-bs-placement="top">
                                                                    <i class="ti ti-trash"></i>
                                                                </a>
                                                            </div>
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['hearing.destroy', $hearing->id],
                                                                'id' => 'delete-form-' . $hearing->id,
                                                            ]) !!}
                                                            {!! Form::close() !!}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">{{ __('Aucune audience trouvée') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Documents -->
                        <div class="tab-pane fade" id="documents-content" role="tabpanel" 
                            aria-labelledby="documents-tab">
                            <div class="card-header border-0 bg-light">
                                <div class="row align-items-center justify-content-between gap-2">
                                    <div class="col-auto">
                                        <h5 class="mb-0"> {{ __('Documents') }} </h5>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('documents.index') }}?case={{ $case->id }}" 
                                            class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i> {{ __('Ajouter un document') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body table-border-style">
                                <div class="table-responsive">
                                    <table class="table dataTable data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('#') }}</th>
                                                <th>{{ __('Nom du document') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Date de création') }}</th>
                                                <th width="150px">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($docs as $key => $document)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $document->name ?? '-' }}</td>
                                                    <td>{{ $document->purpose ?? '-' }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($document->created_at)) }}</td>
                                                    <td>
                                                        <a href="{{ $documentsfile . $document->file }}" 
                                                            target="_blank" 
                                                            class="btn btn-sm btn-info"
                                                            data-bs-toggle="tooltip"
                                                            title="{{ __('Voir') }}">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                        <a href="{{ $documentsfile . $document->file }}" 
                                                            target="_blank" 
                                                            download
                                                            class="btn btn-sm btn-primary"
                                                            data-bs-toggle="tooltip"
                                                            title="{{ __('Télécharger') }}">
                                                            <i class="ti ti-download"></i>
                                                        </a>
                                                        @if (Auth::user()->type != 'client')
                                                            <a href="#"
                                                                class="btn btn-sm btn-danger bs-pass-para"
                                                                data-confirm="{{ __('Are You Sure?') }}"
                                                                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                data-confirm-yes="delete-document-{{ $document->id }}"
                                                                title="{{ __('Delete') }}"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-placement="top">
                                                                <i class="ti ti-trash"></i>
                                                            </a>
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['documents.destroy', $document->id],
                                                                'id' => 'delete-document-' . $document->id,
                                                            ]) !!}
                                                            {!! Form::close() !!}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">{{ __('Aucun document trouvé') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Tâches -->
                        <div class="tab-pane fade" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
                            <div class="card-header border-0 bg-light">
                                <div class="row align-items-center justify-content-between gap-2">
                                    <div class="col-auto">
                                        <h5 class="mb-0"> {{ __('Tâches') }} </h5>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('to-do.index') }}?case={{ $case->id }}" 
                                            class="btn btn-sm btn-primary">
                                            <i class="ti ti-plus"></i> {{ __('Ajouter une tâche') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body table-border-style">
                                <div class="table-responsive">
                                    <table class="table dataTable data-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('#') }}</th>
                                                <th>{{ __('Titre') }}</th>
                                                <th>{{ __('Priorité') }}</th>
                                                <th>{{ __('Date limite') }}</th>
                                                <th>{{ __('Statut') }}</th>
                                                <th width="100px">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($todos as $key => $todo)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $todo->description ?? '-' }}</td>
                                                    <td>
                                                        @if($todo->priority == 'high')
                                                            <span class="badge bg-danger">{{ __('Haute') }}</span>
                                                        @elseif($todo->priority == 'medium')
                                                            <span class="badge bg-warning">{{ __('Moyenne') }}</span>
                                                        @else
                                                            <span class="badge bg-info">{{ __('Basse') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ !empty($todo->due_date) ? date('d-m-Y', strtotime($todo->due_date)) : '-' }}</td>
                                                    <td>
                                                        @if($todo->status == 'complete')
                                                            <span class="badge bg-success">{{ __('Complété') }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ __('En cours') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (Auth::user()->type != 'client')
                                                            <div class="action-btn me-2">
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm btn-info align-items-center"
                                                                    data-url="{{ route('to-do.edit', $todo->id) }}"
                                                                    data-size="lg"
                                                                    data-ajax-popup="true"
                                                                    data-title="{{ __('Modifier la tâche') }}"
                                                                    title="{{ __('Modifier') }}"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-placement="top">
                                                                    <i class="ti ti-pencil"></i>
                                                                </a>
                                                            </div>
                                                            <div class="action-btn me-2">
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm btn-danger align-items-center bs-pass-para"
                                                                    data-confirm="{{ __('Are You Sure?') }}"
                                                                    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="delete-todo-{{ $todo->id }}"
                                                                    title="{{ __('Delete') }}"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-placement="top">
                                                                    <i class="ti ti-trash"></i>
                                                                </a>
                                                            </div>
                                                            {!! Form::open([
                                                                'method' => 'DELETE',
                                                                'route' => ['to-do.destroy', $todo->id],
                                                                'id' => 'delete-todo-' . $todo->id,
                                                            ]) !!}
                                                            {!! Form::close() !!}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">{{ __('Aucune tâche trouvée') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Notes/Commentaires -->
                        <div class="tab-pane fade" id="notes-content" role="tabpanel" 
                            aria-labelledby="notes-tab">
                            <div class="card-header border-0 bg-light">
                                <div class="row align-items-center justify-content-between gap-2">
                                    <div class="col-auto">
                                        <h5 class="mb-0"> {{ __('Notes/Commentaires') }} </h5>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true"
                                            data-size="md" data-title="{{ __('Ajouter une note') }}"
                                            data-url="{{ route('case-notes.create', $case->id) }}"
                                            data-bs-original-title="{{ __('Create') }}" data-bs-placement="top"
                                            data-bs-toggle="tooltip">
                                            <i class="ti ti-plus"></i> {{ __('Ajouter une note') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                @forelse($notes as $note)
                                    <div class="card mb-3 border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong>{{ $note->user->name }}</strong>
                                                    <small class="text-muted ms-2">
                                                        {{ date('d/m/Y à H:i', strtotime($note->created_at)) }}
                                                    </small>
                                                </div>
                                                @if($note->user_id == Auth::user()->id || Auth::user()->type == 'company')
                                                    <div class="action-btn">
                                                        <a href="#"
                                                            class="btn btn-sm btn-danger bs-pass-para"
                                                            data-confirm="{{ __('Are You Sure?') }}"
                                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="delete-note-{{ $note->id }}"
                                                            title="{{ __('Delete') }}"
                                                            data-bs-toggle="tooltip">
                                                            <i class="ti ti-trash"></i>
                                                        </a>
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['case-notes.destroy', $note->id],
                                                            'id' => 'delete-note-' . $note->id,
                                                        ]) !!}
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="mb-2">{{ $note->note }}</p>
                                            <div class="mt-2">
                                                <a href="#" class="btn btn-sm btn-outline-primary" 
                                                    data-ajax-popup="true"
                                                    data-size="md" 
                                                    data-title="{{ __('Répondre à la note') }}"
                                                    data-url="{{ route('case-notes.reply-form', $note->id) }}"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('Répondre') }}">
                                                    <i class="ti ti-message"></i> {{ __('Répondre') }}
                                                </a>
                                            </div>

                                            <!-- Afficher les réponses -->
                                            @if($note->replies->count() > 0)
                                                <div class="mt-3 ms-4 border-start ps-3">
                                                    @foreach($note->replies as $reply)
                                                        <div class="card mb-2 bg-light">
                                                            <div class="card-body p-2">
                                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                                    <div>
                                                                        <strong class="text-sm">{{ $reply->user->name }}</strong>
                                                                        <small class="text-muted ms-2">
                                                                            {{ date('d/m/Y à H:i', strtotime($reply->created_at)) }}
                                                                        </small>
                                                                    </div>
                                                                    @if($reply->user_id == Auth::user()->id || Auth::user()->type == 'company')
                                                                        <div class="action-btn">
                                                                            <a href="#"
                                                                                class="btn btn-sm btn-danger bs-pass-para"
                                                                                data-confirm="{{ __('Are You Sure?') }}"
                                                                                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                                                data-confirm-yes="delete-note-{{ $reply->id }}"
                                                                                title="{{ __('Delete') }}"
                                                                                data-bs-toggle="tooltip">
                                                                                <i class="ti ti-trash"></i>
                                                                            </a>
                                                                            {!! Form::open([
                                                                                'method' => 'DELETE',
                                                                                'route' => ['case-notes.destroy', $reply->id],
                                                                                'id' => 'delete-note-' . $reply->id,
                                                                            ]) !!}
                                                                            {!! Form::close() !!}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <p class="mb-0 text-sm">{{ $reply->note }}</p>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <i class="ti ti-notes" style="font-size: 48px; color: #ccc;"></i>
                                        <p class="text-muted mt-3">{{ __('Aucune note ou commentaire pour le moment') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
