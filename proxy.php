<?php
// Разрешаем кросс-доменные запросы
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/html; charset=utf-8");

// Получаем параметр канала
$channel = $_GET['channel'] ?? 'muzhskoe-kinotv.html';

// Проверяем безопасность имени канала
if (!preg_match('/^[a-z0-9\-_]+\.html$/i', $channel)) {
    http_response_code(400);
    echo "Invalid channel name";
    exit;
}

// Формируем URL
$url = "https://ip.ontivi.net/$channel";

// Настройки запроса
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.6422.165 Mobile Safari/537.36\r\n"
    ]
];

// Выполняем запрос
$context = stream_context_create($options);
$content = @file_get_contents($url, false, $context);

if ($content === false) {
    http_response_code(500);
    echo "Ошибка получения контента";
    exit;
}

// Возвращаем результат
echo $content;
?>