<?php

namespace App\Http\Controllers;

use App\Models\MobileAppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MobileAppSettingsController extends Controller
{
    /**
     * Display the mobile app settings page
     */
    public function index()
    {
        // Ensure a single settings row exists
        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        return view('mobile-app-settings.index', compact('settings'));
    }

    /**
     * Update app version settings
     */
    public function updateVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'android_version' => 'required|string',
            'android_build_number' => 'required|integer|min:1',
            'ios_version' => 'required|string',
            'ios_build_number' => 'required|integer|min:1',
            'force_update_android' => 'boolean',
            'min_android_version' => 'required_if:force_update_android,true',
            'force_update_ios' => 'boolean',
            'min_ios_version' => 'required_if:force_update_ios,true',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        $settings->update($request->only([
            'android_version',
            'android_build_number',
            'ios_version',
            'ios_build_number',
            'force_update_android',
            'min_android_version',
            'force_update_ios',
            'min_ios_version',
        ]));

        return redirect()->back()->with('success', 'Versions de l\'application mises à jour avec succès.');
    }

    /**
     * Update maintenance mode settings
     */
    public function updateMaintenance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'maintenance_mode' => 'required|boolean',
            'maintenance_message' => 'nullable|string|max:500',
            'maintenance_start' => 'nullable|date',
            'maintenance_end' => 'nullable|date|after:maintenance_start',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        $settings->update($request->only([
            'maintenance_mode',
            'maintenance_message',
            'maintenance_start',
            'maintenance_end',
        ]));

        return redirect()->back()->with('success', 'Mode maintenance mis à jour avec succès.');
    }

    /**
     * Update API keys
     */
    public function updateApiKeys(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'openai_api_key' => 'nullable|string',
            'pinecone_api_key' => 'nullable|string',
            'pinecone_index_name' => 'nullable|string',
            'pinecone_environment' => 'nullable|string',
            'pinecone_host' => 'nullable|string',
            'pinecone_verify_ssl' => 'nullable|boolean',
            'flutterwave_public_key' => 'nullable|string',
            'flutterwave_secret_key' => 'nullable|string',
            'flutterwave_environment' => 'required|in:test,live',
            'firebase_server_key' => 'nullable|string',
            'firebase_messaging_sender_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        $settings->update($request->only([
            'openai_api_key',
            'pinecone_api_key',
            'pinecone_index_name',
            'pinecone_environment',
            'pinecone_host',
            'pinecone_verify_ssl',
            'flutterwave_public_key',
            'flutterwave_secret_key',
            'flutterwave_environment',
            'firebase_server_key',
            'firebase_messaging_sender_id',
        ]));

        return redirect()->back()->with('success', 'Clés API mises à jour avec succès.');
    }

    /**
     * Update features toggle
     */
    public function updateFeatures(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_enabled' => 'required|boolean',
            'documents_enabled' => 'required|boolean',
            'tools_enabled' => 'required|boolean',
            'referral_enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        $settings->update($request->only([
            'chat_enabled',
            'documents_enabled',
            'tools_enabled',
            'referral_enabled',
        ]));

        return redirect()->back()->with('success', 'Fonctionnalités mises à jour avec succès.');
    }

    /**
     * Update plan limits
     */
    public function updateLimits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'free_plan_limits' => 'required|array',
            'free_plan_limits.searches' => 'required|integer|min:0',
            'free_plan_limits.analyses' => 'required|integer|min:0',
            'free_plan_limits.downloads' => 'required|integer|min:0',
            'free_plan_limits.messages_per_day' => 'required|integer|min:0',
            
            'student_plan_limits' => 'required|array',
            'student_plan_limits.searches' => 'required|integer|min:0',
            'student_plan_limits.analyses' => 'required|integer|min:0',
            'student_plan_limits.downloads' => 'required|integer|min:0',
            'student_plan_limits.messages_per_day' => 'required|integer|min:0',
            
            'professional_plan_limits' => 'required|array',
            'professional_plan_limits.searches' => 'required|integer|min:0',
            'professional_plan_limits.analyses' => 'required|integer|min:0',
            'professional_plan_limits.downloads' => 'required|integer|min:0',
            'professional_plan_limits.messages_per_day' => 'required|integer|min:0',
            
            'cabinet_plan_limits' => 'required|array',
            'cabinet_plan_limits.searches' => 'required|integer|min:-1',
            'cabinet_plan_limits.analyses' => 'required|integer|min:-1',
            'cabinet_plan_limits.downloads' => 'required|integer|min:-1',
            'cabinet_plan_limits.messages_per_day' => 'required|integer|min:-1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        $settings->update($request->only([
            'free_plan_limits',
            'student_plan_limits',
            'professional_plan_limits',
            'cabinet_plan_limits',
        ]));

        return redirect()->back()->with('success', 'Limites des plans mises à jour avec succès.');
    }

    /**
     * Update app URLs and support info
     */
    public function updateInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'play_store_url' => 'nullable|url',
            'app_store_url' => 'nullable|url',
            'privacy_policy_url' => 'nullable|url',
            'terms_of_service_url' => 'nullable|url',
            'support_email' => 'nullable|email',
            'support_phone' => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settings = MobileAppSetting::first();
        if (!$settings) {
            $settings = MobileAppSetting::create([]);
        }
        $settings->update($request->only([
            'play_store_url',
            'app_store_url',
            'privacy_policy_url',
            'terms_of_service_url',
            'support_email',
            'support_phone',
            'whatsapp_number',
        ]));

        return redirect()->back()->with('success', 'Informations de l\'application mises à jour avec succès.');
    }
}
