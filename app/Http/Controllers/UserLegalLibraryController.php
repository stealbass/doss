<?php

namespace App\Http\Controllers;

use App\Models\LegalCategory;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserLegalLibraryController extends Controller
{
    /**
     * Display the legal library for users
     */
    public function index(Request $request)
    {
        if (Auth::user()->can('view legal library')) {
            $search = $request->get('search');
            
            // Get all categories with document count (Super Admin level - global library)
            $categories = LegalCategory::withCount('documents')
                ->get();

            // If there's a search query, get matching documents
            $documents = null;
            if ($search) {
                $documents = LegalDocument::where(function($query) use ($search) {
                        $query->where('title', 'like', '%' . $search . '%')
                              ->orWhere('description', 'like', '%' . $search . '%');
                    })
                    ->with('category')
                    ->get();
            }

            return view('user-legal-library.index', compact('categories', 'documents', 'search'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display documents in a specific category
     */
    public function showCategory($categoryId)
    {
        if (Auth::user()->can('view legal library')) {
            $category = LegalCategory::find($categoryId);
            
            if (!$category) {
                return redirect()->back()->with('error', __('Category not found.'));
            }

            // Get all documents from this category (Super Admin level - global library)
            $documents = LegalDocument::where('category_id', $categoryId)
                ->get();

            return view('user-legal-library.category', compact('category', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * View a document (preview)
     */
    public function viewDocument($id)
    {
        if (Auth::user()->can('view legal library')) {
            $document = LegalDocument::with('category')->find($id);
            
            if (!$document) {
                return redirect()->back()->with('error', __('Document not found.'));
            }

            // Increment view/download count
            $document->incrementDownloads();

            return view('user-legal-library.view', compact('document'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Download a document
     */
    public function downloadDocument($id)
    {
        if (Auth::user()->can('view legal library')) {
            $document = LegalDocument::find($id);
            
            if (!$document) {
                return redirect()->back()->with('error', __('Document not found.'));
            }

            $filePath = storage_path('app/public/' . $document->file_path);
            
            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', __('File not found.'));
            }

            // Increment download count
            $document->incrementDownloads();

            return response()->download($filePath, $document->file_name);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
