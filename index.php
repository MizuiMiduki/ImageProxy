<?php
declare(strict_types=1);

require_once "util.php";
require_once "config.php";
header("Access-Control-Allow-Origin: {$CORS}");

$src = $_GET["source"] ?? '';
if ($src === '' || filter_var($src, FILTER_VALIDATE_URL) === false || !vaild($src)) {
    err_image(400, $EPATH);
    exit;
}

$UA = $UA ?? "ImageProxy_v1.0.0";
[$body, $info] = CurlGet($src, $UA);

if ($body === null || $info === null || !isset($info["http_code"])) {
    err_image(500, $EPATH);
    exit;
}

$code = (int)$info["http_code"];

if ($code === 200) {
    $mime = get_mime_type($body) ?? "application/octet-stream";
    header("Content-Type: {$mime}");
    echo $body;
    exit;
}

if (in_array($code, $ERROR_RESPONSE, true)) {
    err_image($code, $EPATH);
} else {
    err_image(500, $EPATH);
}
