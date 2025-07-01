<?php
// Включение вывода ошибок для диагностики
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Разрешаем кросс-доменные запросы
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=utf-8");

// Получаем параметр канала
$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';

// Проверка параметра канала на безопасность
if (!preg_match('/^[a-z0-9\-_]+\.html$/i', $channel)) {
    http_response_code(400);
    echo "Invalid channel name";
    exit;
}

$url = "https://ip.ontivi.net/$channel";
$content = false;

// Пробуем получить содержимое через file_get_contents
$content = @file_get_contents($url);

// Если не получилось, пробуем через cURL
if ($content === false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($content === false || $httpCode != 200) {
        http_response_code(502);
        echo "Failed to fetch content: ";
        if ($error) {
            echo "cURL Error: $error";
        } else {
            echo "HTTP Status: $httpCode";
        }
        exit;
    }
}

// Возвращаем результат
echo $content;
?>