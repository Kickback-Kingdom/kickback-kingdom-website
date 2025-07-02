<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>KickbackAPI Wrapper Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { margin: 5px; padding: 10px; }
        #results { margin-top: 20px; padding: 10px; background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>KickbackAPI Wrapper Test</h1>
    
    <div class="test-section">
        <h2>Wrapper Status</h2>
        <button onclick="checkStatus()">Check Status</button>
        <button onclick="testItemAPI()">Test Item API</button>
        <button onclick="testWithInvalidId()">Test Invalid ID</button>
        <button onclick="clearResults()">Clear Results</button>
    </div>

    <div id="results">
        <h3>Results will appear here...</h3>
    </div>

    <!-- Load our wrapper components -->
    <?php include 'item-api.php'; ?>
    <script src="kickback-api-wrapper.js"></script>

    <script>
        function log(message, type = 'info') {
            const results = document.getElementById('results');
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'error' : type === 'success' ? 'success' : 'info';
            results.innerHTML += `<div class="${className}">[${timestamp}] ${message}</div>`;
            console.log(message);
        }

        function clearResults() {
            document.getElementById('results').innerHTML = '<h3>Results cleared...</h3>';
        }

        function checkStatus() {
            try {
                const status = kickbackApi.getStatus();
                log('Wrapper Status: ' + JSON.stringify(status, null, 2), 'success');
                log('ItemAPI available: ' + (typeof ItemAPI !== 'undefined'), 'info');
                log('kickbackApi.item available: ' + (typeof kickbackApi.item !== 'undefined'), 'info');
            } catch (error) {
                log('Error checking status: ' + error.message, 'error');
            }
        }

        async function testItemAPI() {
            try {
                log('Testing ItemAPI.getById(1)...', 'info');
                
                // Test with a likely existing item ID (1 is usually a safe bet)
                const response = await kickbackApi.item.getById(1);
                
                if (response.success) {
                    log('✅ API call successful!', 'success');
                    log('Response: ' + JSON.stringify(response, null, 2), 'success');
                } else {
                    log('❌ API returned success=false: ' + response.message, 'error');
                }
                
            } catch (error) {
                log('❌ API call failed: ' + error.message, 'error');
            }
        }

        async function testWithInvalidId() {
            try {
                log('Testing ItemAPI.getById(99999) (should fail)...', 'info');
                
                const response = await kickbackApi.item.getById(99999);
                log('Unexpected success with invalid ID: ' + JSON.stringify(response), 'error');
                
            } catch (error) {
                log('✅ Expected error with invalid ID: ' + error.message, 'success');
            }
        }

        // Test on page load
        window.addEventListener('load', function() {
            log('Page loaded, testing wrapper...', 'info');
            checkStatus();
        });
    </script>
</body>
</html> 