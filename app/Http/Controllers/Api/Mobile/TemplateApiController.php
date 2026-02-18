<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TemplateApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('activeMobileSubscription.plan');
            $userCountry = $user->country;
            $userPlan = $user->plan;
            
            $page = max(1, (int) $request->input('page', 1));
            $limit = (int) $request->input('limit', 20);
            $limit = $limit > 0 ? min($limit, 50) : 20;
            $categoryId = $request->input('category_id');
            $search = $request->input('search');

            $query = DocumentTemplate::with('category')
                ->mobileVisible();

            // Filtrage par pays seulement si des templates existent pour ce pays
            if ($userCountry && $userCountry !== 'ALL') {
                $countWithCountry = DocumentTemplate::where('country', $userCountry)->count();
                if ($countWithCountry > 0) {
                    $query->where('country', $userCountry);
                }
            }

            // Filtrage par catégorie
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            // Recherche
            if ($search) {
                $searchTerm = strtolower($search);
                $query->where(function($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                      ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$searchTerm}%"]);
                });
            }

            $paginator = $query->orderByDesc('id')->paginate($limit, ['*'], 'page', $page);

            $templates = $paginator->getCollection()->map(function($template) {
                return [
                    'id' => $template->id,
                    'title' => $template->name,
                    'name' => $template->name,
                    'description' => $template->description,
                    'category_name' => $template->category->name ?? 'Sans catégorie',
                    'category_id' => $template->category_id,
                    'country' => $template->country,
                    'file_type' => $template->file_type,
                    'file_url' => $template->file_path ? $template->file_url : null,
                    'required_plan' => $template->required_plan ?? 'Gratuit',
                    'is_mobile_visible' => $template->is_mobile_visible,
                    'downloads_count' => $template->downloads_count,
                    'views_count' => $template->views_count,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $templates,
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total_pages' => $paginator->lastPage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Template API list failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'page' => $request->input('page'),
                'limit' => $request->input('limit'),
                'category_id' => $request->input('category_id'),
                'search' => $request->input('search'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load templates',
            ], 500);
        }
    }

    public function show($id)
    {
        $template = DocumentTemplate::with('category')->findOrFail($id);
        $template->incrementViews();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'title' => $template->name,
                'name' => $template->name,
                'description' => $template->description,
                'category_name' => $template->category->name ?? 'Sans catégorie',
                'category_id' => $template->category_id,
                'country' => $template->country,
                'file_type' => $template->file_type,
                'file_url' => $template->file_url,
                'required_plan' => $template->required_plan ?? 'Gratuit',
                'is_mobile_visible' => $template->is_mobile_visible,
                'downloads_count' => $template->downloads_count,
                'views_count' => $template->views_count,
            ],
        ]);
    }

    public function download(Request $request, $id)
    {
        $template = DocumentTemplate::findOrFail($id);
        $user = $request->user();
        $user->load('activeMobileSubscription.plan');

        if (!$template->isAccessibleByPlan($user->plan)) {
            return response()->json(['success' => false, 'error' => 'Upgrade your plan to access this template'], 403);
        }

        $template->incrementDownloads();

        // Get file URL from cloud storage (R2/S3/Wasabi) or local
        $downloadUrl = Utility::get_file($template->file_path);

        if (empty($downloadUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'File not accessible',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $downloadUrl,
                'file_name' => $template->file_name,
            ],
        ]);
    }
}
