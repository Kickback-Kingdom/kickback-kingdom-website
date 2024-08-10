<?php
declare(strict_types=1);

namespace Kickback\InitializationScripts;

// Function to set error reporting and custom error handler
function initializeErrorHandling(): void
{
    error_reporting(E_ALL);

    set_error_handler("Kickback\InitializationScripts\\fatalErrorHandler");
    register_shutdown_function("Kickback\InitializationScripts\\shutdownHandler");

    // Start output buffering
    ob_start();
}

// Error handler function
function fatalErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

// Shutdown handler function to catch fatal errors
function shutdownHandler()
{
    $error = error_get_last();
    if ($error !== null) {
        // Clean (erase) the output buffer and turn off output buffering
        ob_end_clean();
        // Set HTTP response code to 500 (Internal Server Error)
        http_response_code(500);
        // Display custom error message
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
            <title>Error</title>
            <style>
                body { background-color: #f8f9fa; }
                .container { max-width: 800px; margin-top: 50px; }
                .card { border-color: #dc3545; }
                .card-header { background-color: #dc3545; color: white; }
                .card-body pre { background-color: #f8d7da; padding: 15px; border-radius: 5px; white-space: pre-wrap; overflow-x: auto; }
                .card-body p { margin-bottom: 0; }
                .card-body hr { margin-top: 20px; margin-bottom: 20px; }
            </style>
            <script>
                function toggleWrap(checkbox) {
                    var preElement = document.getElementById('error-message');
                    if (checkbox.checked) {
                        preElement.style.whiteSpace = 'pre-wrap';
                    } else {
                        preElement.style.whiteSpace = 'pre';
                    }
                }
                
                window.onload = function() {
                    var checkbox = document.getElementById('wrap-toggle');
                    toggleWrap(checkbox);
                };
            </script>
        </head>
        <body>
            <div class='container'>
                <div class='card'>
                    <div class='card-header'>
                        <h4 class='card-title'>An error occurred</h4>
                    </div>
                    <div class='card-body'>
                        <div class='form-check'>
                            <input class='form-check-input' type='checkbox' id='wrap-toggle' onclick='toggleWrap(this)' checked>
                            <label class='form-check-label' for='wrap-toggle'>Toggle word wrapping</label>
                        </div>
                        <p><strong>Message:</strong></p>
                        <pre id='error-message'>{$error['message']}</pre>
                        <hr>
                        <p><strong>File:</strong> {$error['file']}</p>
                        <p><strong>Line:</strong> {$error['line']}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    } else {
        // Send the output buffer and turn off output buffering
        ob_end_flush();
    }
}

// Initialize error handling
initializeErrorHandling();
?>
