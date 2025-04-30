<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Return a success response.
     *
     * @param array $data
     * @param string $message
     * @param int $statusCode
     */
    public static function success(array $data = [], string $message = 'Request successful', int $statusCode = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     */
    public static function error(string $message = 'Request failed', int $statusCode = 400, array $errors = [])
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
}
