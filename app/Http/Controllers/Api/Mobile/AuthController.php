<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\MobileAppSubscription;
use App\Models\MobileAppPlan;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Register a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Flutter sends 'password_confirmation', ensure compatibility
        if ($request->has('passwordConfirmation') && !$request->has('password_confirmation')) {
            $request->merge(['password_confirmation' => $request->passwordConfirmation]);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|string|same:password',
            'phone' => 'nullable|string|max:20',
            'jurisdiction' => 'nullable|string|max:10',
            'mobile_role' => 'nullable|string|in:student,lawyer,enterprise',
            'referral_code' => 'nullable|string|max:50',
            'device_type' => 'nullable|string|in:ios,android,web',
        ]);

        if ($validator->fails()) {
            Log::warning('Registration validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Log::info('Starting user registration', ['email' => $request->email, 'name' => $request->name]);

            // Create user with basic data
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => 'client',
                'lang' => 'fr',
                'is_active' => 1,
                'referral_code' => $this->generateUniqueReferralCode(),
            ]);

            Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

            // Create user details with phone and additional info
            if ($request->phone) {
                UserDetail::create([
                    'user_id' => $user->id,
                    'mobile_number' => $request->phone,
                ]);
                Log::info('UserDetail created', ['user_id' => $user->id, 'phone' => $request->phone]);
            }

            // Store jurisdiction and role in user table if columns exist
            // Otherwise we'll store them in a JSON field or separate table later
            if ($request->jurisdiction || $request->mobile_role) {
                $user->update([
                    'jurisdiction' => $request->jurisdiction,
                    'mobile_role' => $request->mobile_role ?? 'student',
                ]);
            }

            // Track mobile installation/activity
            $deviceType = $request->get('device_type');
            if ($deviceType && in_array($deviceType, ['ios', 'android', 'web'], true)) {
                $user->primary_device = $deviceType;
            }
            if (!$user->mobile_app_installed_at) {
                $user->mobile_app_installed_at = now();
            }
            $user->last_mobile_activity_at = now();
            $user->save();

            // Apply referral code if provided
            if ($request->referral_code) {
                try {
                    Log::info('Applying referral code', ['user_id' => $user->id, 'referral_code' => $request->referral_code]);
                    app(ReferralController::class)->applyReferralCode($user->id, $request->referral_code);
                } catch (\Exception $e) {
                    Log::warning('Failed to apply referral code', ['error' => $e->getMessage()]);
                    // Don't fail registration if referral code fails
                }
            }

            // Assign free plan by default
            $freePlan = MobileAppPlan::where('price_monthly', 0)->first();
            
            if (!$freePlan) {
                Log::error('No free plan found in database');
                throw new \Exception('Free plan not configured in system');
            }

            MobileAppSubscription::create([
                'user_id' => $user->id,
                'mobile_app_plan_id' => $freePlan->id,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => null, // Free plan never expires
                'auto_renew' => false,
                'searches_used' => 0,
                'ai_analyses_used' => 0,
                'pdf_downloads_used' => 0,
            ]);

            Log::info('Free subscription created', ['user_id' => $user->id, 'plan_id' => $freePlan->id]);

            // Generate API token
            $token = $user->createToken('mobile-app')->plainTextToken;

            Log::info('Registration completed successfully', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $request->phone,
                        'plan' => $freePlan->name ?? 'Gratuit',
                        'mobile_role' => $request->mobile_role ?? 'student',
                        'jurisdiction' => $request->jurisdiction,
                        'subscription_end' => null,
                        'searches_used' => 0,
                        'searches_limit' => $freePlan->searches_limit ?? 5,
                        'analyses_used' => 0,
                        'analyses_limit' => $freePlan->ai_analyses_limit ?? 2,
                        'downloads_used' => 0,
                        'downloads_limit' => $freePlan->pdf_downloads_limit ?? 0,
                        'messages_used' => 0,
                        'messages_limit' => $freePlan->messages_per_day_limit ?? 10,
                        'referral_code' => $user->referral_code,
                        'referral_count' => 0,
                        'created_at' => $user->created_at->toIso8601String(),
                    ],
                    'token' => $token,
                    'subscription' => [
                        'plan_name' => $freePlan->name ?? 'Gratuit',
                        'status' => 'active',
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_email' => $request->email
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_type' => 'nullable|string|in:ios,android,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find user
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive. Please contact support.',
            ], 403);
        }

        // Get active subscription
        $subscription = $user->activeMobileSubscription()
            ->with('plan')
            ->first();

        // Get user details for phone, address, city
        $userDetail = UserDetail::where('user_id', $user->id)->first();

        // Update mobile activity tracking
        $deviceType = $request->get('device_type');
        if ($deviceType && in_array($deviceType, ['ios', 'android', 'web'], true)) {
            $user->primary_device = $deviceType;
        }
        if (!$user->mobile_app_installed_at) {
            $user->mobile_app_installed_at = now();
        }
        $user->last_mobile_activity_at = now();
        $user->save();

        // Generate token
        $token = $user->createToken('mobile-app')->plainTextToken;

        $missingFields = $this->getMissingProfileFields($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => optional($userDetail)->mobile_number ?? $user->phone,
                    'address' => optional($userDetail)->address,
                    'city' => optional($userDetail)->city,
                    'plan' => $subscription && $subscription->plan ? $subscription->plan->name : 'Gratuit',
                    'role' => $user->mobile_role ?? 'student',
                    'mobile_role' => $user->mobile_role,
                    'jurisdiction' => $user->jurisdiction,
                    'subscription_end' => $subscription ? $subscription->expires_at : null,
                    'searches_used' => $subscription ? $subscription->searches_used : 0,
                    'searches_limit' => $subscription && $subscription->plan ? $subscription->plan->searches_limit : 5,
                    'analyses_used' => $subscription ? $subscription->ai_analyses_used : 0,
                    'analyses_limit' => $subscription && $subscription->plan ? $subscription->plan->ai_analyses_limit : 2,
                    'downloads_used' => $subscription ? $subscription->pdf_downloads_used : 0,
                    'downloads_limit' => $subscription && $subscription->plan ? $subscription->plan->pdf_downloads_limit : 0,
                    'referral_code' => $user->referral_code,
                    'referral_count' => $user->referrals()->count(),
                    'created_at' => $user->created_at->toIso8601String(),
                ],
                'profile_complete' => empty($missingFields),
                'missing_fields' => $missingFields,
                'token' => $token,
                'subscription' => $subscription ? [
                    'plan_id' => $subscription->plan->id,
                    'plan_name' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'end_date' => $subscription->expires_at,
                    'quotas' => [
                        'searches_limit' => $subscription->plan->searches_limit,
                        'searches_used' => $subscription->searches_used,
                        'ai_analyses_limit' => $subscription->plan->ai_analyses_limit,
                        'ai_analyses_used' => $subscription->ai_analyses_used,
                        'pdf_downloads_limit' => $subscription->plan->pdf_downloads_limit,
                        'pdf_downloads_used' => $subscription->pdf_downloads_used,
                    ],
                ] : null,
            ],
        ], 200);
    }

    /**
     * Forgot password - send reset link to email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Configurer les paramètres SMTP depuis la base de données
            Utility::getSMTPDetails(1); // Admin SMTP settings
            
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                Log::info('Password reset link sent successfully', ['email' => $request->email]);
                return response()->json([
                    'success' => true,
                    'message' => 'Un email de réinitialisation a été envoyé à votre adresse.',
                ], 200);
            }

            Log::warning('Password reset link failed to send', ['email' => $request->email, 'status' => $status]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'envoyer l\'email de réinitialisation. Veuillez vérifier votre adresse email.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password reset error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer plus tard.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user (revoke token)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ], 200);
    }

    /**
     * Get authenticated user profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $userDetail = UserDetail::where('user_id', $user->id)->first();

        $missingFields = $this->getMissingProfileFields($user);
        
        // Get subscription
        $subscription = $user->activeMobileSubscription()
            ->with('plan')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => optional($userDetail)->mobile_number ?? $user->phone,
                    'address' => optional($userDetail)->address,
                    'city' => optional($userDetail)->city,
                    'plan' => $subscription && $subscription->plan ? $subscription->plan->name : 'Gratuit',
                    'role' => $user->mobile_role ?? 'student',
                    'mobile_role' => $user->mobile_role,
                    'jurisdiction' => $user->jurisdiction,
                    'subscription_end' => $subscription ? $subscription->expires_at : null,
                    'searches_used' => $subscription ? $subscription->searches_used : 0,
                    'searches_limit' => $subscription && $subscription->plan ? $subscription->plan->searches_limit : 5,
                    'analyses_used' => $subscription ? $subscription->ai_analyses_used : 0,
                    'analyses_limit' => $subscription && $subscription->plan ? $subscription->plan->ai_analyses_limit : 2,
                    'downloads_used' => $subscription ? $subscription->pdf_downloads_used : 0,
                    'downloads_limit' => $subscription && $subscription->plan ? $subscription->plan->pdf_downloads_limit : 0,
                    'referral_code' => $user->referral_code,
                    'referral_count' => $user->referrals()->count(),
                    'created_at' => $user->created_at->toIso8601String(),
                ],
                'profile_complete' => empty($missingFields),
                'missing_fields' => $missingFields,
                'subscription' => $subscription ? [
                    'plan_id' => $subscription->plan->id,
                    'plan_name' => $subscription->plan->name,
                    'price_monthly' => $subscription->plan->price_monthly,
                    'price_annual' => $subscription->plan->price_yearly,
                    'status' => $subscription->status,
                    'start_date' => optional($subscription->started_at)->format('Y-m-d'),
                    'end_date' => optional($subscription->expires_at)->format('Y-m-d'),
                    'quotas' => [
                        'searches_limit' => $subscription->plan->searches_limit,
                        'searches_used' => $subscription->searches_used,
                        'ai_analyses_limit' => $subscription->plan->ai_analyses_limit,
                        'ai_analyses_used' => $subscription->ai_analyses_used,
                        'pdf_downloads_limit' => $subscription->plan->pdf_downloads_limit,
                        'pdf_downloads_used' => $subscription->pdf_downloads_used,
                    ],
                ] : null,
            ],
        ], 200);
    }

    /**
     * Update user profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Debug logging
        \Log::info('UpdateProfile called', [
            'user_id' => $user->id,
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|nullable|string|max:100',
            'jurisdiction' => 'sometimes|nullable|string|max:10',
            'mobile_role' => 'sometimes|nullable|string|in:student,lawyer,enterprise',
            'current_password' => 'required_with:new_password',
            'new_password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            \Log::error('UpdateProfile validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $user) {
                $userDetail = UserDetail::firstOrNew(['user_id' => $user->id]);

                // Ensure the relation key is set for new records
                $userDetail->user_id = $user->id;

                // Update basic info
                if ($request->has('name')) {
                    $user->name = $request->name;
                }

                if ($request->has('jurisdiction')) {
                    $user->jurisdiction = $request->jurisdiction;
                }

                if ($request->has('mobile_role')) {
                    $user->mobile_role = $request->mobile_role;
                }

                // Phone is stored in user_details table, NOT in users table
                if ($request->has('phone')) {
                    $userDetail->mobile_number = $request->phone;
                }

                if ($request->has('address')) {
                    $userDetail->address = $request->address;
                }

                if ($request->has('city')) {
                    $userDetail->city = $request->city;
                }

                // Update password if provided
                if ($request->has('new_password')) {
                    if (!Hash::check($request->current_password, $user->password)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Current password is incorrect',
                        ], 422);
                    }

                    $user->password = Hash::make($request->new_password);
                }

                $user->save();
                $userDetail->save();

                // Return a consistent user payload (same shape as profile)
                $subscription = $user->activeMobileSubscription()
                    ->with('plan')
                    ->first();

                $missingFields = $this->getMissingProfileFields($user);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $userDetail->mobile_number ?? $user->phone,
                            'address' => $userDetail->address,
                            'city' => $userDetail->city,
                            'plan' => $subscription && $subscription->plan ? $subscription->plan->name : 'Gratuit',
                            'role' => $user->mobile_role ?? 'student',
                            'mobile_role' => $user->mobile_role,
                            'jurisdiction' => $user->jurisdiction,
                            'subscription_end' => $subscription ? $subscription->expires_at : null,
                            'searches_used' => $subscription ? $subscription->searches_used : 0,
                            'searches_limit' => $subscription && $subscription->plan ? $subscription->plan->searches_limit : 5,
                            'analyses_used' => $subscription ? $subscription->ai_analyses_used : 0,
                            'analyses_limit' => $subscription && $subscription->plan ? $subscription->plan->ai_analyses_limit : 2,
                            'downloads_used' => $subscription ? $subscription->pdf_downloads_used : 0,
                            'downloads_limit' => $subscription && $subscription->plan ? $subscription->plan->pdf_downloads_limit : 0,
                            'messages_used' => $subscription ? $subscription->messages_sent_today : 0,
                            'messages_limit' => $subscription && $subscription->plan ? $subscription->plan->messages_per_day_limit : 10,
                            'referral_code' => $user->referral_code,
                            'referral_count' => $user->referrals()->count(),
                            'created_at' => $user->created_at->toIso8601String(),
                        ],
                        'profile_complete' => empty($missingFields),
                        'missing_fields' => $missingFields,
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            \Log::error('UpdateProfile exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh authentication token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();
        
        // Generate new token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Generate unique referral code
     * 
     * @return string
     */
    private function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Determine missing profile fields required for mobile AI usage.
     */
    private function getMissingProfileFields(User $user): array
    {
        $missing = [];

        if (empty($user->jurisdiction)) {
            $missing[] = 'jurisdiction';
        }

        if (empty($user->mobile_role)) {
            $missing[] = 'mobile_role';
        }

        return $missing;
    }
}