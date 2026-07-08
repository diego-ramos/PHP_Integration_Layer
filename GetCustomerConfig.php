<?php
header('Content-Type: application/json');

// Simple .env file loader
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            // Strip quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

$customerId = isset($_GET['customer_id']) ? trim($_GET['customer_id']) : '';

if (empty($customerId)) {
    echo json_encode(['error' => 'No customer ID provided']);
    exit;
}

$apiBaseUrl = getenv('CLOUD_AI_BRIDGE_API_URL') ? : 'http://localhost:8080';
$apiUrl = rtrim($apiBaseUrl, '/') . '/customer_config/' . urlencode($customerId);
$apiKey = getenv('CLOUD_AI_BRIDGE_API_KEY') ?: 'secret-php-api-key';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = array();
if (!empty($apiKey)) {
    $headers[] = 'Authorization: Bearer ' . $apiKey;
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'CURL Error: ' . $error]);
    exit;
}

if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'API Error (HTTP ' . $httpCode . '): ' . $response]);
    exit;
}

echo $response;
?>
