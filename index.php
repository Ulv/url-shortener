<?php

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    errorResponse('Method ' . $method . ' not allowed. Use POST', 400);
}

$ct = $_SERVER['HTTP_CONTENT_TYPE'] ?? 'text/plain';
if ($ct !== 'application/json') {
    errorResponse('Invalid Content-Type header: '.$ct.'. Use "application/json"', 415);
}

var_dump($_SERVER);

/**
 * @param string $method
 */
function errorResponse(string $error, int $httpCode = 500)
{
    http_response_code($httpCode);
    echo $error;
    exit;
}
