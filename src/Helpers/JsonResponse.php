<?php

class JsonResponse
{
    public static function success(mixed $data = null, string $message = 'Berhasil', int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }

    public static function error(string $message, int $statusCode = 400): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => null
        ]);
        exit;
    }
}