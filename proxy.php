<?php
// proxy.php

// Конфигурация
define('DEBUG_MODE', true);
define('LOG_FILE', 'proxy_log.txt');
define('USER_AGENT', 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36');
define('TIMEOUT', 15);

// Функция логирования
function logMessage($message) {
    if (!DEBUG_MODE) return;
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = "$timestamp $message\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

// Основной код
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=utf-8");

// Получаем параметр канала
$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';

// Валидация канала
if (!preg_match('/^[a-z0-9\-_]+\.html$/i', $channel)) {
    http_response_code(400);
    echo "Invalid channel parameter";
    exit;
}

// Формируем URL
$targetUrl = "https://ip.ontivi.net/$channel";
logMessage("Requesting: $targetUrl");

// Инициализируем cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => TIMEOUT,
    CURLOPT_USERAGENT => USER_AGENT,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Обработка ошибок
if ($error || $httpCode !== 200) {
    http_response_code(502);
    echo "Proxy error: " . ($error ?: "HTTP $httpCode");
    logMessage("ERROR: $error (HTTP $httpCode)");
    exit;
}

// Возвращаем результат
echo $response;
logMessage("SUCCESS: 200 OK");
?>