<?php

namespace App\Http\Controllers;

use App\Models\FiscalSocialResource;
use App\Models\Utility;
use App\Models\SalaryGrid;
use App\Models\TaxParameter;
use App\Models\ResourceCategory;
use App\Jobs\ProcessFiscalResourceForRAG;
use App\Services\AdvancedRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FiscalSocialResourceController extends Controller
{
    /**
     * Get storage disk based on settings (local, r2, s3, wasabi)
     */
    private function getStorageDisk(): string
    {
        $settings = Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';

        if (in_array($storageSetting, ['r2', 's3', 'wasabi'])) {
            $disk = $storageSetting;
            if ($storageSetting === 'r2') {
                config([
                    'filesystems.disks.r2.key' => $settings['r2_key'],
                    'filesystems.disks.r2.secret' => $settings['r2_secret'],
                    'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                    'filesystems.disks.r2.bucket' => $settings['r2_bucket'],
                    'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'],
                    'filesystems.disks.r2.url' => $settings['r2_url'],
                    'filesystems.disks.r2.use_path_style_endpoint' => false,
                ]);
            }
            return $disk;
        }

        return 'public';
    }

    /**
     * Check if user can manage fiscal resources
     * Works for both 'super admin' type and 'superAdminEmployee' with permission
     */
    private function canManageFiscalResources()
    {
        $user = auth()->user();
        
        // SuperAdmin has full access
        if ($user->type === 'super admin') {
            return true;
        }
        
        // SuperAdmin Employee with specific permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            // Check if 'manage fiscal-resources' permission exists (permission id 5 or name)
            return in_array('manage fiscal-resources', $permissions) || in_array(5, $permissions);
        }
        
        return false;
    }

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

        $resourceType = $request->get('resource_type', $request->get('type'));
        if (!empty($resourceType)) {
            $query->where('resource_type', $resourceType);
        }

        if ($request->has('year') && $request->year) {
            $query->where('year', $request->year);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('file_name', 'like', '%' . $search . '%');
            });
        }

        $resources = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => FiscalSocialResource::count(),
            'mobile_visible' => FiscalSocialResource::where('is_mobile_visible', true)->count(),
            'countries' => FiscalSocialResource::distinct('country')->count(),
            'current_year' => FiscalSocialResource::where('year', date('Y'))->count(),
            'total_views' => FiscalSocialResource::sum('views_count'),
        ];

        return view('fiscal-resources.index', compact('resources', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!$this->canManageFiscalResources()) {
            return redirect()->route('fiscal-resources.index')->with('error', __('Permission Denied.'));
        }

        // Build simple country array
        $countries = [
            'BJ' => 'Bénin',
            'BF' => 'Burkina Faso',
            'CM' => 'Cameroun',
            'CI' => 'Côte d\'Ivoire',
            'CD' => 'RD Congo',
            'GA' => 'Gabon',
            'GW' => 'Guinée-Bissau',
            'MG' => 'Madagascar',
            'ML' => 'Mali',
            'MA' => 'Maroc',
            'NE' => 'Niger',
            'SN' => 'Sénégal',
            'TG' => 'Togo',
            'TN' => 'Tunisie'
        ];
        
        $years = range(date('Y'), date('Y') + 5);
        $categories = ResourceCategory::active()->get();

        return view('fiscal-resources.create', compact('countries', 'years', 'categories'));
    }

    /**
     * Show the form for bulk upload fiscal/social resources
     */
    public function bulkUploadForm()
    {
        if (!$this->canManageFiscalResources()) {
            return redirect()->route('fiscal-resources.index')->with('error', __('Permission Denied.'));
        }

        $countries = [
            'BJ' => 'Bénin',
            'BF' => 'Burkina Faso',
            'CM' => 'Cameroun',
            'CI' => 'Côte d\'Ivoire',
            'CD' => 'RD Congo',
            'GA' => 'Gabon',
            'GW' => 'Guinée-Bissau',
            'MG' => 'Madagascar',
            'ML' => 'Mali',
            'MA' => 'Maroc',
            'NE' => 'Niger',
            'SN' => 'Sénégal',
            'TG' => 'Togo',
            'TN' => 'Tunisie'
        ];

        $years = range(date('Y'), date('Y') + 5);
        $categories = ResourceCategory::active()->get();

        return view('fiscal-resources.bulk-upload', compact('countries', 'years', 'categories'));
    }

    /**
     * Store multiple fiscal/social resources at once
     */
    public function bulkUploadStore(Request $request)
    {
        if (!$this->canManageFiscalResources()) {
            return redirect()->route('fiscal-resources.index')->with('error', __('Permission Denied.'));
        }

        $request->merge([
            'is_mobile_visible' => $request->has('is_mobile_visible'),
        ]);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:resource_categories,id',
            'resource_type' => 'required|in:cgi,finance_law,tax_procedure,circular,doctrine,convention,labor_code,social_code,collective_agreement,salary_grid,administrative_form,other',
            'country' => 'required|string',
            'year' => 'required|integer|min:2020|max:2030',
            'version' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_mobile_visible' => 'boolean',
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:153600|mimes:pdf,doc,docx,xlsx,xls',
        ]);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $uploadedCount = 0;
        $errors = [];

        if ($request->hasFile('files')) {
            $disk = $this->getStorageDisk();
            foreach ($request->file('files') as $file) {
                try {
                    $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $title = trim($baseName) !== '' ? $baseName : 'resource_' . time();
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('fiscal_resources', $fileName, $disk);

                    $resource = FiscalSocialResource::create([
                        'category_id' => $request->category_id,
                        'title' => $title,
                        'slug' => Str::slug($title),
                        'description' => $request->description,
                        'resource_type' => $request->resource_type,
                        'country' => $request->country,
                        'year' => $request->year,
                        'version' => $request->version ?? '1.0',
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'is_mobile_visible' => $request->boolean('is_mobile_visible'),
                        'created_by' => auth()->id(),
                        'extracted_text' => null,
                    ]);

                    ProcessFiscalResourceForRAG::dispatchSync($resource->id);
                    $uploadedCount++;
                } catch (\Exception $e) {
                    $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                }
            }
        }

        $message = '';
        if ($uploadedCount > 0) {
            $message = __('Successfully uploaded :count resource(s).', ['count' => $uploadedCount]);
        }
        if (!empty($errors)) {
            $message .= ' ' . __('Errors: ') . implode(', ', $errors);
            return redirect()->route('fiscal-resources.index')->with('warning', $message);
        }

        return redirect()->route('fiscal-resources.index')->with('success', $message);
    }

    /**
     * Show single resource
     */
    public function show($id)
    {
        $resource = FiscalSocialResource::findOrFail($id);
        return view('fiscal-resources.show', compact('resource'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        if (!$this->canManageFiscalResources()) {
            return redirect()->route('fiscal-resources.index')->with('error', __('Permission Denied.'));
        }

        $resource = FiscalSocialResource::findOrFail($id);

        $countries = [
            'BJ' => 'Bénin',
            'BF' => 'Burkina Faso',
            'CM' => 'Cameroun',
            'CI' => 'Côte d\'Ivoire',
            'CD' => 'RD Congo',
            'GA' => 'Gabon',
            'GW' => 'Guinée-Bissau',
            'MG' => 'Madagascar',
            'ML' => 'Mali',
            'MA' => 'Maroc',
            'NE' => 'Niger',
            'SN' => 'Sénégal',
            'TG' => 'Togo',
            'TN' => 'Tunisie'
        ];
        
        $years = range(date('Y'), date('Y') + 5);
        $categories = ResourceCategory::active()->get();

        return view('fiscal-resources.edit', compact('resource', 'countries', 'years', 'categories'));
    }

    /**
     * Store a new fiscal/social resource
     */
    public function store(Request $request)
    {
        if (!$this->canManageFiscalResources()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Normalize checkbox value to proper boolean
        $request->merge([
            'is_mobile_visible' => $request->has('is_mobile_visible'),
        ]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:resource_categories,id',
            'resource_type' => 'required|in:cgi,finance_law,tax_procedure,circular,doctrine,convention,labor_code,social_code,collective_agreement,salary_grid,administrative_form,other',
            'country' => 'required|string',
            'year' => 'required|integer|min:2020|max:2030',
            'version' => 'nullable|string|max:50',
            'file' => 'required|file|max:153600|mimes:pdf,doc,docx,xlsx,xls',
            'description' => 'nullable|string',
            'is_mobile_visible' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            $disk = $this->getStorageDisk();
            $filePath = $file->storeAs('fiscal_resources', $fileName, $disk);

            $resource = FiscalSocialResource::create([
                'category_id' => $request->category_id,
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'description' => $request->description,
                'resource_type' => $request->resource_type,
                'country' => $request->country,
                'year' => $request->year,
                'version' => $request->version ?? '1.0',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'is_mobile_visible' => $request->boolean('is_mobile_visible'),
                'created_by' => auth()->id(),
                'extracted_text' => null,
            ]);

            // Immediate extraction + Pinecone indexing
            ProcessFiscalResourceForRAG::dispatchSync($resource->id);

            return redirect()->route('fiscal-resources.index')
                ->with('success', 'Ressource fiscale/sociale créée et indexée avec succès');
        }

        return redirect()->back()
            ->with('error', 'Erreur : aucun fichier uploadé')
            ->withInput();
    }

    /**
     * Update fiscal/social resource
     */
    public function update(Request $request, $id)
    {
        if (!$this->canManageFiscalResources()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $resource = FiscalSocialResource::findOrFail($id);

        // Normalize checkbox value to proper boolean
        $request->merge([
            'is_mobile_visible' => $request->has('is_mobile_visible'),
        ]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:resource_categories,id',
            'resource_type' => 'required|in:cgi,finance_law,tax_procedure,circular,doctrine,convention,labor_code,social_code,collective_agreement,salary_grid,administrative_form,other',
            'country' => 'required|string',
            'year' => 'required|integer|min:2020|max:2030',
            'version' => 'nullable|string|max:50',
            'file' => 'nullable|file|max:153600|mimes:pdf,doc,docx,xlsx,xls',
            'description' => 'nullable|string',
            'is_mobile_visible' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'resource_type' => $request->resource_type,
            'country' => $request->country,
            'year' => $request->year,
            'version' => $request->version ?? '1.0',
            'is_mobile_visible' => $request->boolean('is_mobile_visible'),
        ];

        $shouldReindex = false;
        // Handle file upload if provided
        if ($request->hasFile('file')) {
            $disk = $this->getStorageDisk();
            
            if (Storage::disk($disk)->exists($resource->file_path)) {
                Storage::disk($disk)->delete($resource->file_path);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('fiscal_resources', $fileName, $disk);

            $updateData['file_path'] = $filePath;
            $updateData['file_name'] = $fileName;
            $updateData['file_type'] = $file->getClientOriginalExtension();
            $updateData['file_size'] = $file->getSize();
            $updateData['extracted_text'] = null;
            $shouldReindex = true;
        }

        $resource->update($updateData);

        if ($shouldReindex) {
            ProcessFiscalResourceForRAG::dispatchSync($resource->id);
        }

        return redirect()->route('fiscal-resources.index')
            ->with('success', 'Ressource fiscale/sociale mise à jour avec succès');
    }

    /**
     * Store new category via AJAX
     */
    public function storeCategory(Request $request)
    {
        if (!$this->canManageFiscalResources()) {
            return response()->json([
                'success' => false,
                'message' => __('Permission Denied.')
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:resource_categories,name',
            'description' => 'nullable|string',
        ]);

        try {
            $category = ResourceCategory::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'type' => 'fiscal',
                'is_active' => true,
                'sort_order' => ResourceCategory::max('sort_order') + 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Catégorie créée avec succès.'),
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create fiscal category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Une erreur est survenue lors de la création de la catégorie fiscale.')
            ], 500);
        }

        return response()->json([
            'success' => false,
            'message' => __('Une erreur inconnue est survenue.')
        ], 500);
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
        $disk = $this->getStorageDisk();
        
        // Delete file
        if ($resource->file_path && Storage::disk($disk)->exists($resource->file_path)) {
            Storage::disk($disk)->delete($resource->file_path);
        }

        // Remove from vector index
        (new AdvancedRagService())->deleteFiscalResource($resource->id);

        // Hard delete to remove from DB entirely
        $resource->forceDelete();

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

    /**
     * Download fiscal/social resource file
     */
    public function download($id)
    {
        $resource = FiscalSocialResource::findOrFail($id);

        $settings = Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';

        // Cloud storages (R2/S3/Wasabi): redirect to public URL
        if (in_array($storageSetting, ['r2', 's3', 'wasabi'])) {
            $url = Utility::get_file($resource->file_path);
            if (!empty($url)) {
                $resource->increment('downloads_count');
                return redirect($url);
            }
            // fallback to local if URL missing
        }

        $filePath = storage_path('app/public/' . $resource->file_path);
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', __('File not found.'));
        }

        $resource->increment('downloads_count');
        return response()->download($filePath, $resource->file_name ?? basename($filePath));
    }
}
