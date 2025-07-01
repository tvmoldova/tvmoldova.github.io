<?php
// proxy.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=utf-8");

// Получаем название канала из запроса
$channel = isset($_GET['channel']) ? $_GET['channel'] : 'muzhskoe-kinotv.html';
$targetUrl = 'https://ip.ontivi.net/' . urlencode($channel);

// Настройки cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Для HTTPS
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Таймаут 10 сек

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch) || $httpCode !== 200) {
    http_response_code(500);
    echo "Ошибка прокси: " . curl_error($ch);
    exit;
}

curl_close($ch);
echo $response;
?>