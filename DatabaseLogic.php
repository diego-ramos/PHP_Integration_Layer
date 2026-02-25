<?php
// Mock Database Logic for local testing
// Designed for PHP 5.5.12

class DatabaseLogic {
    // In a real application, inject PDO dependency in constructor
    public function __construct() {
        // Mock DB connection setup
    }

    /**
     * Save the purchase order basic data.
     */
    public function savePurchaseOrder($orderData) {
        // Mocking an INSERT query logic
        $poNumber = isset($orderData['purchase_order']) ? $orderData['purchase_order'] : 'UNKNOWN_PO';
        $address = isset($orderData['delivery_address']) ? $orderData['delivery_address'] : 'UNKNOWN_ADDRESS';
        
        // Return a mock inserted ID
        $insertId = mt_rand(1000, 9999);
        return $insertId;
    }

    /**
     * Save materials associated with the purchase order.
     */
    public function saveOrderMaterials($purchaseOrderId, $materials) {
        $savedCount = 0;
        if (is_array($materials)) {
            foreach ($materials as $item) {
                // Mocking an INSERT query for each item
                $itemNum = isset($item['item_number']) ? $item['item_number'] : '';
                $desc = isset($item['description']) ? $item['description'] : '';
                $qty = isset($item['quantity']) ? $item['quantity'] : 0;
                $uom = isset($item['unit_of_measure']) ? $item['unit_of_measure'] : '';
                
                $savedCount++;
            }
        }
        return $savedCount;
    }
}
?>
