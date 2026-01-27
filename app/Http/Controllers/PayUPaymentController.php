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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PayUPaymentController extends Controller
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

    private function generatePayuHash($params, $salt)
    {
        $key = $params['key'];
        $txnid = $params['txnid'];
        $amount = $params['amount'];
        $productinfo = $params['productinfo'];
        $firstname = $params['firstname'];
        $email = $params['email'];
        $udf1 = isset($params['udf1']) ? $params['udf1'] : '';
        $udf2 = isset($params['udf2']) ? $params['udf2'] : '';
        $udf3 = isset($params['udf3']) ? $params['udf3'] : '';
        $udf4 = isset($params['udf4']) ? $params['udf4'] : '';
        $udf5 = isset($params['udf5']) ? $params['udf5'] : '';

        $hashString = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' .
            $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|' .
            $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $salt;

        return strtolower(hash('sha512', $hashString));
    }

    private function getPayUSettings($userId = null)
    {
        $settings = $userId ? Utility::getCompanyPaymentSetting($userId) : Utility::payment_settings();

        $mode = $settings['payu_mode'] ?? 'sandbox';
        $url = ($mode === 'sandbox') ? 'https://test.payu.in/_payment' : 'https://secure.payu.in/_payment';
        $payuSettings = [
            'payu_merchant_id' => $settings['payu_merchant_id'] ?? '',
            'payu_salt_key' => $settings['payu_salt_key'] ?? '',
            'payu_mode' => $settings['payu_mode'] ?? 'sandbox',
            'site_currency' => $settings['site_currency'] ?? 'USD',
            'payu_url' => $url,
            'is_payu_enabled' => $settings['is_payu_enabled'] ?? 'off',
        ];
        return $payuSettings;
    }

    public function settingConfig(Request $request)
    {
        $validate = [
            'payu_mode' => 'required|in:sandbox,live',
            'payu_merchant_id' => 'required|string',
            'payu_salt_key' => 'required|string|max:65535',
        ];

        $validator = Validator::make($request->all(), $validate);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        return $this->paymentSetting($request, 'payu manage', 'payu_payment_is_on', $validate);
    }

    public function planPayWithPayU(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|string',
                'coupon' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->route('plans.index')->with('error', $validator->errors()->first());
            }

            $user = Auth::user();
            $settings = $this->getPayUSettings(null);
            $currency = $settings['site_currency'] ?? 'USD';
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
                        return redirect()->route('plans.index')->with('error', __('This coupon code has expired.'));
                    }

                    $coupon_id = $coupons->id;
                } else {
                    return redirect()->route('plans.index')->with('error', __('This coupon code is invalid or has expired.'));
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
                        'payment_type' => 'PAYU',
                        'payment_status' => 'succeeded',
                        'user_id' => $user->id,
                    ]);
                    return redirect()->route('plans.index')->with('success', __('Plan upgraded successfully.'));
                }
                return redirect()->route('plans.index')->with('error', __('Plan upgrade failed.'));
            }

            Session::put($orderID, [
                'plan_id' => $plan->id,
                'amount' => $get_amount,
                'currency' => $currency,
                'frequency' => $request->frequency ?? 'Month',
                'order_id' => $orderID,
                'coupon_id' => $coupon_id,
            ]);

            $params = [
                'key'       => $settings['payu_merchant_id'],
                'txnid'     => $orderID,
                'amount'    => number_format($get_amount, 2, '.', ''),
                'productinfo' => $plan->name,
                'firstname' => $user->name,
                'email'     => $user->email,
                'phone'     => '',
                'surl'      => route('payu.response'),
                'furl'      => route('payu.response'),
                'service_provider' => 'payu_paisa',
            ];

            $params['hash'] = $this->generatePayuHash($params, $settings['payu_salt_key']);

            $html = '<html><head><title>Redirecting...</title></head><body onload="document.forms[\'payuForm\'].submit();">';
            $html .= '<form method="post" name="payuForm" action="' . $settings['payu_url'] . '">';
            if (!isset($params['key'])) {
                throw new \Exception('Missing PayU key in the parameters.');
            }
            foreach ($params as $key => $value) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
            }

            $html .= '<noscript><p>Please click the button below to proceed to PayU.</p><input type="submit" value="Pay Now"></noscript>';
            $html .= '</form></body></html>';

            return response($html);
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('Error: ') . $e->getMessage());
        }
    }

    public function response(Request $request)
    {
        $status = $request->input('status');
        $orderID = $request->input('txnid');

        if (!$orderID) {
            return redirect()->route('plans.index')->with('Something Went Wrong.');
        }

        if ($status !== 'success') {
            return redirect()->route('plans.index')->with('Something Went Wrong.');
        }
        return redirect()->route('plans.index');
    }

    public function failure()
    {
        return redirect()->route('plans.index')->with('error', __('Something Went Wrong.'));
    }

    public function payBillWithPayU(Request $request)
    {
        try {
            $billId = $request->input('bill_id');
            if ($billId) {
                $billId = Crypt::decrypt($billId);
            }

            $validator = Validator::make(
                array_merge($request->all(), ['bill_id' => $billId]),
                [
                    'bill_id' => 'required|integer|exists:bills,id',
                    'amount' => 'required|numeric|min:1',
                ]
            );

            if ($validator->fails()) {
                $billId = $billId ? Crypt::encrypt($billId) : null;
                $route = $billId ? route('pay.invoice', $billId) : route('bills.index');
                return redirect()->to($route)->with('error', $validator->errors()->first());
            }

            $bill = Bill::findOrFail($billId);
            $settings = $this->getPayUSettings($bill->created_by);
            if ($settings['is_payu_enabled'] !== 'on') {
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('PayU payment is not enabled.'));
            }

            $currency = $settings['site_currency'];
            if (!array_key_exists($currency, $this->currencyArray)) {
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Currency not supported.'));
            }

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $user = Auth::user();

            Session::put($orderID, [
                'bill_id' => $bill->id,
                'amount' => $request->amount,
                'currency' => $currency,
                'order_id' => $orderID,
            ]);

            $encryptedBillId = Crypt::encrypt($bill->id);
            $params = [
                'key' => $settings['payu_merchant_id'],
                'txnid' => $orderID,
                'amount' => number_format($request->amount, 2, '.', ''),
                'productinfo' => $bill->id,
                'firstname' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '9876543210',
                'surl' => route('payu.bill.response', $encryptedBillId),
                'furl' => route('payu.bill.response', $encryptedBillId),
                'service_provider' => 'payu_paisa',
            ];

            $params['hash'] = $this->generatePayuHash($params, $settings['payu_salt_key']);

            $html = '<html><head><title>Redirecting...</title></head><body onload="document.forms[\'payuForm\'].submit();">';
            $html .= '<form method="post" name="payuForm" action="' . $settings['payu_url'] . '">';
            if (!isset($params['key'])) {
                throw new \Exception('Missing PayU key in the parameters.');
            }
            foreach ($params as $key => $value) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
            }

            $html .= '<noscript><p>Please click the button below to proceed to PayU.</p><input type="submit" value="Pay Now"></noscript>';
            $html .= '</form></body></html>';

            return response($html);
        } catch (\Exception $e) {
            $billId = $billId ? Crypt::encrypt($billId) : null;
            $route = $billId ? route('pay.invoice', $billId) : route('bills.index');
            return redirect()->to($route)->with('error', __('An error occurred during payment setup: ') . $e->getMessage());
        }
    }


    public function billResponse(Request $request, $bill_id)
    {
        try {
            $billId = Crypt::decrypt($bill_id);
            $bill = Bill::findOrFail($billId);

            $status = $request->input('status');
            $orderID = $request->input('txnid');

            if ($orderID) {
                Session::forget($orderID);
            }

            if ($status !== null && $status !== 'success') {
                return redirect()->route('pay.invoice', Crypt::encrypt($bill->id))->with('error', __('Something Went Wrong.'));
            }

            return redirect()->route('pay.invoice', Crypt::encrypt($bill->id));
        } catch (\Exception $e) {
            return redirect()->route('bills.index')->with('error', __('Invalid bill ID or something went wrong.'));
        }
    }

    public function billFailure(Request $request)
    {
        $orderID = $request->input('txnid');
        if ($orderID) {
            Session::forget($orderID);
        }
        return redirect()->route('bills.index')->with('error', __('Something went wrong.'));
    }
}
