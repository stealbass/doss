<?php

namespace App\Http\Controllers;

use App\Models\LegalCategory;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileLegalLibraryController extends Controller
{
    /**
     * Display legal library sync dashboard
     */
    public function index(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $stats = $this->getStatistics();
        
        // Get categories with document counts
        $categories = LegalCategory::withCount(['documents' => function($query) {
            $query->where('is_mobile_visible', true);
        }])->get();

        // Get recent sync activity
        $recentSyncs = DB::table('mobile_legal_sync_logs')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get documents with filters
        $query = LegalDocument::with('category');
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('mobile_status')) {
            $query->where('is_mobile_visible', $request->mobile_status === 'visible');
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate(15);

        return view('mobile-legal-library.index', compact('stats', 'categories', 'documents', 'recentSyncs'));
    }

    /**
     * Get statistics for mobile legal library
     */
    private function getStatistics()
    {
        $totalDocuments = LegalDocument::count();
        $mobileVisible = LegalDocument::where('is_mobile_visible', true)->count();
        $totalCategories = LegalCategory::count();
        $activeCategories = LegalCategory::has('documents')->count();
        
        // Get sync statistics from the last 30 days
        $lastSyncDate = DB::table('mobile_legal_sync_logs')
            ->orderBy('created_at', 'desc')
            ->value('created_at');
        
        $totalSyncs = DB::table('mobile_legal_sync_logs')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_documents' => $totalDocuments,
            'mobile_visible' => $mobileVisible,
            'mobile_hidden' => $totalDocuments - $mobileVisible,
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'last_sync_date' => $lastSyncDate,
            'total_syncs_30days' => $totalSyncs,
        ];
    }

    /**
     * Toggle mobile visibility for a document
     */
    public function toggleVisibility($id)
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $document = LegalDocument::findOrFail($id);
        $document->is_mobile_visible = !$document->is_mobile_visible;
        $document->save();

        // Log the action
        $this->logSync('toggle_visibility', [
            'document_id' => $document->id,
            'document_title' => $document->title,
            'new_status' => $document->is_mobile_visible ? 'visible' : 'hidden',
        ]);

        return response()->json([
            'success' => true,
            'is_mobile_visible' => $document->is_mobile_visible,
            'message' => __('Document visibility updated successfully.')
        ]);
    }

    /**
     * Bulk update mobile visibility
     */
    public function bulkToggleVisibility(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:legal_documents,id',
            'action' => 'required|in:show,hide',
        ]);

        $visibility = $request->action === 'show';
        $updated = LegalDocument::whereIn('id', $request->document_ids)
            ->update(['is_mobile_visible' => $visibility]);

        // Log the bulk action
        $this->logSync('bulk_toggle_visibility', [
            'count' => $updated,
            'action' => $request->action,
            'document_ids' => $request->document_ids,
        ]);

        return redirect()->back()->with('success', __(':count documents updated successfully.', ['count' => $updated]));
    }

    /**
     * Sync category settings
     */
    public function syncCategory($id, Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $category = LegalCategory::findOrFail($id);
        
        $request->validate([
            'is_mobile_visible' => 'required|boolean',
        ]);

        $category->is_mobile_visible = $request->is_mobile_visible;
        $category->save();

        // Also update all documents in this category
        LegalDocument::where('category_id', $id)
            ->update(['is_mobile_visible' => $request->is_mobile_visible]);

        // Log the action
        $this->logSync('sync_category', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'is_mobile_visible' => $request->is_mobile_visible,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Category and all its documents synced successfully.')
        ]);
    }

    /**
     * Force full sync - Make all documents visible on mobile
     */
    public function forceSync()
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        try {
            DB::beginTransaction();

            // Update all categories
            LegalCategory::query()->update(['is_mobile_visible' => true]);
            
            // Update all documents
            $updated = LegalDocument::query()->update(['is_mobile_visible' => true]);

            // Log the full sync
            $this->logSync('force_full_sync', [
                'total_documents' => $updated,
                'timestamp' => now()->toDateTimeString(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', __('Full sync completed. All :count documents are now visible on mobile.', ['count' => $updated]));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mobile legal library full sync failed: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Sync failed. Please try again.'));
        }
    }

    /**
     * Get sync statistics API
     */
    public function statistics()
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $stats = $this->getStatistics();
        
        // Add category breakdown
        $categoryStats = LegalCategory::withCount([
            'documents',
            'documents as mobile_visible_count' => function($query) {
                $query->where('is_mobile_visible', true);
            }
        ])->get();

        // Get sync history chart data (last 30 days)
        $syncHistory = DB::table('mobile_legal_sync_logs')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'stats' => $stats,
            'category_breakdown' => $categoryStats,
            'sync_history' => $syncHistory,
        ]);
    }

    /**
     * Export mobile library configuration as CSV
     */
    public function export()
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $documents = LegalDocument::with('category')->get();

        $filename = 'mobile_legal_library_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($documents) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Title',
                'Category',
                'Description',
                'File Type',
                'File Size',
                'Mobile Visible',
                'Created At',
                'Updated At',
            ]);

            // CSV Data
            foreach ($documents as $doc) {
                fputcsv($file, [
                    $doc->id,
                    $doc->title,
                    $doc->category->name ?? 'N/A',
                    substr($doc->description, 0, 100),
                    $doc->file_type ?? 'N/A',
                    $doc->file_size ? number_format($doc->file_size / 1024, 2) . ' KB' : 'N/A',
                    $doc->is_mobile_visible ? 'Yes' : 'No',
                    $doc->created_at->format('Y-m-d H:i'),
                    $doc->updated_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get sync logs
     */
    public function syncLogs(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $logs = DB::table('mobile_legal_sync_logs')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('mobile-legal-library.logs', compact('logs'));
    }

    /**
     * Log sync activity
     */
    private function logSync($action, $details = [])
    {
        try {
            DB::table('mobile_legal_sync_logs')->insert([
                'action' => $action,
                'details' => json_encode($details),
                'user_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log mobile legal library sync: ' . $e->getMessage());
        }
    }

    /**
     * Clear sync logs older than 90 days
     */
    public function clearOldLogs()
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $deleted = DB::table('mobile_legal_sync_logs')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        return redirect()->back()->with('success', __(':count old log entries cleared.', ['count' => $deleted]));
    }
}
