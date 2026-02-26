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
    
    // For now, return a success response
    echo json_encode(['status' => 'success', 'message' => 'Data successfully verified and submitted!']);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
