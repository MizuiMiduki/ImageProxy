<?php
declare(strict_types=1);

function is_allowed_url(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, ['https'], true)) {
        return false;
    }

    return true;
}

function is_safe_host(string $url): bool {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;

    $ip = gethostbyname($host);
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }

    return true;
}

function CurlGet(string $url, string $userAgent): array {

    if (!is_allowed_url($url) || !is_safe_host($url)) {
        throw new RuntimeException("Blocked URL");
    }

    $ch = curl_init($url);

    if (!($ch instanceof CurlHandle)) {
        throw new RuntimeException("curl_init failed");
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER      => true,
        CURLOPT_USERAGENT           => $userAgent,
        CURLOPT_PROTOCOLS           => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS     => CURLPROTO_HTTPS,
        CURLOPT_FOLLOWLOCATION      => true,
        CURLOPT_MAXREDIRS           => 3,
        CURLOPT_SSL_VERIFYPEER      => true,
        CURLOPT_SSL_VERIFYHOST      => 2,
        CURLOPT_TIMEOUT             => 5,
        CURLOPT_CONNECTTIMEOUT      => 2,
        CURLOPT_EXPECT_100_TIMEOUT_MS => 0,
    ]);

    $result = curl_exec($ch);

    if ($result === false) {
        throw new RuntimeException("Curl error");
    }

    $info = curl_getinfo($ch);

    if ($info['http_code'] !== 200) {
        throw new RuntimeException("Invalid HTTP status: " . $info['http_code']);
    }

    return [$result, $info];
}

function get_mime_type(string $data): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    if (is_file($data)) {
        $mime = $finfo->file($data);
    } else {
        $mime = $finfo->buffer($data);
    }

    if (!is_string($mime)) {
        throw new RuntimeException("MIME detect failed");
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
