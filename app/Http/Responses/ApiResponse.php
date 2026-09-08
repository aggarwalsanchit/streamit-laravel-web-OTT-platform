<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API Response Class
 * 
 * This class provides a consistent way to format API responses
 * while maintaining the existing response structure used throughout the application.
 */
class ApiResponse
{
    /** @var int Keep Unicode readable in mobile clients (avoid \u00b7-style escapes). */
    private const JSON_ENCODE_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    /**
     * Return a successful JSON response
     * 
     * @param mixed $data The response data (optional)
     * @param string|null $message Success message (optional)
     * @param int $code HTTP status code (default: 200)
     * @param array $additional Additional fields to include in response
     * @return JsonResponse
     */
    public static function success($data = null, ?string $message = null, int $code = 200, array $additional = []): JsonResponse
    {
        $response = [
            'status' => true,
        ];
        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        // Merge any additional fields
        $response = array_merge($response, $additional);

        return response()->json($response, $code, [], self::JSON_ENCODE_OPTIONS);
    }

    /**
     * Return an error JSON response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param mixed $errors Validation errors or additional error data (optional)
     * @param array $additional Additional fields to include in response
     * @return JsonResponse
     */
    public static function error(string $message, int $code = 400, $errors = null, array $additional = []): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // Merge any additional fields
        $response = array_merge($response, $additional);

        return response()->json($response, $code, [], self::JSON_ENCODE_OPTIONS);
    }

    /**
     * Return a JSON response with custom structure
     * Useful for responses that don't fit the standard success/error pattern
     * 
     * @param array $data Response data
     * @param int $code HTTP status code (default: 200)
     * @return JsonResponse
     */
    public static function custom(array $data, int $code = 200): JsonResponse
    {
        return response()->json($data, $code, [], self::JSON_ENCODE_OPTIONS);
    }
}

