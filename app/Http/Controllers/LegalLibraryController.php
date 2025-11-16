<?php

namespace App\Http\Controllers;

use App\Models\LegalCategory;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class LegalLibraryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        // Restrict to Super Admin only - global library management
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
            $category = LegalCategory::find($categoryId);
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }

            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'title' => 'required|max:255',
                    'description' => 'nullable',
                    'file' => 'required|file|mimes:pdf|max:20480', // 20MB max
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('legal_documents', $fileName, 'public');

                LegalDocument::create([
                    'category_id' => $categoryId,
                    'title' => $request->title,
                    'description' => $request->description,
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'created_by' => 0, // Super Admin level - no company association
                ]);

                return redirect()->route('legal-library.documents', $categoryId)
                    ->with('success', __('Document successfully uploaded.'));
            }

            return redirect()->back()->with('error', __('File upload failed.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for editing a document
     */
    public function editDocument($id)
    {
        if (Auth::user()->type == 'super admin') {
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
        if (Auth::user()->type == 'super admin') {
            $document = LegalDocument::find($id);
            if (!$document) {
                return redirect()->back()->with('error', __('Document not found.'));
            }

            $validator = FacadesValidator::make(
                $request->all(),
                [
                    'title' => 'required|max:255',
                    'description' => 'nullable',
                    'file' => 'nullable|file|mimes:pdf|max:20480',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
            ];

            // If a new file is uploaded
            if ($request->hasFile('file')) {
                // Delete old file
                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('legal_documents', $fileName, 'public');

                $updateData['file_path'] = $filePath;
                $updateData['file_name'] = $file->getClientOriginalName();
                $updateData['file_size'] = $file->getSize();
            }

            $document->update($updateData);

            return redirect()->route('legal-library.documents', $document->category_id)
                ->with('success', __('Document successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Delete a document
     */
    public function destroyDocument($id)
    {
        if (Auth::user()->type == 'super admin') {
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
}
