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
use Illuminate\Support\Facades\Session;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeNetController extends Controller
{

    public $secret_key;
    public $is_enabled;

    public function planPayWithAuthorizeNet(Request $request)
    {
        $payment_setting = Utility::payment_settings();
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        // if ($currency != 'USD') {
        //     return redirect()->route('plans.index')->with('error', __('Your currency is not USD'));
        // }
        $planID = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
        $plan = Plan::find($planID);
        $authuser = Auth::user();
        $coupon_id = '';

        $planAmount = $plan->price;
        $get_amount = intval($planAmount);

        $coupon_code = null;
        $discount_value = null;
        $coupons = Coupon::where('code', $request->coupon)->where('is_active', '1')->first();
        if ($planID) {
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
                        $assignPlan = $authuser->assignPlan($plan->id);
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
                                    'payment_type' => 'Authorizenet',
                                    'payment_status' => 'Success',
                                    'receipt' => null,
                                    'user_id' => $authuser->id,
                                ]
                            );
                            $assignPlan = $authuser->assignPlan($plan->id, $request->frequency);

                            return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                        }
                    }
                }
                // dd($get_amount);
                $data = [
                    'id' => $plan->id,
                    'amount' => $get_amount,
                    'coupon_code' => $request->coupon_code,
                ];
                $data = json_encode($data);
                try {
                    return view('AuthorizeNet.request', compact('plan', 'get_amount', 'data', 'currency'));
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __($e->getMessage()));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planGetAuthorizeNetStatus(Request $request)
    {
        $input = $request->all();
        $admin_settings = Utility::payment_settings();
        $data = json_decode($input['data'], true);
        $amount = $data['amount'];
        $plan = Plan::find($data['id']);
        $price = $plan->price;
        $authuser = Auth::user();
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $admin_currancy = !empty($admin_settings['defult_currancy']) ? $admin_settings['defult_currancy'] : 'USD';
        try {
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($admin_settings['authorizenet_merchant_login_id']);
            $merchantAuthentication->setTransactionKey($admin_settings['authorizenet_merchant_transaction_key']);
            $refId = 'ref' . time();
            // Create the payment data for a credit card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($input['cardNumber']);
            $creditCard->setExpirationDate($input['year'] . '-' . $input['month']);
            $creditCard->setCardCode($input['cvv']);

            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);
            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($amount);
            $transactionRequestType->setPayment($paymentOne);
            // Assemble the complete transaction request
            $requestNet = new AnetAPI\CreateTransactionRequest();
            $requestNet->setMerchantAuthentication($merchantAuthentication);
            $requestNet->setRefId($refId);
            $requestNet->setTransactionRequest($transactionRequestType);
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', __('something Went wrong!'));
        }

        $controller = new AnetController\CreateTransactionController($requestNet);
        if (!empty($admin_settings['authorizenet_mode']) && $admin_settings['authorizenet_mode'] == 'live') {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION); // change SANDBOX to PRODUCTION in live mode
        } else {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX); // change SANDBOX to PRODUCTION in live mode
        }

        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    Order::create(
                        [
                            'order_id' => $orderID,
                            'name' => $authuser->name ?? '',
                            'email' => $authuser->email ?? '',
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => $plan->name,
                            'plan_id' => $plan->id,
                            'price' => $amount == null ? 0 : $amount,
                            'price_currency' => $admin_currancy,
                            'txn_id' => '',
                            'payment_type' => __('Authorizenet'),
                            'payment_status' => 'Success',
                            'receipt' => null,
                            'user_id' => $authuser->id,
                        ]
                    );

                    if (isset($input['coupon_code']) && $input['coupon_code']) {
                        $coupons = Coupon::where('id', $request->coupon_id)->where('is_active', '1')->first();
                        if (!empty($coupons)) {
                            $userCoupon = new UserCoupon();
                            $userCoupon->user = $authuser->id;
                            $userCoupon->coupon = $coupons->id;
                            $userCoupon->order = $orderID;
                            $userCoupon->save();
                            $usedCoupun = $coupons->used_coupon();
                            if ($coupons->limit <= $usedCoupun) {
                                $coupons->is_active = 0;
                                $coupons->save();
                            }
                        }
                    }
                    // dd($data['frequency']);
                    $assignPlan = $authuser->assignPlan($plan->id);

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                    if ($tresponse->getErrors() != null) {
                        return redirect()->route('plans.index')->with('error', __('Transaction Failed!'));
                    }
                }
            } else {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    return redirect()->route('plans.index')->with('error', __('Transaction Failed!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __('No reponse returned!'));
                }
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('No reponse returned!'));
        }
    }

    public function invoicePayWithAuthorizeNet(Request $request)
    {
        Session::put('invoice_id', $request->invoice_id);
        $invoice_id = Session::get('invoice_id');
        $invoice = Bill::find($invoice_id);
        $getAmount = $request->amount;
        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
        }

        $authuser = User::where('id', $user->id)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $merchant_login_id = $payment_setting['authorizenet_merchant_login_id'];
        $merchant_transaction_key = $payment_setting['authorizenet_merchant_transaction_key'];
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'US';
        $get_amount = round($request->amount);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($invoice) {
            $invoiceID = $request->invoice_id;
            $get_amount = $request->amount;
            $type = $request->type;
            $authuser = User::where('id', $user->id)->first();
            $data = [
                'invoiceID' => $invoiceID,
                'user_id' => $user,
                'get_amount' => $get_amount,
                'type' => $type,
                'authuser' => $authuser,
            ];

            $data = json_encode($data);

            try {
                return view('AuthorizeNet.invoice', compact('invoice', 'get_amount', 'authuser', 'data', 'currency'));
            } catch (\Exception $e) {
                dd($e);
                \Log::error($e->getMessage());
            }
        } else {
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Invoice is deleted.'));
        }
    }

    public function invoiceGetAuthorizeNetStatus(Request $request)
    {
        $input = $request->all();
        $data = json_decode($input['data'], true);
        $invoice_id = $data['invoiceID'];
        $amount = $data['get_amount'];
        $type = $data['type'];
        $invoice = Bill::find($invoice_id);
        $payment_setting = Utility::payment_settings();
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
        }

        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $get_amount = $request->get_amount;

        // Declare $requestNet before the try block to avoid undefined variable error
        $requestNet = null;

        try {
            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($payment_setting['authorizenet_merchant_login_id']);
            $merchantAuthentication->setTransactionKey($payment_setting['authorizenet_merchant_transaction_key']);
            $refId = 'ref' . time();

            // Create the payment data for a credit card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($input['cardNumber']);
            $creditCard->setExpirationDate($input['year'] . '-' . $input['month']);
            $creditCard->setCardCode($input['cvv']);

            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);

            // Create a TransactionRequestType object and add the previous objects to it
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount($amount);
            $transactionRequestType->setPayment($paymentOne);

            // Assemble the complete transaction request
            $requestNet = new AnetAPI\CreateTransactionRequest();
            $requestNet->setMerchantAuthentication($merchantAuthentication);
            $requestNet->setRefId($refId);
            $requestNet->setTransactionRequest($transactionRequestType);
        } catch (\Exception $e) {
            // Handle exception
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Transaction Failed!'));
        }

        $controller = new AnetController\CreateTransactionController($requestNet);

        if (!empty($admin_settings['authorizenet_mode']) && $payment_setting['authorizenet_mode'] == 'live') {

            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION); // Use PRODUCTION for live mode
        } else {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX); // Use SANDBOX for testing
        }

        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    // Handle successful transaction
                    if (!empty($invoice_id)) {

                        $invoice = Bill::find($invoice_id);

                        $invoice_payment = new BillPayment();
                        $invoice_payment->bill_id = $invoice->id;
                        $invoice_payment->amount = $amount;
                        $invoice_payment->date = date('Y-m-d');
                        $invoice_payment->currency = $currency;
                        $invoice_payment->method = __('Authorizenet');
                        $invoice_payment->note = $invoice->description;
                        $invoice_payment->order_id = $orderID;
                        $invoice_payment->save();



                        $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

                        if ($payment >= $invoice->total_amount) {
                            $invoice->status = 'PAID';
                            $invoice->due_amount = 0.00;
                        } else {
                            $invoice->status = 'Partially Paid';
                            $invoice->due_amount = $invoice->due_amount - $amount;
                        }
                        $invoice->save();


                        if (Auth::check()) {
                            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('success', __('Invoice paid Successfully!'));
                        } else {
                            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('success', __('Invoice paid Successfully!'));
                        }
                    } else {
                        return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Oops something went wrong.'));
                    }
                }
            } else {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Transaction Failed!'));
                } else {
                    return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('No response returned!'));
                }
            }
        } else {
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('No response returned!'));
        }
    }
}
