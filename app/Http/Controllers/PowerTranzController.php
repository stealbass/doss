<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Utility;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PowerTranzController extends Controller
{
    private $currencyArray = [
        "USD" => "840",
        "EUR" => "978",
        "GBP" => "826",
        "CAD" => "124",
        "AUD" => "036",
        "JPY" => "392",
        "CHF" => "756",
        "SEK" => "752",
        "NOK" => "578",
        "DKK" => "208",
        "NZD" => "554",
        "SGD" => "702",
        "HKD" => "344",
        "ZAR" => "710",
        "MXN" => "484",
        "BRL" => "986",
        "INR" => "356",
        "CNY" => "156",
        "RUB" => "643",
    ];

    public function settingConfig(Request $request)
    {
        $validate = [
            'powertranz_mode' => 'required|in:sandbox,live',
            'powertranz_merchant_id' => 'required|string',
            'powertranz_processing_password' => 'required|string',
        ];

        if ($request->powertranz_mode == 'live') {
            $validate['production_url'] = 'required|string';
        } else {
            $request->merge(['production_url' => null]);
        }

        $validator = Validator::make($request->all(), $validate);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        return $this->paymentSetting($request, 'powertranz manage', 'powertranz_payment_is_on', $validate);
    }

    private function getPowerTranzSettings($userId = null)
    {
        $settings = $userId ? Utility::getCompanyPaymentSetting($userId) : Utility::payment_settings();

        if (($settings['powertranz_mode'] ?? 'sandbox') == "sandbox") {
            $url = "https://staging.ptranz.com";
        } else {
            $url = rtrim($settings['production_url'] ?? 'https://api.ptranz.com', '/');
        }

        return [
            'powertranz_merchant_id' => $settings['powertranz_merchant_id'] ?? '',
            'powertranz_processing_password' => $settings['powertranz_processing_password'] ?? '',
            'powertranz_mode' => $settings['powertranz_mode'] ?? 'sandbox',
            'site_currency' => $settings['site_currency'] ?? 'USD',
            'powertranz_url' => $url,
            'is_powertranz_enabled' => $settings['is_powertranz_enabled'] ?? 'off',
        ];
    }

    private function curl_response($data, $request, $arr)
    {
        $user = Auth::user();
        $expiryDate = explode('/', $request->expiryDate);
        $expiryDate = $expiryDate[1] . $expiryDate[0];

        if (!array_key_exists($arr['site_currency'], $this->currencyArray)) {
            return [
                'status' => false,
                'message' => __('Currency not supported.')
            ];
        }

        $currency_code = $this->currencyArray[$arr['site_currency']];
        $auth_url = $arr['powertranz_url'] . "/api/spi/auth";
        $host = parse_url($auth_url, PHP_URL_HOST);
        $headers = [
            "Accept: application/json",
            "PowerTranz-PowerTranzId: " . trim($arr['powertranz_merchant_id']),
            "PowerTranz-PowerTranzPassword: " . trim($arr['powertranz_processing_password']),
            "Content-Type: application/json; charset=utf-8",
            "Host: " . $host,
            "Connection: Keep-Alive"
        ];

        $fields = [
            "TotalAmount" => $data['amount'],
            "CurrencyCode" => $currency_code,
            "ThreeDSecure" => true,
            "Source" => [
                "CardPan" => $request->cardNumber,
                "CardCvv" => $request->cvv ?? "",
                "CardExpiration" => $expiryDate,
                "CardholderName" => $request->card_name ?? ""
            ],
            "OrderIdentifier" => $data['order_id'],
            "BillingAddress" => [
                "FirstName" => $user->name ?? "Example",
                "LastName" => "",
                "Line1" => "",
                "County" => "",
                "State" => "",
                "EmailAddress" => $user->email ?? "example@gmail.com",
                "PhoneNumber" => $user->phone ?? "9876543210"
            ],
            "AddressMatch" => false,
            "ExtendedData" => [
                "ThreeDSecure" => [
                    "ChallengeWindowSize" => 4,
                    "ChallengeIndicator" => "01"
                ],
                "MerchantResponseUrl" => $arr['status_url']
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            curl_close($ch);
            return [
                'status' => false,
                'message' => __('Unable to connect to PowerTranz gateway. Please try again later.')
            ];
        }

        curl_close($ch);
        $json_response = json_decode($response, true);

        if ($http_status !== 200) {
            return [
                'status' => false,
                'message' => __('Something went wrong. Please try again.')
            ];
        }

        return $json_response;
    }

    public function planPayWithPowerTranz(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return redirect()->route('plans.index')->with('error', $validator->errors()->first());
            }

            $user = Auth::user();
            $admin_settings = $this->getPowerTranzSettings(null);

            $currency = $admin_settings['site_currency'] ?? 'USD';
            $planID = Crypt::decrypt($request->plan_id);
            $plan = Plan::find($planID);

            if (!$plan) {
                return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
            }

            if ($plan->price <= 0) {
                return redirect()->route('plans.index')->with('error', __('Plan price must be greater than or equal to 1.'));
            }

            if (!array_key_exists($currency, $this->currencyArray)) {
                return redirect()->route('plans.index')->with('error', __('Currency not supported.'));
            }

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $get_amount = $plan->price;
            $coupon_id = null;

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if ($coupons) {
                    $usedCoupon = $coupons->used_coupon();
                    $discount_value = ($get_amount / 100) * $coupons->discount;
                    $get_amount = $get_amount - $discount_value;

                    if ($get_amount < 0) {
                        $get_amount = 0;
                    }

                    if ($coupons->limit == $usedCoupon) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }

                    $coupon_id = $coupons->id;
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }

            if ($get_amount <= 0) {
                $user->plan = $plan->id;
                $user->save();
                $assignPlan = $user->assignPlan($plan->id, $request->frequency ?? 'Month');

                if ($assignPlan['is_success']) {
                    Order::create([
                        'order_id' => $orderID,
                        'plan_name' => $plan->name,
                        'plan_id' => $plan->id,
                        'price' => $get_amount,
                        'price_currency' => $currency,
                        'payment_type' => 'POWERTRANZ',
                        'payment_status' => 'succeeded',
                        'user_id' => $user->id,
                    ]);
                    return redirect()->route('plans.index')->with('success', __('Plan upgraded successfully.'));
                }
                return redirect()->route('plans.index')->with('error', __('Plan upgrade failed.'));
            }

            $pay = [
                'title' => $plan->name,
                'amount' => $get_amount,
                'currency' => $currency,
                'currency_code' => $this->currencyArray[$currency] ?? '840',
                'order_id' => $orderID,
                'back_url' => route('plans.index'),
                'plan_id' => $plan->id,
                'action' => route('powertranz.response'),
                'frequency' => $request->frequency ?? 'Month',
            ];

            Session::put($orderID, [
                'plan_id' => $plan->id,
                'amount' => $get_amount,
                'currency' => $currency,
                'frequency' => $request->frequency ?? 'Month',
                'order_id' => $orderID,
                'coupon_id' => $coupon_id,
            ]);

            try {
                $encryptedID = Crypt::encrypt($plan->id);
                $encryptedPay = Crypt::encrypt($pay);
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __('Failed to prepare payment data.'));
            }

            return redirect()->route('powertranz.show', [
                'id' => $encryptedID,
                'pay' => $encryptedPay,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('An error occurred during payment setup: ') . $e->getMessage());
        }
    }

    public function show($id, Request $request)
    {
        try {
            $plan_id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Invalid plan ID.'));
        }

        try {
            $pay = Crypt::decrypt($request->query('pay'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Invalid payment details.'));
        }

        $plan = Plan::find($plan_id);
        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('Plan not found.'));
        }

        if ($plan->price <= 0) {
            return redirect()->route('plans.index')->with('error', __('Plan price must be greater than or equal to 1.'));
        }
        $admin_settings = $this->getPowerTranzSettings(null);
        if ($admin_settings['is_powertranz_enabled'] !== 'on') {
            return redirect()->route('plans.index')->with('error', __('PowerTranz payment is not enabled.'));
        }

        $currency = $admin_settings['site_currency'];
        if (!array_key_exists($currency, $this->currencyArray)) {
            return redirect()->route('plans.index')->with('error', __('Currency not supported.'));
        }

        $orderID = $pay['order_id'] ?? strtoupper(str_replace('.', '', uniqid('', true)));

        $pay = [
            'title' => $plan->name,
            'amount' => $pay['amount'] ?? $plan->price,
            'currency' => $currency,
            'currency_code' => $this->currencyArray[$currency],
            'back_url' => route('plans.index'),
            'plan_id' => $plan_id,
            'order_id' => $orderID,
            'action' => route('powertranz.response'),
            'frequency' => $pay['frequency'] ?? 'Month',
        ];

        Session::put($orderID, [
            'plan_id' => $plan_id,
            'amount' => $pay['amount'],
            'currency' => $currency,
            'frequency' => $pay['frequency'] ?? 'Month',
            'order_id' => $orderID,
        ]);

        return view('settings.powertranz', compact('pay', 'plan'));
    }

    public function response(Request $request)
    {
        try {
            $orderID = $request->input('order_id');
            if (!$orderID) {
                return redirect()->route('plans.index')->with('error', __('Invalid order ID in response.'));
            }

            $session = Session::get($orderID);
            if (!$session || !isset($session['amount']) || !isset($session['plan_id'])) {
                return redirect()->route('plans.index')->with('error', __('Invalid session data.'));
            }
            $admin_settings = $this->getPowerTranzSettings(null);
            if ($admin_settings['is_powertranz_enabled'] !== 'on') {
                return redirect()->route('plans.index')->with('error', __('PowerTranz payment is not enabled.'));
            }
            if (empty($admin_settings['powertranz_merchant_id']) || empty($admin_settings['powertranz_processing_password'])) {
                return redirect()->route('plans.index')->with('error', __('PowerTranz Merchant ID or Processing Password is missing. Please configure them in settings.'));
            }
            if (!in_array($admin_settings['powertranz_mode'], ['sandbox', 'live'])) {
                return redirect()->route('plans.index')->with('error', __('Invalid PowerTranz mode configured.'));
            }
            if ($admin_settings['powertranz_mode'] === 'live' && empty($admin_settings['production_url'])) {
                return redirect()->route('plans.index')->with('error', __('PowerTranz production URL is not configured.'));
            }

            $validator = Validator::make($request->all(), [
                'cardNumber' => 'required|digits:16',
                'expiryDate' => 'required|regex:/^\d{2}\/\d{2}$/',
                'cvv' => 'required|digits_between:3,4',
                'card_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return redirect()->route('plans.index')->with('error', $validator->errors()->first());
            }

            $session['card_details'] = [
                'card_number' => substr($request->cardNumber, -4),
                'card_exp_month' => explode('/', $request->expiryDate)[0] ?? null,
                'card_exp_year' => explode('/', $request->expiryDate)[1] ?? null,
            ];
            Session::put($orderID, $session);

            $json_response = $this->curl_response([
                'amount' => $session['amount'],
                'order_id' => $orderID,
            ], $request, array_merge($admin_settings, [
                'status_url' => route('powertranz.status', ['order_id' => $orderID]),
            ]));

            if (isset($json_response['status']) && $json_response['status'] === false) {
                return redirect()->route('plans.index')->with('error', $json_response['message']);
            }

            if (isset($json_response['Approved']) && $json_response['Approved'] === false) {
                if (isset($json_response['RedirectData'])) {
                    ob_clean();
                    header('Content-Type: text/html');
                    echo $json_response['RedirectData'];
                    exit;
                }
                return redirect()->route('plans.index')->with('error', __('Something went wrong. Please try again.'));
            }

            return redirect()->route('plans.index')->with('error', __('Payment processing. Please wait for confirmation.'));
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('An error occurred: ') . $e->getMessage());
        }
    }

    public function status(Request $request, $order_id)
    {
        $response = json_decode($request->Response, true);
        $orderID = $response['OrderIdentifier'] ?? $order_id;
        $session = Session::get($orderID);

        if (is_null($session)) {
            return redirect()->route('plans.index')->with('error', __('Payment session expired or invalid. Please try again.'));
        }

        Session::forget($orderID);

        $user = Auth::user();
        $plan = Plan::find($session['plan_id']);

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('Plan not found.'));
        }

        $company_payment_setting = $this->getPowerTranzSettings($user->id);
        $currency = $company_payment_setting['site_currency'];

        if (empty($request->SpiToken)) {
            return redirect()->route('plans.index')->with('error', __('Invalid payment response from gateway.'));
        }

        $pay_url = $company_payment_setting['powertranz_url'] . "/api/spi/Payment";

        $response = Http::withHeaders([
            "Accept: text/plain",
            "Content-Type: application/json-patch+json",
            "Host: " . parse_url($pay_url, PHP_URL_HOST),
            "Connection: Keep-Alive",
        ])->post($pay_url, $request->SpiToken);

        $json_response = json_decode($response->body(), true);

        try {
            if (isset($json_response['Approved']) && $json_response['Approved'] == "true" && $json_response['IsoResponseCode'] == "00") {
                $card_details = $session['card_details'] ?? [];
                $order = Order::create([
                    'order_id' => $orderID,
                    'name' => $user->name,
                    'email' => $user->email,
                    'card_number' => $card_details['card_number'] ?? null,
                    'card_exp_month' => $card_details['card_exp_month'] ?? null,
                    'card_exp_year' => $card_details['card_exp_year'] ?? null,
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => $session['amount'],
                    'price_currency' => $currency,
                    'txn_id' => 'txn_' . uniqid(),
                    'payment_status' => 'succeeded',
                    'payment_type' => 'POWERTRANZ',
                    'receipt' => null,
                    'user_id' => $user->id,
                    'refund' => 0,
                ]);

                $assignPlan = $user->assignPlan($plan->id, $session['frequency'] ?? 'Month');
                if ($assignPlan['is_success']) {
                    Utility::referralcommisonadd($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan upgraded successfully.'));
                }
                return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
            }
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Transaction failed: ') . $e->getMessage());
        }
        return redirect()->route('plans.index')->with('error', __('Something went wrong. Please try again.'));
    }

    public function showBillPaymentForm($id, Request $request)
    {
        $bill = Bill::findOrFail($id);
        $amount = $request->amount ?? $bill->due_amount;

        if ($amount <= 0) {
            return redirect()->route('bills.show', $bill->id)->with('error', __('Amount must be greater than or equal to 1.'));
        }

        $company_payment_setting = $this->getPowerTranzSettings($bill->created_by);
        if ($company_payment_setting['is_powertranz_enabled'] !== 'on') {
            return redirect()->route('bills.show', $bill->id)->with('error', __('PowerTranz payment is not enabled.'));
        }

        $currency = $company_payment_setting['site_currency'];
        if (!array_key_exists($currency, $this->currencyArray)) {
            return redirect()->route('bills.show', $bill->id)->with('error', __('Currency not supported.'));
        }

        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $pay = [
            'title' => 'Bill Payment',
            'amount' => $amount,
            'currency' => $currency,
            'currency_code' => $this->currencyArray[$currency],
            'back_url' => route('pay.invoice', Crypt::encrypt($bill->id)),
            'bill_id' => $bill->id,
            'order_id' => $orderID,
            'action' => route('powertranz.bill.response'),
        ];

        Session::put($orderID, [
            'bill_id' => $bill->id,
            'amount' => $amount,
            'currency' => $currency,
            'order_id' => $orderID,
        ]);

        return view('settings.powertranz', compact('pay', 'bill'));
    }

    public function payBillWithPowerTranz(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cardNumber' => 'required|digits:16',
            'expiryDate' => 'required|regex:/^\d{2}\/\d{2}$/',
            'cvv' => 'required|digits_between:3,4',
            'card_name' => 'required|string|max:255',
            'bill_id' => 'required|integer|exists:bills,id',
            'amount' => 'required|numeric|min:1',
        ]);
        $bill = Bill::findOrFail($request->bill_id);

        if ($validator->fails()) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', $validator->errors()->first());
        }

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', __('Please log in to proceed with payment.'));
        }


        $company_settings = $this->getPowerTranzSettings($bill->created_by);
        if ($company_settings['is_powertranz_enabled'] !== 'on') {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('PowerTranz payment is not enabled.'));
        }
        if (empty($company_settings['powertranz_merchant_id']) || empty($company_settings['powertranz_processing_password'])) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('PowerTranz Merchant ID or Processing Password is missing. Please configure them in settings.'));
        }
        if (!in_array($company_settings['powertranz_mode'], ['sandbox', 'live'])) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Invalid PowerTranz mode configured.'));
        }
        if ($company_settings['powertranz_mode'] === 'live' && empty($company_settings['production_url'])) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('PowerTranz production URL is not configured.'));
        }

        $currency = $company_settings['site_currency'];
        if (!array_key_exists($currency, $this->currencyArray)) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Currency not supported.'));
        }

        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        Session::put($orderID, [
            'bill_id' => $bill->id,
            'amount' => $request->amount,
            'currency' => $currency,
            'order_id' => $orderID,
        ]);

        try {
            $response = $this->curl_response([
                'amount' => $request->amount,
                'order_id' => $orderID,
            ], $request, array_merge($company_settings, [
                'status_url' => route('powertranz.bill.status', ['order_id' => $orderID]),
            ]));

            if (isset($response['status']) && $response['status'] === false) {
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', $response['message']);
            }

            if (isset($response['Approved']) && $response['Approved'] === false) {
                if (isset($response['RedirectData'])) {
                    return response($response['RedirectData']);
                }
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Something went wrong. Please try again.'));
            }

            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Unexpected response from payment gateway.'));
        } catch (\Exception $e) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __($e->getMessage()));
        }
    }

    public function billResponse(Request $request)
    {
        $orderID = $request->input('order_id') ?? null;
        if (!$orderID) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Invalid order ID in response.'));
        }

        $session = Session::get($orderID);
        if (!$session || !isset($session['amount'])) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Invalid session data.'));
        }

        $bill = Bill::find($session['bill_id']);
        $company_payment_setting = $this->getPowerTranzSettings($bill->created_by);

        try {
            $json_response = $this->curl_response([
                'amount' => $session['amount'],
                'order_id' => $orderID,
            ], $request, array_merge($company_payment_setting, [
                'status_url' => route('powertranz.bill.status', ['order_id' => $orderID]),
            ]));

            if (isset($json_response['status']) && $json_response['status'] === false) {
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', $json_response['message']);
            }

            if (isset($json_response['Approved']) && $json_response['Approved'] === false) {
                if (isset($json_response['RedirectData'])) {
                    return response($json_response['RedirectData']);
                }
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Something went wrong. Please try again.'));
            }

            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Unexpected response from payment gateway.'));
        } catch (\Exception $e) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __($e->getMessage()));
        }
    }

    public function billStatus(Request $request, $order_id)
    {
        $response = json_decode($request->Response, true);
        $orderID = $response['OrderIdentifier'] ?? $order_id;
        $session = Session::get($orderID);

        if (is_null($session)) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Payment session expired or invalid. Please try again.'));
        }

        Session::forget($orderID);

        $bill = Bill::find($session['bill_id']);
        if (!$bill) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Bill not found.'));
        }

        $company_payment_setting = $this->getPowerTranzSettings($bill->created_by);
        $currency = $company_payment_setting['site_currency'];

        if (empty($request->SpiToken)) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Invalid payment response from gateway.'));
        }

        $pay_url = $company_payment_setting['powertranz_url'] . "/api/spi/Payment";

        $response = Http::withHeaders([
            "Accept" => "text/plain",
            "Content-Type" => "application/json-patch+json",
            "Host" => parse_url($pay_url, PHP_URL_HOST),
            "Connection" => "Keep-Alive",
        ])->post($pay_url, $request->SpiToken);
        $json_response = json_decode($response->body(), true);

        try {
            if (isset($json_response['Approved']) && $json_response['Approved'] == "true" && $json_response['IsoResponseCode'] == "00") {
                BillPayment::create([
                    'bill_id' => $bill->id,
                    'date' => now()->toDateString(),
                    'amount' => $session['amount'],
                    'method' => 'POWERTRANZ',
                    'note' => $bill->description ?? 'Paid via PowerTranz',
                    'order_id' => $orderID,
                    'currency' => $currency,
                    'txn_id' => 'txn_' . uniqid(),
                    'payment_type' => 'PowerTranz',
                    'payment_status' => 'succeeded',
                    'user_id' => Auth::check() ? Auth::id() : null,
                ]);

                $bill->due_amount -= $session['amount'];
                if ($bill->due_amount <= 0) {
                    $bill->status = 'paid';
                    $bill->due_amount = 0;
                }else {
                    $bill->status  = 'Partialy Paid';
                }
                $bill->save();

                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('success', __('Bill payment successful.'));
            }
        } catch (\Exception $e) {
            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Transaction failed: ') . $e->getMessage());
        }
        return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Something went wrong. Please try again.'));
    }
}
