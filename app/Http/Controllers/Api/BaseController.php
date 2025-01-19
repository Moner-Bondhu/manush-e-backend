<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($message, $result)
    {
        $response = [
                'success' => true,
                'data'    => $result,
                'message' => $message,
        ];
        return response()->json(
            $response,
            200,
            [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Charset' => 'utf-8'
            ],
            JSON_UNESCAPED_UNICODE );
    }
/**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
                'success' => false,
                'message' => $error,
        ];
        if(!empty($errorMessages)){
                    $response['data'] = $errorMessages;
        }
        return response()->json($response, $code);
    }
}
