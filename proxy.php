<?php
// proxy.php

// ===== КОНФИГУРАЦИЯ =====
define('DEBUG_MODE', true);        // Режим отладки (сохраняет логи)
define('LOG_FILE', 'proxy_log.txt'); // Файл лога
define('MAX_LOG_SIZE', 1048576);   // Макс. размер лога (1MB)
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
define('TIMEOUT', 15);             // Таймаут соединения в секундах

// ===== ФУНКЦИИ =====

/**
 * Логирование событий
 */
function logMessage($message, $level = 'INFO') {
    if (!DEBUG_MODE) return;
    
    // Ротация логов
    if (file_exists(LOG_FILE) && filesize(LOG_FILE) > MAX_LOG_SIZE) {
        rename(LOG_FILE, LOG_FILE . '.' . date('Ymd-His'));
    }
    
    $timestamp = date('[Y-m-d H:i:s]');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $level = str_pad($level, 5, ' ', STR_PAD_RIGHT);
    
    $logEntry = "$timestamp $ip $level $message\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

/**
 * Валидация имени канала
 */
function isValidChannel($channel) {
    // Разрешенные символы: буквы, цифры, дефисы, подчеркивания, точки
    if (!preg_match('/^[a-z0-9\-_.]+\.html$/i', $channel)) {
        logMessage("Invalid channel format: $channel", 'ERROR');
        return false;
    }
    
    // Дополнительные проверки при необходимости
    return true;
}

/**
 * Получение контента через cURL
 */
function fetchContent($url) {
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_USERAGENT => USER_AGENT,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive'
        ]
    ]);
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    $duration = round(microtime(true) - $startTime, 3);
    
    // Логирование запроса
    $logData = [
        'url' => $url,
        'http_code' => $info['http_code'],
        'size' => $info['size_download'],
        'time' => $duration,
        'error' => $error
    ];
    
    logMessage("CURL: " . json_encode($logData), $error ? 'ERROR' : 'INFO');
    
    return $error ? false : $response;
}

// ===== ОСНОВНОЙ КОД =====

// Разрешаем кросс-доменные запросы
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=utf-8");

// Получаем параметры
$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';
$debug = isset($_GET['debug']); // Параметр для вывода отладочной информации

// Валидация канала
if (!isValidChannel($channel)) {
    http_response_code(400);
    echo "Invalid channel parameter";
    exit;
}

// Формируем целевой URL
$targetUrl = "https://ip.ontivi.net/$channel";
logMessage("Requesting channel: $targetUrl");

// Выполняем запрос
$response = fetchContent($targetUrl);

// Обработка ошибок
if ($response === false) {
    http_response_code(502);
    echo "Proxy error: Failed to fetch content";
    exit;
}

// Извлекаем HTTP код и тело ответа
$httpCode = substr($response, 0, strpos($response, "\r\n\r\n") + 4);
$body = substr($response, strlen($httpCode));

// Парсим заголовки
$headers = [];
foreach (explode("\r\n", substr($httpCode, 0, strrpos($httpCode, "\r\n"))) as $hdr) {
    if (empty($hdr)) continue;
    $parts = explode(': ', $hdr, 2);
    if (count($parts) === 2) {
        $headers[strtolower($parts[0])] = $parts[1];
    }
}

// Возвращаем результат
if ($debug) {
    // Режим отладки - показываем подробности
    echo "<!DOCTYPE html><html><head><title>Proxy Debug</title></head><body>";
    echo "<h1>Proxy Debug Information</h1>";
    echo "<h2>Request Details</h2>";
    echo "<p><strong>Channel:</strong> $channel</p>";
    echo "<p><strong>Target URL:</strong> <a href='$targetUrl'>$targetUrl</a></p>";
    
    echo "<h2>Response Headers</h2>";
    echo "<pre>";
    print_r($headers);
    echo "</pre>";
    
    echo "<h2>Response Body (first 2000 chars)</h2>";
    echo "<pre>" . htmlspecialchars(substr($body, 0, 2000)) . "...</pre>";
    
    echo "<h2>Proxy Logs</h2>";
    if (file_exists(LOG_FILE)) {
        echo "<pre>" . htmlspecialchars(file_get_contents(LOG_FILE)) . "</pre>";
    } else {
        echo "<p>No log file found</p>";
    }
    
    echo "</body></html>";
} else {
    // Режим работы - возвращаем чистый контент
    foreach ($headers as $name => $value) {
        // Пропускаем проблемные заголовки
        if (in_array($name, ['content-length', 'transfer-encoding', 'connection'])) continue;
        header("$name: $value");
    }
    echo $body;
}