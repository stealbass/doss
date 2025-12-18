<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentTemplateController extends Controller
{
    /**
     * Display templates dashboard
     */
    public function index(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
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
        $categories = TemplateCategory::active()->get();
        $countries = config('mobile_countries.supported_countries');

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
        if (Auth::user()->type !== 'super admin') {
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
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:template_categories,id',
            'template_type' => 'required|in:contract,act,form,letter,calculator,checklist',
            'file' => 'required|file|mimes:doc,docx,pdf,xlsx,xls|max:10240', // 10MB
            'country' => 'nullable|string|size:2',
            'is_premium' => 'boolean',
            'is_mobile_visible' => 'boolean',
        ]);

        try {
            // Upload file
            $file = $request->file('file');
            $fileName = Str::slug($request->name) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('templates', $fileName, 'public');

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
                'is_premium' => $request->has('is_premium'),
                'is_mobile_visible' => $request->has('is_mobile_visible') ? true : false,
                'variables' => $request->variables ? json_decode($request->variables, true) : null,
                'ai_context' => $request->ai_context,
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->route('document-templates.index')
                ->with('success', __('Template created successfully.'));

        } catch (\Exception $e) {
            Log::error('Template creation failed: ' . $e->getMessage());
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
        if (Auth::user()->type !== 'super admin') {
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
        if (Auth::user()->type !== 'super admin') {
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
            // Handle file upload if new file provided
            if ($request->hasFile('file')) {
                // Delete old file
                if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                    Storage::disk('public')->delete($template->file_path);
                }

                // Upload new file
                $file = $request->file('file');
                $fileName = Str::slug($request->name) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('templates', $fileName, 'public');

                $template->file_path = $filePath;
                $template->file_name = $fileName;
                $template->file_type = $file->getClientOriginalExtension();
                $template->file_size = $file->getSize();
            }

            // Update template
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
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        try {
            $template = DocumentTemplate::findOrFail($id);

            // Delete file
            if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                Storage::disk('public')->delete($template->file_path);
            }

            $template->delete();

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

        // Check if file exists
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
        if (Auth::user()->type !== 'super admin') {
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
        if (Auth::user()->type !== 'super admin') {
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
        if (Auth::user()->type !== 'super admin') {
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
}
