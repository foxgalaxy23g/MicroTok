<?php
require 'vendor/autoload.php'; // Убедись, что путь к autoload корректный

use Aws\S3\S3Client;

// Конфигурация MinIO
define('S3_BUCKET', ''); // Название бакета
define('S3_REGION', ''); // Можно оставить 'us-east-1' для MinIO
define('S3_ENDPOINT', ''); // Адрес MinIO
define('S3_ACCESS_KEY', ''); // Твой Access Key
define('S3_SECRET_KEY', ''); // Твой Secret Key

// Создаём клиент MinIO (S3-совместимый)
$s3Client = new S3Client([
    'version'     => 'latest',
    'region'      => S3_REGION,
    'endpoint'    => S3_ENDPOINT, // MinIO URL
    'use_path_style_endpoint' => true, // Включает совместимость с MinIO
    'credentials' => [
        'key'    => S3_ACCESS_KEY,
        'secret' => S3_SECRET_KEY,
    ],
]);
?>
