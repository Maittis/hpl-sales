<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$connection = db();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['event_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$sessionId = isset($input['session_id']) ? substr($input['session_id'], 0, 100) : null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

$rateLimitKey = 'rate_limit_' . md5($ipAddress . ($sessionId ?? ''));
$rateLimitFile = sys_get_temp_dir() . '/' . $rateLimitKey . '.json';

$rateLimit = ['count' => 0, 'time' => time()];
if (file_exists($rateLimitFile)) {
    $rateLimit = json_decode(file_get_contents($rateLimitFile), true) ?: $rateLimit;
}

$window = 60;
$maxRequests = 100;

if (time() - $rateLimit['time'] > $window) {
    $rateLimit = ['count' => 1, 'time' => time()];
} else {
    $rateLimit['count']++;
}

file_put_contents($rateLimitFile, json_encode($rateLimit));

if ($rateLimit['count'] > $maxRequests) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

$eventType = $input['event_type'];
$eventData = isset($input['event_data']) ? json_encode($input['event_data']) : null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

$stmt = $connection->prepare('INSERT INTO analytics (event_type, event_data, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('sssss', $eventType, $eventData, $sessionId, $ipAddress, $userAgent);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save event']);
}
