<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userCountry = $user->country;
        $userPlan = $user->plan ?? 'Gratuit';

        $templates = DocumentTemplate::with('category')
            ->byCountry($userCountry)
            ->mobileVisible()
            ->accessibleByPlan($userPlan)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function show($id)
    {
        $template = DocumentTemplate::findOrFail($id);
        $template->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    public function download(Request $request, $id)
    {
        $template = DocumentTemplate::findOrFail($id);
        $user = $request->user();

        if (!$template->isAccessibleByPlan($user->plan ?? 'Gratuit')) {
            return response()->json(['error' => 'Upgrade your plan to access this template'], 403);
        }

        $template->incrementDownloads();

        if (!Storage::disk('public')->exists($template->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->json([
            'success' => true,
            'download_url' => url('storage/' . $template->file_path),
        ]);
    }
}
