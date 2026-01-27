<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Symfony\Component\HttpFoundation\Response;
    

class Google2fa
{
    public function handle(Request $request, Closure $next): Response
{
    $user = Auth::user();

    if ($user && $user->google2fa_enable == 1 && $user->google2fa_secret) {
        if (Session::get('2fa_verified', false)) {
            return $next($request);
        }

        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            Session::put('2fa_verified', true); 
            return $next($request);
        }
        return redirect()->route('2fa.index');
    }

    return $next($request); 
}

}
