<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Session;

class OzowController extends Controller
{
    function generate_request_hash_check($inputString)
    {
        $stringToHash = strtolower($inputString);
        return $this->get_sha512_hash($stringToHash);
    }

    function get_sha512_hash($stringToHash)
    {
        return hash('sha512', $stringToHash);
    }

    public function planPayWithOzow(Request $request)
    {
        $planID = Crypt::decrypt($request->plan_id);
        $payment_setting = Utility::payment_settings();
        $currency = $payment_setting['currency'] ?? 'ZAR';
        if ($currency != 'ZAR') {
            return redirect()->route('plans.index')->with('error', __('Your currency is not ZAR'));
        }
        $plan = Plan::find($planID);
        $coupon_id = '0';
        $coupon_code = null;
        $discount_value = null;
        $coupons = Coupon::where('code', $request->coupon)->where('is_active', '1')->first();

        $net = $plan->price;
        $get_amount = intval($net);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($plan) {
            try {
                if ($coupons) {
                    $coupon_code = $coupons->code;
                    $usedCoupun = $coupons->used_coupon();
                    if ($coupons->limit == $usedCoupun) {
                        $res_data['error'] = __('This coupon code has expired.');
                    } else {
                        $discount_value = ($get_amount / 100) * $coupons->discount;
                        $get_amount = $get_amount - $discount_value;

                        if ($get_amount < 0) {
                            $get_amount = $plan->price;
                        }
                        $coupon_id = $coupons->id;
                    }

                    if ($get_amount <= 0) {
                        $authuser = Auth::user();
                        $authuser->plan = $plan->id;
                        $authuser->save();
                        $assignPlan = $authuser->assignPlan($plan->id, $request->frequency);
                        if ($assignPlan['is_success'] == true && !empty($plan)) {
                            if (!empty($authuser->payment_subscription_id) && $authuser->payment_subscription_id != '') {
                                try {
                                    $authuser->cancel_subscription($authuser->id);
                                } catch (\Exception $exception) {
                                    \Log::debug($exception->getMessage());
                                }
                            }
                            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                            $userCoupon = new UserCoupon();
                            $userCoupon->user = $authuser->id;
                            $userCoupon->coupon = $coupons->id;
                            $userCoupon->order = $orderID;
                            $userCoupon->save();
                            Order::create(
                                [
                                    'order_id' => $orderID,
                                    'name' => null,
                                    'email' => null,
                                    'card_number' => null,
                                    'card_exp_month' => null,
                                    'card_exp_year' => null,
                                    'plan_name' => $plan->name,
                                    'plan_id' => $plan->id,
                                    'price' => $get_amount == null ? 0 : $get_amount,
                                    'price_currency' => $currency,
                                    'txn_id' => '',
                                    'payment_type' => 'Ozow',
                                    'payment_status' => 'succeeded',
                                    'receipt' => null,
                                    'user_id' => $authuser->id,
                                ]
                            );
                            $assignPlan = $authuser->assignPlan($plan->id);

                            return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                        }
                    }
                }

                $siteCode = isset($payment_setting['company_ozow_site_key']) ? $payment_setting['company_ozow_site_key'] : '';
                $privateKey = isset($payment_setting['company_ozow_private_key']) ? $payment_setting['company_ozow_private_key'] : '';
                $apiKey = isset($payment_setting['company_ozow_api_key']) ? $payment_setting['company_ozow_api_key'] : '';
                $isTest = isset($payment_setting['company_ozow_mode']) && $payment_setting['company_ozow_mode'] == 'sandbox' ? 'true' : 'false';
                $plan_id = $plan->id;
                $countryCode = "ZA";
                $currencyCode = $payment_setting['currency'] ?? 'ZAR';
                $amount = $get_amount;
                $bankReference = time() . 'FKU';
                $transactionReference = time();

                $cancelUrl = route('plan.get.ozow.status', [
                    $plan_id,
                    'amount' => $get_amount,
                    'coupon_code' => $request->coupon,
                ]);
                $errorUrl = route('plan.get.ozow.status', [
                    $plan_id,
                    'amount' => $get_amount,
                    'coupon_code' => $request->coupon,
                ]);
                $successUrl = route('plan.get.ozow.status', [
                    $plan_id,
                    'amount' => $get_amount,
                    'coupon_code' => $request->coupon,
                ]);
                $notifyUrl = route('plan.get.ozow.status', [
                    $plan_id,
                    'amount' => $get_amount,
                    'coupon_code' => $request->coupon,
                ]);

                $inputString = $siteCode . $countryCode . $currencyCode . $amount . $transactionReference . $bankReference . $cancelUrl . $errorUrl . $successUrl . $notifyUrl . $isTest . $privateKey;
                $hashCheck = $this->generate_request_hash_check($inputString);

                $data = [
                    "countryCode" => $countryCode,
                    "amount" => $amount,
                    "transactionReference" => $transactionReference,
                    "bankReference" => $bankReference,
                    "cancelUrl" => $cancelUrl,
                    "currencyCode" => $currencyCode,
                    "errorUrl" => $errorUrl,
                    "isTest" => $isTest,
                    "notifyUrl" => $notifyUrl,
                    "siteCode" => $siteCode,
                    "successUrl" => $successUrl,
                    "hashCheck" => $hashCheck,
                ];

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.ozow.com/postpaymentrequest',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'ApiKey: ' . $apiKey,
                        'Content-Type: application/json'
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $json_attendance = json_decode($response, true);

                if (isset($json_attendance['url']) && $json_attendance['url'] != null) {
                    return redirect()->away($json_attendance['url']);
                } else {
                    return redirect()
                        ->route('plans.index', \Illuminate\Support\Facades\Crypt::encrypt($plan->id))
                        ->with('error', $json_attendance['message'] ?? 'Something went wrong.');
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __($e->getMessage()));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planGetOzowStatus(Request $request, $plan_id)
    {
        $plan = Plan::find($plan_id);
        $user = Auth::user();
        $payment_setting = Utility::payment_settings();
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $price = $plan->price ?? 0;
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($plan) {
            $admin_settings = Utility::payment_settings();
            try {
                if (isset($request['Status']) && $request['Status'] == 'Complete') {
                    $order = Order::create(
                        [
                            'order_id' => $orderID,
                            'name' => $user->name,
                            'email' => $user->email,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => !empty($plan->name) ? $plan->name : '',
                            'plan_id' => $plan->id,
                            'price' => !empty($request->amount) ? $request->amount : 0,
                            'price_currency' => $currency,
                            'txn_id' => '',
                            'payment_type' => __('OZOW'),
                            'payment_status' => __('succeeded'),
                            'receipt' => null,
                            'user_id' => $user->id,
                        ]
                    );

                    $user = User::find($user->id);
                    $coupons = Coupon::where('code', $request->coupon_code)->where('is_active', '1')->first();

                    if (!empty($coupons)) {
                        $userCoupon = new UserCoupon();
                        $userCoupon->user = $user->id;
                        $userCoupon->coupon = $coupons->id;
                        $userCoupon->order = $order->order_id;
                        $userCoupon->save();
                        $usedCoupun = $coupons->used_coupon();
                        if ($coupons->limit <= $usedCoupun) {
                            $coupons->is_active = 0;
                            $coupons->save();
                        }
                    }

                    $assignPlan = $user->assignPlan($plan->id, $request->frequency);

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                } else {
                    return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __('Transaction has been failed.'));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function invoicePayWithOzow(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required',
            'amount' => 'required|numeric|min:0',
        ]);

        Session::put('invoice_id', $request->invoice_id);
        $invoice_id = Session::get('invoice_id');
        $invoice = Bill::find($invoice_id);

        if (!$invoice) {
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Invoice not found.'));
        }

        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $currency = $payment_setting['site_currency'] ?? 'ZAR';
        if ($currency != 'ZAR') {
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Your currency is not ZAR'));
        }
        $getAmount = $request->amount;

        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            if (!$user) {
                return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('User not found.'));
            }
        }

        $authuser = User::where('id', $user->id)->first();

        $siteCode = isset($payment_setting['company_ozow_site_key']) ? $payment_setting['company_ozow_site_key'] : '';
        $privateKey = isset($payment_setting['company_ozow_private_key']) ? $payment_setting['company_ozow_private_key'] : '';
        $apiKey = isset($payment_setting['company_ozow_api_key']) ? $payment_setting['company_ozow_api_key'] : '';
        $isTest = isset($payment_setting['company_ozow_mode']) && $payment_setting['company_ozow_mode'] == 'sandbox' ? 'true' : 'false';
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'ZAR';
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $get_amount = round($request->amount);

        if ($invoice) {
            $invoiceID = $request->invoice_id;
            $get_amount = $request->amount;
            $type = $request->type;

            $countryCode = "ZA";
            $currencyCode = $payment_setting['currency'] ?? 'ZAR';
            $amount = $get_amount;
            $bankReference = time() . 'FKU';
            $transactionReference = time();

            $cancelUrl = route('invoice.ozow.status', ['id' => $invoiceID, 'amount' => $get_amount]);
            $errorUrl = route('invoice.ozow.status', ['id' => $invoiceID, 'amount' => $get_amount]);
            $successUrl = route('invoice.ozow.status', ['id' => $invoiceID, 'amount' => $get_amount]);
            $notifyUrl = route('invoice.ozow.status', ['id' => $invoiceID, 'amount' => $get_amount]);

            $inputString = $siteCode . $countryCode . $currencyCode . $amount . $transactionReference . $bankReference . $cancelUrl . $errorUrl . $successUrl . $notifyUrl . $isTest . $privateKey;
            $hashCheck = $this->generate_request_hash_check($inputString);

            $data = [
                "countryCode" => $countryCode,
                "amount" => $amount,
                "transactionReference" => $transactionReference,
                "bankReference" => $bankReference,
                "cancelUrl" => $cancelUrl,
                "currencyCode" => $currencyCode,
                "errorUrl" => $errorUrl,
                "isTest" => $isTest,
                "notifyUrl" => $notifyUrl,
                "siteCode" => $siteCode,
                "successUrl" => $successUrl,
                "hashCheck" => $hashCheck,
            ];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.ozow.com/postpaymentrequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'ApiKey: ' . $apiKey,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $json_attendance = json_decode($response, true);

            if (isset($json_attendance['url']) && $json_attendance['url'] != null) {
                return redirect()->away($json_attendance['url']);
            } else {
                return redirect()
                    ->route('pay.invoice', encrypt($invoice_id))
                    ->with('error', $json_attendance['message'] ?? 'Something went wrong.');
            }
        } else {
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Invoice not found.'));
        }
    }

    public function invoiceGetOzowStatus(Request $request, $id, $amount = null)
    {
        $invoice = Bill::find($id);
        if (!$invoice) {
            return redirect()->route('pay.invoice', encrypt($id))->with('error', __('Invoice not found.'));
        }

        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'ZAR';

        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            if (!$user) {
                return redirect()->route('pay.invoice', encrypt($id))->with('error', __('User not found.'));
            }
        }

        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        try {
            if (isset($request['Status']) && $request['Status'] == 'Complete') {
                $invoice_payment = new BillPayment();
                $invoice_payment->bill_id = $invoice->id;
                $invoice_payment->amount = $amount ?? $request->input('amount', 0);
                $invoice_payment->date = date('Y-m-d');
                $invoice_payment->currency = $currency;
                $invoice_payment->method = __('Ozow');
                $invoice_payment->order_id = $orderID;
                $invoice_payment->save();

                $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

                if ($payment >= $invoice->total_amount) {
                    $invoice->status = 'PAID';
                    $invoice->due_amount = 0.00;
                } else {
                    $invoice->status = 'Partially Paid';
                    $invoice->due_amount -= $invoice_payment->amount;
                }

                $invoice->save();

                return redirect()->route('pay.invoice', encrypt($id))->with('success', __('Invoice paid successfully!'));
            } else {
                return redirect()->route('pay.invoice', encrypt($id))->with('error', __('Ozow payment failed.'));
            }
        } catch (\Exception $e) {
            return redirect()->route('pay.invoice', encrypt($id))->with('error', __('Payment processing error.'));
        }
    }
}