<?php

namespace App\Http\Controllers;

use App\Mail\AccountDeletionFormMessageMail;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicAccountDeletionController extends Controller
{
    public function submit(Request $request)
    {
        $settings = Utility::settings(1);

        $rules = [
            'name' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:5000',
            'reason' => 'nullable|string|max:5000',
            'details' => 'nullable|string|max:5000',
            'description' => 'nullable|string|max:5000',
        ];

        if (($settings['recaptcha_module'] ?? 'off') === 'on') {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }

        $validator = Validator::make(
            $request->all(),
            $rules,
            [
                'g-recaptcha-response.required' => __('Veuillez valider le captcha.'),
                'g-recaptcha-response.captcha' => __('Verification captcha invalide. Veuillez reessayer.'),
            ]
        );

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
            return redirect()->back()->withInput()->with('error', $error);
        }

        $validated = $validator->validated();

        $name = $validated['name'] ?? $validated['full_name'] ?? 'Utilisateur';
        $message = $validated['message']
            ?? $validated['reason']
            ?? $validated['details']
            ?? $validated['description']
            ?? null;

        if (empty($message)) {
            $error = __('Le message est obligatoire.');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
            return redirect()->back()->withInput()->with('error', $error);
        }

        $payload = [
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? __('Demande de suppression de compte'),
            'message' => $message,
            'source_url' => $request->headers->get('referer') ?? $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now()->toDateTimeString(),
        ];

        $recipients = $this->resolveRecipients();
        if (empty($recipients)) {
            $error = __('Aucun destinataire n\'est configure pour recevoir les demandes.');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 500);
            }
            return redirect()->back()->withInput()->with('error', $error);
        }

        try {
            Utility::getSMTPDetails();
            Mail::to($recipients)->send(new AccountDeletionFormMessageMail($payload));
        } catch (\Throwable $e) {
            Log::error('Account deletion form mail send failed: ' . $e->getMessage());

            $error = __('Envoi impossible pour le moment. Veuillez reessayer.');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 500);
            }
            return redirect()->back()->withInput()->with('error', $error);
        }

        $success = __('Votre demande a bien ete envoyee. Nous vous contacterons rapidement.');
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $success]);
        }

        return redirect()->back()->with('success', $success);
    }

    private function resolveRecipients(): array
    {
        $settings = Utility::settings(1);

        // Custom recipient configurable from admin settings table (comma-separated emails).
        $customRecipients = [];
        if (!empty($settings['account_deletion_contact_email'])) {
            $customRecipients = array_filter(array_map('trim', explode(',', (string) $settings['account_deletion_contact_email'])));
        }
        if (!empty($customRecipients)) {
            return $customRecipients;
        }

        // Fallback to configured admin sender address in Settings.
        if (!empty($settings['mail_from_address'])) {
            return [(string) $settings['mail_from_address']];
        }

        $adminRecipients = User::where('type', 'super admin')->pluck('email')->filter()->unique()->values()->all();
        if (!empty($adminRecipients)) {
            return $adminRecipients;
        }

        return [];
    }
}
