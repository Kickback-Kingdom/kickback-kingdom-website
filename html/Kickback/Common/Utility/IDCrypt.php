<?php
declare(strict_types=1);

namespace Kickback\Common\Utility;

use Kickback\Common\Exceptions\EncryptionException;

class IDCrypt {
    private string $key;
    private string $method;

    public function __construct(string $key,string $method = 'AES-256-CBC') {
        // Ensure the key length is suitable for AES-256-CBC
        $this->key = substr(hash('sha256', $key, true), 0, 32);
        $this->method = $method;
    }

    public function encrypt(string $id) : string
    {
        $ivlen = openssl_cipher_iv_length($this->method);
        if ( $ivlen === false ) {
            throw new EncryptionException('`openssl_cipher_iv_length` returned `false` (failed to get cipher iv length during encryption)');
        }

        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($id, $this->method, $this->key, 0, $iv);

        // Check for successful encryption
        if ($ciphertext === false) {
            throw new EncryptionException('`openssl_encrypt` returned `false` (failed to encrypt data)');
        }

        return base64_encode($iv . $ciphertext);
    }

    public function decrypt(string $input) : string
    {
        $data = base64_decode($input, true);
        if ( $data === false ) {
            throw new EncryptionException('`base64_decode` returned `false` (base64 decoding failed before decryption could commence)');
        }

        $ivlen = openssl_cipher_iv_length($this->method);
        if ( $ivlen === false ) {
            throw new EncryptionException('`openssl_cipher_iv_length` returned `false` (failed to get cipher iv length during decryption)');
        }

        $iv = substr($data, 0, $ivlen);
        $ciphertext = substr($data, $ivlen);

        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, 0, $iv);

        // Check for successful decryption
        if ($plaintext === false) {
            throw new EncryptionException('`openssl_decrypt` returned `false` (failed to decrypt data)');
        }

        return $plaintext;
    }
}


?>
