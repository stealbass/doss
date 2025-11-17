<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;
use App\Mail\SubscriptionConfirmation;
use App\Mail\AdminSubscriptionNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'type',
        'plan',
        'lang',
        'avatar',
        'created_by',
        'create_refercode',
        'use_refercode',
        'email_verified_at',
        'trial_expire_date',
        'plan_expire_date',
        'is_trial_done',
        'is_enable_login',
        'google2fa_enable',
        'google2fa_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function creatorId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    public static function getTeams($id)
    {
        $advName = User::whereIn('id', explode(',', $id))->pluck('name')->toArray();
        return !empty($advName) ? implode(', ', $advName) : null;
    }

    public static function getUser($id)
    {
        $advName = User::find($id);
        return $advName;
    }

    public function currentLanguage()
    {
        return $this->lang;
    }

    public function invoiceNumberFormat($number)
    {
        $settings = Utility::settings();
        return '#' . sprintf("%05d", $number);
    }

    public static function dateFormat($date)
    {
        $settings = Utility::settings();
        return date($settings['site_date_format'], strtotime($date));
    }

    public function assignPlan($planID, $duration = null, $user_id = null)
    {
        $plan = Plan::find($planID);
        if ($plan) {
            $this->plan = $plan->id;
            if ($plan->duration == 'month') {
                $this->plan_expire_date = Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
            } elseif ($plan->duration == 'year') {
                $this->plan_expire_date = Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
            } else {
                $this->plan_expire_date = null;
            }
            $this->save();
            if (isset($user_id) && !empty($user_id)) {
                $users = User::where('created_by', '=', $user_id)->where('type', '!=', 'advocate')->where('type', '!=', 'client')->get();
                $employees = User::where('created_by', '=', $user_id)->where('type', 'advocate')->get();
            } else {
                $users = User::where('created_by', '=', \Auth::user()->creatorId())->where('type', '!=', 'advocate')->where('type', '!=', 'client')->get();
                $employees = User::where('created_by', '=', \Auth::user()->creatorId())->where('type', 'advocate')->get();
            }

            if ($plan->max_users == -1) {
                foreach ($users as $user) {
                    $user->is_active = 1;
                    $user->save();
                }
            } else {
                $userCount = 0;
                foreach ($users as $user) {
                    $userCount++;
                    if ($userCount <= $plan->max_users) {
                        $user->is_active = 1;
                        $user->save();
                    } else {
                        $user->is_active = 0;
                        $user->save();
                    }

                }
            }

            if ($plan->max_advocates == -1) {
                foreach ($employees as $employee) {
                    $user->is_active = 1;
                    $user->save();
                }
            } else {
                $employeeCount = 0;
                foreach ($employees as $employee) {
                    $employeeCount++;
                    if ($employeeCount <= $plan->max_advocates) {
                        $employee->is_active = 1;
                        $employee->save();
                    } else {
                        $employee->is_active = 0;
                        $employee->save();
                    }
                }
            }
            if ($user_id != null) {
                $user = User::find($user_id);
            } else {
                $user = User::find(Auth::user()->id);
            }

            if ($duration == 'Trial') {
                $user->trial_expire_date = Carbon::now()->addDays((int) $plan->trial_days)->isoFormat('YYYY-MM-DD');
                if ($user->plan_expire_date) {
                    $user->plan_expire_date = null;
                }
            } else {
                if ($user->trial_expire_date) {
                    $user->trial_expire_date = null;
                }
            }
            $user->save();

            // Send subscription confirmation emails
            try {
                Utility::getSMTPDetails(1); // Admin SMTP settings
                
                $planPrice = Utility::getValByName('currency_symbol') . number_format($plan->price, 2);
                $planDuration = $plan->duration === 'month' ? 'Mensuel (1 mois)' : 'Annuel (12 mois)';
                $paymentMethod = session('payment_method', 'Paiement en ligne'); // Try to get from session
                
                // Send email to user
                if ($user->email) {
                    $userEmailData = [
                        'userName' => $user->name,
                        'planName' => $plan->name,
                        'planPrice' => $planPrice,
                        'planDuration' => $planDuration,
                        'expirationDate' => $user->plan_expire_date,
                        'paymentMethod' => $paymentMethod,
                        'dashboardUrl' => route('home'),
                    ];
                    
                    Mail::to($user->email)->send(
                        new SubscriptionConfirmation($user, $plan, $userEmailData)
                    );
                    
                    \Log::info('Subscription confirmation email sent', [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id
                    ]);
                }
                
                // Send email to admin
                $adminEmail = Utility::getValByName('mail_from_address');
                if ($adminEmail) {
                    $adminEmailData = [
                        'type' => 'new',
                        'userName' => $user->name,
                        'userEmail' => $user->email,
                        'planName' => $plan->name,
                        'planPrice' => $planPrice,
                        'expirationDate' => $user->plan_expire_date,
                        'paymentMethod' => $paymentMethod,
                        'adminUrl' => route('users.index'),
                    ];
                    
                    Mail::to($adminEmail)->send(
                        new AdminSubscriptionNotification($user, $plan, $adminEmailData, 'new')
                    );
                    
                    \Log::info('Admin notification sent for new subscription', [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error sending subscription confirmation emails', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'error' => $e->getMessage()
                ]);
                // Don't block plan assignment if email fails
            }

            return ['is_success' => true];
        } else {
            return [
                'is_success' => false,
                'error' => 'Plan is deleted.',
            ];
        }
    }

    public function countPaidCompany()
    {
        return User::where('type', '=', 'company')->whereNotIn(
            'plan',
            [
                0,
                1,
            ]
        )->where('created_by', '=', \Auth::user()->id)->count();
    }

    public function getPlan()
    {
        $user = User::find($this->creatorId());
        return Plan::find($user->plan);
    }

    /**
     * Check if user has a free plan (price = 0 or null)
     */
    public function hasFreePlan()
    {
        $plan = $this->getPlan();
        return $plan ? ($plan->price <= 0) : true;
    }

    public static function MakeRole($company_id)
    {
        $data = [];
        $advocate_role_permission = [
            "show dashboard",

            "show group",
            "manage group",

            "manage cause",
            "create cause",
            "delete cause",
            "edit cause",

            "manage case",
            "create case",
            "edit case",
            "view case",
            "delete case",

            "create todo",
            "edit todo",
            "view todo",
            "delete todo",
            "manage todo",

            "manage timesheet",
            "create timesheet",
            "edit timesheet",
            "delete timesheet",
            "view timesheet",

            "manage expense",
            "create expense",
            "edit expense",
            "delete expense",
            "view expense",

            "manage document",
            "create document",
            "edit document",
            "delete document",
            "view document",

            "manage bill",
            "create bill",
            "edit bill",
            "delete bill",
            "view bill",

            "manage diary",
            "view calendar",
        ];

        $advocate_role = Role::where('name', 'advocate')->where('created_by', $company_id)->where('guard_name', 'web')->first();

        if (empty($advocate_role)) {
            $advocate_role = new Role();
            $advocate_role->name = 'advocate';
            $advocate_role->guard_name = 'web';
            $advocate_role->created_by = $company_id;
            $advocate_role->save();

            foreach ($advocate_role_permission as $permission_s) {
                $permission = Permission::where('name', $permission_s)->first();
                $advocate_role->givePermissionTo($permission);
            }
        }

        $client_role_permission = [
            "show dashboard",

            "show group",
            "manage group",

            "manage cause",

            "manage case",
            "view case",

            "manage todo",
            "view todo",

            "manage bill",
            "view bill",

            "manage diary",
            "view calendar",

            "manage timesheet",
            "view timesheet",

            "manage expense",
            "view expense",

            "manage feereceived",
            "view feereceived",

            "manage document",
            "view document",
        ];

        $client_role = Role::where('name', 'client')->where('created_by', $company_id)->where('guard_name', 'web')->first();

        if (empty($client_role)) {
            $client_role = new Role();
            $client_role->name = 'client';
            $client_role->guard_name = 'web';
            $client_role->created_by = $company_id;
            $client_role->save();

            foreach ($client_role_permission as $client_permission_s) {
                $permission = Permission::where('name', $client_permission_s)->first();
                $client_role->givePermissionTo($permission);
            }
        }

        $data['advocate_role'] = $advocate_role;
        $data['client_role'] = $client_role;

        return $data;
    }

    private static $getDefualtViewRouteByModule = null;

    public static function getDefualtViewRouteByModule($module = null)
    {
        if (self::$getDefualtViewRouteByModule === null) {
            self::$getDefualtViewRouteByModule = self::fetchGetDefualtViewRouteByModule($module);
        }

        return self::$getDefualtViewRouteByModule;
    }

    public static function fetchGetDefualtViewRouteByModule($module)
    {
        $userId = \Auth::user()->id;
        $defaultView = UserDefualtView::select('route')->where('module', $module)->where('user_id', $userId)->first();

        return !empty($defaultView) ? $defaultView->route : '';
    }

    public static function priceFormat($price)
    {
        $settings = Utility::settings();
        return (($settings['site_currency_symbol_position'] == "pre") ? $settings['site_currency_symbol'] : '') . number_format($price, 2) . (($settings['site_currency_symbol_position'] == "post") ? $settings['site_currency_symbol'] : '');
    }

    public function timeFormat($time)
    {
        $settings = Utility::settings();
        return date($settings['site_time_format'], strtotime($time));
    }

    public function crmcreatorId()
    {
        if ($this->type == 'super admin') {
            return $this->id;
        } else {
            if ($this->type == 'advocate') {
                $company = User::where('id', $this->created_by)->first();
                return $company->created_by;
            } else {
                return $this->created_by;
            }
        }
    }

    public static function userDefualtView($request)
    {
        $userId = \Auth::user()->id;
        $defaultView = UserDefualtView::where('module', $request->module)->where('user_id', $userId)->first();

        if (empty($defaultView)) {
            $userView = new UserDefualtView();
        } else {
            $userView = $defaultView;
        }

        $userView->module = $request->module;
        $userView->route = $request->route;
        $userView->view = $request->view;
        $userView->user_id = $userId;
        $userView->save();
    }

    public function supportTicketCreatorId()
    {
        if ($this->type == 'super admin') {
            return $this->id;
        } else {
            if ($this->type == 'advocate') {
                $company = User::where('id', $this->created_by)->first();
                return $company->created_by;
            } else {
                return $this->created_by;
            }
        }
    }

    public static function assginecasetypepermission()
    {
        $permissions = [
            ['name' => 'manage casetype', 'guard_name' => 'web', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'create casetype', 'guard_name' => 'web', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'edit casetype', 'guard_name' => 'web', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'delete casetype', 'guard_name' => 'web', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $companyRole = Role::where('name', 'company')->first();

        $companyPermissions = [

            ["name" => "manage casetype"],
            ["name" => "create casetype"],
            ["name" => "edit casetype"],
            ["name" => "delete casetype"],

        ];

        foreach ($companyPermissions as $permissionData) {

            $permissionName = $permissionData['name'];
            $permission = Permission::where('name', $permissionName)->first();
            if (!$companyRole->hasPermissionTo($permission)) {
                if (!$permission) {
                    $permission = Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ]);
                }
                $companyRole->givePermissionTo($permission);
            }
        }
    }
}
