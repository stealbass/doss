<?php

namespace App\Http\Controllers;

use App\Models\MobileAppSetting;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Diagnostic Controller for OpenAI Integration
 * Route: GET /admin/diagnostics/openai-integration
 */
class OpenAIDiagnosticsController extends Controller
{
    public function index()
    {
        $diagnostics = [
            'timestamp' => now()->toIso8601String(),
            'database' => $this->checkDatabase(),
            'migration' => $this->checkMigration(),
            'settings' => $this->checkSettings(),
            'openai_service' => $this->checkOpenAIService(),
            'env_fallback' => $this->checkEnvFallback(),
            'test_api_call' => $this->testOpenAICall(),
        ];

        return response()->json($diagnostics, 200);
    }

    /**
     * Check if table exists and has the column
     */
    private function checkDatabase()
    {
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('mobile_app_settings');
            
            return [
                'status' => in_array('openai_api_key', $columns) ? 'success' : 'error',
                'table_exists' => true,
                'has_openai_column' => in_array('openai_api_key', $columns),
                'columns_count' => count($columns),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'table_exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if migration has been run
     */
    private function checkMigration()
    {
        try {
            $migrations = DB::table('migrations')
                ->where('migration', 'like', '%mobile_app_settings%')
                ->first();

            return [
                'status' => $migrations ? 'success' : 'pending',
                'migration_found' => (bool) $migrations,
                'migration_name' => $migrations->migration ?? 'Not found',
                'batch' => $migrations->batch ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => 'Could not check migrations',
            ];
        }
    }

    /**
     * Check current settings in DB
     */
    private function checkSettings()
    {
        try {
            $settings = MobileAppSetting::first();

            if (!$settings) {
                return [
                    'status' => 'warning',
                    'message' => 'No mobile app settings found. Creating default...',
                    'created' => MobileAppSetting::create([]) ? 'success' : 'failed',
                ];
            }

            $hasKey = !empty($settings->openai_api_key);
            $keyPreview = $hasKey ? substr($settings->openai_api_key, 0, 20) . '...' : 'NOT SET';

            return [
                'status' => $hasKey ? 'success' : 'warning',
                'settings_id' => $settings->id,
                'openai_api_key_set' => $hasKey,
                'openai_api_key_preview' => $keyPreview,
                'key_starts_with' => $hasKey ? substr($settings->openai_api_key, 0, 8) : 'N/A',
                'key_length' => $hasKey ? strlen($settings->openai_api_key) : 0,
                'flutterwave_public_key_set' => !empty($settings->flutterwave_public_key),
                'chat_enabled' => $settings->chat_enabled ?? false,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check OpenAIService initialization
     */
    private function checkOpenAIService()
    {
        try {
            $openai = new OpenAIService();
            
            // Use reflection to check private apiKey property
            $reflection = new \ReflectionClass($openai);
            $property = $reflection->getProperty('apiKey');
            $property->setAccessible(true);
            $apiKey = $property->getValue($openai);

            $hasKey = !empty($apiKey);
            $keyPreview = $hasKey ? substr($apiKey, 0, 20) . '...' : 'EMPTY';

            return [
                'status' => $hasKey ? 'success' : 'error',
                'api_key_loaded' => $hasKey,
                'api_key_preview' => $keyPreview,
                'key_length' => strlen($apiKey ?? ''),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check .env fallback
     */
    private function checkEnvFallback()
    {
        try {
            $envKey = env('OPENAI_API_KEY', '');
            $hasKey = !empty($envKey);
            $keyPreview = $hasKey ? substr($envKey, 0, 20) . '...' : 'NOT SET';

            return [
                'status' => $hasKey ? 'success' : 'warning',
                'env_openai_api_key_set' => $hasKey,
                'env_key_preview' => $keyPreview,
                'env_key_length' => strlen($envKey ?? ''),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test actual API call to OpenAI
     */
    private function testOpenAICall()
    {
        try {
            $openai = new OpenAIService();

            $response = $openai->chatWithContext(
                'Dis bonjour en français',
                '',
                [],
                'gpt-3.5-turbo'
            );

            return [
                'status' => $response['success'] ? 'success' : 'error',
                'success' => $response['success'],
                'message' => $response['success'] ? substr($response['message'] ?? '', 0, 100) . '...' : $response['error'],
                'model' => $response['model'] ?? 'N/A',
                'tokens_used' => $response['tokens_used'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Test Call Error: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable APP_DEBUG to see trace',
            ];
        }
    }
}
