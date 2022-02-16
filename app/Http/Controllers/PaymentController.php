<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\Payment;
use App\Models\User; 
use App\Models\Plan;

class PaymentController extends Controller
{
    public function save_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_reference' => 'required',
            'amount_paid' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        } else {

            $user_id = auth()->user()->id;

            $data = [
                'user_id' => $user_id,
                'payment_reference' => $request->payment_reference,
                'payment_type' => $request->payment_type,
                'amount_paid' => $request->amount_paid,
                'payment_status' => $request->payment_status,
            ];

            $payment = Payment::create($data);

            // get user's payment plan
            $plan = Plan::where('name', $request->payment_type)->first();

            $plan_id = $plan->id;
            $asset_limit = $plan->no_of_devices;

            // get user's current asset limit
            $user_current_asset_limit = User::where('id', $user_id)->value('asset_limit');

            $user_new_asset_limit = $user_current_asset_limit + $asset_limit;


            if($payment != null) {
                // update user's asset limit and plan id 
                User::where('id', $user_id)->update(['asset_limit' => $user_new_asset_limit, 'plan_id' => $plan_id]);
                return $this->sendSuccess('Payment successfully created', $payment);
            } else {
                return $this->sendError('Unable to create Payment. Please try again', $payment = []);
            }
        }
    }

    public function get_user_payments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        } else {
            $user_id = $request->user_id;

            $user_exists = User::where('id', $user_id)->exists();

            if($user_exists) {
                $payments = Payment::where('user_id', $user_id)->get();

                if($payments != null) {
                    return $this->sendSuccess('User payments', $payments);
                } else {
                    return $this->sendError('No payment found', $payments = []);
                }
            } else {
                return $this->sendError('User ID does not exist', $payments = []);
            }
        }
    }

    public function create_plan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'amount' => 'required',
            'description' => 'required',
            'account_type' => 'required',
            'no_of_devices' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        } else {

            $plan_name = $request->name;
            $plan_exists = Plan::where('name', $plan_name)->exists();

            if($plan_exists) {
                return $this->sendError('The plan name '. $plan_name. ' already exists', $plan = []);
            }

            $data = [
                'name' => $request->name,
                'amount' => $request->amount,
                'description' => $request->description,
                'account_type' => $request->account_type,
                'no_of_devices' => $request->no_of_devices,
            ];

            $plan = Plan::create($data);

            if($plan != null) {
                return $this->sendSuccess('Payment Plan successfully created', $plan);
            } else {
                return $this->sendError('Unable to create Payment Plan. Please try again', $plan = []);
            }
        }
    }

    public function edit_plan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'amount' => 'required',
            'description' => 'required',
            'account_type' => 'required',
            'no_of_devices' => 'required',
            'plan_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $plan = [
            'name' => $request->name,
            'amount' => $request->amount,
            'description' => $request->description,
            'account_type' => $request->account_type,
            'no_of_devices' => $request->no_of_devices,
        ];

        $plan_id = $request->plan_id;

        $plan_exists = Plan::where('id', $plan_id)->exists();

        if($plan_exists) {
            Plan::where('id', $plan_id)->update($plan);
            return $this->sendSuccess('Plan successfully updated', $plan);
        } else {
            return $this->sendError('Plan ID '.$plan_id.' does not exist', $plan = []);
        }
    }

    public function get_plans()
    {
        $plans = Plan::get();
        return $plans;
    }

    public function delete_plan($plan_id)
    {
        $plan_exists = Plan::where('id', $plan_id)->exists();

        if($plan_exists) {
            Plan::where('id', $plan_id)->delete();
            return $this->sendSuccess('Plan successfully deleted', $plan = []);
        } else {
            return $this->sendError('Plan ID '.$plan_id.' does not exist', $plan = []);
        }
    }

}
