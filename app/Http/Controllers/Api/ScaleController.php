<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ScaleResource;
use App\Models\Profile;
use App\Models\Response;
use App\Models\Scale;
use Illuminate\Http\Request;

class ScaleController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $profile)
    {
        try {
            // Fetch all scales visible to the profile
            $scales = Scale::where('visible_to', $profile)->get();
            $user = $request->user();
            $profile_id = $user->profiles->where('type', $profile)->first()->id;

            // Transform the scales collection to include custom data
            $scales = $scales->map(function ($scale) use ($profile_id) {
                // Count total questions in the scale
                $totalQuestions = $scale->questions()->count();

                // Count responses for the profile related to this scale
                $responseCount = Response::where('profile_id', $profile_id)
                ->join('questions', 'responses.question_id', '=', 'questions.id')
                ->where('questions.scale_id', $scale->id)
                ->count();

                // Determine status
                $status = $responseCount < $totalQuestions ? 'incomplete' : 'complete';

                // Return the scale data with custom fields
                return array_merge(
                    $scale->toArray(),
                    [
                        'totalQuestions' => $totalQuestions,
                        'response_count' => $responseCount,
                        'status' => $status,
                    ]
                );
            });

            return $this->sendResponse('fetched', $scales);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), $th->getLine(), 500);
        }
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $scale_id)
    {
        //
        try {
            $scale = new ScaleResource(Scale::find($scale_id)->load('questions.options'));
            return $this->sendResponse('fetched', $scale);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->sendError($th->getMessage(), $th->getLine(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Scale $scale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Scale $scale)
    {
        //
    }
}
