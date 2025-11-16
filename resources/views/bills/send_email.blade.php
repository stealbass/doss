{{ Form::open(['route' => ['bill.send.email', $bill->id], 'method' => 'POST']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('email', __('Email du Destinataire'), ['class' => 'form-label']) }}
                {{ Form::email('email', $clientEmail, [
                    'class' => 'form-control',
                    'required' => 'required',
                    'placeholder' => __('Entrer l\'email du destinataire')
                ]) }}
                <small class="text-muted">{{ __('La facture sera envoyée à cette adresse email') }}</small>
            </div>
        </div>
        
        <div class="col-md-12 mt-3">
            <div class="form-group">
                {{ Form::label('subject', __('Objet de l\'email'), ['class' => 'form-label']) }}
                {{ Form::text('subject', __('Facture') . ' #' . $bill->bill_number, [
                    'class' => 'form-control',
                    'required' => 'required',
                    'placeholder' => __('Objet de l\'email')
                ]) }}
            </div>
        </div>
        
        <div class="col-md-12 mt-3">
            <div class="form-group">
                {{ Form::label('message', __('Message'), ['class' => 'form-label']) }}
                {{ Form::textarea('message', __('Veuillez trouver ci-joint votre facture.'), [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => __('Message personnalisé')
                ]) }}
            </div>
        </div>
        
        <div class="col-md-12 mt-3">
            <div class="alert alert-info">
                <i class="ti ti-info-circle"></i>
                {{ __('Un fichier PDF de la facture sera automatiquement joint à cet email.') }}
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-send"></i> {{ __('Envoyer') }}
    </button>
</div>
{{ Form::close() }}
