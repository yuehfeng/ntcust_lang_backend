<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class BaseController extends Controller
{
    public function sendResponse($data, $parameter)
    {
        $response = [
            'status' => true,
            'feedback' => '',
            'data' => $data,
            'parameter' => $parameter,
        ];

        return response()->json($response, 200);
    }

    public function sendErrorResponse($code, $msg, $data, $parameter)
    {
        $response = [
            'status' => false,
            'feedback' => $msg,
            'data' => $data,
            'parameter' => $parameter,
        ];

        return response()->json($response, $code);
    }
}
