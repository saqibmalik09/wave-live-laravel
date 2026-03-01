<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = [], $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function error($message = 'Error', $statusCode = 400, $data = [])
    {
        return response()->json([
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}