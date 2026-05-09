<?php

function api_request($method, $url, $payload = null)
{
    $headers = "Accept: application/json\r\n";

    if ($payload !== null) {
        $headers .= "Content-Type: application/json\r\n";
    }

    $options = [
        "http" => [
            "method" => strtoupper($method),
            "header" => $headers,
            "timeout" => 10,
            "ignore_errors" => true,
        ]
    ];

    if ($payload !== null) {
        $options["http"]["content"] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $context = stream_context_create($options);
    $rawResponse = @file_get_contents($url, false, $context);

    if ($rawResponse === false) {
        return [
            "ok" => false,
            "status" => 0,
            "data" => null,
            "error" => "No se pudo conectar con la API.",
        ];
    }

    $statusCode = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $statusCode = (int)$matches[1];
    }

    $decoded = json_decode($rawResponse, true);

    return [
        "ok" => $statusCode >= 200 && $statusCode < 300,
        "status" => $statusCode,
        "data" => is_array($decoded) ? $decoded : null,
        "raw" => $rawResponse,
        "error" => $statusCode >= 200 && $statusCode < 300 ? null : "La API devolvio un error ({$statusCode}).",
    ];
}

function api_get_account($personId)
{
    $url = "http://localhost:8083/api/account/" . urlencode($personId);
    return api_request("GET", $url);
}

function api_deduct_account($personId, $amount, $reason)
{
    $url = "http://localhost:8083/api/account/" . urlencode($personId) . "/deduct";

    $payload = [
        "amount" => (float)$amount,
        "reason" => $reason,
    ];

    return api_request("POST", $url, $payload);
}
