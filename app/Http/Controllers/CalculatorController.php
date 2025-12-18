<?php

namespace App\Http\Controllers;

use App\Models\CalculatorConfig;
use App\Models\CalculatorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalculatorController extends Controller
{
    /**
     * Display listing of calculators
     */
    public function index(Request $request)
    {
        $calculators = CalculatorConfig::query()
            ->when($request->calculator_type, function ($q) use ($request) {
                $q->where('calculator_type', $request->calculator_type);
            })
            ->when($request->country, function ($q) use ($request) {
                $q->where('country', $request->country);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => CalculatorConfig::count(),
            'active' => CalculatorConfig::where('is_active', true)->count(),
            'total_uses' => CalculatorLog::count(),
            'this_month' => CalculatorLog::whereMonth('created_at', date('m'))->count(),
        ];

        return view('calculators.index', compact('calculators', 'stats'));
    }

    /**
     * Store a new calculator configuration
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'calculator_type' => 'required|in:hiring_cost,severance_pay,net_salary,taxes,social_charges,leave_indemnity,overtime',
            'country' => 'required|string',
            'formula' => 'required|json',
            'required_plan' => 'required|in:Gratuit,Étudiant,Professionnel,Cabinet/Entreprise',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $calculator = CalculatorConfig::create([
            'name' => $request->name,
            'description' => $request->description,
            'calculator_type' => $request->calculator_type,
            'country' => $request->country,
            'formula' => json_decode($request->formula),
            'required_plan' => $request->required_plan,
            'is_active' => $request->has('is_active')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Calculateur créé avec succès',
            'data' => $calculator
        ], 201);
    }

    /**
     * Update calculator
     */
    public function update(Request $request, $id)
    {
        $calculator = CalculatorConfig::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'formula' => 'sometimes|json',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('formula')) {
            $request->merge(['formula' => json_decode($request->formula)]);
        }

        $calculator->update($request->only(['name', 'description', 'formula', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Calculateur mis à jour avec succès',
            'data' => $calculator
        ]);
    }

    /**
     * Delete calculator
     */
    public function destroy($id)
    {
        $calculator = CalculatorConfig::findOrFail($id);
        $calculator->delete();

        return response()->json([
            'success' => true,
            'message' => 'Calculateur supprimé avec succès'
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $calculator = CalculatorConfig::findOrFail($id);
        $calculator->is_active = !$calculator->is_active;
        $calculator->save();

        return response()->json([
            'success' => true,
            'message' => $calculator->is_active ? 'Calculateur activé' : 'Calculateur désactivé',
            'is_active' => $calculator->is_active
        ]);
    }

    /**
     * View calculator usage logs
     */
    public function logs(Request $request)
    {
        $logs = CalculatorLog::with(['user', 'calculator'])
            ->when($request->calculator_id, function ($q) use ($request) {
                $q->where('calculator_id', $request->calculator_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('calculators.logs', compact('logs'));
    }
}
