<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseSubAccount;
use App\Models\MobileSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EnterpriseApiController extends Controller
{
    /**
     * Get all sub-accounts for the authenticated enterprise user
     */
    public function getSubAccounts()
    {
        $user = Auth::user();
        
        // Check if user has enterprise/cabinet plan
        if (!$this->hasEnterprisePlan($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité est réservée aux comptes Cabinet/Entreprise'
            ], 403);
        }

        $subAccounts = EnterpriseSubAccount::where('main_account_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sub_accounts' => $subAccounts,
                'count' => $subAccounts->count(),
                'max_allowed' => 10
            ]
        ]);
    }

    /**
     * Create a new sub-account
     */
    public function createSubAccount(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->hasEnterprisePlan($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité est réservée aux comptes Cabinet/Entreprise'
            ], 403);
        }

        // Check limit
        $currentCount = EnterpriseSubAccount::where('main_account_id', $user->id)->count();
        if ($currentCount >= 10) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez atteint la limite de 10 sous-comptes'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:enterprise_sub_accounts,email',
            'role' => 'required|in:dg,hr,accountant,legal,other',
            'permissions' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subAccount = EnterpriseSubAccount::create([
            'main_account_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sous-compte créé avec succès',
            'data' => $subAccount
        ], 201);
    }

    /**
     * Update a sub-account
     */
    public function updateSubAccount(Request $request, $id)
    {
        $user = Auth::user();
        
        $subAccount = EnterpriseSubAccount::where('main_account_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$subAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-compte non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:enterprise_sub_accounts,email,' . $id,
            'role' => 'sometimes|in:dg,hr,accountant,legal,other',
            'permissions' => 'nullable|array',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subAccount->update($request->only(['name', 'email', 'role', 'permissions', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Sous-compte mis à jour avec succès',
            'data' => $subAccount
        ]);
    }

    /**
     * Delete a sub-account
     */
    public function deleteSubAccount($id)
    {
        $user = Auth::user();
        
        $subAccount = EnterpriseSubAccount::where('main_account_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$subAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-compte non trouvé'
            ], 404);
        }

        $subAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sous-compte supprimé avec succès'
        ]);
    }

    /**
     * Toggle sub-account status
     */
    public function toggleStatus($id)
    {
        $user = Auth::user();
        
        $subAccount = EnterpriseSubAccount::where('main_account_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$subAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-compte non trouvé'
            ], 404);
        }

        $subAccount->is_active = !$subAccount->is_active;
        $subAccount->save();

        return response()->json([
            'success' => true,
            'message' => $subAccount->is_active ? 'Sous-compte activé' : 'Sous-compte désactivé',
            'data' => $subAccount
        ]);
    }

    /**
     * Get enterprise dashboard statistics
     */
    public function getDashboard()
    {
        $user = Auth::user();
        
        if (!$this->hasEnterprisePlan($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette fonctionnalité est réservée aux comptes Cabinet/Entreprise'
            ], 403);
        }

        $subAccountsCount = EnterpriseSubAccount::where('main_account_id', $user->id)->count();
        $activeSubAccounts = EnterpriseSubAccount::where('main_account_id', $user->id)
            ->where('is_active', true)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sub_accounts' => $subAccountsCount,
                'active_sub_accounts' => $activeSubAccounts,
                'inactive_sub_accounts' => $subAccountsCount - $activeSubAccounts,
                'remaining_slots' => 10 - $subAccountsCount,
                'plan_type' => 'Cabinet/Entreprise'
            ]
        ]);
    }

    /**
     * Check if user has enterprise plan
     */
    private function hasEnterprisePlan($user)
    {
        // Check if user has active subscription with enterprise plan
        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->whereHas('plan', function ($query) {
                $query->where('plan_type', 'cabinet_entreprise');
            })
            ->first();

        return $subscription !== null;
    }
}
