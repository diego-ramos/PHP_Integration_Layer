<?php
require_once 'PdfExtractor.php';

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

// Check if a file was uploaded successfully
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_upload'])) {
    
    $file = $_FILES['pdf_upload'];
    $customerId = isset($_POST['customer_id']) && trim($_POST['customer_id']) !== '' ? trim($_POST['customer_id']) : null;
    $newInstructions = isset($_POST['new_instructions']) && trim($_POST['new_instructions']) !== '' ? trim($_POST['new_instructions']) : null;
    $rotatePages = isset($_POST['rotate_pages']) ? (int)$_POST['rotate_pages'] : 0;
    
    // Quick validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'File upload error code: ' . $file['error']]);
        exit;
    }

    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileType !== 'pdf') {
        echo json_encode(['error' => 'Only PDF files are allowed.']);
        exit;
    }

    // Configurable endpoint
    $apiBaseUrl = getenv('CLOUD_AI_BRIDGE_API_URL') ? : 'http://localhost:8080/';
    $apiUrl = rtrim($apiBaseUrl, '/') . '/extract_pdf';
    $apiKey = getenv('CLOUD_AI_BRIDGE_API_KEY') ?: 'secret-php-api-key';

    try {
        // Initialize extractor
        $extractor = new PdfExtractor($apiUrl, $apiKey);
        
        // Pass the uploaded tmp file path and optional customer id directly to the extractor
        $result = $extractor->extractData($file['tmp_name'], $customerId, $newInstructions, $rotatePages);

        // Attach some extra metadata for the front-end view
        $result['db_status'] = "Data extracted. Pending verification.";
        
        // Send JSON data back to the view
        header('Content-Type: application/json');
        echo json_encode($result);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process PDF: ' . $e->getMessage()]);
    }
} else {
    // Not a valid POST request
    echo json_encode(['error' => 'Invalid request method or no file uploaded.']);
}
?>
