<?php

namespace App\Http\Controllers;

use App\Exports\ClientExport;
use App\Imports\ClientsImport;
use App\Models\Advocate;
use App\Models\group;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Hearing;
use App\Models\PointOfContacts;
use App\Models\User;
use App\Models\Bill;
use App\Models\Fee;
use App\Models\UserDetail;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use DB;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->can('manage client')) {
            $users = User::where('created_by', '=', Auth::user()->creatorId())
                ->where('type', 'client')
                ->get();
            $user_details = UserDetail::get();

            return view('client.index', compact('users', 'user_details'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function userList()
    {

        if (Auth::user()->can('manage client')) {
            $users = User::where('created_by', '=', Auth::user()->creatorId())->where('type', 'client')->get();
            $user_details = UserDetail::get();

            return view('client.list', compact('users', 'user_details'));
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
        // Generate a random unique email
        $randomEmail = 'client' . uniqid() . '@exemple.com';
        return view('client.create', compact('randomEmail'));
        if (Auth::user()->can('create client')) {
            return view('client.create');
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
        if (Auth::user()->can('create client')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required|max:120',
                    'email' => 'required|email',
                    'password' => 'nullable|min:8',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $user = new User();
            $user['name'] = $request->name;
            $user['email'] = $request->email;
            $user['password'] = Hash::make($request->password);
            $user['lang'] = 'en';
            $user['created_by'] = Auth::user()->creatorId();
            //$user['email_verified_at'] = date('Y-m-d H:i:s');
            $user['type'] = 'client';
            $user->save();

            $role_r = Role::where('name', 'client')->where('created_by', Auth::user()->creatorId())->first();
            if ($role_r) {
                $user->assignRole($role_r);
            } else {
                $user->assignRole('client');
            }

            $detail = new UserDetail();
            $detail->user_id = $user->id;
            $detail->save();

            return redirect()->route('client.index')->with('success', __('Client successfully created.'));
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
    public function show($user_id)
    {
        if (Auth::user()->can('manage client')) {
            $user = User::where('id', $user_id)->first();

            if (!$user) {
                return redirect()->back()->with('error', __('Client Not Found.'));
            }

            $case = DB::table("cases")
                ->select("cases.*")
                ->get();
            $cases = [];
            foreach ($case as $value) {
                $data = json_decode($value->your_party_name);
                foreach ($data as $key => $val) {
                    if (isset($val->clients) && $val->clients == $user->id) {
                        $hearings = Hearing::where('case_id', $value->id)->get();
                        $value->hearings = $hearings;
                        $cases[$value->id] = $value;
                    }
                }
            }
            $bills = Bill::where('bill_to', $user->id)->get();
            $fees = Fee::where('member', $user->id)->get();

            return view('client.view', compact('user', 'cases', 'bills', 'fees'));
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function fileImport()
    {
        return view('client.import');
    }

    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt,xlsx',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $clients = (new ClientsImport())->toArray(request()->file('file'))[0];

        $totalcase = count($clients) - 1;
        $errorArray = [];

        $user = Auth::user();

        for ($i = 1; $i <= count($clients) - 1; $i++) {
            $deal = $clients[$i];
            $check_user = User::where('email', 'like', $deal[1])->first();

            if (!$check_user) {

                $dealData = new User();
                $dealData->name = $deal[0];
                $dealData->email = $deal[1];
                $dealData->password = Hash::make($deal[2]);
                $dealData->lang = 'en';
                $dealData->created_by = Auth::user()->creatorId();
                $dealData->email_verified_at = date('Y-m-d H:i:s');
                $dealData->type = 'client';
                $dealData->save();

                $role_r = Role::where('name', 'client')->where('created_by', Auth::user()->creatorId())->first();
                if ($role_r) {
                    $dealData->assignRole($role_r);
                } else {
                    $dealData->assignRole('client');
                }

                $detail = new UserDetail();
                $detail->user_id = $dealData->id;
                $detail->save();
            } else {
                $errorArray[] = $i;
                $data['status'] = 'error';
                $data['msg'] = $totalcase . '  ' . __($check_user->email . ' Already exist.');
            }
        }

        if (!empty($errorArray)) {
            $data['status'] = 'error';
            $data['msg'] = $totalcase . '  ' . __('Record imported fail out of' . ' ' . count($errorArray) . ' ' . 'record');
        } else {
            $data['status'] = 'success';
            $data['msg'] = __('Record successfully imported');
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function exportFile()
    {
        $name = 'clients_' . date('Y-m-d i:h:s');
        $data = Excel::download(new ClientExport(), $name . '.xlsx');
        ob_end_clean();
        return $data;
    }
}
