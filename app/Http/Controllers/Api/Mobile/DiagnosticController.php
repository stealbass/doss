<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\FiscalSocialResource;
use App\Models\CalculatorConfig;
use App\Models\LegalAlert;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    public function checkLibraryData(Request $request)
    {
        $user = $request->user();
        $user->load('activeMobileSubscription.plan');
        
        $data = [];
        
        // Templates
        $templatesCount = DocumentTemplate::count();
        $templatesVisible = DocumentTemplate::where('is_mobile_visible', true)->count();
        $data['templates'] = [
            'total' => $templatesCount,
            'visible' => $templatesVisible,
            'sample' => DocumentTemplate::with('category')->first()?->toArray(),
        ];
        
        // Fiscal Resources
        try {
            $fiscalCount = FiscalSocialResource::count();
            $fiscalVisible = FiscalSocialResource::where('is_mobile_visible', true)->count();
            $data['fiscal_resources'] = [
                'total' => $fiscalCount,
                'visible' => $fiscalVisible,
                'sample' => FiscalSocialResource::first()?->toArray(),
            ];
        } catch (\Exception $e) {
            $data['fiscal_resources'] = ['error' => $e->getMessage()];
        }
        
        // Calculators
        try {
            $calcCount = CalculatorConfig::count();
            $calcVisible = CalculatorConfig::where('is_mobile_visible', true)->count();
            $data['calculators'] = [
                'total' => $calcCount,
                'visible' => $calcVisible,
                'sample' => CalculatorConfig::first()?->toArray(),
            ];
        } catch (\Exception $e) {
            $data['calculators'] = ['error' => $e->getMessage()];
        }
        
        // Legal Alerts
        try {
            $alertsCount = LegalAlert::count();
            $alertsPublished = LegalAlert::where('is_published', true)->count();
            $data['legal_alerts'] = [
                'total' => $alertsCount,
                'published' => $alertsPublished,
                'sample' => LegalAlert::first()?->toArray(),
            ];
        } catch (\Exception $e) {
            $data['legal_alerts'] = ['error' => $e->getMessage()];
        }
        
        $data['user_info'] = [
            'id' => $user->id,
            'plan' => $user->plan,
            'country' => $user->country,
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
