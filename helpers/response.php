<?php
function sendResponse($status, $message, $data, $httpCode)
{
    http_response_code($httpCode);
    header("Content-Type: application/json; charset=utf-8");

    echo json_encode(
        array(
            "status" => $status,
            "message" => $message,
            "data" => $data
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}