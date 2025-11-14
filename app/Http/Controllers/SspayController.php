<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserCoupon;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class SspayController extends Controller
{
    public $secretKey, $callBackUrl, $returnUrl, $categoryCode, $is_enabled, $invoiceData, $currency;

    public function __construct()
    {
        $payment_setting = Utility::payment_settings();

        $this->secretKey = isset($payment_setting['sspay_secret_key']) ? $payment_setting['sspay_secret_key'] : '';
        $this->categoryCode = isset($payment_setting['sspay_category_code']) ? $payment_setting['sspay_category_code'] : '';
        $this->is_enabled = isset($payment_setting['is_sspay_enabled']) ? $payment_setting['is_sspay_enabled'] : 'off';

        if (empty($this->secretKey) || empty($this->categoryCode)) {
            throw new Exception(__('SSPay configuration is incomplete. Please check secret key and category code.'));
        }
    }

    public function SspayPaymentPrepare(Request $request)
    {
        try {
            $planID = Crypt::decrypt($request->plan_id);
            $plan = Plan::find($planID);

            if (!$plan) {
                return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
            }

            $coupon_id = null;
            $price = $plan->price;

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $price = $plan->price - $discount_value;

                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                    $coupon_id = $coupons->id;
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }

                if ($price <= 0) {
                    $authuser = Auth::user();
                    $authuser->plan = $plan->id;
                    $authuser->save();
                    $assignPlan = $authuser->assignPlan($plan->id);

                    $coupons = Coupon::find($coupon_id);
                    $user = Auth::user();
                    $orderID = time();

                    if (!empty($coupons)) {
                        $userCoupon = new UserCoupon();
                        $userCoupon->user = $user->id;
                        $userCoupon->coupon = $coupons->id;
                        $userCoupon->order = $orderID;
                        $userCoupon->save();
                        $usedCoupun = $coupons->used_coupon();
                        if ($coupons->limit == $usedCoupun) {
                            $coupons->is_active = 0;
                            $coupons->save();
                        }
                    }

                    if ($assignPlan['is_success'] && !empty($plan)) {
                        if (!empty($authuser->payment_subscription_id) && $authuser->payment_subscription_id != '') {
                            try {
                                $authuser->cancel_subscription($authuser->id);
                            } catch (Exception $e) {
                                return redirect()->route('plans.index')->with('error', $e->getMessage());

                            }
                        }

                        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                        Order::create([
                            'order_id' => $orderID,
                            'name' => null,
                            'email' => null,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => $plan->name,
                            'plan_id' => $plan->id,
                            'price' => $price ?: 0,
                            'price_currency' => !empty($this->currency) ? $this->currency : 'USD',
                            'txn_id' => '',
                            'payment_type' => __('Sspay'),
                            'payment_status' => 'succeeded',
                            'receipt' => null,
                            'user_id' => $authuser->id,
                        ]);

                        return redirect()->route('plans.index')->with('success', __('Plan activated successfully.'));
                    }
                }
            }

            $coupon = empty($request->coupon) ? "0" : $request->coupon;
            $this->callBackUrl = route('plan.sspay.callback', [$plan->id, $price, $coupon]);
            $this->returnUrl = route('plan.sspay.callback', [$plan->id, $price, $coupon]);

            $Date = date('d-m-Y');
            $amount = $price;
            $description = !empty($plan->description) ? $plan->description : $plan->name;
            $billName = $plan->name;
            $billExpiryDays = 3;
            $billExpiryDate = date('d-m-Y', strtotime($Date . ' + 3 days'));
            $billContentEmail = "Thank you for purchasing our product!";
            $user = Auth::user();

            if ($amount <= 0) {
                return redirect()->route('plans.index')->with('error', __('Invalid payment amount.'));
            }

            $some_data = [
                'userSecretKey' => $this->secretKey,
                'categoryCode' => $this->categoryCode,
                'billName' => $billName,
                'billDescription' => $description,
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => (int)(100 * $amount), 
                'billReturnUrl' => $this->returnUrl,
                'billCallbackUrl' => $this->callBackUrl,
                'billExternalReferenceNo' => 'AFR341DFI',
                'billTo' => !empty($user->name) ? $user->name : '',
                'billEmail' => !empty($user->email) ? $user->email : '',
                'billPhone' => !empty($user->phone_no) ? $user->phone_no : '0000000000',
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => '0',
                'billContentEmail' => $billContentEmail,
                'billChargeToCustomer' => 1,
                'billExpiryDate' => $billExpiryDate,
                'billExpiryDays' => $billExpiryDays
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://sspay.my/index.php/api/createBill');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($some_data));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($result === false || $httpCode != 200) {
                return redirect()->route('plans.index')->with('error', __('Failed to connect to payment gateway. Please try again.'));
            }

            $obj = json_decode($result, true);

            if (empty($obj) || !is_array($obj) || !isset($obj[0]['BillCode'])) {
                return redirect()->route('plans.index')->with('error', __('Invalid response from payment gateway. Please try again.'));
            }

            return redirect('https://sspay.my/' . $obj[0]['BillCode']);
        } catch (Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function SspayPlanGetPayment(Request $request, $planId, $getAmount, $couponCode)
    {
        $coupons = null;
        if ($couponCode != 0) {
            $coupons = Coupon::where('code', strtoupper($couponCode))->where('is_active', '1')->first();
            $request['coupon_id'] = $coupons ? $coupons->id : null;
        }

        $plan = Plan::find($planId);
        $user = auth()->user();

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }

        try {
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $statuses = 'pending';

            if ($request->status_id == 3) {
                $statuses = 'Fail';
            } elseif ($request->status_id == 1) {
                $statuses = 'succeeded';
            }

            $order = new Order();
            $order->order_id = $orderID;
            $order->name = $user->name;
            $order->card_number = '';
            $order->card_exp_month = '';
            $order->card_exp_year = '';
            $order->plan_name = $plan->name;
            $order->plan_id = $plan->id;
            $order->price = $getAmount;
            $order->price_currency = isset($this->currency) ? $this->currency : 'USD';
            $order->payment_type = __('Sspay');
            $order->payment_status = $statuses;
            $order->receipt = '';
            $order->user_id = $user->id;
            $order->save();

            if ($request->status_id == 3) {
                return redirect()->route('plans.index')->with('error', __('Your transaction has failed. Please try again.'));
            } elseif ($request->status_id == 2) {
                return redirect()->route('plans.index')->with('error', __('Your transaction is pending.'));
            } elseif ($request->status_id == 1) {
                $assignPlan = $user->assignPlan($plan->id);

                if (!empty($request->coupon_id) && !empty($coupons)) {
                    $userCoupon = new UserCoupon();
                    $userCoupon->user = $user->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
                    $userCoupon->save();

                    $usedCoupun = $coupons->used_coupon();
                    if ($coupons->limit <= $usedCoupun) {
                        $coupons->is_active = 0;
                        $coupons->save();
                    }
                }

                if ($assignPlan['is_success']) {
                    Utility::referralcommisonadd($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan activated successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                }
            }
        } catch (Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function invoicepaywithsspaypay(Request $request)
    {
        $invoice_id = $request->input('invoice_id');
        try {
            $invoice = Bill::find($invoice_id);
            $this->invoiceData = $invoice;

            if (!$invoice) {
                return redirect()->back()->with('error', __('Invoice not found.'));
            }

            $get_amount = $request->amount;
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getCompanyPaymentSetting($user->id);

            if (empty($payment_setting['sspay_secret_key']) || empty($payment_setting['sspay_category_code'])) {
                return redirect()->back()->with('error', __('SSPay configuration is incomplete for this user.'));
            }

            if ($get_amount > $invoice->due_amount) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            }

            if ($get_amount <= 0) {
                return redirect()->back()->with('error', __('Invalid payment amount.'));
            }

            $this->callBackUrl = route('customer.sspay', [$invoice->id, $get_amount]);
            $this->returnUrl = route('customer.sspay', [$invoice->id, $get_amount]);

            $Date = date('d-m-Y');
            $description = !empty($invoice->description) ? $invoice->description : $invoice->title;
            $billName = $invoice->title;
            $billExpiryDays = 3;
            $billExpiryDate = date('d-m-Y', strtotime($Date . ' + 3 days'));
            $billContentEmail = "Thank you for purchasing our product!";

            $some_data = [
                'userSecretKey' => $payment_setting['sspay_secret_key'],
                'categoryCode' => $payment_setting['sspay_category_code'],
                'billName' => $billName,
                'billDescription' => $description,
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => (int)(100 * $get_amount),
                'billReturnUrl' => $this->returnUrl,
                'billCallbackUrl' => $this->callBackUrl,
                'billExternalReferenceNo' => 'AFR341DFI',
                'billTo' => !empty($user->name) ? $user->name : '',
                'billEmail' => !empty($user->email) ? $user->email : '',
                'billPhone' => !empty($user->phone_no) ? $user->phone_no : '0000000000',
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => '0',
                'billContentEmail' => $billContentEmail,
                'billChargeToCustomer' => 1,
                'billExpiryDate' => $billExpiryDate,
                'billExpiryDays' => $billExpiryDays
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://sspay.my/index.php/api/createBill');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($some_data));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($result === false || $httpCode != 200) {
                return redirect()->back()->with('error', __('Failed to connect to payment gateway. Please try again.'));
            }

            $obj = json_decode($result, true);

            if (empty($obj) || !is_array($obj) || !isset($obj[0]['BillCode'])) {
                return redirect()->back()->with('error', __('Invalid response from payment gateway. Please try again.'));
            }

            return redirect('https://sspay.my/' . $obj[0]['BillCode']);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $amount)
    {
        $invoice = Bill::find($invoice_id);
        $this->invoiceData = $invoice;

        if (!$invoice) {
            return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('error', __('Invoice not found.'));
        }

        try {
            if ($request->status_id == 3) {
                return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('error', __('Your transaction has failed. Please try again.'));
            } elseif ($request->status_id == 2) {
                return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('error', __('Your transaction is pending.'));
            } elseif ($request->status_id == 1) {
                $invoice_payment = new BillPayment();
                $invoice_payment->bill_id = $invoice_id;
                $invoice_payment->amount = $amount;
                $invoice_payment->date = date('Y-m-d');
                $invoice_payment->method = 'Sspay';
                $invoice_payment->save();

                $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

                if ($payment >= $invoice->total_amount) {
                    $invoice->status = 'PAID';
                    $invoice->due_amount = 0.00;
                } else {
                    $invoice->status = 'Partialy Paid';
                    $invoice->due_amount = $invoice->due_amount - $amount;
                }
                $invoice->save();

                if (Auth::check()) {
                    return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('success', __('Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __('Payment successfully added.'));
                }
            }
        } catch (Exception $e) {
            if (Auth::check()) {
                return redirect()->route('invoices.show', $invoice_id)->with('error', $e->getMessage());
            } else {
                return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('error', $e->getMessage());
            }
        }
    }
}
