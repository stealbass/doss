<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FAQRCode\Google2FA;
use Illuminate\Support\Facades\Hash;

class GoogleAuthenticationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show2faForm()
    {
        if (Session::get('2fa_verified', false)) {
            return redirect()->route('dashboard');
        }
        return view('auth.2fa');
    }

    public function verify2fa(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $google2fa = new Google2FA();
        $user = Auth::user();

        if ($google2fa->verifyKey($user->google2fa_secret, $request->one_time_password)) {
            Session::put('2fa_verified', true); // 
            return redirect()->back()->with('success', '2FA verification successful.');
        }

        return back()->withErrors(['one_time_password' => 'Invalid OTP, please try again.']);
    }

    public function generate2faSecret()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $google2fa = new Google2FA();

            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->google2fa_enable = 0;
                $user->save();
            }

            // Generate QR Code
            $qrCodeInline = $google2fa->getQRCodeInline(
                config('app.name'),
                $user->email,
                $user->google2fa_secret
            );

            return view('users.edit', [
                'user' => $user,
                'data' => [
                    'user' => $user,
                    'secret' => $user->google2fa_secret,
                    'google2fa_url' => $qrCodeInline,
                ],
            ])->with('success', __('Secret key is generated.'));
        }

        return redirect()->route('login')->with('error', __('Please log in to generate a 2FA secret.'));
    }

    public function enable2fa(Request $request)
    {
        $user = Auth::user();
        $google2fa = new Google2FA();

        if ($google2fa->verifyKey($user->google2fa_secret, $request->input('secret'))) {
            $user->google2fa_enable = 1;
            $user->save();
            return redirect()->back()->with('success', __('2FA is enabled successfully.'));
        }

        return redirect()->back()->with('error', __('Invalid verification Code, Please try again.'));
    }

    public function disable2fa(Request $request)
    {
        $request->validate(['current-password' => 'required']);

        $user = Auth::user();
        if (!Hash::check($request->input('current-password'), $user->password)) {
            return back()->withErrors(['current-password' => 'Your password does not match your account password.']);
        }

        $user->google2fa_enable = 0;
        $user->google2fa_secret = null;
        $user->save();

        return back()->with('success', 'Two-Factor Authentication has been disabled successfully.');
    }
}
