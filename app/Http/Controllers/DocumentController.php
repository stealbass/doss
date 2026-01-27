<?php

namespace App\Http\Controllers;

use App\Models\Cases;
use App\Models\DocType;
use App\Models\Document;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->can('manage document')) {
            $docs_tmp = Document::where('created_by', Auth::user()->creatorId())->with('user', 'getDocType')->get();
            $user = Auth::user()->id;

            if (Auth::user()->type == 'client') {
                $docs = [];
                if (isset($docs_tmp)) {
                    foreach ($docs_tmp as $value) {
                        $case = Cases::find($value->cases);
                        $data = json_decode($case->your_party_name);
                        if (isset($data)) {
                            foreach ($data as $key => $val) {
                                if (isset($val->clients) && $val->clients == $user) {
                                    $docs[$value->id] = $value;
                                }
                            }
                        }
                    }
                }
            } elseif (Auth::user()->type == 'advocate') {
                $docs = [];
                foreach ($docs_tmp as $value) {
                    $case = Cases::find($value->cases);
                    if ($case) {
                        $data = explode(',', $case->advocates);
                        if (isset($data) && in_array($user, $data)) {
                            $docs[$value->id] = $value;
                        }
                    }
                }
            } else {
                $docs = $docs_tmp;
            }

            return view('documents.index', compact('docs'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Auth::user()->can('create document')) {
            $types = DocType::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');

            if (Auth::user()->type == 'client') {
                $user = Auth::user()->id;
                $case = DB::table("cases")
                    ->select("cases.*")
                    ->get();
                $cases = [];
                foreach ($case as $value) {
                    $data = json_decode($value->your_party_name);
                    foreach ($data as $key => $val) {
                        if (isset($val->clients) && $val->clients == $user) {
                            $cases[$value->id] = $value;
                        }
                    }
                }
            } elseif (Auth::user()->type == 'advocate') {
                $user = Auth::user()->id;
                $case = DB::table("cases")
                    ->select("cases.*")
                    ->get();
                $cases = [];
                foreach ($case as $value) {
                    $data = explode(',', $value->advocates);
                    if (isset($data) && in_array($user, $data)) {
                        $cases[$value->id] = $value;
                    }
                }
            } else {
                $cases = Cases::where('created_by', Auth::user()->creatorId())->get();
            }

            return view('documents.create', compact('types', 'cases'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create document')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'type' => 'required',
                    'file' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $uploadedFile) {
                    $image_size = $uploadedFile->getSize();
                    $result = \App\Models\Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);

                    if ($result == 1) {
                        $filenameWithExt = $uploadedFile->getClientOriginalName();
                        $extension = $uploadedFile->getClientOriginalExtension();
                        $fileNameToStores = 'document_' . time() . '_' . uniqid() . '.' . $extension;

                        $settings = Utility::getStorageSetting();
                        if ($settings['storage_setting'] == 'local') {
                            $dir = 'uploads/documents/';
                        } else {
                            $dir = 'uploads/documents';
                        }

                        // On crée une Request temporaire pour utiliser Utility::upload_file
                        $tempRequest = new Request();
                        $tempRequest->files->set('file', $uploadedFile);

                        $path = Utility::upload_file($tempRequest, 'file', $fileNameToStores, $dir, []);

                        if ($path['flag'] == 1) {
                            $filesize = number_format($image_size / 1000000, 4);

                            $doc = new Document();
                            $doc['name'] = $request->name;
                            $doc['type'] = $request->type;
                            $doc['purpose'] = $request->purpose;
                            $doc['description'] = $request->description;
                            $doc['document_subtype'] = $request->document_subtype;
                            $doc['created_by'] = Auth::user()->creatorId();
                            $doc['cases'] = $request->cases;
                            $doc['doc_link'] = $request->doc_link;
                            $doc['file'] = $fileNameToStores;
                            $doc['doc_size'] = $filesize;
                            $doc->save();
                        } else {
                            return redirect()->back()->with('error', __($path['msg']));
                        }
                    } else {
                        return redirect()->back()->with('error', __('Storage limit exceeded.'));
                    }
                }
            }

            return redirect()->route('documents.index')->with('success', __('Document successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (Auth::user()->can('view document')) {
            $doc = Document::find($id);
            $cases = '-';
            if (!empty($doc->cases)) {
                $cases = Cases::whereIn('id', explode(',', $doc->cases))->get()->pluck('title')->toArray();
                $cases = implode(',', $cases);

            }
            return view('documents.view', compact('doc', 'cases'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (Auth::user()->can('edit document')) {
            $doc = Document::find($id);
            $types = DocType::where('created_by', Auth::user()->creatorId())->orWhere('created_by', 0)->get()->pluck('name', 'id');

            if (Auth::user()->type == 'client') {
                $user = Auth::user()->id;
                $case = DB::table("cases")
                    ->select("cases.*")
                    ->get();
                $cases = [];
                foreach ($case as $value) {
                    $data = json_decode($value->your_party_name);
                    foreach ($data as $key => $val) {
                        if (isset($val->clients) && $val->clients == $user) {
                            $cases[$value->id] = $value;
                        }
                    }
                }
            } elseif (Auth::user()->type == 'advocate') {
                $user = Auth::user()->id;
                $case = DB::table("cases")
                    ->select("cases.*")
                    ->get();
                $cases = [];
                foreach ($case as $value) {
                    $data = explode(',', $value->advocates);

                    if (isset($data) && in_array($user, $data)) {
                        $cases[$value->id] = $value;
                    }
                }
            } else {
                $cases = Cases::where('created_by', Auth::user()->creatorId())->get();
            }

            $doc_typ = $doc->type;

            return view('documents.edit', compact('doc', 'types', 'cases', 'doc_typ'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    if (Auth::user()->can('edit document')) {

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'type' => 'required',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $doc = Document::find($id);

        // Si des fichiers sont uploadés, on crée un document pour chaque fichier (comme dans store)
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $uploadedFile) {
                $image_size = $uploadedFile->getSize();
                $result = Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);

                if ($result == 1) {
                    $filenameWithExt = $uploadedFile->getClientOriginalName();
                    $extension = $uploadedFile->getClientOriginalExtension();
                    $fileNameToStores = 'document_' . time() . '_' . uniqid() . '.' . $extension;

                    $settings = Utility::getStorageSetting();
                    if ($settings['storage_setting'] == 'local') {
                        $dir = 'uploads/documents/';
                    } else {
                        $dir = 'uploads/documents';
                    }

                    $tempRequest = new Request();
                    $tempRequest->files->set('file', $uploadedFile);

                    $path = Utility::upload_file($tempRequest, 'file', $fileNameToStores, $dir, []);

                    if ($path['flag'] == 1) {
                        $filesize = number_format($image_size / 1000000, 4);

                        // On crée un nouveau document pour chaque fichier supplémentaire
                        $newDoc = new Document();
                        $newDoc['name'] = $request->name;
                        $newDoc['type'] = $request->type;
                        $newDoc['purpose'] = $request->purpose;
                        $newDoc['description'] = $request->description;
                        $newDoc['document_subtype'] = $request->document_subtype;
                        $newDoc['created_by'] = Auth::user()->creatorId();
                        $newDoc['cases'] = $request->cases;
                        $newDoc['doc_link'] = $request->doc_link;
                        $newDoc['file'] = $fileNameToStores;
                        $newDoc['doc_size'] = $filesize;
                        $newDoc->save();
                    } else {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                } else {
                    return redirect()->back()->with('error', __('Storage limit exceeded.'));
                }
            }
            // On ne modifie pas l'ancien document si de nouveaux fichiers sont uploadés
        } else {
            // Si pas de nouveaux fichiers, on met à jour les infos du document existant
            $doc['name'] = $request->name;
            $doc['type'] = $request->type;
            $doc['purpose'] = $request->purpose;
            $doc['description'] = $request->description;
            $doc['created_by'] = Auth::user()->creatorId();
            $doc['document_subtype'] = $request->document_subtype;
            $doc['cases'] = $request->cases;
            $doc['doc_link'] = $request->doc_link;
            $doc->save();
        }

        return redirect()->route('documents.index')->with('success', __('Document successfully updated.'));
    } else {
        return redirect()->back()->with('error', __('Permission Denied.'));
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::user()->can('delete document')) {
            $doc = Document::find($id);
            if ($doc) {
                $filepath = storage_path('uploads/documents/' . $doc->file);
                Utility::changeStorageLimit(Auth::user()->creatorId(), 'uploads/documents/' . $doc->file);

                if (File::exists($filepath)) {
                    File::delete($filepath);
                }

                $doc->delete();
            }
            return redirect()->route('documents.index')->with('success', __('Document successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
