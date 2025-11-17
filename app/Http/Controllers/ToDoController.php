<?php

namespace App\Http\Controllers;

use App\Models\Cases;
use App\Models\ToDo;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskAssignedNotification;

class ToDoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if (Auth::user()->can('manage todo')) {
            if ($request->filter == 'All' || empty($request->filter)) {
                $todo_tmp = ToDo::where('created_by', Auth::user()->creatorId())->get();
            } else {
                $todo_tmp = ToDo::where('created_by', Auth::user()->creatorId())->where('priority', $request->filter)->get();
            }

            $user = Auth::user()->id;
            if (Auth::user()->type == 'client') {
                $todo = [];
                foreach ($todo_tmp as $value) {
                    $case = Cases::find($value->relate_to);
                    if ($case != null) {
                        $data = json_decode($case->your_party_name);
                        foreach ($data as $key => $val) {
                            if (isset($val->clients) && $val->clients == $user) {
                                $todo[$value->id] = $value;
                            }
                        }
                    }
                }
            } elseif (Auth::user()->type == 'advocate') {
                $todo = [];
                foreach ($todo_tmp as $value) {
                    $data = explode(',', $value->assign_to);
                    if (isset($data) && in_array($user, $data)) {
                        $todo[$value->id] = $value;
                    }
                }
                $todo_tmp = ToDo::where('assign_by', Auth::user()->id)->get();
                foreach ($todo_tmp as $value) {
                    $todo[$value->id] = $value;
                }
            } else {
                $todo = $todo_tmp;
            }

            $priorities = [
                'Tout',
                'Préparation',
                'Terminé',
                'Annulé',
                'Traitement',
            ];

            $todos = [];
            foreach ($priorities as $value) {
                foreach ($todo as $ke => $val) {
                    if ($value == $val->priority) {
                        if ($value == 'Préparation') {
                            $p_id = 1;
                        } elseif ($value == 'Terminé') {
                            $p_id = 2;
                        } elseif ($value == 'Annulé') {
                            $p_id = 3;
                        } elseif ($value == 'Traitement') {
                            $p_id = 4;
                        } else {
                            $p_id = 0;
                        }

                        $val->p_id = $p_id;
                        $todos[] = $val;
                    }
                }
            }

            $curr_time = strtotime(date("Y-m-d h:i:s"));

            // UPCOMING
            $upcoming_todo = [];

            foreach ($todos as $key => $utd) {
                $start_date = strtotime($utd->start_date);
                if ($start_date > $curr_time && $utd->status == 1) {
                    if ($utd->priority == 'Préparation') {
                        $p_id = 1;
                    } elseif ($utd->priority == 'Terminé') {
                        $p_id = 2;
                    } elseif ($utd->priority == 'Annulé') {
                        $p_id = 3;
                    } elseif ($utd->priority == 'Traitement') {
                        $p_id = 4;
                    } else {
                        $p_id = 0;
                    }
                    $upcoming_todo[$key]['id'] = $utd->id;
                    $upcoming_todo[$key]['title'] = $utd->title;
                    $upcoming_todo[$key]['description'] = $utd->description;
                    $upcoming_todo[$key]['due_date'] = $utd->due_date;
                    $upcoming_todo[$key]['relate_to'] = $utd->relate_to;
                    $upcoming_todo[$key]['assign_to'] = $utd->assign_to;
                    $upcoming_todo[$key]['assign_by'] = $utd->assign_by;
                    $upcoming_todo[$key]['status'] = $utd->status;
                    $upcoming_todo[$key]['priority'] = $utd->priority;
                    $upcoming_todo[$key]['p_id'] = $p_id;
                }
            }

            // PENDING
            $pending_todo = [];

            foreach ($todos as $key => $ptd) {
                $start_date = strtotime($ptd->start_date);
                if ($start_date < $curr_time && $ptd->status == 1) {
                    if ($ptd->priority == 'Préparation') {
                        $p_id = 1;
                    } elseif ($ptd->priority == 'Terminé') {
                        $p_id = 2;
                    } elseif ($ptd->priority == 'Annulé') {
                        $p_id = 3;
                    } elseif ($ptd->priority == 'Traitement') {
                        $p_id = 4;
                    } else {
                        $p_id = 0;
                    }
                    $pending_todo[$key]['id'] = $ptd->id;
                    $pending_todo[$key]['title'] = $ptd->title;
                    $pending_todo[$key]['description'] = $ptd->description;
                    $pending_todo[$key]['due_date'] = $ptd->due_date;
                    $pending_todo[$key]['relate_to'] = $ptd->relate_to;
                    $pending_todo[$key]['assign_to'] = $ptd->assign_to;
                    $pending_todo[$key]['assign_by'] = $ptd->assign_by;
                    $pending_todo[$key]['status'] = $ptd->status;
                    $pending_todo[$key]['priority'] = $ptd->priority;
                    $pending_todo[$key]['p_id'] = $p_id;
                }
            }

            // complted
            $complted = [];

            foreach ($todos as $key => $ctd) {
                if ($ctd->status == 0) {
                    if ($ctd->priority == 'Préparation') {
                        $p_id = 1;
                    } elseif ($ctd->priority == 'Terminé') {
                        $p_id = 2;
                    } elseif ($ctd->priority == 'Annulé') {
                        $p_id = 3;
                    } elseif ($ctd->priority == 'Traitement') {
                        $p_id = 4;
                    } else {
                        $p_id = 0;
                    }
                    $complted[$key]['id'] = $ctd->id;
                    $complted[$key]['title'] = $ctd->title;
                    $complted[$key]['description'] = $ctd->description;
                    $complted[$key]['due_date'] = $ctd->due_date;
                    $complted[$key]['relate_to'] = $ctd->relate_to;
                    $complted[$key]['assign_to'] = $ctd->assign_to;
                    $complted[$key]['assign_by'] = $ctd->assign_by;
                    $complted[$key]['completed_by'] = $ctd->completed_by;
                    $complted[$key]['completed_at'] = $ctd->completed_at;
                    $complted[$key]['status'] = $ctd->status;
                    $complted[$key]['priority'] = $ctd->priority;
                    $complted[$key]['p_id'] = $p_id;
                }
            }

            return view('todo.index', compact('todos', 'upcoming_todo', 'pending_todo', 'complted', 'priorities'));
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
        if (Auth::user()->can('create todo')) {

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

            $priorities = ToDo::priorities();

            return view('todo.create', compact('cases', 'priorities'));
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
        if (Auth::user()->can('create todo')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'due_date' => 'required',
                    'relate_to' => 'required',
                    'assigned_date' => 'required',
                    'priority' => 'required',
                    'title' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $todo = new ToDo();
            $todo['title'] = $request->title;
            $todo['description'] = $request->description;
            $todo['due_date'] = $request->due_date;
            $todo['start_date'] = $request->assigned_date;
            $todo['end_date'] = $request->due_date;
            $todo['relate_to'] = $request->relate_to;
            $todo['assign_to'] = !empty($request->assign_to) ? implode(',', $request->assign_to) : '';
            $todo['assign_by'] = Auth::user()->id;
            $todo['priority'] = $request->priority;
            $todo['created_by'] = Auth::user()->creatorId();
            $todo->save();

            if ($request->get('is_check') == '1') {
                $type = 'task';
                $request1 = new ToDo();
                $request1->title = $request->description;
                $request1->start_date = $request->assigned_date;
                $request1->end_date = $request->due_date;
                Utility::addCalendarData($request1, $type);
            }

            // Send email notification to assigned users
            try {
                Utility::getSMTPDetails(Auth::user()->creatorId());
                
                // Get assigned users
                $assignedUserIds = !empty($request->assign_to) ? $request->assign_to : [];
                
                foreach ($assignedUserIds as $userId) {
                    $assignedUser = User::find($userId);
                    if ($assignedUser && $assignedUser->email) {
                        // Get case name if related
                        $caseName = null;
                        if ($request->relate_to) {
                            $case = Cases::find($request->relate_to);
                            if ($case) {
                                $caseName = $case->title;
                            }
                        }
                        
                        // Prepare email data
                        $emailData = [
                            'task' => $todo,
                            'assignedToName' => $assignedUser->name,
                            'assignedByName' => Auth::user()->name,
                            'caseName' => $caseName,
                            'taskUrl' => route('to-do.show', $todo->id),
                        ];
                        
                        // Send email
                        Mail::to($assignedUser->email)->send(
                            new TaskAssignedNotification($todo, $emailData)
                        );
                        
                        \Log::info('Task assignment email sent', [
                            'task_id' => $todo->id,
                            'to' => $assignedUser->email
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error sending task assignment email', [
                    'task_id' => $todo->id,
                    'error' => $e->getMessage()
                ]);
                // Don't block task creation if email fails
            }

            return redirect()->route('to-do.index')->with('success', __('To-Do successfully created.'));
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
        if (Auth::user()->can('view todo')) {
            $todo = ToDo::find($id);
            return view('todo.view', compact('todo'));
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
        if (Auth::user()->can('edit todo')) {

            $todo = ToDo::find($id);

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

            $members = [];
            $case_id = $todo->relate_to;
            $case = Cases::find($case_id);
            $data = explode(',', $case->advocates);
            $team = User::whereIn('id', $data)->get();
            if ($team) {
                foreach ($team as $value) {
                    $members[$value->id] = $value->name;
                }
            }

            $team = User::where('created_by', Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'advocate')->get();
            if ($team) {
                foreach ($team as $value) {
                    $members[$value->id] = $value->name;
                }
            }

            $assign_to = User::whereIn('id', explode(',', $todo->assign_to))->get();
            $priorities = ToDo::priorities();

            return view('todo.edit', compact('todo', 'cases', 'members', 'assign_to', 'priorities'));
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
        if (Auth::user()->can('edit todo')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'due_date' => 'required',
                    'relate_to' => 'required',
                    'assigned_date' => 'required',
                    'priority' => 'required',
                    'title' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }


            $todo = ToDo::find($id);
            $todo['title'] = $request->title;
            $todo['description'] = $request->description;
            $todo['due_date'] = $request->due_date;
            $todo['start_date'] = $request->assigned_date;
            $todo['end_date'] = $request->due_date;
            $todo['relate_to'] = $request->relate_to;
            $todo['assign_to'] = !empty($request->assign_to) ? implode(',', $request->assign_to) : '';
            $todo['assign_by'] = Auth::user()->id;
            $todo['priority'] = $request->priority;
            $todo->save();

            return redirect()->route('to-do.index')->with('success', __('To-Do successfully updated.'));
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
        if (Auth::user()->can('delete todo')) {
            $todo = ToDo::find($id);
            $todo->delete();
            return redirect()->route('to-do.index')->with('success', __('To-Do successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function status($id)
    {
        if (Auth::user()->can('edit todo')) {
            $todo = ToDo::find($id);
            return view('todo.status', compact('todo'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function statusUpdate($id)
    {
        if (Auth::user()->can('edit todo')) {

            $todo = ToDo::find($id);
            if ($todo->status == 0) {
                return redirect()->route('to-do.index')->with('error', __('This to-do already marked as completed.'));
            }

            if ($todo->status == 1) {
                $todo->status = 0;
                $todo->completed_at = date("d-m-y h:i");
                $todo->completed_by = Auth::user()->id;
                $todo->save();
            }
            return redirect()->route('to-do.index')->with('success', __('You have successfully completed the to-dos.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function assgineadvocate(Request $request)
    {
        $teams = [];
        $case_id = $request->case_id;
        $case = Cases::find($case_id);
        $data = explode(',', $case->advocates);
        $team = User::whereIn('id', $data)->get();
        foreach ($team as $value) {
            $teams[$value->id] = $value->name;
        }

        $team = User::where('created_by', Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'advocate')->get();
        foreach ($team as $value) {
            $teams[$value->id] = $value->name;
        }
        if (isset($team) && !empty($team)) {
            return response()->json(['status' => true, 'message' => "team get success", 'data' => $teams])->setStatusCode(200);
        }
    }

    public function assgineclient(Request $request)
    {
        $teams = [];
        $case_id = $request->case_id;
        $case = Cases::find($case_id);
        $data = json_decode($case->your_party_name);
        foreach ($data as $key => $val) {
            if (isset($val->clients)) {
                $team = User::where('id', $val->clients)->get();
                foreach ($team as $v) {
                    $teams[$v->id] = $v->name;
                }
            }
        }

        if (isset($team) && !empty($team)) {
            return response()->json(['status' => true, 'message' => "team get success", 'data' => $teams])->setStatusCode(200);
        }
    }
}
