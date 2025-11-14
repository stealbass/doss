<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomField;
use App\Mail\SendCloseTicket;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Utility;
use App\Models\UserCatgory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Priority;
use Illuminate\Support\Facades\Auth;
class TicketController extends Controller
{
   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if ($user->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {

            if (\Auth::user()->created_by == 1) {
            $categories = Category::where('created_by', \Auth::user()->supportTicketCreatorId())->get()->pluck('name', 'id');
            $categories->prepend('Select Category', 'All');

            $priorities = Priority::where('created_by', \Auth::user()->supportTicketCreatorId())->get()->pluck('name', 'id');
            $priorities->prepend('Select Priority', 'All');

            $statues = Ticket::$statues;

            $tickets = Ticket::select(
                [
                    'tickets.*',
                    'categories.name as category_name',
                    'categories.color',
                    'priorities.color as priorities_color',
                    'priorities.name as priorities_name',
                ]
            )->join('categories', 'categories.id', '=', 'tickets.category')->join('priorities', 'priorities.id', '=', 'tickets.priority');


            if ($request->category != 'All' && $request->all() != null) {
                $tickets->where('category', $request->category);
            }

            if ($request->priority != 'All' && $request->all() != null) {
                $tickets->where('priority', $request->priority);
            }

            if ($request->status != 'All' && $request->all() != null) {
                $tickets->where('status', $request->status);
            }

            if (Auth::user()->type == 'company') {
                $tickets->where('company', Auth::user()->id);
            }

            $tickets = $tickets->orderBy('id', 'desc')->get();

                return view('tickets.index', compact('tickets', 'categories', 'priorities', 'statues'));
            } else {
                $categories1 = UserCatgory::where('user_id', auth()->user()->id)->pluck('category_id');

                $categories = \DB::table('categories')
                    ->join('user_categories', 'user_categories.category_id', '=', 'categories.id')
                    ->select(['user_categories.category_id', 'categories.name', 'categories.id'])
                    ->where('user_id', auth()->user()->id)
                    ->pluck('categories.name', 'categories.id');
                $categories->prepend('Select Category', 'All');

                $priorities = Priority::where('created_by', \Auth::user()->supportTicketCreatorId())->get()->pluck('name', 'id');
                $priorities->prepend('Select Priority', 'All');
                $statues = Ticket::$statues;

                $tickets = Ticket::select(
                    [
                        'tickets.*',
                        'categories.name as category_name',
                        'categories.color',
                        'priorities.color as priorities_color',
                        'priorities.name as priorities_name',
                    ]
                )->join('categories', 'categories.id', '=', 'tickets.category')->join('priorities', 'priorities.id', '=', 'tickets.priority')->whereIn('category', $categories1)->with('priorities');

                if ($request->category != 'All' && $request->all() != null) {
                    $tickets->where('category', $request->category);
                }

                if ($request->priority != 'All' && $request->all() != null) {
                    $tickets->where('priority', $request->priority);
                }

                if ($request->status != 'All' && $request->all() != null) {
                    $tickets->where('status', $request->status);
                }

                $tickets = $tickets->orderBy('id', 'desc')->get();

                return view('tickets.index', compact('tickets', 'categories', 'priorities', 'statues'));
            }

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $customFields = CustomField::where('id', '>', '8')->get();
            $categories = Category::where('created_by', \Auth::user()->supportTicketCreatorId())->get();
            $priorities = Priority::where('created_by', \Auth::user()->supportTicketCreatorId())->get();
            $companies = User::where('type', 'company')->get();

            return view('tickets.create', compact('categories', 'customFields', 'priorities', 'companies'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }

        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $validation = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'category' => 'required|string|max:255',
                'priority' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'status' => 'required|string|max:100',
                'description' => 'required',
            ];

            if ($user->type != 'company') {
                $validation = [
                    'company' => 'required|string|max:255',
                ];
            }

            $this->validate($request, $validation);

            if ($user->type != 'company') {
                $company_id = $request->company;
            } else {
                $company_id = \Auth::user()->id;
            }

            $post = $request->all();
            $post['ticket_id'] = time();
            $post['created_by'] = \Auth::user()->supportTicketCreatorId();
            $post['company'] = $company_id;
            $data = [];

            if ($request->hasfile('attachments')) {
                $errors = [];
                foreach ($request->file('attachments') as $filekey => $file) {
                    $name = $file->getClientOriginalName();
                    $dir = ('tickets/' . $post['ticket_id']);
                    $path = Utility::keyWiseUpload_file($request, 'attachments', $name, $dir, $filekey, []);

                    if ($path['flag'] == 1) {
                        $data[] = $path['url'];
                    } elseif ($path['flag'] == 0) {
                        $errors = __($path['msg']);
                    }

                }
            }
            $post['attachments'] = json_encode($data);

            $ticket = Ticket::create($post);
            CustomField::saveData($ticket, $request->customField);

            if (isset($error_msg)) {
                Session::put('smtp_error', '<span class="text-danger ml-2">' . $error_msg . '</span>');
            }

            return redirect()->route('tickets.index')->with('success', __('Ticket created successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function storeNote($ticketID, Request $request)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $validation = [
                'note' => ['required'],
            ];
            $this->validate($request, $validation);

            $ticket = Ticket::find($ticketID);
            if ($ticket) {
                $ticket->note = $request->note;
                $ticket->save();

                return redirect()->back()->with('success', __('Ticket note saved successfully'));
            } else {
                return redirect()->back()->with('error', __('Ticket not found'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Ticket $ticket
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Ticket $ticket)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Ticket $ticket
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $ticket = Ticket::find($id);
            if ($ticket) {
                $customFields = CustomField::where('id', '>', '8')->get();
                $ticket->customField = CustomField::getData($ticket);
                $categories = Category::where('created_by', \Auth::user()->supportTicketCreatorId())->get();
                $priorities = Priority::where('created_by', \Auth::user()->supportTicketCreatorId())->get();
                $companies = User::where('type', 'company')->get();

                return view('tickets.edit', compact('ticket', 'categories', 'customFields', 'priorities', 'companies'));
            } else {
                return redirect()->back()->with('error', __('Ticket not found'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Ticket $ticket
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $ticket = Ticket::find($id);
            if ($ticket) {
                $validation = [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255',
                    'category' => 'required|string|max:255',
                    'priority' => 'required|string|max:255',
                    'subject' => 'required|string|max:255',
                    'status' => 'required|string|max:100',
                    'description' => 'required',
                ];

                if ($user->type != 'company') {
                    $validation = [
                        'company' => 'required|string|max:255',
                    ];
                }
                $this->validate($request, $validation);

                if ($user->type != 'company') {
                    $company_id = $request->company;
                } else {
                    $company_id = \Auth::user()->id;
                }

                $post = $request->all();
                $post['created_by'] = \Auth::user()->supportTicketCreatorId();
                $post['company'] = $company_id;

                if ($request->hasfile('attachments')) {
                    $data = json_decode($ticket->attachments, true);
                    foreach ($request->file('attachments') as $filekey => $file) {
                        $name = $file->getClientOriginalName();
                        $file->storeAs('tickets/' . $ticket->ticket_id, $name);
                        $data[] = $name;
                        $url = '';
                        $dir = ('tickets/' . $ticket->ticket_id);
                        $path = Utility::keyWiseUpload_file($request, 'attachments', $name, $dir, $filekey, []);
                        if ($path['flag'] == 1) {
                            $url = $path['url'];
                        } else {
                            return redirect()->route('tickets.store', \Auth::user()->id)->with('error', __($path['msg']));
                        }
                    }
                    $post['attachments'] = json_encode($data);
                }

                if ($request->status == 'Resolved') {
                    $ticket->reslove_at = now();
                }
                $ticket->update($post);
                CustomField::saveData($ticket, $request->customField);

                $error_msg = '';
                if ($ticket->status == 'Closed') {
                    // Send Email to User
                    try {
                        Utility::getSMTPDetails();
                        Mail::to($ticket->email)->send(new SendCloseTicket($ticket));
                    } catch (\Exception $e) {
                        $error_msg = "E-Mail has been not sent due to SMTP configuration ";
                    }
                }

                return redirect()->back()->with('success', __('Ticket updated successfully.') . ((isset($error_msg) && !empty($error_msg)) ? '<span class="text-danger">' . $error_msg . '</span>' : ''));
            } else {
                return redirect()->back()->with('error', __('Ticket not found'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Ticket $ticket
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $ticket = Ticket::find($id);
            $ticket->delete();

            return redirect()->back()->with('success', __('Ticket deleted successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function attachmentDestroy($ticket_id, $id)
    {
        $user = \Auth::user();
        $premission_arr = [];
        if (\Auth::user()->super_admin_employee == 1) {
            $premission = json_decode(\Auth::user()->permission_json);
            $premission_arr = get_object_vars($premission);
        }
        if (Auth::user()->super_admin_employee == 1 && array_search('manage support ticket', $premission_arr) || $user->type == 'company') {
            $ticket = Ticket::find($ticket_id);
            $attachments = json_decode($ticket->attachments);
            if (isset($attachments[$id])) {
                if (asset(Storage::exists('tickets/' . $ticket->ticket_id . "/" . $attachments[$id]))) {
                    asset(Storage::delete('tickets/' . $ticket->ticket_id . "/" . $attachments[$id]));
                }
                unset($attachments[$id]);
                $ticket->attachments = json_encode(array_values($attachments));
                $ticket->save();

                return redirect()->back()->with('success', __('Attachment deleted successfully'));
            } else {
                return redirect()->back()->with('error', __('Attachment is missing'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function exportFile()
    {
        $name = 'Tickets' . date('Y-m-d i:h:s');
        $data = Excel::download(new TicketsExport(), $name . '.csv');
        return $data;
    }
}
