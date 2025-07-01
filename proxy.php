<?php
header("Access-Control-Allow-Origin: *");
$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';

// Проверка параметра канала на безопасность
if (!preg_match('/^[a-z0-9\-_]+\.html$/i', $channel)) {
    http_response_code(400);
    echo "Invalid channel name";
    exit;
}

$url = "https://ip.ontivi.net/$channel";

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
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        http_response_code(502);
        echo "Failed to fetch content (HTTP $httpCode)";
        exit;
    }
}

echo $content;
?>