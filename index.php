<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order PDF Extractor</title>
    <!-- Basic styling for demo -->
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f9; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1 { color: #0056b3; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="file"] { border: 1px solid #ccc; padding: 10px; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { background-color: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #004494; }
        
        #results-container { display: none; margin-top: 30px; border-top: 2px solid #eee; padding-top: 20px; }
        .result-block { margin-bottom: 20px; }
        .result-block h3 { margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }

        /* Low Confidence Styling */
        .low-confidence-alert { 
            background-color: #ffebee; 
            color: #c62828; 
            padding: 15px; 
            border-left: 5px solid #c62828; 
            margin-bottom: 20px; 
            font-weight: bold;
            display: none; /* hidden by default */
        }

        .highlight-unconfident {
            border: 2px dashed #d32f2f !important;
            background-color: #fff8f8;
            position: relative;
        }

        /* Tooltip styling - Only render when parent is unconfident */
        .tooltiptext {
            display: none;
        }

        .highlight-unconfident .tooltiptext {
            display: block;
            visibility: hidden;
            width: 250px;
            background-color: #d32f2f;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -125px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-weight: normal;
        }

        .highlight-unconfident .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #d32f2f transparent transparent transparent;
        }

        .highlight-unconfident:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
            display: none;
            margin-top: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>

<div class="container">
    <h1>Order PDF Extraction System</h1>
    <p>Upload a customer PDF order to extract its structured data.</p>
    
    <form id="upload-form" enctype="multipart/form-data">
        <div class="form-group">
            <label for="customer_id">Customer ID (Optional):</label>
            <input type="text" name="customer_id" id="customer_id" placeholder="e.g. CUST-999" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            <small style="color: #666; display: block; margin-top: 5px;">Used to apply specific instructions for tricky PDF formats.</small>
        </div>
        <div class="form-group" style="margin-top: 15px;">
            <label for="pdf_upload">Select PDF File:</label>
            <input type="file" name="pdf_upload" id="pdf_upload" accept="application/pdf" required>
        </div>
        <button type="submit" id="submit-btn">Extract Data</button>
        <div id="loading" class="loader"></div>
    </form>
    
    <div id="error-message" style="color: red; margin-top: 10px; display: none;"></div>

    <div id="results-container">
        <h2>Extracted Data</h2>
        
        <div id="low-confidence-alert" class="low-confidence-alert">
            [!] WARNING: Low Confidence Score (<span id="confidence-value"></span>). Please review the highlighted fields manually.
            <br>
            <small>AI Reasoning: <span id="reasoning-text"></span></small>
        </div>

        <div class="result-block">
            <h3>Order Details</h3>
            <p><strong>Database Status:</strong> <span id="db-status"></span></p>
            <p id="po-container"><strong>Purchase Order:</strong> <span id="po-val"></span> <span class="tooltiptext">Low Confidence: Value may be incorrect.</span></p>
            <p id="address-container"><strong>Delivery Address:</strong> <span id="address-val"></span> <span class="tooltiptext">Low Confidence: Address may be incomplete.</span></p>
        </div>

        <div class="result-block">
            <h3>Materials</h3>
            <table>
                <thead>
                    <tr>
                        <th>Item Number</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>UOM</th>
                    </tr>
                </thead>
                <tbody id="materials-table-body">
                    <!-- Populated via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submit-btn');
    const loading = document.getElementById('loading');
    const resultsContainer = document.getElementById('results-container');
    const errorMessage = document.getElementById('error-message');

    // Reset UI
    submitBtn.disabled = true;
    loading.style.display = 'inline-block';
    resultsContainer.style.display = 'none';
    errorMessage.style.display = 'none';
    
    // Clear previous highlighting
    document.getElementById('po-container').classList.remove('highlight-unconfident');
    document.getElementById('address-container').classList.remove('highlight-unconfident');
    document.getElementById('low-confidence-alert').style.display = 'none';

    fetch('OrderController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        submitBtn.disabled = false;
        loading.style.display = 'none';

        if (data.error) {
            errorMessage.textContent = data.error;
            errorMessage.style.display = 'block';
            return;
        }

        // Populate Data
        document.getElementById('db-status').textContent = data.db_status || 'N/A';
        document.getElementById('po-val').textContent = data.purchase_order || 'N/A';
        document.getElementById('address-val').textContent = data.delivery_address || 'N/A';

        // Populate Materials Table
        const tbody = document.getElementById('materials-table-body');
        tbody.innerHTML = ''; // clear previous
        
        if (data.materials && data.materials.length > 0) {
            data.materials.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.item_number || ''}</td>
                    <td>${item.description || ''}</td>
                    <td>${item.quantity || ''}</td>
                    <td>${item.unit_of_measure || ''}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4">No materials found.</td></tr>';
        }

        // Logic for "Low Confidence" highlighting
        if (data.confidence_score !== undefined && data.confidence_score < 0.8) {
            document.getElementById('low-confidence-alert').style.display = 'block';
            document.getElementById('confidence-value').textContent = data.confidence_score;
            document.getElementById('reasoning-text').textContent = data.reasoning || 'None provided by the model.';
            
            // Highlight containers that might need attention
            document.getElementById('po-container').classList.add('highlight-unconfident');
            document.getElementById('address-container').classList.add('highlight-unconfident');
            
            // Add highlighting to the table block
            const table = document.querySelector('table');
            table.classList.add('highlight-unconfident');
        } else {
            const table = document.querySelector('table');
            table.classList.remove('highlight-unconfident');
        }

        resultsContainer.style.display = 'block';
    })
    .catch(error => {
        submitBtn.disabled = false;
        loading.style.display = 'none';
        errorMessage.textContent = 'Error processing request. Check console for details. Ensure Flask server is running.';
        errorMessage.style.display = 'block';
        console.error('Error:', error);
    });
});
</script>

</body>
</html>
