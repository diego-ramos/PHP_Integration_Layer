<?php

/**
 * PdfExtractor - A PHP 5.5.12 compatible class for sending PDFs to the Cloud AI Bridge.
 */
class PdfExtractor {
    private $apiUrl;
    private $apiKey;

    /**
     * @param string $apiUrl The URL of the Python Flask Server / Cloud Function (e.g., http://localhost:8080/extract_pdf)
     * @param string $apiKey Optional API Key for authentication if added on the Cloud side.
     */
    public function __construct($apiUrl, $apiKey = '') {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * Extracts data from a local PDF file path.
     * 
     * @param string $pdfFilePath Absolute or relative path to the PDF.
     * @param string|null $customerId Optional customer ID to fetch specific extraction instructions.
     * @param string|null $newInstructions Optional new instructions to save for this customer.
     * @return array The JSON decoded array of the extracted data.
     * @throws Exception If cURL fails or returning non-200.
     */
    public function extractData($pdfFilePath, $customerId = null, $newInstructions = null) {
        if (!file_exists($pdfFilePath)) {
            throw new Exception("PDF file not found at path: " . $pdfFilePath);
        }

        $pdfContent = file_get_contents($pdfFilePath);
        $base64Pdf = base64_encode($pdfContent);

        $payload = array(
            'pdf_base64' => $base64Pdf
        );
        
        if ($customerId) {
            $payload['customer_id'] = $customerId;
        }

        if ($newInstructions) {
            $payload['new_instructions'] = $newInstructions;
        }
        
        $jsonPayload = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonPayload)
        );
        if (!empty($this->apiKey)) {
            // Include a custom authorization header if configured on backend
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Note: For local development with self-signed certs, uncomment below.
        // For production, keep verify peer true.
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }

        if ($httpCode >= 400) {
            throw new Exception("API Error (HTTP " . $httpCode . "): " . $response);
        }

        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null) {
            throw new Exception("Failed to decode JSON from AI Bridge: " . $response);
        }

        return $decodedResponse;
    }
}
