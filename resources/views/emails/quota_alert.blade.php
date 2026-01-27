<p>Bonjour {{ $user->name ?? 'utilisateur' }},</p>

<p>Votre quota pour <strong>{{ $label }}</strong> a atteint {{ $threshold }}%.</p>

<ul>
    <li>Limite mensuelle : {{ $limit }}</li>
    <li>Utilisé : {{ $used }}</li>
    <li>Restant : {{ $remaining }}</li>
</ul>

<p>@if($threshold >= 100)
    Votre quota est épuisé. Merci de mettre à niveau votre plan pour continuer à utiliser cette fonctionnalité.
@else
    Pensez à optimiser vos prochaines utilisations ou à mettre à niveau votre plan pour éviter l'interruption du service.
@endif</p>

@if($resetAt)
<p>Réinitialisation prévue le : {{ $resetAt->format('d/m/Y') }}</p>
@endif

<p>L'équipe Dossy</p>
