<?php
declare(strict_types=1);

require_once(($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . "/../../../..") . "/Kickback/init.php");

use Kickback\Common\Exceptions\ThrowableOverrides;
use Kickback\Backend\Models\Response;
use Kickback\Services\ApiV2\Endpoint;
use Kickback\Services\Session;
use Kickback\Services\StoreService;

\header('Content-Type: application/json');

Endpoint::begin();
try
{
    $sessionAccount = Endpoint::requireAccountSession();
    $request_contents_json = Endpoint::file_get_contents('php://input');
    $response = null;
    $response_code = StoreService::get_cart_for_account($sessionAccount, $request_contents_json, $response);
    if ( $response_code !== 0 ) {
        \http_response_code($response_code);
        // Otherwise let PHP/Apache/HTTPD respond with what it feels is appropriate.
    }
}
catch( \Throwable $e )
{
    $code = ThrowableOverrides::code($e);
    $code = ($code === 0) ? 500 : $code;
    \http_response_code($code);
    $response = new Response(false,
        'Failed to get cart: ' . ThrowableOverrides::message($e),
        $e->__toString());
}
finally {
    Endpoint::end();
}

echo \json_encode($response);
