<?php

namespace App\Http\Controllers;

use App\Models\FiscalSocialResource;
use App\Models\SalaryGrid;
use App\Models\TaxParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FiscalSocialResourceController extends Controller
{
    /**
     * Display listing of fiscal & social resources
     */
    public function index(Request $request)
    {
        $query = FiscalSocialResource::query();

        // Filters
        if ($request->has('country') && $request->country) {
            $query->where('country', $request->country);
        }

        if ($request->has('resource_type') && $request->resource_type) {
            $query->where('resource_type', $request->resource_type);
        }

        if ($request->has('year') && $request->year) {
            $query->where('year', $request->year);
        }

        $resources = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => FiscalSocialResource::count(),
            'mobile_visible' => FiscalSocialResource::where('is_mobile_visible', true)->count(),
            'countries' => FiscalSocialResource::distinct('country')->count(),
            'current_year' => FiscalSocialResource::where('year', date('Y'))->count(),
        ];

        return view('fiscal-resources.index', compact('resources', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (auth()->user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $countries = config('mobile_countries.supported_countries', []);
        $years = range(date('Y') + 1, 2020);
        
        return view('fiscal-resources.create', compact('countries', 'years'));
    }

    /**
     * Store a new fiscal/social resource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'resource_type' => 'required|in:cgi,finance_law,lpf,administrative_doctrine,tax_convention,social_security_code,salary_tax_scale,labor_code,collective_agreement,other',
            'country' => 'required|string',
            'year' => 'required|integer|min:2020|max:2030',
            'version' => 'nullable|string|max:50',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xlsx,xls',
            'description' => 'nullable|string',
            'is_mobile_visible' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('fiscal_resources', $fileName, 'public');

            $resource = FiscalSocialResource::create([
                'title' => $request->title,
                'description' => $request->description,
                'resource_type' => $request->resource_type,
                'country' => $request->country,
                'year' => $request->year,
                'version' => $request->version ?? '1.0',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'is_mobile_visible' => $request->has('is_mobile_visible'),
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ressource fiscale/sociale créée avec succès',
                'data' => $resource
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun fichier uploadé'
        ], 400);
    }

    /**
     * Toggle mobile visibility
     */
    public function toggleMobileVisibility($id)
    {
        $resource = FiscalSocialResource::findOrFail($id);
        $resource->is_mobile_visible = !$resource->is_mobile_visible;
        $resource->save();

        return response()->json([
            'success' => true,
            'message' => $resource->is_mobile_visible ? 'Visible sur mobile' : 'Masqué du mobile',
            'is_mobile_visible' => $resource->is_mobile_visible
        ]);
    }

    /**
     * Delete resource
     */
    public function destroy($id)
    {
        $resource = FiscalSocialResource::findOrFail($id);
        
        // Delete file
        if (Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ressource supprimée avec succès'
        ]);
    }

    /**
     * Salary grids management
     */
    public function salaryGrids(Request $request)
    {
        $grids = SalaryGrid::query()
            ->when($request->country, function ($q) use ($request) {
                $q->where('country', $request->country);
            })
            ->when($request->year, function ($q) use ($request) {
                $q->where('year', $request->year);
            })
            ->orderBy('year', 'desc')
            ->paginate(20);

        return view('fiscal-resources.salary-grids', compact('grids'));
    }

    /**
     * Store salary grid
     */
    public function storeSalaryGrid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string',
            'year' => 'required|integer|min:2020|max:2030',
            'category' => 'required|string|max:255',
            'min_salary' => 'required|numeric|min:0',
            'max_salary' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $grid = SalaryGrid::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Grille salariale créée avec succès',
            'data' => $grid
        ], 201);
    }

    /**
     * Tax parameters management
     */
    public function taxParameters(Request $request)
    {
        $parameters = TaxParameter::query()
            ->when($request->country, function ($q) use ($request) {
                $q->where('country', $request->country);
            })
            ->when($request->year, function ($q) use ($request) {
                $q->where('year', $request->year);
            })
            ->orderBy('year', 'desc')
            ->paginate(20);

        return view('fiscal-resources.tax-parameters', compact('parameters'));
    }

    /**
     * Store tax parameter
     */
    public function storeTaxParameter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string',
            'year' => 'required|integer|min:2020|max:2030',
            'tax_type' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $parameter = TaxParameter::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Paramètre fiscal créé avec succès',
            'data' => $parameter
        ], 201);
    }

    /**
     * Export resources
     */
    public function export()
    {
        $resources = FiscalSocialResource::all();
        
        $filename = 'fiscal_resources_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($resources) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Titre', 'Type', 'Pays', 'Année', 'Version', 'Mobile', 'Téléchargements']);

            foreach ($resources as $resource) {
                fputcsv($file, [
                    $resource->title,
                    $resource->resource_type,
                    $resource->country,
                    $resource->year,
                    $resource->version,
                    $resource->is_mobile_visible ? 'Oui' : 'Non',
                    $resource->downloads_count
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
