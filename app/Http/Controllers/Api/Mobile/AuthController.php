<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MobileAppSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => 'client',
                'lang' => 'fr',
                'phone' => $request->phone,
                'is_active' => 1,
            ]);

            // Assign free plan by default
            $freePlan = \App\Models\MobileAppPlan::where('price_monthly', 0)->first();
            
            if ($freePlan) {
                MobileAppSubscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => null, // Free plan never expires
                    'is_trial' => false,
                ]);
            }

            // Generate API token
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'token' => $token,
                    'subscription' => [
                        'plan_name' => $freePlan->name ?? 'Gratuit',
                        'status' => 'active',
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
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
        $subscription = $user->mobileAppSubscription()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        // Generate token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'token' => $token,
                'subscription' => $subscription ? [
                    'plan_id' => $subscription->plan->id,
                    'plan_name' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'end_date' => $subscription->end_date,
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
        
        // Get subscription
        $subscription = $user->mobileAppSubscription()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ],
                'subscription' => $subscription ? [
                    'plan_id' => $subscription->plan->id,
                    'plan_name' => $subscription->plan->name,
                    'price_monthly' => $subscription->plan->price_monthly,
                    'price_annual' => $subscription->plan->price_annual,
                    'status' => $subscription->status,
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date?->format('Y-m-d'),
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

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'current_password' => 'required_with:new_password',
            'new_password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update basic info
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
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

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
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
}
