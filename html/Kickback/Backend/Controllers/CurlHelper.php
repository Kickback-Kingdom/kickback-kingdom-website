<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

trait CurlHelper
{
    /**
     * Apply a CA certificate bundle to a cURL handle.
     *
     * @param resource $ch cURL handle
     */
    private static function applyCaBundle($ch) : void
    {
        foreach ([
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem',
            'C:/xampp/apache/bin/curl-ca-bundle.crt',
        ] as $path) {
            if (is_string($path) && $path !== '' && is_readable($path)) {
                curl_setopt($ch, CURLOPT_CAINFO, $path);
                break;
            }
        }
    }
}
