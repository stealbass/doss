<?php

namespace App\Http\Controllers;

use App\Models\CaseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaseTypeController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage casetype')) {
            $casetype = CaseType::where('created_by', \Auth::user()->creatorId())->get();
            return view('casetypes.index', compact('casetype'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create casetype')) {
            return view('casetypes.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create casetype')) {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $created_by = \Auth::user()->creatorId();
            $casetype = new CaseType();
            $casetype->name = $request->name;
            $casetype->created_by = $created_by;
            $casetype->save();

            return redirect()->route('casetype.index')->with('success', __('Case Type created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(CaseType $caseType)
    {
        //
    }

    public function edit(CaseType $caseType, $id)
    {
        if (\Auth::user()->can('edit casetype')) {
            $casetype = CaseType::find($id);
            return view('casetypes.edit', compact('casetype'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, CaseType $caseType, $id)
    {
        if (\Auth::user()->can('edit casetype')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $casetype = CaseType::find($id);
            $casetype->name = $request->name;
            $casetype->created_by = \Auth::user()->creatorId();
            $casetype->save();

            return redirect()->route('casetype.index')->with('success', __('Case Type updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(CaseType $caseType, $id)
    {
        if (\Auth::user()->can('delete casetype')) {
            $casetype = CaseType::find($id);
            $casetype->delete();

            return redirect()->route('casetype.index')->with('success', __('Case Type deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
