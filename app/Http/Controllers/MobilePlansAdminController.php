<?php

namespace App\Http\Controllers;

use App\Models\MobileSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MobilePlansAdminController extends Controller
{
    public function index()
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $plans = MobileSubscriptionPlan::orderBy('sort_order')->get();
        
        return view('mobile-plans-admin.index', compact('plans'));
    }

    public function create()
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        return view('mobile-plans-admin.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
        ]);

        MobileSubscriptionPlan::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'price_monthly' => $request->price_monthly,
            'price_yearly' => $request->price_yearly,
            'max_searches' => $request->max_searches ?? -1,
            'max_analyses' => $request->max_analyses ?? -1,
            'max_downloads' => $request->max_downloads ?? 0,
            'audio_transcription' => $request->has('audio_transcription'),
            'anonymization' => $request->has('anonymization'),
            'multi_accounts' => $request->has('multi_accounts'),
            'max_sub_accounts' => $request->max_sub_accounts ?? 0,
            'legal_alerts' => $request->has('legal_alerts'),
            'word_export' => $request->has('word_export'),
            'priority_support' => $request->has('priority_support'),
            'access_templates' => $request->has('access_templates'),
            'access_fiscal_resources' => $request->has('access_fiscal_resources'),
            'access_calculators' => $request->has('access_calculators'),
            'access_premium_templates' => $request->has('access_premium_templates'),
            'ai_messages_per_month' => $request->ai_messages_per_month ?? 0,
            'advanced_ai' => $request->has('advanced_ai'),
            'badge_color' => $request->badge_color,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 999,
            'is_popular' => $request->has('is_popular'),
            'is_active' => $request->has('is_active') ? true : false,
            'is_visible' => $request->has('is_visible') ? true : false,
        ]);

        return redirect()->route('mobile-plans-admin.index')
            ->with('success', __('Plan created successfully.'));
    }

    public function edit($id)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $plan = MobileSubscriptionPlan::findOrFail($id);
        return view('mobile-plans-admin.edit', compact('plan'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $plan = MobileSubscriptionPlan::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
        ]);

        $plan->update([
            'name' => $request->name,
            'description' => $request->description,
            'price_monthly' => $request->price_monthly,
            'price_yearly' => $request->price_yearly,
            'max_searches' => $request->max_searches ?? -1,
            'max_analyses' => $request->max_analyses ?? -1,
            'max_downloads' => $request->max_downloads ?? 0,
            'audio_transcription' => $request->has('audio_transcription'),
            'anonymization' => $request->has('anonymization'),
            'multi_accounts' => $request->has('multi_accounts'),
            'max_sub_accounts' => $request->max_sub_accounts ?? 0,
            'legal_alerts' => $request->has('legal_alerts'),
            'word_export' => $request->has('word_export'),
            'priority_support' => $request->has('priority_support'),
            'access_templates' => $request->has('access_templates'),
            'access_fiscal_resources' => $request->has('access_fiscal_resources'),
            'access_calculators' => $request->has('access_calculators'),
            'access_premium_templates' => $request->has('access_premium_templates'),
            'ai_messages_per_month' => $request->ai_messages_per_month ?? 0,
            'advanced_ai' => $request->has('advanced_ai'),
            'badge_color' => $request->badge_color,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 999,
            'is_popular' => $request->has('is_popular'),
            'is_active' => $request->has('is_active') ? true : false,
            'is_visible' => $request->has('is_visible') ? true : false,
        ]);

        return redirect()->route('mobile-plans-admin.index')
            ->with('success', __('Plan updated successfully.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $plan = MobileSubscriptionPlan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => __('Plan deleted successfully.')
        ]);
    }

    public function toggleActive($id)
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $plan = MobileSubscriptionPlan::findOrFail($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();

        return response()->json([
            'success' => true,
            'is_active' => $plan->is_active,
            'message' => __('Plan status updated successfully.')
        ]);
    }
}
