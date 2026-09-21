<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $connection = get_database_connection();
    $connection->close();
    header('Location: pages/index.html');
    exit;
} catch (RuntimeException $exception) {
    http_response_code(503);
    echo '<h1>Worknest is temporarily unavailable</h1><p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
