<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillPayment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use App\Models\Utility;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TapPaymentController extends Controller
{
    public function planPayWithTap(Request $request)
    {
        $authUser = Auth::user();
        $paymentSetting = Utility::payment_settings();
        $currency = $paymentSetting['currency'] ?? 'USD';
        $planID = Crypt::decrypt($request->plan_id);
        $plan = Plan::find($planID);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }

        $planAmount = $plan->price;

        // Handle coupon
        if (!empty($request->coupon)) {
            $coupon = Coupon::where('code', strtoupper(trim($request->coupon)))
                ->where('is_active', '1')
                ->first();

            if ($coupon) {
                $usedCoupon = $coupon->used_coupon();
                if ($usedCoupon < $coupon->limit) {
                    $discountValue = ($planAmount / 100) * $coupon->discount;
                    $planAmount -= $discountValue;
                } else {
                    return redirect()->back()->with('error', __('This coupon code has expired.'));
                }
            } else {
                return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
            }
        }

        if ($planAmount <= 0) {
            // Assign the plan to the user for free
            $authUser->plan = $plan->id;
            $authUser->save();
            $authUser->assignPlan($plan->id);
            Order::create([
                'order_id' => $orderID,
                'name' => $authUser->name,
                'email' => $authUser->email,
                'plan_name' => $plan->name,
                'plan_id' => $plan->id,
                'price' => 0,
                'price_currency' => $currency,
                'payment_type' => 'tap',
                'payment_status' => 'Succeeded',
                'user_id' => $authUser->id,
            ]);
            return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
        }


        if (!empty($request->coupon)) {
            $response = ['plan_id' => $plan->id, 'amount' => $planAmount, 'coupon_code' => $coupon->id];
        } else {
            $response = [ 'plan_id' => $plan->id, 'amount' => $planAmount];
        }

        try {
            $TapPay = new \App\Package\Payment(['company_tap_secret_key' => $paymentSetting['company_tap_secret_key']]);
            return $TapPay->charge([
                'amount'        => $planAmount,
                'currency'      => $currency,
                'threeDSecure'  => 'true',
                'description'   => 'test description',
                'statement_descriptor' => 'sample',
                'customer'      => [
                    'first_name'    => \Auth::user()->first_name ?? '',
                    'email'         => \Auth::user()->email ?? '',
                ],
                'source' => [
                    'id' => 'src_card'
                ],
                'post' => [
                    'url' => null
                ],
                'redirect' => [
                    'url' => route('plan.get.tap.status', [
                        'plan_id' => $plan->id,
                        'amount' => $planAmount,
                        'coupon_code' => $request->coupon ?? '',
                    ]),
                ]
            ], true);
        } catch (Exception $e) {
            // dd($e->getMessage());
            \Log::debug($e->getMessage());
            return redirect()->route('plans.index')->with('error', __('Plan is deleted or something went wrong.'));
        }
    }

    public function planGetTapStatus(Request $request, $plan_id)
    {
        $paymentSetting = Utility::payment_settings();
        $currency = $paymentSetting['currency'];
        $plan = Plan::find($plan_id);
        $user = Auth::user();

        if (!$plan) {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }

        // Create an order
        Order::create([
            'order_id' => time(),
            'name' => $user->name,
            'plan_name' => $plan->name,
            'plan_id' => $plan->id,
            'price' => $request->amount,
            'price_currency' => $currency,
            'txn_id' => time(),
            'payment_type' => __('Tap'),
            'payment_status' => 'success',
            'user_id' => $user->id,
        ]);

        $assignPlan = $user->assignPlan($plan->id, $request->payment_frequency ?? '');

        if ($assignPlan['is_success']) {
            return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
        }
    }


    public function invoicePayWithTap(Request $request)
    {
        try {
            $invoice = Bill::find($request->invoice_id);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $get_amount = $request->amount;

            $customer = User::where('id', $invoice->bill_to)->first();
            $currency = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'USD';
            $api_key = isset($payment_setting['company_tap_secret_key']) ? $payment_setting['company_tap_secret_key'] : ''; // Ensure you have the correct API key

            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);
            $TapPay = new \App\Package\Payment(['company_tap_secret_key' => $payment_setting['company_tap_secret_key']]);

            // dd($TapPay);
            // Prepare charge data for Tap
            return $TapPay->charge([
                'amount' => (int) $get_amount,
                'currency' => $currency,
                'threeDSecure' => 'true',
                'description' => 'Invoice Payment for Invoice #' . $invoice->id,
                'statement_descriptor' => 'Invoice Payment',
                'customer' => [
                    'first_name' => $customer->name,
                    'email' => $customer->email,
                ],
                'source' => [
                    'id' => 'src_card' // Ensure this ID comes from your frontend
                ],
                'post' => [
                    'url' => null
                ],
                'redirect' => [
                    'url' => route('invoice.tap.status', [$invoice->id, $get_amount]),
                ],
            ]);


        } catch (Exception $e) {
            return redirect()->route('pay.invoice', encrypt($invoice->id))->with('error', $e->getMessage());
        }
    }


    public function invoiceGetTapStatus(Request $request, $invoice_id, $amount)
    {
        try {
            $invoice = Bill::find($invoice_id);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

            if ($request->has('tap_id')) {
                // Payment is successful
                $invoice_payment = new BillPayment();
                $invoice_payment->bill_id = $invoice->id;
                $invoice_payment->txn_id = $request->tap_id; // You can set the transaction ID if you get it from Tap
                $invoice_payment->amount = $amount;
                $invoice_payment->date = now();
                $invoice_payment->method = __('Tap');
                $invoice_payment->note = $invoice->description;
                $invoice_payment->currency = $currency;
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

                return redirect()->route('pay.invoice', encrypt($invoice_id))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __('Your Payment has failed!'));
            }
        } catch (\Throwable $e) {
            return redirect()->route('pay.invoice', encrypt($invoice_id))->with('error', __($e->getMessage()));
        }
    }


}
