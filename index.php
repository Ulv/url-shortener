<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Method "' . $_SERVER['REQUEST_METHOD'] . '" not allowed. Use POST', 400);
}

if ($_SERVER['HTTP_CONTENT_TYPE'] !== 'application/json') {
    errorResponse('Invalid "Content-Type" header: ' . $_SERVER['HTTP_CONTENT_TYPE'] . '. Use "application/json"', 415);
}

if ($_SERVER['HTTP_ACCEPT'] !== 'text/plain') {
    errorResponse('Invalid "Accept" header: ' . $_SERVER['HTTP_ACCEPT'] . '. Use "text/plain"', 415);
}

$requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
if (!array_key_exists('url', $requestBody) || empty($requestBody['url'])) {
    errorResponse('"url" param must be not empty. Error: ' . json_last_error_msg(), 400);
}

if (!($url = filter_var($requestBody['url'], FILTER_VALIDATE_URL))) {
    errorResponse('Invalid url in param!');
}

$redis = new \Redis();
$redis->pconnect('172.16.238.14', 6379);

$urlHash = sha1($url);
if ($resultUrl = $redis->hGet('url:hash', $urlHash)) {
    successResponse(buildUrl($resultUrl));
    exit;
}

$encodedId = encode($redis->incr('url:counter'));
$redis->hSet('url:dict', $urlHash, $url);
$redis->hSet('url:hash', $urlHash, $encodedId);

successResponse(buildUrl($encodedId));

function encode($id)
{
    return base_convert($id, 10, 36);
}

function buildUrl($id)
{
    return sprintf('%s://%s/%s', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $id);
}

function errorResponse(string $error, int $httpCode = 500)
{
    http_response_code($httpCode);
    echo $error;
    exit;
}

function successResponse(string $msg = null)
{
    header('Content-type: text/plain');
    $msg = $msg ?? 'OK';
    echo $msg;
    exit;
}
