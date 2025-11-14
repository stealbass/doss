<?php

namespace App\Http\Controllers;

use App\Khalti\Khalti;
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
use Illuminate\Support\Facades\Log;

class KhaltiPaymentController extends Controller
{
    public function planPayWithKhalti(Request $request)
    {
        
        try {
            $payment_setting = Utility::payment_settings();
            $user            = Auth::user();
            $currency        = $payment_setting['currency'] ?? '';
            $planID          = Crypt::decrypt($request->plan_id);
            $plan            = Plan::find($planID);

            if (!$plan) {
                return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
            }

            $orderID     = strtoupper(str_replace('.', '', uniqid('', true)));
            $get_amount  = $plan->price;
            $coupon_id   = null;
            
            if (!empty($request->coupon_code)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon_code))->where('is_active', '1')->first();
                if ($coupons) {
                    $usedCoupon     = $coupons->used_coupon();
                    $discount_value = ($get_amount / 100) * $coupons->discount;
                    $get_amount     = $get_amount - $discount_value;
                    
                    if ($get_amount < 0) {
                        $get_amount = $plan->price;
                    }
                    if ($coupons->limit == $usedCoupon) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                    $coupon_id = $coupons->id;

                    if ($get_amount <= 0) {
                        
                        $authuser = Auth::user();
                        $authuser->plan = $plan->id;
                        $authuser->save();

                        $assignPlan = $authuser->assignPlan($plan->id, $request->frequency);
                        if ($assignPlan['is_success']) {
                            if (!empty($authuser->payment_subscription_id)) {
                                try {
                                    $authuser->cancel_subscription($authuser->id);
                                } catch (\Exception $exception) {
                                    Log::error($exception->getMessage());
                                }
                            }

                            // Save coupon usage
                            $userCoupon = new UserCoupon();
                            $userCoupon->user = $authuser->id;
                            $userCoupon->coupon = $coupons->id;
                            $userCoupon->order = $orderID;
                            $userCoupon->save();

                            // Save order details
                            Order::create([
                                'order_id'        => $orderID,
                                'name'            => $authuser->name,
                                'email'           => $authuser->email,
                                'plan_name'       => $plan->name,
                                'plan_id'         => $plan->id,
                                'price'           => $get_amount ?? 0,
                                'price_currency'  => $currency,
                                'txn_id'          => '',
                                'payment_type'    => 'Free',
                                'payment_status'  => 'Success',
                                'user_id'         => $authuser->id,
                            ]);

                            return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                        } else {
                            return redirect()->route('plans.index')->with('error', $assignPlan['error'] ?? 'Something went wrong.');
                        }
                    }
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }

            
            try {
                $secret     = !empty($payment_setting['khalti_secret_key']) ? $payment_setting['khalti_secret_key'] : '';
                
                $amount     = $get_amount;
                return $amount;
            } catch (\Exception $e) {
                \Log::debug($e->getMessage());
                return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
            }

        } catch (\Exception $e) {
            Log::error('Khalti Payment Error: ' . $e->getMessage());
            return redirect()->route('plans.index')->with('error', __('An error occurred during payment.'));
        }
    }
    
    public function planGetKhaltiStatus(Request $request)
    {
        try {
            $planID     = Crypt::decrypt($request->plan_id);
            $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
            $admin_settings = Utility::payment_settings();
            $plan       = Plan::find($planID);
            $user       = User::find(Auth::user()->id);

            if (!$plan) {
                return response()->json(['error' => 'Plan not found'], 404);
            }

            $payload    = $request->payload;
            $secret     = $admin_settings['khalti_secret_key'] ?? '';
            $token      = $payload['token'];
            $amount     = $payload['amount'];
            $khalti     = new Khalti();

            $response   = $khalti->verifyPayment($secret, $token, $amount);

            if ($response['status_code'] == '200') {
                $currency = 'USD'; // Assuming USD currency

                // Create order
                Order::create([
                    'order_id'        => $orderID,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'plan_name'       => $plan->name,
                    'plan_id'         => $plan->id,
                    'price'           => $amount ?? 0,
                    'price_currency'  => $currency,
                    'txn_id'          => '',
                    'payment_type'    => 'Khalti',
                    'payment_status'  => 'Success',
                    'user_id'         => $user->id,
                ]);

                $assignPlan = $user->assignPlan($plan->id, $request->plan_frequency);
                if ($assignPlan['is_success']) {
                    return response()->json(['success' => true, 'message' => 'Payment verified successfully'], 200);
                } else {
                    return response()->json(['error' => 'Plan assignment failed'], 400);
                }
            } else {
                return response()->json(['error' => 'Transaction failed'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Khalti Payment Status Error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while verifying payment'], 500);
        }
    }


    public function invoicePayWithKhalti(Request $request )
    {
        
        try {
            $amount = $request->amount;
            $invoiceID = $request->invoice_id;
            $invoice = Bill::find($invoiceID);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $user = Auth::user();
            $currency = $payment_setting['site_currency'] ?? '';
            
            if (!$invoice) {
                return redirect()->route('pay.invoice')->with('error', __('Invoice not found.'));
            }
            
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            try {
                $secret     = !empty($payment_setting['khalti_secret_key']) ? $payment_setting['khalti_secret_key'] : '';
                return $amount;
            } catch (\Exception $e) {
                \Log::debug($e->getMessage());
                return redirect()->route('pay.invoice', encrypt($invoiceID))->with('error', __('Plan is deleted.'));
            }
        } catch (\Exception $e) {
            Log::error('Khalti Invoice Payment Error: ' . $e->getMessage());
            return redirect()->route('pay.invoice', encrypt($invoiceID))->with('error', __('Plan is deleted.'));
        }
    }
    
    public function invoiceGetKhaltiStatus(Request $request)
    {
        try {
            $invoiceID = $request->invoice_id;
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            $invoice = Bill::find($invoiceID);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $user = User::find(Auth::user()->id);
            $currency = $payment_setting['site_currency'] ?? '';

            if (!$invoice) {
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            $payload = $request->payload;
            $secret = $payment_setting['khalti_secret_key'] ?? '';
            $token = $payload['token'];
            $amount = $payload['amount'];
            $khalti = new Khalti();
            
            $response = $khalti->verifyPayment($secret, $token, $amount);
            $get_amount = $request->amount;
            if ($response['status_code'] == '200') {
                // Create order for the invoice
                
                $invoice_payment = new BillPayment();
                $invoice_payment->bill_id = $invoice->id;
                $invoice_payment->amount = $get_amount;
                $invoice_payment->date = now();
                $invoice_payment->method = __('Khalti');
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
                    $invoice->due_amount = $invoice->due_amount - $get_amount;
                }
                $invoice->save();


                $encryptedinvoice = encrypt($invoiceID);
                
                return response()->json([
                    'success' => true,
                    'message' => __('Payment successfully added.'),
                    'invoice' => $invoice->id,
                ]);
            } else {
                return response()->json(['error' => false, 'message' => __('An error occurred during payment.')], 500);
            }
        } catch (\Exception $e) {
            Log::error('Khalti Invoice Payment Status Error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while verifying payment'], 500);
        }
    }
}
