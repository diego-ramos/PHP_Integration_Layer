<?php
require_once 'PdfExtractor.php';
require_once 'DatabaseLogic.php';

// Check if a file was uploaded successfully
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_upload'])) {
    
    $file = $_FILES['pdf_upload'];
    $customerId = isset($_POST['customer_id']) && trim($_POST['customer_id']) !== '' ? trim($_POST['customer_id']) : null;
    $newInstructions = isset($_POST['new_instructions']) && trim($_POST['new_instructions']) !== '' ? trim($_POST['new_instructions']) : null;
    
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
    $apiUrl = 'http://localhost:8080/extract_pdf'; // Adjust port or URL as needed
    $apiKey = getenv('CLOUD_AI_BRIDGE_API_KEY') ?: 'secret-php-api-key'; // Example of picking it up from env or config

    try {
        // Initialize extractor
        $extractor = new PdfExtractor($apiUrl, $apiKey);
        
        // Pass the uploaded tmp file path and optional customer id directly to the extractor
        $result = $extractor->extractData($file['tmp_name'], $customerId, $newInstructions);

        // Mock saving data to our database only if valid
        // Ideally we wouldn't auto-save if confidence is low, but let's assume we store it as 'pending_review'
        $db = new DatabaseLogic();
        $orderId = $db->savePurchaseOrder($result);
        $savedMaterialsCount = $db->saveOrderMaterials($orderId, $result['materials']);

        // Attach some extra metadata for the front-end view
        $result['db_status'] = "Order saved with ID: $orderId. $savedMaterialsCount items saved.";
        
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
