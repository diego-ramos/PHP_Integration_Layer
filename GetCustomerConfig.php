<?php
header('Content-Type: application/json');

$customerId = isset($_GET['customer_id']) ? trim($_GET['customer_id']) : '';

if (empty($customerId)) {
    echo json_encode(['error' => 'No customer ID provided']);
    exit;
}

$apiUrl = 'http://localhost:8080/customer_config/' . urlencode($customerId);
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
