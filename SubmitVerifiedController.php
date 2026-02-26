<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Receive JSON body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if data is valid
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing JSON payload']);
        exit;
    }
    
    // In a real application, you'd process/save $data into your internal database here
    require_once 'DatabaseLogic.php';
    $db = new DatabaseLogic();
    $orderId = $db->savePurchaseOrder($data);
    $savedMaterialsCount = $db->saveOrderMaterials($orderId, isset($data['materials']) ? $data['materials'] : array());
    
    // Return a success response with the new ID
    echo json_encode([
        'status' => 'success', 
        'message' => "Data successfully verified and saved! Order ID: $orderId ($savedMaterialsCount items)."
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
