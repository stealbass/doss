<?php

namespace App\Http\Controllers;

use App\Models\LegalAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalAlertController extends Controller
{
    public function index()
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $alerts = LegalAlert::latest()->paginate(20);
        $countries = config('mobile_countries.supported_countries');

        return view('legal-alerts.index', compact('alerts', 'countries'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required',
            'alert_type' => 'required',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        LegalAlert::create([
            'title' => $request->title,
            'content' => $request->content,
            'alert_type' => $request->alert_type,
            'country' => $request->country,
            'priority' => $request->priority,
            'summary' => $request->summary,
            'is_published' => $request->has('is_published'),
            'published_at' => $request->has('is_published') ? now() : null,
            'send_email' => $request->has('send_email'),
            'send_whatsapp' => $request->has('send_whatsapp'),
            'send_push' => $request->has('send_push'),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('legal-alerts.index')->with('success', __('Alert created successfully.'));
    }

    public function destroy($id)
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        LegalAlert::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => __('Alert deleted successfully.')]);
    }

    public function publish($id)
    {
        if (Auth::user()->type !== 'super admin') {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $alert = LegalAlert::findOrFail($id);
        $alert->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => __('Alert published successfully.')]);
    }
}
