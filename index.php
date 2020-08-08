<?php
ob_start();

const REDIS_HOST = '172.16.238.14';
const REDIS_PORT = 6379;

$redis = new \Redis();
$redis->pconnect(REDIS_HOST, REDIS_PORT);

$shortenedUri = trim($_SERVER['REQUEST_URI'] ?? '/', '/');
if ($shortenedUri) {
    // redirect
    ($_SERVER['REQUEST_METHOD'] ?? []) === 'GET' || err('Method "' . $_SERVER['REQUEST_METHOD'] . '" not allowed. Use GET', 400);

    if ($url = $redis->hGet('u:d', $shortenedUri)) {
        ob_end_flush();
        header('Location: '.$url);
        exit;
    }

    err('Shortened url not found', 400);
} else {
    //encode
    ($_SERVER['REQUEST_METHOD'] ?? []) === 'POST' || err('Method "' . $_SERVER['REQUEST_METHOD'] . '" not allowed. Use POST', 400);
    ($_SERVER['HTTP_CONTENT_TYPE'] ?? []) === 'application/json' || err('Invalid "Content-Type" header: ' . $_SERVER['HTTP_CONTENT_TYPE'] . '. Use "application/json"', 415);
    ($_SERVER['HTTP_ACCEPT'] ?? []) === 'text/plain' || err('Invalid "Accept" header: ' . $_SERVER['HTTP_ACCEPT'] . '. Use "text/plain"', 415);

    $requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!isset($requestBody['url']) || !$requestBody['url']) {
        err('"url" param must be not empty. Error: ' . json_last_error_msg(), 400);
    }

    if (!($url = filter_var($requestBody['url'], FILTER_VALIDATE_URL))) {
        err('Invalid url in request body!');
    }

    $url = htmlentities($url, ENT_QUOTES, 'UTF-8');
    $urlHash = sha1($url);
    if ($resultUrl = $redis->hGet('u:h', $urlHash)) {
        ok(url($resultUrl));
        exit;
    }

    $encodedId = encode($redis->incr('u:c')); // counter
    $redis->hSet('u:d', $encodedId, $url); // dictionary
    $redis->hSet('u:h', $urlHash, $encodedId); // hash

    ok(url($encodedId));
}

function encode($id)
{
    return base_convert($id, 10, 36);
}

function url($id)
{
    return sprintf('%s://%s/%s', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $id);
}

function err(string $error, int $httpCode = 500)
{
    ob_end_flush();
    http_response_code($httpCode);
    die($error);
}

function ok(string $msg = null)
{
    ob_end_flush();
    http_response_code(200);
    header('Content-type: text/plain');
    die($msg ?? 'OK');
}
