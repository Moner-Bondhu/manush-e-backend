<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Response;
use App\Models\LearningGroup;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            $question = Question::findOrFail($question_id);
            $scale = $question->scale;
            $rules = match ($question->type) {
                'select_one' => ['option_id' => ['required', 'integer', Rule::exists('options', 'id')->where('question_id', $question->id)]],
                'select_multiple' => ['text_answer' => ['required', 'string', 'max:1000']],
                'text' => ['text_answer' => ['required', 'string', 'min:2', 'max:2000']],
                'slider' => ['numeric_answer' => ['required', 'integer', 'between:1,5']],
                default => [],
            };
            $request->validate(array_merge($rules, ['profile_id' => ['nullable', 'integer', 'exists:profiles,id']]));
            $profile = $request->filled('profile_id')
                ? Profile::findOrFail($request->integer('profile_id'))
                : ($user->profiles()->where('type', $scale->visible_to)->first()
                    ?? $user->assignedProfiles()->where('type', $scale->visible_to)->firstOrFail());
            $owns = $user->profiles()->whereKey($profile->id)->exists();
            $assigned = $user->assignedProfiles()->whereKey($profile->id)->exists();
            $teaches = LearningGroup::where('teacher_user_id', $user->id)
                ->whereHas('profiles', fn ($query) => $query->whereKey($profile->id))->exists();
            abort_unless($owns || $assigned || $teaches || $user->user_type === 'admin', 403);
            $role = $user->portal_role ?: ($user->user_type === 'teacher' ? 'teacher' : null);
            abort_unless($user->user_type === 'admin' || $role === $scale->visible_to || ($role === null && $owns), 403);

            $response = Response::where('profile_id', $profile->id)->where('question_id', $question_id)->first();

            if($response){
                $response->option_id = $request->input('option_id');
                $response->text_answer = $request->input('text_answer');
                $response->numeric_answer = $request->input('numeric_answer');
                $response->save();
            } else {
                $response = Response::create([
                    'profile_id' => $profile->id,
                    'question_id' => $question->id,
                    'option_id' => $request->input('option_id'),
                    'text_answer' => $request->input('text_answer'),
                    'numeric_answer' => $request->input('numeric_answer')
                ]);
            }

            return $this->sendResponse('created', $response);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $th) {
            throw $th;
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
