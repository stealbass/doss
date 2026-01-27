{{ Form::open(['route' => ['bill.post.send.email', $bill->id], 'method' => 'POST', 'id' => 'send-bill-email-form']) }}
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
                {{ Form::textarea('message', __('Veuillez trouver ci-dessous le détail de votre facture.'), [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => __('Message personnalisé')
                ]) }}
            </div>
        </div>
        
        <div class="col-md-12 mt-3">
            <div class="alert alert-info">
                <i class="ti ti-info-circle"></i>
                {{ __('Le détail complet de la facture sera inclus dans le corps de l\'email.') }}
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
    <button type="submit" class="btn btn-primary" id="send-bill-btn">
        <i class="ti ti-send"></i> {{ __('Envoyer') }}
    </button>
</div>
{{ Form::close() }}

<script>
$(document).ready(function() {
    $('#send-bill-email-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        var submitBtn = $('#send-bill-btn');
        
        // Désactiver le bouton pendant l'envoi
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>{{ __("Envoi en cours...") }}');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                // Fermer le modal
                $('#commonModal').modal('hide');
                
                // Afficher le message de succès
                if (response.success) {
                    show_toastr('{{ __("Success") }}', response.success, 'success');
                }
                
                // Réactiver le bouton
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="ti ti-send"></i> {{ __("Envoyer") }}');
            },
            error: function(xhr) {
                var errorMessage = '{{ __("Une erreur est survenue lors de l\'envoi de l\'email.") }}';
                
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMessage = response.error;
                        }
                    } catch (e) {
                        console.error('Erreur parsing response:', e);
                    }
                }
                
                // Afficher le message d'erreur
                show_toastr('{{ __("Error") }}', errorMessage, 'error');
                
                // Réactiver le bouton
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="ti ti-send"></i> {{ __("Envoyer") }}');
            }
        });
    });
});
</script>
