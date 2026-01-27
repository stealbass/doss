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
        $user = Auth::user();
        
        // Check if user has access
        $hasAccess = $user->type === 'super admin' || $user->type === 'company' || $user->can('view legal library');
        
        // SuperAdminEmployee with 'manage legal-library' permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            $hasAccess = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
        
        if ($hasAccess) {
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
        $user = Auth::user();
        
        // Check if user has access
        $hasAccess = $user->type === 'super admin' || $user->type === 'company' || $user->can('view legal library');
        
        // SuperAdminEmployee with 'manage legal-library' permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            $hasAccess = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
        
        if ($hasAccess) {
            // Check if user has free plan (skip for superadmin and superAdminEmployee)
            if ($user->type !== 'super admin' && $user->type !== 'superAdminEmployee' && $user->hasFreePlan()) {
                return redirect()->route('user.legal-library.index')
                    ->with('error', __('Cette fonctionnalité nécessite un abonnement premium. Veuillez souscrire à un plan pour accéder aux documents.'));
            }

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
        $user = Auth::user();
        
        // Check if user has access
        $hasAccess = $user->type === 'super admin' || $user->type === 'company' || $user->can('view legal library');
        
        // SuperAdminEmployee with 'manage legal-library' permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            $hasAccess = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
        
        if ($hasAccess) {
            // Check if user has free plan (skip for superadmin)
            if ($user->type !== 'super admin' && $user->hasFreePlan()) {
                return redirect()->route('user.legal-library.index')
                    ->with('error', __('Cette fonctionnalité nécessite un abonnement premium. Veuillez souscrire à un plan pour accéder aux documents.'));
            }

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
     * Stream a document for preview (inline display)
     */
    public function streamDocument($id)
    {
        $user = Auth::user();
        
        // Check if user has access
        $hasAccess = $user->type === 'super admin' || $user->type === 'company' || $user->can('view legal library');
        
        // SuperAdminEmployee with 'manage legal-library' permission
        if ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            $hasAccess = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
        
        if ($hasAccess) {
            // Check if user has free plan (skip for superadmin)
            if ($user->type !== 'super admin' && $user->hasFreePlan()) {
                abort(403, 'Cette fonctionnalité nécessite un abonnement premium.');
            }

            $document = LegalDocument::find($id);
            
            if (!$document) {
                abort(404, 'Document not found');
            }

            // Get storage setting
            $settings = \App\Models\Utility::settings();
            $storageSetting = $settings['storage_setting'] ?? 'local';
            
            if ($storageSetting === 'r2') {
                // For R2: redirect to public URL for inline preview
                $url = \App\Models\Utility::get_file($document->file_path);
                
                if (empty($url)) {
                    abort(404, 'File not found on R2');
                }
                
                // Redirect to R2 public URL (browser will display PDF inline)
                return redirect($url);
            } else {
                // For local storage: stream file directly
                $filePath = storage_path('app/public/' . $document->file_path);
                
                if (!file_exists($filePath)) {
                    abort(404, 'File not found');
                }

                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
                ]);
            }
        } else {
            abort(403, 'Permission Denied');
        }
    }

    /**
     * Download a document
     */
    public function downloadDocument($id)
    {
        $user = Auth::user();
        
        // Check if user has access
        $hasAccess = false;
        
        // Super admin always has access
        if ($user->type === 'super admin') {
            $hasAccess = true;
        }
        // SuperAdminEmployee with 'manage legal-library' permission
        elseif ($user->type === 'superAdminEmployee') {
            $permissions = json_decode($user->permission_json, true) ?? [];
            $hasAccess = in_array('manage legal-library', $permissions) || in_array(3, $permissions);
        }
        // Company users or users with 'view legal library' permission
        elseif ($user->type === 'company' || $user->can('view legal library')) {
            $hasAccess = true;
        }
        
        if ($hasAccess) {
            // Check if user has free plan (skip for superadmin and superAdminEmployee)
            if ($user->type !== 'super admin' && $user->type !== 'superAdminEmployee' && $user->hasFreePlan()) {
                return redirect()->route('user.legal-library.index')
                    ->with('error', __('Cette fonctionnalité nécessite un abonnement premium. Veuillez souscrire à un plan pour télécharger des documents.'));
            }

            $document = LegalDocument::find($id);
            
            if (!$document) {
                return redirect()->back()->with('error', __('Document not found.'));
            }

            // Get storage setting
            $settings = \App\Models\Utility::settings();
            $storageSetting = $settings['storage_setting'] ?? 'local';
            
            if ($storageSetting === 'r2' || $storageSetting === 's3' || $storageSetting === 'wasabi') {
                // For cloud storage: redirect to public URL
                $url = \App\Models\Utility::get_file($document->file_path);
                
                // Increment download count
                $document->incrementDownloads();
                
                return redirect($url);
            } else {
                // For local storage: direct download
                $filePath = storage_path('app/public/' . $document->file_path);
                
                if (!file_exists($filePath)) {
                    return redirect()->back()->with('error', __('File not found.'));
                }

                // Increment download count
                $document->incrementDownloads();

                return response()->download($filePath, $document->file_name);
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
