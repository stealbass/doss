<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTemplateForRAG;
use App\Models\DocumentTemplate;
use App\Models\TemplateCategory;
use App\Models\Utility;
use App\Services\AdvancedRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentTemplateController extends Controller
{
    /**
     * Resolve storage disk based on settings (local, r2, s3, wasabi)
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
     * Check if user can manage document templates
     * Works for both 'super admin' type and 'superAdminEmployee' with permission
     */
    private function canManageDocumentTemplate()
    {
        $user = Auth::user();
        
        // SuperAdmin has full access
        if ($user->type === 'super admin') {
            return true;
        }
        
        // SuperAdmin Employee with specific permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            // Check if 'manage document-template' permission exists (permission id 4 or name)
            return in_array('manage document-template', $permissions) || in_array(4, $permissions);
        }
        
        return false;
    }

    /**
     * Display templates dashboard
     */
    public function index(Request $request)
    {
        if (!$this->canManageDocumentTemplate()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $query = DocumentTemplate::with('category');

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        if ($request->filled('template_type')) {
            $query->where('template_type', $request->template_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->paginate(20);
        
        // Initialize $categories safely to prevent null foreach errors
        $categories = collect();
        try {
            if (Schema::hasTable('template_categories')) {
                $cats = TemplateCategory::active()->get();
                if ($cats && count($cats) > 0) {
                    $categories = $cats;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Template categories load failed: ' . $e->getMessage());
        }
        
        // Ensure $categories is never null
        if (!$categories || is_null($categories)) {
            $categories = collect();
        }
        
        $countries = config('mobile_countries.supported_countries', []);

        $stats = [
            'total_templates' => DocumentTemplate::count(),
            'mobile_visible' => DocumentTemplate::where('is_mobile_visible', true)->count(),
            'premium_templates' => DocumentTemplate::where('is_premium', true)->count(),
            'total_downloads' => DocumentTemplate::sum('downloads_count'),
            'by_country' => DocumentTemplate::selectRaw('country, COUNT(*) as count')
                ->whereNotNull('country')
                ->groupBy('country')
                ->pluck('count', 'country')
                ->toArray(),
        ];

        return view('document-templates.index', compact('templates', 'categories', 'countries', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!$this->canManageDocumentTemplate()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $categories = TemplateCategory::active()->get();
        $countries = config('mobile_countries.supported_countries');

        return view('document-templates.create', compact('categories', 'countries'));
    }

    /**
     * Store new template
     */
    public function store(Request $request)
    {
        if (!$this->canManageDocumentTemplate()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Permission Denied.')], 403);
            }
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Normalize booleans coming from checkbox fields
        $request->merge([
            'is_mobile_visible' => $request->has('is_mobile_visible'),
            'is_premium' => $request->has('is_premium'),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:template_categories,id',
            'template_type' => 'required|in:contract,act,form,letter,calculator,checklist',
            'file' => 'required|file|mimes:doc,docx,pdf,xlsx,xls|max:10240', // 10MB
            'country' => 'nullable|string|size:2',
            'allowed_plans' => 'required|in:free,student,professional,enterprise',
            'is_premium' => 'boolean',
            'is_mobile_visible' => 'boolean',
        ]);

        try {
            // Upload file
            $file = $request->file('file');
            $fileName = Str::slug($request->name) . '_' . time() . '.' . $file->getClientOriginalExtension();

            $disk = $this->getStorageDisk();
            $filePath = $file->storeAs('templates', $fileName, $disk);

            // Create template
            $template = DocumentTemplate::create([
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . Str::random(6),
                'description' => $request->description,
                'country' => $request->country ? strtoupper($request->country) : null,
                'language' => $request->language ?? 'fr',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'template_type' => $request->template_type,
                'allowed_plans' => $request->allowed_plans ?? null,
                'is_premium' => $request->boolean('is_premium'),
                'is_mobile_visible' => $request->boolean('is_mobile_visible'),
                'variables' => $request->variables ? json_decode($request->variables, true) : null,
                'ai_context' => $request->ai_context,
                'created_by' => Auth::id(),
                'extracted_text' => null,
            ]);

            // Immediate extraction + Pinecone index
            ProcessTemplateForRAG::dispatchSync($template->id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Template created successfully.'),
                    'template' => $template
                ]);
            }
            
            return redirect()
                ->route('document-templates.index')
                ->with('success', __('Template created and indexed successfully.'));

        } catch (\Exception $e) {
            Log::error('Template creation failed: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to create template. Please try again.'),
                    'error' => $e->getMessage()
                ], 500);
            }
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to create template. Please try again.'));
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        if (!$this->canManageDocumentTemplate()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $template = DocumentTemplate::findOrFail($id);
        $categories = TemplateCategory::active()->get();
        $countries = config('mobile_countries.supported_countries');

        return view('document-templates.edit', compact('template', 'categories', 'countries'));
    }

    /**
     * Update template
     */
    public function update(Request $request, $id)
    {
        if (!$this->canManageDocumentTemplate()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $template = DocumentTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:template_categories,id',
            'template_type' => 'required|in:contract,act,form,letter,calculator,checklist',
            'file' => 'nullable|file|mimes:doc,docx,pdf,xlsx,xls|max:10240',
            'country' => 'nullable|string|size:2',
        ]);

        try {
            $shouldReindex = false;
            // Handle file upload if new file provided
            if ($request->hasFile('file')) {
                $disk = $this->getStorageDisk();
                
                if ($template->file_path && Storage::disk($disk)->exists($template->file_path)) {
                    Storage::disk($disk)->delete($template->file_path);
                }

                $file = $request->file('file');
                $fileName = Str::slug($request->name) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('templates', $fileName, $disk);

                $template->file_path = $filePath;
                $template->file_name = $fileName;
                $template->file_type = $file->getClientOriginalExtension();
                $template->file_size = $file->getSize();
                $template->extracted_text = null;
                $shouldReindex = true;
            }

            $template->update([
                'category_id' => $request->category_id,
                'name' => $request->name,
                'description' => $request->description,
                'country' => $request->country ? strtoupper($request->country) : null,
                'language' => $request->language ?? 'fr',
                'template_type' => $request->template_type,
                'allowed_plans' => $request->allowed_plans ?? null,
                'is_premium' => $request->has('is_premium'),
                'is_mobile_visible' => $request->has('is_mobile_visible') ? true : false,
                'variables' => $request->variables ? json_decode($request->variables, true) : null,
                'ai_context' => $request->ai_context,
            ]);

            if ($shouldReindex) {
                ProcessTemplateForRAG::dispatchSync($template->id);
            }

            return redirect()
                ->route('document-templates.index')
                ->with('success', __('Template updated successfully.'));

        } catch (\Exception $e) {
            Log::error('Template update failed: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to update template. Please try again.'));
        }
    }

    /**
     * Delete template
     */
    public function destroy($id)
    {
        if (!$this->canManageDocumentTemplate()) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        try {
            $template = DocumentTemplate::findOrFail($id);
            $disk = $this->getStorageDisk();

            // Delete file
            if ($template->file_path && Storage::disk($disk)->exists($template->file_path)) {
                Storage::disk($disk)->delete($template->file_path);
            }

            // Remove from vector index
            (new AdvancedRagService())->deleteTemplate($template->id);

            // Hard delete to remove from DB entirely
            $template->forceDelete();

            return response()->json([
                'success' => true,
                'message' => __('Template deleted successfully.')
            ]);

        } catch (\Exception $e) {
            Log::error('Template deletion failed: ' . $e->getMessage());
            return response()->json([
                'error' => __('Failed to delete template.')
            ], 500);
        }
    }

    /**
     * Download template
     */
    public function download($id)
    {
        $template = DocumentTemplate::findOrFail($id);
        
        // Increment download count
        $template->incrementDownloads();
        $settings = Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';

        if ($storageSetting === 'r2' || $storageSetting === 's3' || $storageSetting === 'wasabi') {
            $url = Utility::get_file($template->file_path);
            if (!empty($url)) {
                return redirect($url);
            }
            // fallback local if cloud URL missing
        }

        if (!Storage::disk('public')->exists($template->file_path)) {
            return redirect()->back()->with('error', __('File not found.'));
        }

        return Storage::disk('public')->download($template->file_path, $template->file_name);
    }

    /**
     * Toggle mobile visibility
     */
    public function toggleVisibility($id)
    {
        if (!$this->canManageDocumentTemplate()) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $template = DocumentTemplate::findOrFail($id);
        $template->is_mobile_visible = !$template->is_mobile_visible;
        $template->save();

        return response()->json([
            'success' => true,
            'is_mobile_visible' => $template->is_mobile_visible,
            'message' => __('Visibility updated successfully.')
        ]);
    }

    /**
     * Bulk delete
     */
    public function bulkDelete(Request $request)
    {
        if (!$this->canManageDocumentTemplate()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'template_ids' => 'required|array',
            'template_ids.*' => 'exists:document_templates,id',
        ]);

        try {
            $templates = DocumentTemplate::whereIn('id', $request->template_ids)->get();

            foreach ($templates as $template) {
                // Delete file
                if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                    Storage::disk('public')->delete($template->file_path);
                }
                $template->delete();
            }

            return redirect()
                ->back()
                ->with('success', __(':count templates deleted successfully.', ['count' => count($request->template_ids)]));

        } catch (\Exception $e) {
            Log::error('Bulk delete failed: ' . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', __('Failed to delete templates.'));
        }
    }

    /**
     * Export templates list as CSV
     */
    public function export()
    {
        if (!$this->canManageDocumentTemplate()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $templates = DocumentTemplate::with('category')->get();

        $filename = 'document_templates_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($templates) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Name',
                'Category',
                'Type',
                'Country',
                'File Type',
                'Downloads',
                'Views',
                'Premium',
                'Mobile Visible',
                'Created At',
            ]);

            // CSV Data
            foreach ($templates as $template) {
                $countryInfo = $template->country_info;
                fputcsv($file, [
                    $template->id,
                    $template->name,
                    $template->category->name ?? 'N/A',
                    $template->template_type,
                    $countryInfo ? $countryInfo['name'] : 'All',
                    strtoupper($template->file_type),
                    $template->downloads_count,
                    $template->views_count,
                    $template->is_premium ? 'Yes' : 'No',
                    $template->is_mobile_visible ? 'Yes' : 'No',
                    $template->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * Store new category via AJAX
     */
    public function storeCategory(Request $request)
    {
        if (!$this->canManageDocumentTemplate()) {
            return response()->json([
                'success' => false,
                'message' => __('Permission Denied.')
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:template_categories,name',
            'description' => 'nullable|string',
        ]);

        try {
            $category = TemplateCategory::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'is_active' => true,
                'sort_order' => TemplateCategory::max('sort_order') + 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Category created successfully.'),
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create template category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Failed to create category. Please try again.')
            ], 500);
        }
    }
}