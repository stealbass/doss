<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserCoupon;
use App\Models\User;
use App\Models\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class NepalstePaymnetController extends Controller
{
    public function invoiceGetNepalsteCancel(Request $request)
    {
        return redirect()->back()->with('error', __('Transaction has failed'));
    }

    public function invoicePayWithNepalste(Request $request, $invoice_id)
    {
        try {
            $invoice_id = Crypt::decrypt($invoice_id);
            $invoice = Bill::find($invoice_id);

            if (!$invoice) {
                return redirect()->back()->with('error', __('Invoice not found.'));
            }

            $user = User::where('id', $invoice->created_by)->first();
            $get_amount = $request->amount;

            $request->validate(['amount' => 'required|numeric|min:0.01']);

            if ($get_amount > $invoice->due_amount) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            }

            $payment_setting = Utility::getCompanyPaymentSetting($user->id);
            $api_key = isset($payment_setting['nepalste_public_key']) ? $payment_setting['nepalste_public_key'] : '';

            if (empty($api_key)) {
                return redirect()->back()->with('error', __('Nepalste configuration is incomplete. Please check public key.'));
            }

            $parameters = [
                'identifier' => 'DFU80XZIKS',
                'currency' => isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'USD',
                'amount' => number_format($get_amount, 2, '.', ''), // Ensure proper float format
                'details' => 'Invoice #' . $invoice->id,
                'ipn_url' => route('invoice.nepalste.status', [$invoice_id, $get_amount]),
                'cancel_url' => route('invoice.nepalste.cancel'),
                'success_url' => route('invoice.nepalste.status', [$invoice_id, $get_amount]),
                'public_key' => $api_key,
                'site_logo' => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
                'checkout_theme' => 'dark',
                'customer_name' => $user->name ?? '',
                'customer_email' => $user->email ?? '',
            ];

            $url = $payment_setting['nepalste_mode'] == 'live' ? 'https://nepalste.com.np/payment/initiate' : 'https://nepalste.com.np/sandbox/payment/initiate';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Temporarily disable for debugging
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($result === false || !empty($curlError)) {
                return redirect()->back()->with('error', __('Failed to connect to payment gateway: ') . $curlError);
            }

            $resultDecoded = json_decode($result, true);

            if ($httpCode != 200) {
                $errorMessage = isset($resultDecoded['message']) ? $resultDecoded['message'] : 'No error message provided.';
                return redirect()->back()->with('error', __('Payment gateway error (HTTP Code: ') . $httpCode . '): ' . $errorMessage);
            }

            if (empty($resultDecoded) || !is_array($resultDecoded) || !isset($resultDecoded['success']) || !isset($resultDecoded['url'])) {
                return redirect()->back()->with('error', __('Invalid response from payment gateway: ') . (isset($resultDecoded['message']) ? $resultDecoded['message'] : 'No error message provided.'));
            }

            if ($resultDecoded['success']) {
                return redirect($resultDecoded['url']);
            } else {
                return redirect()->back()->with('error', __($resultDecoded['message'] ?? 'Payment initiation failed.'));
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function invoiceGetNepalsteStatus(Request $request, $invoice_id, $getAmount)
    {
        $invoice = Bill::find($invoice_id);

        if (!$invoice) {
            return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('error', __('Invoice not found.'));
        }

        $user = User::where('id', $invoice->created_by)->first();

        try {
            $invoice_payment = new BillPayment();
            $invoice_payment->bill_id = $invoice->id;
            $invoice_payment->txn_id = app('App\Http\Controllers\BillController')->transactionNumber($user->id);
            $invoice_payment->amount = $getAmount;
            $invoice_payment->date = date('Y-m-d');
            $invoice_payment->method = 'Nepalste';
            $invoice_payment->save();

            $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

            if ($payment >= $invoice->total_amount) {
                $invoice->status = 'PAID';
                $invoice->due_amount = 0.00;
            } else {
                $invoice->status = 'Partialy Paid';
                $invoice->due_amount = $invoice->due_amount - $getAmount;
            }
            $invoice->save();

            if (Auth::check()) {
                return redirect()->route('bills.show', $invoice->id)->with('success', __('Payment successfully added'));
            } else {
                return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('success', __('Payment successfully added'));
            }
        } catch (Exception $e) {
            if (Auth::check()) {
                return redirect()->route('bills.show', $invoice->id)->with('error', $e->getMessage());
            } else {
                return redirect()->route('pay.invoice', Crypt::encrypt($invoice_id))->with('error', $e->getMessage());
            }
        }
    }

    public function planPayWithNepalste(Request $request)
    {
        try {
            $authuser = Auth::user();
            $payment_setting = Utility::payment_settings();
            $api_key = isset($payment_setting['nepalste_public_key']) ? $payment_setting['nepalste_public_key'] : '';
            $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';

            if (empty($api_key)) {
                return redirect()->back()->with('error', __('Nepalste configuration is incomplete. Please check public key.'));
            }

            $planID = Crypt::decrypt($request->plan_id);
            $plan = Plan::find($planID);

            if (!$plan) {
                return redirect()->back()->with('error', __('Plan not found.'));
            }

            $plan_amount = $plan->price;
            $coupon_id = null;

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $plan_amount = $plan->price - $discount_value;

                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                    $coupon_id = $coupons->id;
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }

            if ($plan_amount <= 0) {
                $authuser->plan = $plan->id;
                $authuser->save();

                $assignPlan = $authuser->assignPlan($plan->id);
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                if (!empty($coupon_id)) {
                    $coupons = Coupon::find($coupon_id);
                    if ($coupons) {
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

                if ($assignPlan['is_success'] && !empty($plan)) {
                    if (!empty($authuser->payment_subscription_id) && $authuser->payment_subscription_id != '') {
                        try {
                            $authuser->cancel_subscription($authuser->id);
                        } catch (Exception $exception) {
                            // Handle silently
                        }
                    }

                    Order::create([
                        'order_id' => $orderID,
                        'name' => null,
                        'email' => null,
                        'card_number' => null,
                        'card_exp_month' => null,
                        'card_exp_year' => null,
                        'plan_name' => $plan->name,
                        'plan_id' => $plan->id,
                        'price' => $plan_amount ?: 0,
                        'price_currency' => $currency,
                        'txn_id' => '',
                        'payment_type' => 'Nepalste',
                        'payment_status' => 'Succeeded',
                        'receipt' => null,
                        'user_id' => $authuser->id,
                    ]);

                    return redirect()->route('plans.index')->with('success', __('Plan activated successfully'));
                } else {
                    return redirect()->back()->with('error', __($assignPlan['error']));
                }
            }

            $response = [
                'plan_id' => $plan->id,
                'plan_amount' => $plan_amount,
                'coupon' => $coupon_id ? $request->coupon : null,
            ];

            $parameters = [
                'identifier' => 'DFU80XZIKS',
                'currency' => $currency,
                'amount' => number_format($plan_amount, 2, '.', ''), // Ensure proper float format
                'details' => $plan->name,
                'ipn_url' => route('plan.nepalste.status', $response),
                'cancel_url' => route('plan.nepalste.cancel'),
                'success_url' => route('plan.nepalste.status', $response),
                'public_key' => $api_key,
                'site_logo' => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
                'checkout_theme' => 'dark',
                'customer_name' => $authuser->name ?? '',
                'customer_email' => $authuser->email ?? '',
            ];

            $url = $payment_setting['nepalste_mode'] == 'live' ? 'https://nepalste.com.np/payment/initiate' : 'https://nepalste.com.np/sandbox/payment/initiate';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Temporarily disable for debugging
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($result === false || !empty($curlError)) {
                return redirect()->back()->with('error', __('Failed to connect to payment gateway: ') . $curlError);
            }

            $resultDecoded = json_decode($result, true);

            if ($httpCode != 200) {
                $errorMessage = isset($resultDecoded['message']) ? $resultDecoded['message'] : 'No error message provided.';
                // \Log::error('Nepalste API Error: HTTP Code ' . $httpCode . ', Response: ' . $result);
                return redirect()->back()->with('error', __('Payment gateway error (HTTP Code: ') . $httpCode . '): ' . $errorMessage);
            }

            if (empty($resultDecoded) || !is_array($resultDecoded) || !isset($resultDecoded['success']) || !isset($resultDecoded['url'])) {
                return redirect()->back()->with('error', __('Invalid response from payment gateway: ') . (isset($resultDecoded['message']) ? $resultDecoded['message'] : 'No error message provided.'));
            }

            if ($resultDecoded['success']) {
                return redirect($resultDecoded['url']);
            } else {
                return redirect()->back()->with('error', __($resultDecoded['message'] ?? 'Payment initiation failed.'));
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function planGetNepalsteStatus(Request $request)
    {
        try {
            $payment_setting = Utility::payment_settings();
            $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $authuser = Auth::user();
            $plan_id = $request->plan_id;
            $plan = Plan::find($plan_id);
            $getAmount = $request->plan_amount;

            if (!$plan) {
                return redirect()->route('plans.index')->with('error', __('Plan not found.'));
            }

            if (isset($request->coupon) && !empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
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

                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $getAmount = $plan->price - $discount_value;
                }
            }

            $order = new Order();
            $order->order_id = $orderID;
            $order->name = $authuser->name;
            $order->card_number = '';
            $order->card_exp_month = '';
            $order->card_exp_year = '';
            $order->plan_name = $plan->name;
            $order->plan_id = $plan->id;
            $order->price = $getAmount;
            $order->price_currency = $currency;
            $order->txn_id = $orderID;
            $order->payment_type = 'Nepalste';
            $order->payment_status = 'Succeeded';
            $order->receipt = '';
            $order->user_id = $authuser->id;
            $order->save();

            $assignPlan = $authuser->assignPlan($plan->id);

            if ($assignPlan['is_success']) {
                Utility::referralcommisonadd($plan->id);
                return redirect()->route('plans.index')->with('success', __('Plan activated successfully'));
            } else {
                return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
            }
        } catch (Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function planGetNepalsteCancel(Request $request)
    {
        return redirect()->back()->with('error', __('Transaction has failed'));
    }
}