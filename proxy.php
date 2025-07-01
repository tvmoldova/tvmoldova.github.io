<?php
// Усиленный прокси с диагностикой
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=utf-8");

$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';

if (!preg_match('/^[a-z0-9\-_]+\.html$/i', $channel)) {
    echo "ERROR: Invalid channel name";
    exit;
}

$url = "https://ip.ontivi.net/$channel";

// Используем cURL для лучшего контроля
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "CURL ERROR: " . curl_error($ch);
    exit;
}

curl_close($ch);

if ($httpCode !== 200) {
    echo "HTTP ERROR: Status $httpCode";
    exit;
}

echo $response;
?>