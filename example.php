<?php

require_once 'PdfExtractor.php';

// The URL where the Cloud AI Bridge is hosted
// For local testing, this is the default Flask port
$apiUrl = 'http://localhost:8080/extract_pdf';

// Optional API Key (if implemented on backend)
$apiKey = '';

$extractor = new PdfExtractor($apiUrl, $apiKey);

$samplePdfPath = 'sample_order.pdf';

echo "Testing PDF Extraction with Cloud Bridge...\n";

try {
    // 1. You would upload a user's PDF to the server, resulting in a local file path
    // 2. We pass the local file path to the extractor
    
    // For demonstration purposes, we assume 'sample_order.pdf' exists.
    // In your actual app, this is the uploaded file's temp path: $_FILES['pdf']['tmp_name']
    
    if (!file_exists($samplePdfPath)) {
        echo "Note: Create a dummy 'sample_order.pdf' file to test this script locally.\n";
        exit;
    }

    echo "Sending request...\n";
    $result = $extractor->extractData($samplePdfPath);
    
    echo "--- Extraction Successful ---\n";
    echo "Purchase Order: " . (isset($result['purchase_order']) ? $result['purchase_order'] : 'N/A') . "\n";
    echo "Delivery Address: " . (isset($result['delivery_address']) ? $result['delivery_address'] : 'N/A') . "\n";
    
    echo "\nMaterials:\n";
    if (!empty($result['materials'])) {
        foreach ($result['materials'] as $item) {
            echo "- ID: {$item['item_number']} | Desc: {$item['description']} | Qty: {$item['quantity']} {$item['unit_of_measure']}\n";
        }
    } else {
        echo "No materials found.\n";
    }

    echo "\nConfidence Score: " . (isset($result['confidence_score']) ? $result['confidence_score'] : 'N/A') . "\n";
    
    if (isset($result['confidence_score']) && $result['confidence_score'] < 0.8) {
        // UI Logic would run here to flag the issue to the human reviewer
        echo "\n[!] WARNING: Low confidence score. Please review the extracted data.\n";
        echo "AI Reasoning: " . (isset($result['reasoning']) ? $result['reasoning'] : 'None provided.') . "\n";
    }

} catch (Exception $e) {
    echo "Extraction Failed!\nError: " . $e->getMessage() . "\n";
}

?>
