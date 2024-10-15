<?php
declare(strict_types=1);

namespace Kickback\Common\Utility;

class IDCrypt {
    private $key;
    private $method;

    public function __construct(string $key,string $method = 'AES-256-CBC') {
        // Ensure the key length is suitable for AES-256-CBC
        $this->key = substr(hash('sha256', $key, true), 0, 32);
        $this->method = $method;
    }

    public function encrypt(string $id) : string {
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($id, $this->method, $this->key, 0, $iv);

        // Check for successful encryption
        if ($ciphertext === false) {
            return null;
        }

        return base64_encode($iv . $ciphertext);
    }

    public function decrypt($input) : string {
        $data = base64_decode($input);
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($data, 0, $ivlen);
        $ciphertext = substr($data, $ivlen);

        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, 0, $iv);

        // Check for successful decryption
        if ($plaintext === false) {
            return null;
        }

        return $plaintext;
    }
}


?>
