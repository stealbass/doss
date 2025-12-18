<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\FiscalSocialResource;
use App\Models\SalaryGrid;
use App\Models\TaxParameter;
use Illuminate\Http\Request;

class FiscalResourceApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userCountry = $user->country;
        $currentYear = date('Y');

        $resources = FiscalSocialResource::byCountry($userCountry)
            ->byYear($currentYear)
            ->latestVersion()
            ->mobileVisible()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resources,
        ]);
    }

    public function salaryGrids(Request $request)
    {
        $user = $request->user();
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
