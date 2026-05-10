<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order PDF Extractor</title>
    <!-- Basic styling for demo -->
    <style>
        body { font-family: sans-serif; margin: 0; padding: 20px; background-color: #f4f4f9; color: #333; height: 100vh; box-sizing: border-box; overflow: hidden; }
        .container { max-width: 1400px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%; box-sizing: border-box; }
        h1 { color: #0056b3; margin-top: 0; margin-bottom: 10px; flex-shrink: 0; }
        p { margin-top: 0; margin-bottom: 15px; flex-shrink: 0; }
        .form-group { margin-bottom: 15px; margin: 0 auto 15px auto;}
        
        #upload-form { margin: 0 auto; }
        .submit-container { text-align: center; margin-top: 20px;}
        
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="file"] { border: 1px solid #ccc; padding: 10px; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { background-color: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #004494; }
        
        #main-split-view { display: none; margin-top: 10px; border-top: 2px solid #eee; padding-top: 15px; display: flex; gap: 20px; flex: 1; min-height: 0; }
        
        #pdf-preview-container { flex: 1; border: 1px solid #ddd; background-color: #f9f9f9; display: flex; flex-direction: column; min-height: 0; }
        #pdf-preview-container h2 { margin-top: 0; padding: 10px; background-color: #eef; border-bottom: 1px solid #ddd;}
        #pdf-preview-iframe { width: 100%; height: 100%; border: none; flex-grow: 1;}
        
        #results-container { flex: 1; display: flex; flex-direction: column; overflow-y: auto; min-height: 0; padding-right: 10px; }
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

        /* Feedback UI Styling */
        .feedback-row { display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
        .feedback-input { 
            display: none; 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ffcc00; 
            background-color: #fffde7;
            border-radius: 4px; 
            box-sizing: border-box; 
            margin-bottom: 15px; 
            margin-top: 5px;
            font-size: 14px;
        }
        .saved-instruction {
            display: none;
            font-size: 13px;
            background-color: #f1f8e9;
            border-left: 4px solid #8bc34a;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 10px;
            color: #33691e;
            border-radius: 0 4px 4px 0;
        }
        #submit-verified-btn { background-color: #28a745; }
        #submit-verified-btn:hover { background-color: #218838; }
        #submit-verified-btn:disabled { background-color: #94d3a2; cursor: not-allowed; }
        
        #re-extract-btn { background-color: #ffc107; color: #333; }
        #re-extract-btn:hover { background-color: #e0a800; }
        #re-extract-btn:disabled { background-color: #ffe8a1; cursor: not-allowed; }

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

        .edit-btn {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 6px 8px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .edit-btn:hover {
            background-color: #004494;
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
    
    <form id="upload-form" enctype="multipart/form-data" style="display: flex; align-items: flex-start; gap: 20px; width: 100%; margin: 0 0 10px 0; flex-shrink: 0;">
        <div class="form-group" style="width: 200px; flex-shrink: 0; margin: 0;">
            <label for="customer_id" style="white-space: nowrap;">Customer ID (Optional):</label>
            <input type="text" name="customer_id" id="customer_id" placeholder="e.g. CUST-999" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            <small style="color: #666; display: block; margin-top: 5px; font-size: 11px; line-height: 1.2;">Used to apply specific instructions for tricky PDF formats.</small>
        </div>
        <div class="form-group" style="flex-grow: 1; margin: 0;">
            <label for="pdf_upload">Select PDF File:</label>
            <input type="file" name="pdf_upload" id="pdf_upload" accept="application/pdf" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; background: #fff;" required>
        </div>
        <div class="submit-container" style="margin: 0; display: flex; align-items: center; padding-top: 22px; flex-shrink: 0;">
            <button type="submit" id="submit-btn" style="margin-top: 0; padding: 10px 20px; white-space: nowrap;">Extract Data</button>
            <div id="loading" class="loader" style="margin: 0 0 0 10px;"></div>
        </div>
    </form>
    
    <div id="error-message" style="color: red; margin-top: 10px; text-align: center; display: none;"></div>

    <!-- Multi-column Layout Container -->
    <div id="main-split-view" style="display: none;">
        
        <!-- Left Side: PDF Preview -->
        <div id="pdf-preview-container">
            <h2>Original Document</h2>
            <iframe id="pdf-preview-iframe" src=""></iframe>
        </div>

        <!-- Right Side: Extraction Results -->
        <div id="results-container">
        <div style="position: sticky; top: 0; background-color: #fff; z-index: 10; padding-bottom: 10px; border-bottom: 2px solid #eee; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">Extracted Data</h2>
                <div style="display: flex; gap: 10px;">
                    <button id="submit-verified-btn" disabled style="margin-top: 0; font-size: 14px; padding: 8px 16px;">Submit Verified Data</button>
                    <button id="re-extract-btn" disabled style="margin-top: 0; font-size: 14px; padding: 8px 16px;">Re-Extract with New Instructions</button>
                </div>
            </div>
            <div id="action-message" style="color: green; margin-top: 10px; font-weight: bold; display: none; text-align: right;"></div>
        </div>
        
        <div id="low-confidence-alert" class="low-confidence-alert">
            [!] WARNING: Low Confidence Score (<span id="confidence-value"></span>). Please review the highlighted fields manually.
            <br>
            <small>AI Reasoning: <span id="reasoning-text"></span></small>
        </div>

        <div class="result-block">
            <h3>Order Details</h3>
            <p><strong>Database Status:</strong> <span id="db-status"></span></p>
            
            <div class="feedback-row" id="po-container">
                <input type="checkbox" id="verify-po" class="verify-checkbox">
                <label for="verify-po" style="margin: 0;"><strong>Purchase Order:</strong></label>
                <span id="po-val"></span> <span class="tooltiptext">Low Confidence: Value may be incorrect.</span>
                <button class="edit-btn" onclick="toggleFeedback('feedback-po')" title="Click to describe where the actual Purchase Order is located in the document.">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                </button>
            </div>
            <div id="saved-po" class="saved-instruction"></div>
            <textarea id="feedback-po" class="feedback-input" placeholder="Please describe where the actual Purchase Order is located in the document."></textarea>
            
            <div class="feedback-row" id="address-container">
                <input type="checkbox" id="verify-address" class="verify-checkbox">
                <label for="verify-address" style="margin: 0;"><strong>Delivery Address:</strong></label>
                <span id="address-val"></span> <span class="tooltiptext">Low Confidence: Address may be incomplete.</span>
                <button class="edit-btn" onclick="toggleFeedback('feedback-address')" title="Click to describe where the correct Delivery Address is located in the document.">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                </button>
            </div>
            <div id="saved-address" class="saved-instruction"></div>
            <textarea id="feedback-address" class="feedback-input" placeholder="Please describe where the correct Delivery Address is located in the document."></textarea>

            <div class="feedback-row" id="postal-container">
                <input type="checkbox" id="verify-postal" class="verify-checkbox">
                <label for="verify-postal" style="margin: 0;"><strong>Postal Code:</strong></label>
                <span id="postal-val"></span> <span class="tooltiptext">Low Confidence: Postal code may be incorrect.</span>
                <button class="edit-btn" onclick="toggleFeedback('feedback-postal')" title="Click to describe where the correct Postal Code is located in the document.">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                </button>
            </div>
            <div id="saved-postal" class="saved-instruction"></div>
            <textarea id="feedback-postal" class="feedback-input" placeholder="Please describe where the correct Postal Code is located in the document."></textarea>
        </div>

        <div class="result-block">
            <div class="feedback-row" style="margin-bottom: 10px;">
                <input type="checkbox" id="verify-materials" class="verify-checkbox">
                <label for="verify-materials" style="margin: 0;"><h3>Materials</h3></label>
                <button class="edit-btn" onclick="toggleFeedback('feedback-materials')" title="Click to describe where the actual materials / items table is located, or what columns to scrape.">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                </button>
            </div>
            <div id="saved-materials" class="saved-instruction"></div>
            <textarea id="feedback-materials" class="feedback-input" placeholder="Please describe where the actual materials / items table is located, or what columns to scrape."></textarea>
            
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
        
    </div> <!-- End Split View -->
</div>

<script>
let currentExtractedData = null;

// Handle checkbox checking and unchecking
document.querySelectorAll('.verify-checkbox').forEach(box => {
    box.addEventListener('change', function() {
        // Toggle the visibility of the corresponding feedback input based on checkbox state
        const targetInputId = this.id.replace('verify-', 'feedback-');
        const feedbackInput = document.getElementById(targetInputId);
        const row = this.closest('.feedback-row');
        const editBtn = row ? row.querySelector('.edit-btn') : null;

        if (this.checked) {
            feedbackInput.style.display = 'none';
            if (editBtn) editBtn.style.display = 'none';
        } else {
            if (editBtn) editBtn.style.display = '';
        }
        
        validateActionButtons();
    });
});

function validateActionButtons() {
    const poChecked = document.getElementById('verify-po').checked;
    const addressChecked = document.getElementById('verify-address').checked;
    const postalChecked = document.getElementById('verify-postal').checked;
    const materialsChecked = document.getElementById('verify-materials').checked;
    
    const allChecked = poChecked && addressChecked && postalChecked && materialsChecked;
    
    // If all are checked, allow submission. If not, require re-extraction.
    document.getElementById('submit-verified-btn').disabled = !allChecked;
    // Re-extract button is enabled if ANY box is unchecked (meaning we need new instructions)
    document.getElementById('re-extract-btn').disabled = allChecked;
}

function triggerExtraction(formData) {
    const submitBtn = document.getElementById('submit-btn');
    const loading = document.getElementById('loading');
    const splitView = document.getElementById('main-split-view');
    const errorMessage = document.getElementById('error-message');
    const actionMessage = document.getElementById('action-message');

    // Reset UI
    submitBtn.disabled = true;
    loading.style.display = 'block';
    errorMessage.style.display = 'none';
    actionMessage.style.display = 'none';
    
    // Clear previous highlighting
    document.getElementById('po-container').classList.remove('highlight-unconfident');
    document.getElementById('address-container').classList.remove('highlight-unconfident');
    document.getElementById('postal-container').classList.remove('highlight-unconfident');
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

        currentExtractedData = data;

        // Reset verification checkboxes to unchecked state so user is forced to re-verify
        document.querySelectorAll('.verify-checkbox').forEach(box => {
            box.checked = false;
        });
        document.querySelectorAll('.feedback-input').forEach(input => {
            input.style.display = 'none';
            input.value = ''; // clear old instructions
        });
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.style.display = ''; // reset edit button visibility
        });
        document.querySelectorAll('.saved-instruction').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });
        validateActionButtons();

        // Populate Data
        document.getElementById('db-status').textContent = data.db_status || 'N/A';
        document.getElementById('po-val').textContent = data.purchase_order || 'N/A';
        document.getElementById('address-val').textContent = data.delivery_address || 'N/A';
        document.getElementById('postal-val').textContent = data.zip_code || 'N/A';

        // Display applied instructions if any
        if (data.applied_instructions) {
            const mappings = [
                { key: 'po', id: 'saved-po' },
                { key: 'address', id: 'saved-address' },
                { key: 'postal', id: 'saved-postal' },
                { key: 'materials', id: 'saved-materials' }
            ];
            mappings.forEach(m => {
                if (data.applied_instructions[m.key]) {
                    const el = document.getElementById(m.id);
                    el.textContent = "Saved Instruction: " + data.applied_instructions[m.key];
                    el.style.display = 'block';
                }
            });
        }

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
            document.getElementById('postal-container').classList.add('highlight-unconfident');
            
            // Add highlighting to the table block
            const table = document.querySelector('table');
            table.classList.add('highlight-unconfident');
        } else {
            const table = document.querySelector('table');
            table.classList.remove('highlight-unconfident');
        }

        // Show the flexbox split layout
        splitView.style.display = 'flex';
    })
    .catch(error => {
        submitBtn.disabled = false;
        loading.style.display = 'none';
        errorMessage.textContent = 'Error processing request. Check console for details. Ensure Flask server is running.';
        errorMessage.style.display = 'block';
        console.error('Error:', error);
    });
}

function toggleFeedback(id) {
    const feedbackInput = document.getElementById(id);
    if (feedbackInput.style.display === 'none' || feedbackInput.style.display === '') {
        feedbackInput.style.display = 'block';
    } else {
        feedbackInput.style.display = 'none';
    }
}

document.getElementById('upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    document.getElementById('main-split-view').style.display = 'none';
    const pdfUploadInput = document.getElementById('pdf_upload');
    
    // Create an object URL from the selected file so we can instantly preview it in the iframe
    if (pdfUploadInput.files && pdfUploadInput.files[0]) {
        const fileURL = window.URL.createObjectURL(pdfUploadInput.files[0]);
        document.getElementById('pdf-preview-iframe').src = fileURL;
    }
    
    const formData = new FormData(this);
    triggerExtraction(formData);
});

// Re-extract Button Logic
document.getElementById('re-extract-btn').addEventListener('click', function() {
    const customerIdEl = document.getElementById('customer_id');
    const customerId = customerIdEl.value.trim();
    
    if (!customerId) {
        alert("You must enter a Customer ID in the upload form to save specific instructions and re-extract!");
        customerIdEl.focus();
        return;
    }

    const poInstr = document.getElementById('feedback-po').value.trim();
    const addressInstr = document.getElementById('feedback-address').value.trim();
    const postalInstr = document.getElementById('feedback-postal').value.trim();
    const materialsInstr = document.getElementById('feedback-materials').value.trim();

    let combinedInstructions = {};
    if (!document.getElementById('verify-po').checked && poInstr) {
        combinedInstructions['po'] = `Purchase Order Location: ${poInstr}`;
    }
    if (!document.getElementById('verify-address').checked && addressInstr) {
        combinedInstructions['address'] = `Delivery Address Location: ${addressInstr}`;
    }
    if (!document.getElementById('verify-postal').checked && postalInstr) {
        combinedInstructions['postal'] = `Postal Code Location: ${postalInstr}`;
    }
    if (!document.getElementById('verify-materials').checked && materialsInstr) {
        combinedInstructions['materials'] = `Materials Table Location: ${materialsInstr}`;
    }

    if (Object.keys(combinedInstructions).length === 0) {
        alert("Please provide text instructions in the boxes below the sections you have left unchecked.");
        return;
    }

    const finalInstructions = JSON.stringify(combinedInstructions);
    
    // Re-submit the form data but append the new instructions
    const form = document.getElementById('upload-form');
    let formData = new FormData(form);
    formData.append('new_instructions', finalInstructions);
    
    triggerExtraction(formData);
});

// Submit Verified Data Logic
document.getElementById('submit-verified-btn').addEventListener('click', function() {
    if (!currentExtractedData) return;
    
    document.getElementById('submit-verified-btn').innerHTML = "Submitting...";
    document.getElementById('submit-verified-btn').disabled = true;
    document.getElementById('re-extract-btn').disabled = true;
    
    fetch('SubmitVerifiedController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(currentExtractedData)
    })
    .then(resp => resp.json())
    .then(data => {
        document.getElementById('submit-verified-btn').innerHTML = "Submit Verified Data";
        
        if (data.status === 'success') {
            const actionMessage = document.getElementById('action-message');
            actionMessage.textContent = data.message;
            actionMessage.style.display = 'block';
            actionMessage.style.color = 'green';
            
            // Optionally clear the UI or provide a completion state here
        } else {
            alert("Error submitting data: " + (data.error || 'Unknown error'));
            validateActionButtons(); // re-enable buttons
        }
    })
    .catch(err => {
        alert("Error submitting data. Check console.");
        console.error(err);
        validateActionButtons(); // re-enable
        document.getElementById('submit-verified-btn').innerHTML = "Submit Verified Data";
    });
});
</script>

</body>
</html>
