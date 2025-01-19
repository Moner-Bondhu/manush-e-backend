<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Demography;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    public function get_user(Request $request)
    {
        try {
            if(!$request->user())
            {
                return $this->sendError('not_found', __('user.not_found'), 404);
            }

            return $this->sendResponse('fetched', new UserResource($request->user()->load('profiles')));
        }
        catch (\Throwable $th) {
            return $this->sendError('server_error', $th->getMessage() . ' on ' . $th->getLine(), 500);
        }
    }

    public function create_profile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fullName' => 'required',
                'type' => 'required|in:child,parent,teacher',
                'relationType' => 'required|in:child,father,mother,guardian',
                'dob' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'grade' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), '401');
            }

            $user = $request->user();


            $profile = Profile::factory()->create([
                'user_id' => $user->id,
                'full_name' => $request->input('fullName'),
                'type' => $request->input('type'),
                'relation_type' => $request->input('relationType'),
            ]);

            $demography = Demography::factory()->create([
                'profile_id' => $profile->id,
                'gender' => $request->input('gender'),
                'dob' => $request->input('dob'),
                'grade' => $request->input('grade')
            ]);

            return $this->sendResponse('created', new ProfileResource($profile));
        }
        catch (\Throwable $th) {
            return $this->sendError('server_error', $th->getMessage() . ' on ' . $th->getLine(), 500);
        }
    }

    public function complete_onboard(Request $request)
    {
        try {
            $user = $request->user();

            if(!$user->profiles()->where('type', 'child')->whereHas('demography')->exists()){
                return $this->sendError('cannot_onboard', 'No child profile on this user.', 406);
            }

            if(!$user->profiles()->where('type', 'parent')->whereHas('demography')->exists()){
                return $this->sendError('cannot_onboard', 'No parent profile on this user.', 406);
            }

            $user->is_onboarded = 1;
            $user->save();
            return $this->sendResponse('onboarded', 'User has been successfully onboarded!');

        } catch (\Throwable $th) {
            return $this->sendError('server_error', $th->getMessage() . ' on ' . $th->getLine(), 500);
        }
    }
}
