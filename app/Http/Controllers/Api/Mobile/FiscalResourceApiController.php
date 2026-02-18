<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\FiscalSocialResource;
use App\Models\SalaryGrid;
use App\Models\TaxParameter;
use App\Models\Utility;
use Illuminate\Http\Request;

class FiscalResourceApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->load('activeMobileSubscription.plan');
        $userCountry = $user->country;
        $currentYear = date('Y');
        
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $year = $request->input('year', $currentYear);
        $search = $request->input('search');

        $query = FiscalSocialResource::with('category')
            ->where('country', $userCountry)
            ->where('is_mobile_visible', true);

        // Filtre par année - si l'année demandée a des données, les utiliser
        // Sinon, récupérer l'année la plus récente disponible
        $yearExists = $query->clone()->where('year', $year)->exists();
        if ($yearExists) {
            $query->where('year', $year);
        } else {
            // Si pas de données pour l'année demandée, prendre la plus récente
            $latestYear = $query->clone()->max('year');
            if ($latestYear) {
                $query->where('year', $latestYear);
            }
        }

        // Recherche
        if ($search) {
            $searchTerm = strtolower($search);
            $query->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(resource_type) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        $total = $query->count();
        $totalPages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        $resources = $query
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function($resource) {
                return [
                    'id' => $resource->id,
                    'title' => $resource->title ?? 'Ressource sans titre',
                    'description' => $resource->description,
                    'category' => [
                        'id' => $resource->category->id ?? null,
                        'name' => $resource->category->name ?? null,
                    ],
                    'resource_type' => $resource->resource_type,
                    'country' => $resource->country,
                    'year' => $resource->year,
                    'version' => $resource->version,
                    'file_path' => $resource->file_url,
                    'file_name' => $resource->file_name,
                    'file_type' => $resource->file_type,
                    'file_size' => $resource->file_size,
                    'views_count' => $resource->views_count ?? 0,
                    'downloads_count' => $resource->downloads_count ?? 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $resources,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'total_pages' => $totalPages,
        ]);
    }

    public function download(Request $request, $id)
    {
        $resource = FiscalSocialResource::findOrFail($id);
        $user = $request->user();

        // Increment download count
        $resource->incrementDownloads();

        // Get file URL from cloud storage (R2/S3/Wasabi) or local
        $downloadUrl = Utility::get_file($resource->file_path);

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
                'file_name' => $resource->file_name,
            ],
        ]);
    }

    public function view(Request $request, $id)
    {
        $resource = FiscalSocialResource::findOrFail($id);
        
        // Increment view count
        $resource->incrementViews();

        return response()->json([
            'success' => true,
            'message' => 'Vue enregistrée',
            'data' => [
                'views_count' => $resource->views_count,
                'downloads_count' => $resource->downloads_count,
            ],
        ]);
    }

    public function salaryGrids(Request $request)
    {
        $user = $request->user();
        $user->load('activeMobileSubscription.plan');
        $userCountry = $user->country;

        $grids = SalaryGrid::byCountry($userCountry)
            ->byYear(date('Y'))
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $grids,
        ]);
    }

    public function taxParameters(Request $request)
    {
        $user = $request->user();
        $user->load('activeMobileSubscription.plan');
        $userCountry = $user->country;

        $parameters = TaxParameter::byCountry($userCountry)
            ->byYear(date('Y'))
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parameters,
        ]);
    }
}
