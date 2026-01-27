<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'android_version',
        'android_build_number',
        'ios_version',
        'ios_build_number',
        'force_update_android',
        'min_android_version',
        'force_update_ios',
        'min_ios_version',
        'maintenance_mode',
        'maintenance_message',
        'maintenance_start',
        'maintenance_end',
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
        'chat_enabled',
        'documents_enabled',
        'tools_enabled',
        'referral_enabled',
        'free_plan_limits',
        'student_plan_limits',
        'professional_plan_limits',
        'cabinet_plan_limits',
        'play_store_url',
        'app_store_url',
        'privacy_policy_url',
        'terms_of_service_url',
        'support_email',
        'support_phone',
        'whatsapp_number',
    ];

    protected $casts = [
        'force_update_android' => 'boolean',
        'force_update_ios' => 'boolean',
        'maintenance_mode' => 'boolean',
        'maintenance_start' => 'datetime',
        'maintenance_end' => 'datetime',
        'chat_enabled' => 'boolean',
        'documents_enabled' => 'boolean',
        'tools_enabled' => 'boolean',
        'referral_enabled' => 'boolean',
        'free_plan_limits' => 'array',
        'student_plan_limits' => 'array',
        'professional_plan_limits' => 'array',
        'cabinet_plan_limits' => 'array',
    ];

    /**
     * Get the singleton instance of settings
     */
    public static function get()
    {
        $settings = self::first();
        
        if (!$settings) {
            $settings = self::create([
                'android_version' => '1.0.0',
                'android_build_number' => 1,
                'ios_version' => '1.0.0',
                'ios_build_number' => 1,
            ]);
        }
        
        return $settings;
    }

    /**
     * Check if app version is valid (not requiring forced update)
     */
    public function isVersionValid($platform, $version)
    {
        if ($platform === 'android') {
            if (!$this->force_update_android) {
                return true;
            }
            return version_compare($version, $this->min_android_version, '>=');
        }
        
        if ($platform === 'ios') {
            if (!$this->force_update_ios) {
                return true;
            }
            return version_compare($version, $this->min_ios_version, '>=');
        }
        
        return true;
    }

    /**
     * Check if app is currently in maintenance
     */
    public function isInMaintenance()
    {
        if (!$this->maintenance_mode) {
            return false;
        }

        $now = now();

        // Si pas de dates définies et maintenance activée
        if (!$this->maintenance_start && !$this->maintenance_end) {
            return true;
        }

        // Si uniquement start défini
        if ($this->maintenance_start && !$this->maintenance_end) {
            return $now->greaterThanOrEqualTo($this->maintenance_start);
        }

        // Si uniquement end défini
        if (!$this->maintenance_start && $this->maintenance_end) {
            return $now->lessThanOrEqualTo($this->maintenance_end);
        }

        // Si les deux sont définis
        return $now->between($this->maintenance_start, $this->maintenance_end);
    }

    /**
     * Get limits for a specific plan
     */
    public function getLimitsForPlan($planName)
    {
        $planLimitsMap = [
            'Gratuit' => 'free_plan_limits',
            'Étudiant' => 'student_plan_limits',
            'Professionnel' => 'professional_plan_limits',
            'Cabinet/Entreprise' => 'cabinet_plan_limits',
        ];

        $attribute = $planLimitsMap[$planName] ?? 'free_plan_limits';
        
        return $this->$attribute ?? [
            'searches' => 5,
            'analyses' => 2,
            'downloads' => 0,
            'messages_per_day' => 10,
        ];
    }
}
