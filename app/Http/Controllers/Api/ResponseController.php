<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Response;
use Illuminate\Http\Request;

class ResponseController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $question_id)
    {
        //
        try {
            $user = $request->user();
            $question = Question::find($question_id);
            $scale = $question->scale;
            $profile = $user->profiles->where('type', $scale->visible_to)->first();

            $response = Response::where('profile_id', $profile->id)->where('question_id', $question_id)->first();

            if($response){
                $response->option_id = $request->input('option_id');
                $response->text_answer = $request->input('text_answer');
                $response->numeric_answer = $request->input('numeric_answer');
                $response->save();
            } else {
                $response = Response::factory()->create([
                    'profile_id' => $profile->id,
                    'question_id' => $question->id,
                    'option_id' => $request->input('option_id'),
                    'text_answer' => $request->input('text_answer'),
                    'numeric_answer' => $request->input('numeric_answer')
                ]);
            }

            return $this->sendResponse('created', $response);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->sendError('server_error', $th->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Response $response)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Response $response)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Response $response)
    {
        //
    }
}
