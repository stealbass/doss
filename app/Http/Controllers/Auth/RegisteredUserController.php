<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Utility;
use App\Models\Advocate;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\DocType;
use App\Models\Motion;
use App\Models\Tax;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create($lang = '')
    {
        $settings = Utility::settings();

        if ($settings['signup_button'] == 'on') {
            if ($lang == '') {
                $lang = Utility::getValByName('default_language');
            }

            if ($lang == 'ar' || $lang == 'he') {
                $value = 'on';
            } else {
                $value = 'off';
            }
            DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values ( ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $value,
                    'SITE_RTL',
                    1,

                ]
            );

            App::setLocale($lang);
            $ref = request('ref');
            if ($ref) {
                $hasRef = $ref;
                $validRef = User::where('create_refercode', $hasRef)->count();
                if ($validRef > 0) {
                    $refcompany = User::where('create_refercode', $hasRef)->first();
                    $refUserId = $refcompany->id;
                    return view('auth.register', compact('lang', 'refUserId'));
                } else {
                    $refUserId = null;
                    return redirect('/register/' . $lang)->with('Invalidererral', __('Invalide referral code'));
                }
            } else {
                $refUserId = null;
                return view('auth.register', compact('lang', 'refUserId'));
            }
        } else {
            return \Redirect::to('login');
        }

    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'on') {
            $validation['g-recaptcha-response'] = 'required|captcha';
        } else {
            $validation = [];
        }

        $this->validate($request, $validation);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Rules\Password::defaults()
            ],
        ]);
        // $refercode = \Str::random(6);
        // $user['create_refercode']=$refercode;

        $length = 6;
        $refercode = '';
        for ($i = 0; $i < $length; $i++) {
            $refercode .= random_int(0, 9);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'plan' => 1,
            'lang' => Utility::getValByName('default_language'),
            'avatar' => '',
            'created_by' => 1,
            'create_refercode' => $refercode,
            'use_refercode' => $request->refUserId,
        ]);

        // if ($request->refUserId != '') {
        //     // $refercode = \Str::random(6);
        //     $length = 6;
        //     $refercode = '';
        //     for ($i = 0; $i < $length; $i++) {
        //         $refercode .= random_int(0, 9);
        //     }
        //     $user1 = User::find($request->refUserId);
        //     $user1['create_refercode'] = $refercode;
        //     $user1->update();
        // }

        $detail = new UserDetail();
        $detail->user_id = $user->id;
        $detail->save();

        Auth::login($user);

        $settings = Utility::settings();

        if ($settings['email_verification'] == 'on') {
            try {
                Utility::getSMTPDetails(1);

                // event(new Registered($user));
                $user->sendEmailVerificationNotification();
                $role_r = Role::findByName('company');
                $user->assignRole($role_r);
                $user->MakeRole($user->id);
                $this->seedCompanyDefaults($user);
                $this->createDefaultJuriste($user);

            } catch (\Exception $e) {

                $user->delete();
                $lang = Utility::getValByName('default_language');
                return redirect('/register/' . $lang)->with('status', __('Email SMTP settings does not configure so please contact to your site admin.'));
            }
            return view('auth.verify');
        } else {

            $user->email_verified_at = date('h:i:s');
            $user->save();

            $role_r = Role::findByName('company');
            $user->assignRole($role_r);
            $user->MakeRole($user->id);
            $this->seedCompanyDefaults($user);
            $this->createDefaultJuriste($user);

            return redirect(RouteServiceProvider::HOME);
        }

    }

    private function seedCompanyDefaults(User $companyUser)
    {
        $companyId = $companyUser->id;

        if (Court::where('created_by', $companyId)->count() === 0) {
            $courts = [
                "La Cour d'Appel",
                'La Cour Supreme',
                'La Haute Cour de Justice',
                'Tribunal de grande instance',
                'Tribunal de première instance',
            ];

            foreach ($courts as $courtName) {
                $court = new Court();
                $court->name = $courtName;
                $court->created_by = $companyId;
                $court->save();
            }
        }

        if (Motion::where('created_by', $companyId)->count() === 0) {
            $motions = [
                'civile',
                "contentieux de l'execution",
                'contentieux du travail',
                'criminelle',
                'droit local',
                'homologation',
                'infraction penale',
                'litige commercial',
                'litige familial',
                "refere d'heure a heure",
                'refere ordinaire',
                'sociale',
                'taxation',
            ];

            foreach ($motions as $motionType) {
                $motion = new Motion();
                $motion->type = $motionType;
                $motion->created_by = $companyId;
                $motion->save();
            }
        }

        if (CaseType::where('created_by', $companyId)->count() === 0) {
            $caseTypes = [
                'civile',
                "contentieux de l'execution",
                'contentieux du travail',
                'criminelle',
                'droit local',
                'homologation',
                'infraction penale',
                'litige commercial',
                'litige familial',
                "refere d'heure a heure",
                'refere ordinaire',
                'sociale',
                'taxation',
            ];

            foreach ($caseTypes as $caseTypeName) {
                $caseType = new CaseType();
                $caseType->name = $caseTypeName;
                $caseType->created_by = (string) $companyId;
                $caseType->save();
            }
        }

        if (DocType::where('created_by', $companyId)->count() === 0) {
            $docTypes = ['pdf', 'word', 'exel', 'image', 'video'];

            foreach ($docTypes as $docTypeName) {
                $docType = new DocType();
                $docType->name = $docTypeName;
                $docType->created_by = $companyId;
                $docType->save();
            }
        }

        if (Tax::where('created_by', $companyId)->count() === 0) {
            $tax = new Tax();
            $tax->name = 'NO TAXE';
            $tax->rate = '0';
            $tax->created_by = $companyId;
            $tax->save();
        }
    }

    private function createDefaultJuriste(User $companyUser)
    {
        $companyId = $companyUser->id;

        if (User::where('created_by', $companyId)->where('type', 'advocate')->exists()) {
            return;
        }

        $placeholderEmail = 'advocate+' . uniqid() . '@placeholder.local';

        $juristeUser = User::create([
            'name' => $companyUser->name,
            'email' => $placeholderEmail,
            'password' => null,
            'type' => 'advocate',
            'lang' => $companyUser->lang ?? Utility::getValByName('default_language'),
            'avatar' => '',
            'created_by' => $companyId,
            'email_verified_at' => now(),
            'is_enable_login' => 0,
        ]);

        $role = Role::where('name', 'advocate')
            ->where('created_by', $companyId)
            ->first();

        if ($role) {
            $juristeUser->assignRole($role);
        } else {
            $juristeUser->assignRole('advocate');
        }

        $advocate = new Advocate();
        $advocate->user_id = $juristeUser->id;
        $advocate->company_name = $companyUser->name;
        $advocate->created_by = $companyId;
        $advocate->save();

        $detail = new UserDetail();
        $detail->user_id = $juristeUser->id;
        $detail->save();
    }
}
