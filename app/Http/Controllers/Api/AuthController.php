<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\UserResource;
use App\Jobs\SendSms;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function generate_otp(Request $request)
    {
        try{
            $phoneNumber = $request->input('phoneNumber');
            if (strlen($phoneNumber) == 10) {
                $phoneNumber = "0" . $phoneNumber;
            }
            if ($phoneNumber) {
                $validator = Validator::make($request->all(), [
                    'phoneNumber' => 'required|phone'
                ]);
                $user = User::where('phone_number', $phoneNumber)->get()->first();
            } else {
                return $this->sendError('validation_error', __('auth.failed'), 401);
            }

            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 401);
            }

            if (!$user) {
                $user = User::factory()->create([
                    'phone_number' => $phoneNumber,
                ]);
            }

            $otp = $user->get_valid_otp();

            if (!$otp) {
                $otp = Otp::factory()->create([
                    'user_id' => $user->id
                ]);

                if ($user->phone_number) {
                    SendSms::dispatch($user->phone_number, __('auth.otp', ['otp' => $otp->otp]));
                }
            }

            return $this->sendResponse('generated', __('auth.otp_generated'));
        }
        catch (\Throwable $th) {
            return $this->sendError('server_error', $th->getMessage() . ' on ' . $th->getLine(), 500);
        }
    }

    public function login_with_otp(Request $request)
    {
        try {
            $phoneNumber = $request->input('phoneNumber');

            if (strlen($phoneNumber) == 10) {
                $phoneNumber = "0" . $phoneNumber;
            }

            if ($phoneNumber) {
                $validator = Validator::make($request->all(), [
                    'phoneNumber' => 'required|phone'
                ]);
                $user = User::where('phone_number', $phoneNumber)->get()->first();
            } else {
                return $this->sendError('validation_error', __('auth.failed'), 401);
            }
            $validator = Validator::make($request->all(), [
                'otp' => 'required|integer|digits:4'
            ]);

            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), '401');
            }

            if (!$user) {
                return $this->sendError('authentication_error', __('auth.failed'), '404');
            }

            $valid_otp = $user->get_valid_otp();
            if (!$valid_otp) {
                return $this->sendError('authentication_error', __('auth.expired'), 401);
            }

            if (intval($valid_otp->otp) !== intval($request->input('otp'))) {
                $valid_otp->attempts_left = $valid_otp->attempts_left - 1;
                $valid_otp->save();
                return $this->sendError('authentication_error', __('auth.wrong_otp', ['attempt' => $valid_otp->attempts_left]), 401);
            }

            $valid_otp->is_valid = 0;
            $valid_otp->save();
            $user->save();
            $token = $user->createToken("API");
            return $this->sendResponse('authenticated', ['token' => $token->plainTextToken, 'user' => new UserResource($user)]);
        }
        catch (\Throwable $th) {
            return $this->sendError('server_error', $th->getMessage() . ' on ' . $th->getLine(), 500);
        }
    }
}
