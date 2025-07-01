<?php
// Усиленный прокси с подробными ошибками
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=utf-8");

// Включим вывод всех ошибок для диагностики
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Получаем параметр канала
$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';

// Проверяем безопасность имени канала
if (!preg_match('/^[a-z0-9\-_]+\.html$/i', $channel)) {
    http_response_code(400);
    echo "ERROR: Invalid channel name\n";
    exit;
}

// Формируем URL
$url = "https://ip.ontivi.net/$channel";
echo "DEBUG: Requesting URL: $url\n\n";

// Используем cURL для лучшего контроля
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "CURL ERROR: " . curl_error($ch) . "\n\n";
    rewind($verbose);
    echo "VERBOSE OUTPUT:\n" . stream_get_contents($verbose) . "\n";
    exit;
}

curl_close($ch);

if ($httpCode !== 200) {
    echo "HTTP ERROR: Status $httpCode\n";
    echo "Response: " . substr($response, 0, 1000) . "\n";
    exit;
}

echo $response;
?>