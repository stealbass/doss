<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CalculatorConfig;
use App\Models\CalculatorLog;
use Illuminate\Http\Request;

class CalculatorApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userCountry = $user->country;
        $userPlan = $user->plan ?? 'Gratuit';

        $calculators = CalculatorConfig::byCountry($userCountry)
            ->mobileVisible()
            ->active()
            ->accessibleByPlan($userPlan)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $calculators,
        ]);
    }

    public function calculate(Request $request, $id)
    {
        $calculator = CalculatorConfig::findOrFail($id);
        $user = $request->user();

        $inputData = $request->input_data;
        $formula = $calculator->calculation_formula;

        // Simple calculation logic (extend based on calculator type)
        $result = $this->executeCalculation($calculator->calculator_type, $inputData, $formula);

        // Log calculation
        CalculatorLog::create([
            'calculator_config_id' => $calculator->id,
            'user_id' => $user->id,
            'input_data' => $inputData,
            'output_data' => $result,
            'country' => $user->country,
            'user_plan' => $user->plan,
            'calculated_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    private function executeCalculation($type, $input, $formula)
    {
        // Simplified calculation - extend this based on needs
        switch ($type) {
            case 'hiring_cost':
                $salary = $input['salary'] ?? 0;
                $socialCharges = $salary * 0.21; // 21% charges patronales (example)
                return [
                    'gross_salary' => $salary,
                    'social_charges' => $socialCharges,
                    'total_cost' => $salary + $socialCharges,
                ];

            case 'severance':
                $salary = $input['monthly_salary'] ?? 0;
                $years = $input['years_of_service'] ?? 0;
                $severance = $salary * $years * 0.5; // 50% par année (example)
                return [
                    'severance_amount' => $severance,
                ];

            default:
                return ['error' => 'Calculator type not implemented'];
        }
    }

    public function history(Request $request)
    {
        $logs = CalculatorLog::where('user_id', $request->user()->id)
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
