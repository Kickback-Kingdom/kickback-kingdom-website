<?php
declare(strict_types=1);

namespace Kickback\InitializationScripts;

/**
* Turns error reporting on and registers an error handler and shutdown handler.
*
* These handlers ensure that all errors are caught. In addition, it makes all
* errors print a report that is displayed to either stdout (CLI use) or as
* an HTML error message to the client (web use). This diverges from PHP's
* default behavior of printing absolutely nothing in either case, which
* can be very confusing in the web/HTTP case specifically: it would output
* the part of the page that was generated before the fatal error occurred, but
* then silently neglect to send the rest of the page. If the error occurred
* early enough, it sends no output, which browsers seem to render as a blank page.
* These scenarios can look like page just hasn't loaded yet, even though it never will.
*
* This function will allocate the following resources:
* * The global error handler, as set by `set_error_handler`
* * The global shutdown handler, as set by `register_shutdown_function`
* * The output buffer, as set by `ob_start`
*
* @see \set_error_handler
* @see \register_shutdown_function
* @see \ob_start
*/
function initializeErrorHandling(): void
{
    error_reporting(E_ALL);

    set_error_handler("Kickback\InitializationScripts\\fatalErrorHandler");
    register_shutdown_function("Kickback\InitializationScripts\\shutdownHandler");

    // Start output buffering
    // This allows us to discard partial output whenever
    // exceptions interrupt PHP execution.
    ob_start();
}

// TODO: Documentation... what does this function do?
// PHP should have a default error handler, so why use this one instead?
// It would seem that this function is used to convert certain types of errors
// into the \ErrorException, because PHP doesn't make them Exceptions by default.
// Hopefully this can be confirmed or corrected.
/**
* Error handler function
*
* The signature of this function is dictated by the `set_error_handler` function's callback definition.
*
* @see \set_error_handler
*/
function fatalErrorHandler(
    int      $errno,
    string   $errstr,
    ?string  $errfile = null,
    ?int     $errline = null
    /* `array $errcontext` is deprecated in PHP 7.2.0 and removed in 8.0.0 */
) : bool
{
    if (0 === (error_reporting() & $errno)) {
        return false;
    }
    // TODO: BUG: Use of class (\Exception) from within init script. (This is dangerous to do in bootstrapping code.)
    // TODO: We should probably have OOP initialization class(es) that are not part of the bootstrapping process.
    // TODO: ... because everything in this file probably doesn't need to be done during bootstrap.
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}

/**
* Shutdown handler function to catch fatal errors.
*
* This is responsible for printing an HTML report for any errors.
*/
function shutdownHandler() : void
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
