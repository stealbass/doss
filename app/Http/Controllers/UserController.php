<?php

namespace App\Http\Controllers;

use App\Models\Advocate;
use App\Models\Cases;
use App\Models\group;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PointOfContacts;
use App\Models\Document;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use PragmaRX\Google2FAQRCode\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa')->except(['logout']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->can('manage member') || Auth::user()->can('manage user')) {
            $users = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '!=', 'advocate')->where('type', '!=', 'client')->where('type', '!=', 'superAdminEmployee')->get();
            $user_details = UserDetail::get();

            return view('users.index', compact('users', 'user_details'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function userList()
    {
        if (Auth::user()->can('manage member') || Auth::user()->can('manage user')) {
            $users = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '!=', 'advocate')->where('type', '!=', 'client')->where('type', '!=', 'superAdminEmployee')->get();
            $user_details = UserDetail::get();

            return view('users.list', compact('users', 'user_details'));
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
        if ((Auth::user()->can('create member') || Auth::user()->can('create user'))) {
            $roles = Role::where('created_by', Auth::user()->creatorId())->where('name', '!=', 'Advocate')->where('name', '!=', 'client')->get()->pluck('name', 'id');
            return view('users.create', compact('roles'));
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
        if (Auth::user()->can('create member') || Auth::user()->can('create user')) {

            if (Auth::user()->type != 'super admin') {
                // Prepare validation rules
                $rules = [
                    'name' => 'required|max:120',
                    'role' => 'required',
                ];

                // Get the role to check if it's advocate
                $role = Role::find($request->role);
                $isAdvocate = $role && strtolower($role->name) === 'advocate';

                // Email is required for non-advocates, optional for advocates
                if ($isAdvocate) {
                    if (!empty($request->email)) {
                        $rules['email'] = 'email|unique:users';
                    }
                } else {
                    $rules['email'] = 'required|email|unique:users';
                }

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $user = Auth::user();
                $plan = $user->getPlan();

                $total_user = User::where('type', '=', 'user')->where('type', '!=', 'client')->where('created_by', '=', $user->creatorId())->count();

                if ($total_user < $plan->max_users || $plan->max_users == -1) {
                    $enableLogin = 0;
                    if (!empty($request->password_switch) && $request->password_switch == 'on') {
                        $enableLogin = 1;
                        $validator = \Validator::make(
                            $request->all(),
                            ['password' => 'required|min:6']
                        );
                        if ($validator->fails()) {
                            return redirect()->back()->with('error', $validator->errors()->first());
                        }
                    }
                    $userpassword = $request->input('password');

                    $user = new User();
                    $user['name'] = $request->name;
                    $user['email'] = $request->email;
                    $user['password'] = !empty($userpassword) ? \Hash::make($userpassword) : null;
                    $user['lang'] = 'en';
                    $user['created_by'] = Auth::user()->creatorId();
                    $user['email_verified_at'] = date('Y-m-d H:i:s');
                    $user['is_enable_login'] = $enableLogin;

                    $role_r = Role::findById($request->role);
                    $user->assignRole($role_r);
                    $user['type'] = $role_r->name;

                    $user->save();

                    $detail = new UserDetail();
                    $detail->user_id = $user->id;
                    $detail->save();

                    return redirect()->route('users.index')->with('success', __('Member successfully created.'));
                } else {
                    return redirect()->route('users.index')->with('error', __('Your member limit is over, Please upgrade plan.'));
                }

            } else {

                $validator = Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $enableLogin = 0;
                if (!empty($request->password_switch) && $request->password_switch == 'on') {
                    $enableLogin = 1;
                    $validator = \Validator::make(
                        $request->all(),
                        ['password' => 'required|min:6']
                    );

                    if ($validator->fails()) {
                        return redirect()->back()->with('error', $validator->errors()->first());
                    }
                }
                $userpassword = $request->input('password');

                $user = new User();
                $user['name'] = $request->name;
                $user['email'] = $request->email;
                $user['password'] = !empty($userpassword) ? \Hash::make($userpassword) : null;
                $user['lang'] = 'en';
                $user['created_by'] = Auth::user()->creatorId();
                $user['plan'] = Plan::first()->id;
                $user['is_enable_login'] = $enableLogin;

                if (Utility::settings()['email_verification'] == 'off') {
                    $user['email_verified_at'] = date('Y-m-d H:i:s');
                }

                $role_r = Role::findByName('company');
                $user->assignRole($role_r);
                $user['type'] = 'company';

                // $refercode CREATE
                $length = 6;
                $refercode = '';
                for ($i = 0; $i < $length; $i++) {
                    $refercode .= random_int(0, 9);
                }
                $user['create_refercode'] = $refercode;

                $user->save();

                $detail = new UserDetail();
                $detail->user_id = $user->id;
                $detail->save();

                //create company default roles
                $user->MakeRole($user->id);

                return redirect()->route('users.index')->with('success', __('Member successfully created.'));
            }

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
        if (Auth::user()->can('show member')) {

            $user_detail = UserDetail::where('user_id', $user_id)->first();

            if ($user_detail) {
                $data = explode(',', $user_detail->my_group);
                $my_groups = group::whereIn('id', $data)->get()->pluck('name');
                return view('users.view', compact('my_groups'));
            }
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
         $user = User::find($id);
         $user_detail = UserDetail::where('user_id', $user->id)->first();

         $advocate = [];
         $users = [];
         $client = [];
         $advocates = [];
         $plan = [];
         $cases = [];

         if (Auth::user()->type == 'super admin') {
             $users = User::where('created_by', $id)
                 ->where('type', '!=', 'advocate')
                 ->where('type', '!=', 'client')
                 ->get();
             $client = User::where('created_by', $id)->where('type', 'like', 'client')->get();
             $advocates = User::where('created_by', $id)->where('type', 'like', 'advocate')->get();
             $plan = Plan::where('id', $user->plan)->first();
             $cases = Cases::where('created_by', $id)->get();
         }

         if (Auth::user()->type == 'advocate' && $user->id == Auth::user()->id) {
             $advocate = Advocate::where('user_id', $user->id)->first();
         }

         $google2fa = new Google2FA();

         // Generate Secret
         $secretKey = $user->google2fa_secret ?: $google2fa->generateSecretKey();

         //Generate inline QR code HTML
         $qrCodeInline = $google2fa->getQRCodeInline(
             config('app.name'),
             $user->email,
             $secretKey
         );

         $data = [
             'user' => $user,
             'google2fa_url' => $qrCodeInline,
             'secret' => $secretKey,
         ];

         return view('users.edit', compact('user', 'user_detail', 'advocate', 'plan', 'users', 'client', 'cases','advocates', 'data'));
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

        $rules = [
            'name' => 'required|max:120',
        ];

        // For advocates editing their own profile, email is optional but must be valid if provided.
        if (Auth::user()->type == 'advocate' && Auth::user()->id == (int) $id) {
            if (!empty($request->email)) {
                $rules['email'] = 'email';
            }
        } else {
            $rules['email'] = 'required|email';
        }

        if (!empty($request->mobile_number)) {
            $rules['mobile_number'] = 'numeric';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $user = User::find($id);

        if ($user) {
            if (Auth::user()->type == 'advocate' && $user->id == Auth::user()->id) {
                $adv = Advocate::where('user_id', $user->id)->first();
                if ($adv) {

                    if (!empty($request->point_of_contacts)) {
                        foreach ($request->point_of_contacts as $items) {
                            foreach ($items as $item) {
                                if (empty($item) && $item < 0) {
                                    $msg['flag'] = 'error';
                                    $msg['msg'] = __('Please enter your contacts');
                                    return redirect()->back()->with('error', $msg);
                                }
                            }
                            $validator = Validator::make(
                                $items,
                                [
                                    'contact_name' => 'required',
                                    'contact_email' => 'required',
                                    'contact_phone' => 'required|numeric',
                                    'contact_designation' => 'required',
                                ]
                            );
                        }
                    }

                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();
                        return redirect()->back()->with('error', $messages->first());
                    }

                    $advocate = Advocate::find($adv->id);
                    $userAdd = $advocate->getAdvUser($advocate->user_id)->first();
                    if ($userAdd->email != $request->email) {
                        $users = User::where('email', $request->email)->first();
                        if (!empty($users)) {
                            return redirect()->back()->with('error', __('Email address already exist.'));
                        }
                    }

                    $advocate['phone_number'] = !empty($request->phone_number) ? $request->phone_number : NULL;
                    $advocate['father_name'] = $request->father_name;
                    $advocate['age'] = !empty($request->age) ? $request->age : NULL;
                    $advocate['company_name'] = $request->company_name;
                    $advocate['website'] = $request->website;
                    $advocate['tin'] = $request->tin;
                    $advocate['gstin'] = $request->gstin;
                    $advocate['pan_number'] = $request->pan_number;
                    $advocate['hourly_rate'] = !empty($request->hourly_rate) ? $request->hourly_rate : NULL;
                    $advocate['ofc_address_line_1'] = $request->ofc_address_line_1;
                    $advocate['ofc_address_line_2'] = $request->ofc_address_line_2;
                    $advocate['ofc_country'] = $request->ofc_country;
                    $advocate['ofc_state'] = !empty($request->ofc_state) ? $request->ofc_state : NULL;
                    $advocate['ofc_city'] = $request->ofc_city;
                    $advocate['ofc_zip_code'] = !empty($request->ofc_zip_code) ? $request->ofc_zip_code : NULL;
                    $advocate['home_address_line_1'] = $request->home_address_line_1;
                    $advocate['home_address_line_2'] = $request->home_address_line_2;
                    $advocate['home_country'] = $request->home_country;
                    $advocate['home_state'] = $request->home_state;
                    $advocate['home_city'] = $request->home_city;
                    $advocate['home_zip_code'] = !empty($request->home_zip_code) ? $request->home_zip_code : NULL;
                    $advocate->save();

                    $userAdd->name = $request->name;
                    $userAdd->email = $request->email;

                    if ($request->hasFile('profile')) {
                        $filenameWithExt = $request->file('profile')->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $request->file('profile')->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        $dir = 'uploads/profile';
                        $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
                        if ($path['flag'] == 1) {
                            $url = $path['url'];
                        } else {
                            return redirect()->route('users.index', Auth::user()->id)->with('error', __($path['msg']));
                        }
                        $userAdd->avatar = $fileNameToStore;
                    }

                    $userAdd->save();
                    if (!empty($request->old_contacts)) {
                        foreach (json_decode($request->old_contacts, true) as $key => $value) {
                            $contacts = PointOfContacts::find($value['id']);
                            $contacts->delete();
                        }
                    }

                    if (!empty($request->point_of_contacts)) {
                        foreach ($request->point_of_contacts as $key => $value) {
                            $contacts = new PointOfContacts();
                            $contacts['advocate_id'] = $advocate->id;
                            $contacts['contact_name'] = $value['contact_name'];
                            $contacts['contact_email'] = $value['contact_email'];
                            $contacts['contact_phone'] = $value['contact_phone'];
                            $contacts['contact_designation'] = $value['contact_designation'];
                            $contacts->save();
                        }
                    }
                    return redirect()->back()->with('success', __('Profile Successfully Updated!'));
                } else {
                    return redirect()->back()->with('error', __('Profile not found.'));
                }
            } else {
                $user['name'] = $request->name;
                $user['email'] = $request->email;

                if ($request->hasFile('profile')) {
                    if (Auth::user()->type == 'super admin') {

                        $filenameWithExt = $request->file('profile')->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $request->file('profile')->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        $settings = Utility::Settings();
                        $url = '';
                        $dir = 'uploads/profile';
                        $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);

                        if ($path['flag'] == 1) {
                            $url = $path['url'];
                        } else {
                            return redirect()->route('users.index', Auth::user()->id)->with('error', __($path['msg']));
                        }

                        $user->avatar = $fileNameToStore;
                    } else {
                        $dir = 'uploads/profile/';
                        $file_path = $dir . $user['avatar'];
                        $image_size = $request->file('profile')->getSize();

                        $result = Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);

                        if ($result == 1) {

                            Utility::changeStorageLimit(Auth::user()->creatorId(), $file_path);
                            $filenameWithExt = $request->file('profile')->getClientOriginalName();
                            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                            $extension = $request->file('profile')->getClientOriginalExtension();
                            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                            $settings = Utility::Settings();
                            $url = '';
                            $dir = 'uploads/profile';
                            $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);

                            if ($path['flag'] == 1) {
                                $url = $path['url'];
                            } else {
                                return redirect()->route('users.index', Auth::user()->id)->with('error', __($path['msg']));
                            }

                            $user->avatar = $fileNameToStore;
                        }
                    }
                }
                $user->update();

                $detail = UserDetail::where('user_id', $user->id)->first();

                $detail->mobile_number = !empty($request->mobile_number) ? $request->mobile_number : null;
                $detail->address = $request->address;
                $detail->city = $request->city;
                $detail->state = $request->state;
                $detail->zip_code = !empty($request->zip_code) ? $request->zip_code : null;
                $detail->landmark = $request->landmark;
                $detail->about = $request->about;

                $detail->save();

                if ($user->id == Auth::user()->id) {
                    return redirect()->back()->with('success', __('Profile Successfully Updated!'));
                } else if (Auth::user()->type == 'super admin') {
                    return redirect()->back()->with('success', __('Company details Successfully Updated!'));
                } else {
                    return redirect()->back()->with('success', __('Member details Successfully Updated!'));
                }
            }
        } else {
            if ($user->id == Auth::user()->id) {
                return redirect()->back()->with('success', __('Profile not found.'));
            } else if (Auth::user()->type == 'super admin') {
                return redirect()->back()->with('success', __('Company not found.'));
            } else {
                return redirect()->back()->with('success', __('Member not found.'));
            }
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
        if (Auth::user()->can('delete member') || Auth::user()->can('delete user') || Auth::user()->can('delete client')) {
            $user = User::find($id);
            $detail = UserDetail::where('user_id', $user->id)->first();

            if ($user->created_by != Auth::user()->creatorId()) {
                return redirect()->back()->with('error', __('You can\'t delete yourself.'));
            } else {
                if ($user && $detail) {
                    $user->delete();
                    $detail->delete();
                    User::where('created_by', $user->id)->delete();
                    Advocate::where('created_by', $user->id)->delete();

                    $data = explode(',', $detail->my_group);
                    $my_groups = group::whereIn('id', $data)->get();

                    foreach ($my_groups as $key => $value) {
                        if (str_contains($value->members, $detail->user_id)) {
                            $value->members = trim($value->members, $detail->user_id);
                            $value->save();
                        }
                    }
                    return redirect()->back()->with('success', __('Member deleted successfully.'));
                }
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function changeMemberPassword(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'password' => 'required|same:confirm_password',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $objUser = User::find($id);
        $objUser->password = Hash::make($request->password);
        $objUser->save();

        return redirect()->back()->with('success', __('Password updated successfully.'));
    }

    public function companyPassword($id)
    {
        $eId = Crypt::decrypt($id);
        $user = User::find($eId);
        $employee = User::where('id', $eId)->first();

        return view('users.reset', compact('user', 'employee'));
    }

    public function upgradePlan($user_id)
    {
        $user = User::find($user_id);
        $plans = Plan::get();
        $admin_payment_setting = Utility::settings();
        return view('users.plan', compact('user', 'plans', 'admin_payment_setting'));
    }

    public function activePlan($user_id, $plan_id)
    {
        $user = User::find($user_id);
        $user->plan = $plan_id;
        $user->save();
        $assignPlan = $user->assignPlan($plan_id, null, $user->id);
        $plan = Plan::find($plan_id);

        if ($assignPlan['is_success'] == true && !empty($plan)) {
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            Order::create(
                [
                    'order_id' => $orderID,
                    'name' => null,
                    'card_number' => null,
                    'card_exp_month' => null,
                    'card_exp_year' => null,
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'price_currency' => env('CURRENCY'),
                    'txn_id' => '',
                    'payment_type' => __('Manually Upgrade By Super Admin'),
                    'payment_status' => 'succeeded',
                    'receipt' => null,
                    'user_id' => $user->id,
                ]
            );
        }

        return redirect()->back()->with('success', __('Plan successfully activated.'));
    }

    public function deactivatePlan($user_id, $plan_id)
    {
        $user = User::find($user_id);
        $user->plan = $plan_id;
        $user->save();
        $assignPlan = $user->assignPlan($plan_id, null, $user->id);
        $plan = Plan::find($plan_id);

        if ($assignPlan['is_success'] == true && !empty($plan)) {
            return redirect()->back()->with('success', __('Plan successfully deactivated.'));
        } else {
            return redirect()->back()->with('error', __('Something went wrong.'));
        }
    }

    public function verify($user_id)
    {
        $user = User::where('id', $user_id)->first();
        if ($user) {
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();
            return redirect()->back()->with('success', __('Email verify!'));
        }
    }

    public function detail($user_id)
    {
        $user = User::where('id', $user_id)->first();
        $users = User::where('created_by', $user_id)
            ->where('type', '!=', 'advocate')
            ->where('type', '!=', 'client')
            ->get();
        $client = User::where('created_by', $user_id)->where('type', 'like', 'client')->get();
        $advocates = User::where('created_by', $user_id)->where('type', 'like', 'advocate')->get();
        $user_detail = UserDetail::where('user_id', $user->id)->first();
        $plan = Plan::where('id', $user->plan)->first();
        $cases = Cases::where('created_by', $user_id)->get();
        return view('users.detail', compact('user', 'user_detail', 'users', 'plan', 'client', 'cases', 'advocates'));
    }

    public function downloadAllDocuments($user_id)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $user = User::find($user_id);
        if (!$user) {
            return redirect()->back()->with('error', __('User not found.'));
        }

        $documents = Document::where('created_by', $user_id)->get();
        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', __('No documents found for this user.'));
        }

        $settings = Utility::getStorageSetting();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        $this->configureStorageDisks($settings, $storageSetting);

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $zipName = 'documents_' . $user_id . '_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', __('Unable to create zip file.'));
        }

        $disk = $storageSetting === 'local' ? Storage::disk() : Storage::disk($storageSetting);

        foreach ($documents as $document) {
            if (empty($document->file)) {
                continue;
            }

            $storagePath = 'uploads/documents/' . $document->file;
            if (!$disk->exists($storagePath)) {
                continue;
            }

            if ($storageSetting === 'local') {
                $zip->addFile($disk->path($storagePath), $document->file);
            } else {
                $zip->addFromString($document->file, $disk->get($storagePath));
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function deleteAllDocuments($user_id)
    {
        if (Auth::user()->type !== 'super admin') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $user = User::find($user_id);
        if (!$user) {
            return redirect()->back()->with('error', __('User not found.'));
        }

        $documents = Document::where('created_by', $user_id)->get();
        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', __('No documents found for this user.'));
        }

        $settings = Utility::getStorageSetting();
        $storageSetting = $settings['storage_setting'] ?? 'local';
        $this->configureStorageDisks($settings, $storageSetting);
        $disk = $storageSetting === 'local' ? Storage::disk() : Storage::disk($storageSetting);

        foreach ($documents as $document) {
            if (!empty($document->file)) {
                $storagePath = 'uploads/documents/' . $document->file;
                if ($disk->exists($storagePath)) {
                    $disk->delete($storagePath);
                }
            }
            $document->delete();
        }

        return redirect()->back()->with('success', __('All documents deleted successfully.'));
    }

    private function configureStorageDisks(array $settings, string $storageSetting): void
    {
        if ($storageSetting === 'wasabi') {
            config([
                'filesystems.disks.wasabi.key' => $settings['wasabi_key'] ?? '',
                'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'] ?? '',
                'filesystems.disks.wasabi.region' => $settings['wasabi_region'] ?? '',
                'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'] ?? '',
                'filesystems.disks.wasabi.endpoint' => 'https://s3.' . ($settings['wasabi_region'] ?? '') . '.wasabisys.com',
            ]);
        } elseif ($storageSetting === 's3') {
            config([
                'filesystems.disks.s3.key' => $settings['s3_key'] ?? '',
                'filesystems.disks.s3.secret' => $settings['s3_secret'] ?? '',
                'filesystems.disks.s3.region' => $settings['s3_region'] ?? '',
                'filesystems.disks.s3.bucket' => $settings['s3_bucket'] ?? '',
                'filesystems.disks.s3.use_path_style_endpoint' => false,
            ]);
        } elseif ($storageSetting === 'r2') {
            config([
                'filesystems.disks.r2.key' => $settings['r2_key'] ?? '',
                'filesystems.disks.r2.secret' => $settings['r2_secret'] ?? '',
                'filesystems.disks.r2.region' => $settings['r2_region'] ?? 'auto',
                'filesystems.disks.r2.bucket' => $settings['r2_bucket'] ?? '',
                'filesystems.disks.r2.endpoint' => $settings['r2_endpoint'] ?? '',
                'filesystems.disks.r2.url' => $settings['r2_url'] ?? '',
                'filesystems.disks.r2.use_path_style_endpoint' => false,
            ]);
        }
    }

    public function LoginWithAdmin(Request $request, User $user, $id)
    {
        $user = User::find($id);
        if ($user && auth()->check()) {
            auth()->user()->impersonate($user);
            return redirect('/dashboard');
        }
    }

    public function ExitAdmin(Request $request)
    {
        Auth::user()->leaveImpersonation($request->user());
        return redirect('/dashboard');
    }

    public function companyInfo(Request $request, $id)
    {
        $userData = User::where('created_by', $id)
            ->where('type', '!=', 'client')
            ->where('type', '!=', 'advocate')
            ->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')
            ->first();

        return view('users.companyinfo', compact('userData', 'id'));
    }

    public function userUnable(Request $request)
    {
        User::where('id', $request->id)->update(['is_disable' => $request->is_disable]);
        $userData = User::where('created_by', $request->company_id)
            ->where('type', '!=', 'client')
            ->where('type', '!=', 'advocate')
            ->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')->first();

        if ($request->is_disable == 1) {
            return response()->json(['success' => __('User successfully enable.'), 'userData' => $userData]);
        } else {
            return response()->json(['success' => __('User successfully disable.'), 'userData' => $userData]);
        }
    }

    public function LoginManage($id)
    {
        $eId = Crypt::decrypt($id);
        $user = User::find($eId);
        if ($user->is_enable_login == 1) {
            $user->is_enable_login = 0;
            $user->save();
            return redirect()->back()->with('success', __('User login disable successfully.'));
        } else {
            $user->is_enable_login = 1;
            $user->save();
            return redirect()->back()->with('success', __('User login enable successfully.'));
        }
    }

    public function userPassword($id)
    {
        $eId = Crypt::decrypt($id);
        $user = User::find($eId);

        return view('reset', compact('user'));
    }

    public function userPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $user = User::where('id', $id)->first();
        $user->forceFill([
            'password' => Hash::make($request->password),
            'is_enable_login' => 1,
        ])->save();

        return redirect()->route('users.index')->with(
            'success',
            'User Password successfully updated.'
        );
    }
}
