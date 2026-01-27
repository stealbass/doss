<?php

namespace App\Http\Controllers;

use App\Models\LegalCategory;
use App\Models\LegalDocument;
use App\Models\Utility;
use App\Jobs\ProcessLegalDocumentForRAG;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class LegalLibraryController extends Controller
{
    /**
     * Get the configured storage disk (local or r2)
     */
    private function getStorageDisk()
    {
        $settings = Utility::settings();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        
        // Configure R2 disk with DB credentials if R2 is selected
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
            return 'r2';
        }
        
        return 'public';
    }

    /**
     * Check if user can manage legal library
     * Allow Super Admin and superAdminEmployee with proper permissions
     */
    private function canManageLegalLibrary()
    {
        $user = Auth::user();
        
        // Allow Super Admin (platform level)
        if ($user->type === 'super admin') {
            return true;
        }
        
        // Allow SuperAdmin Employee with 'manage legal-library' permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            return in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
        
        // Allow all authenticated users (company employees, advocates, clients, etc.)
        // In SAAS, every authenticated user should have access to Legal Library
        return Auth::check();
    }
    /**
     * Display a listing of categories
     */
    public function index()
    {
        // Restrict to Super Admin only - global library management
        if ($this->canManageLegalLibrary()) {
            $categories = LegalCategory::withCount('documents')
                ->get();
            return view('legal-library.index', compact('categories'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new category
     */
    public function createCategory()
    {
        if ($this->canManageLegalLibrary()) {
            return view('legal-library.create-category');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created category
     */
    public function storeCategory(Request $request)
    {
        if ($this->canManageLegalLibrary()) {
            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'name' => 'required|max:255',
                    'description' => 'nullable',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            LegalCategory::create([
                'name' => $request->name,
                'description' => $request->description,
                'country' => $request->country,
                'is_mobile_visible' => $request->has('is_mobile_visible') ? 1 : 0,
                'sort_order' => $request->sort_order ?? 0,
                'created_by' => 0, // Super Admin level - no company association
            ]);

            return redirect()->route('legal-library.index')->with('success', __('Category successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for editing a category
     */
    public function editCategory($id)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($id);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }
            return view('legal-library.edit-category', compact('category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update a category
     */
    public function updateCategory(Request $request, $id)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($id);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }

            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'name' => 'required|max:255',
                    'description' => 'nullable',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'country' => $request->country,
                'is_mobile_visible' => $request->has('is_mobile_visible') ? 1 : 0,
                'sort_order' => $request->sort_order ?? 0,
            ]);

            return redirect()->route('legal-library.index')->with('success', __('Category successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Delete a category
     */
    public function destroyCategory($id)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($id);
            if ($category) {
                $category->delete();
                return redirect()->route('legal-library.index')->with('success', __('Category successfully deleted.'));
            }
            return redirect()->back()->with('error', __('Category not found.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display documents for a category
     */
    public function showDocuments($categoryId)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($categoryId);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }

            // Get all documents for this category (global library)
            $documents = LegalDocument::where('category_id', $categoryId)
                ->get();

            return view('legal-library.documents', compact('category', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new document
     */
    public function createDocument($categoryId)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($categoryId);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }
            return view('legal-library.create-document', compact('category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created document
     */
    public function storeDocument(Request $request, $categoryId)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($categoryId);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }

            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'title' => 'required|max:255',
                    'description' => 'nullable',
                    'country' => 'required|string|max:2',
                    'file' => 'required|file|mimes:pdf|max:204800', // 200MB max
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $disk = $this->getStorageDisk();
                $filePath = $file->storeAs('legal_documents', $fileName, $disk);

                $document = LegalDocument::create([
                    'category_id' => $categoryId,
                    'title' => $request->title,
                    'description' => $request->description,
                    'country' => strtoupper($request->country),
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'created_by' => 0, // Super Admin level - no company association
                    'extracted_text' => null,
                ]);

                // Immediate extraction + Pinecone indexing
                ProcessLegalDocumentForRAG::dispatchSync($document->id);

                return redirect()->route('legal-library.documents', $categoryId)
                    ->with('success', __('Document successfully uploaded and indexed.'));
            }

            return redirect()->back()->with('error', __('File upload failed.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for bulk upload documents
     */
    public function bulkUploadForm($categoryId)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($categoryId);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }
            return view('legal-library.bulk-upload', compact('category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store multiple documents at once
     */
    public function bulkUploadStore(Request $request, $categoryId)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($categoryId);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }

            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'country' => 'required|string|max:10',
                    'files' => 'required|array|min:1',
                    'files.*' => 'required|file|mimes:pdf|max:204800', // 200MB max per file
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $uploadedCount = 0;
            $errors = [];
            $selectedCountry = strtoupper($request->country);

            if ($request->hasFile('files')) {
                $disk = $this->getStorageDisk();
                foreach ($request->file('files') as $file) {
                    try {
                        // Generate unique filename
                        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $filePath = $file->storeAs('legal_documents', $fileName, $disk);

                        // Extract title from filename (remove extension)
                        $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                        // Create document record with selected country
                        $document = LegalDocument::create([
                            'category_id' => $categoryId,
                            'title' => $title,
                            'description' => null, // Can be updated later
                            'country' => $selectedCountry,
                            'file_path' => $filePath,
                            'file_name' => $file->getClientOriginalName(),
                            'file_size' => $file->getSize(),
                            'created_by' => 0,
                            'extracted_text' => null,
                        ]);

                        ProcessLegalDocumentForRAG::dispatchSync($document->id);

                        $uploadedCount++;
                    } catch (\Exception $e) {
                        $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                    }
                }
            }

            // Build success/error message
            $message = '';
            if ($uploadedCount > 0) {
                $message = __('Successfully uploaded :count document(s).', ['count' => $uploadedCount]);
            }
            if (!empty($errors)) {
                $message .= ' ' . __('Errors: ') . implode(', ', $errors);
                return redirect()->route('legal-library.documents', $categoryId)
                    ->with('warning', $message);
            }

            return redirect()->route('legal-library.documents', $categoryId)
                ->with('success', $message);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for editing a document
     */
    public function editDocument($id)
    {
        if ($this->canManageLegalLibrary()) {
            $document = LegalDocument::with('category')->find($id);
            if (!$document) {
                return redirect()->back()->with('error', __('Document not found.'));
            }
            return view('legal-library.edit-document', compact('document'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update a document
     */
    public function updateDocument(Request $request, $id)
    {
        if ($this->canManageLegalLibrary()) {
            $document = LegalDocument::find($id);
            if (!$document) {
                return redirect()->back()->with('error', __('Document not found.'));
            }

            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'title' => 'required|max:255',
                    'description' => 'nullable',
                    'country' => 'required|string|max:2',
                    'file' => 'nullable|file|mimes:pdf|max:204800',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'country' => strtoupper($request->country),
            ];

            // If a new file is uploaded
            if ($request->hasFile('file')) {
                $disk = $this->getStorageDisk();
                
                // Delete old file
                if (Storage::disk($disk)->exists($document->file_path)) {
                    Storage::disk($disk)->delete($document->file_path);
                }

                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('legal_documents', $fileName, $disk);

                $updateData['file_path'] = $filePath;
                $updateData['file_name'] = $file->getClientOriginalName();
                $updateData['file_size'] = $file->getSize();
                $updateData['extracted_text'] = null; // trigger re-extraction
            }

            $document->update($updateData);

            // Re-run extraction/indexing when file replaced
            if ($request->hasFile('file')) {
                ProcessLegalDocumentForRAG::dispatchSync($document->id);
            }

            return redirect()->route('legal-library.documents', $document->category_id)
                ->with('success', __('Document successfully updated and indexed.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Delete a document
     */
    public function destroyDocument($id)
    {
        if ($this->canManageLegalLibrary()) {
            $document = LegalDocument::find($id);
            if ($document) {
                $categoryId = $document->category_id;
                $document->delete();
                return redirect()->route('legal-library.documents', $categoryId)
                    ->with('success', __('Document successfully deleted.'));
            }
            return redirect()->back()->with('error', __('Document not found.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display categories grouped by country
     */
    public function categoriesByCountry()
    {
        if ($this->canManageLegalLibrary()) {
            $countries = ['benin', 'burkina-faso', 'cote-divoire', 'mali', 'niger', 'senegal', 'togo', 'gabon', 'congo', 'cameroun'];
            
            // Get all categories (including those without country)
            $allCategories = LegalCategory::select('id', 'name', 'slug', 'country', 'is_mobile_visible', 'sort_order')
                ->orderBy('country')
                ->orderBy('sort_order')
                ->get();
            
            // Group categories by country
            $categories = $allCategories->whereIn('country', $countries)->groupBy('country');
            
            // Get categories without country
            $categoriesWithoutCountry = $allCategories->whereNull('country');
            
            return view('legal-library.categories-by-country', compact('categories', 'countries', 'categoriesWithoutCountry'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Toggle category mobile visibility
     */
    public function toggleCategoryVisibility($id)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($id);
            if ($category) {
                $category->is_mobile_visible = !$category->is_mobile_visible;
                $category->save();
                return response()->json([
                    'success' => true,
                    'is_mobile_visible' => $category->is_mobile_visible
                ]);
            }
            return response()->json(['success' => false], 404);
        }
        return response()->json(['success' => false, 'message' => 'Permission Denied'], 403);
    }

    /**
     * Update category sort order
     */
    public function updateCategorySort($id, Request $request)
    {
        if ($this->canManageLegalLibrary()) {
            $category = LegalCategory::find($id);
            if ($category) {
                $category->sort_order = $request->sort_order;
                $category->save();
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false], 404);
        }
        return response()->json(['success' => false, 'message' => 'Permission Denied'], 403);
    }

    /**
     * Filter categories by country
     */
    public function filterByCountry($country)
    {
        if ($this->canManageLegalLibrary()) {
            $categories = LegalCategory::where('country', $country)
                ->orderBy('sort_order')
                ->get();
            return response()->json(['categories' => $categories]);
        }
        return response()->json(['success' => false, 'message' => 'Permission Denied'], 403);
    }

    /**
     * Show bulk assign countries page
     */
    public function bulkAssignCountries()
    {
        if ($this->canManageLegalLibrary()) {
            $categories = LegalCategory::withCount('documents')
                ->orderBy('name')
                ->get();
            
            return view('legal-library.bulk-assign-countries', compact('categories'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Save bulk country assignments
     */
    public function saveBulkAssignCountries(Request $request)
    {
        if ($this->canManageLegalLibrary()) {
            $categories = $request->input('categories', []);
            $updatedCount = 0;

            foreach ($categories as $categoryId => $data) {
                $category = LegalCategory::find($categoryId);
                if ($category) {
                    $category->update([
                        'country' => $data['country'] ?? null,
                        'is_mobile_visible' => isset($data['is_mobile_visible']) ? 1 : 0,
                    ]);
                    $updatedCount++;
                }
            }

            return redirect()->route('legal-library.index')
                ->with('success', __('Successfully updated') . ' ' . $updatedCount . ' ' . __('categories'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
