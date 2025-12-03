<?php
declare(strict_types=1);

function CurlGet(string $url, string $userAgent): array {
    $ch = curl_init($url);

    if (!($ch instanceof CurlHandle)) {
        throw new RuntimeException("curl_init failed");
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => $userAgent,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $result = curl_exec($ch);
    if ($result === false) {
        throw new RuntimeException("CURL error: " . curl_error($ch));
    }

    return [$result, curl_getinfo($ch)];
}

function get_mime_type(string $data): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    if (is_file($data)) {
        $mime = $finfo->file($data);
    } else {
        $mime = $finfo->buffer($data);
    }

    if (!is_string($mime)) {
        throw new RuntimeException("Failed to detect MIME type");
    }

    return $mime;
}

function valid(string $text): bool {
    return filter_var($text, FILTER_VALIDATE_URL) !== false;
}

function err_image(int $code, string $basePath): void {
    http_response_code($code);

    $file = rtrim($basePath, "/") . "/{$code}.png";

    if (!is_file($file)) {
        exit;
    }

    header("Content-Type: image/png");
    header("Content-Length: " . filesize($file));

    readfile($file);
    exit;
}
