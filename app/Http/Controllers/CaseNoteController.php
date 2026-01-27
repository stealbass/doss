<?php

namespace App\Http\Controllers;

use App\Models\CaseNote;
use App\Models\Cases;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CaseNoteController extends Controller
{
    /**
     * Store a new note for a case
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'case_id' => 'required|exists:cases,id',
                'note' => 'required|string',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $note = new CaseNote();
        $note->case_id = $request->case_id;
        $note->user_id = Auth::user()->id;
        $note->note = $request->note;
        $note->created_by = Auth::user()->creatorId();
        $note->save();

        return redirect()->back()->with('success', __('Note ajoutée avec succès.'));
    }

    /**
     * Store a reply to an existing note
     */
    public function reply(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'case_id' => 'required|exists:cases,id',
                'parent_id' => 'required|exists:case_notes,id',
                'note' => 'required|string',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $note = new CaseNote();
        $note->case_id = $request->case_id;
        $note->user_id = Auth::user()->id;
        $note->parent_id = $request->parent_id;
        $note->note = $request->note;
        $note->created_by = Auth::user()->creatorId();
        $note->save();

        return redirect()->back()->with('success', __('Réponse ajoutée avec succès.'));
    }

    /**
     * Delete a note
     */
    public function destroy($id)
    {
        $note = CaseNote::find($id);

        if (!$note) {
            return redirect()->back()->with('error', __('Note introuvable.'));
        }

        // Check permission - only creator or admin can delete
        if ($note->user_id != Auth::user()->id && Auth::user()->type != 'company') {
            return redirect()->back()->with('error', __('Permission refusée.'));
        }

        $note->delete();

        return redirect()->back()->with('success', __('Note supprimée avec succès.'));
    }

    /**
     * Get create note form
     */
    public function create($case_id)
    {
        $case = Cases::find($case_id);
        
        if (!$case) {
            return redirect()->back()->with('error', __('Affaire introuvable.'));
        }

        return view('case_notes.create', compact('case'));
    }

    /**
     * Get reply form
     */
    public function replyForm($note_id)
    {
        $note = CaseNote::find($note_id);
        
        if (!$note) {
            return redirect()->back()->with('error', __('Note introuvable.'));
        }

        return view('case_notes.reply', compact('note'));
    }
}
