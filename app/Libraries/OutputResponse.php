<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Response;

class OutputResponse
{
    /**
     * Send response
     *
     * @param array|null $data
     * @param array|string $message
     * @param int $status
     * @return array
     */
    public static function sendResponse(?array $data, mixed $message, int $status = Response::HTTP_OK)
    {
        if ($status == Response::HTTP_OK) {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];
        } else {
            $response = [
                'status' => $status,
                'message' => $message,
            ];
        }

        \http_response_code($status);
        \header('Content-Type: application/json');

        echo json_encode($response);
        exit(1);
    }
}
