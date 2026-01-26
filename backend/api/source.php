<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/rss_lib.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($id === '' || !preg_match('/^[a-z0-9_]+$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid id.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$path = __DIR__ . '/../storage/cache/feeds/' . $id . '.json';

if (!file_exists($path)) {
    http_response_code(404);
    echo json_encode(['error' => 'No cache for this source yet.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = readJsonFile($path);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Cache read/parse failed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

?>