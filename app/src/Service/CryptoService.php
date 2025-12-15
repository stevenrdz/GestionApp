<?php

namespace App\Service;

class CryptoService
{
    private const CIPHER         = 'aes-256-gcm';
    private const MIN_KEY_LENGTH = 32;
    private const TAG_LENGTH     = 16;

    private string $key;

    public function __construct(string $key)
    {
        if (strlen($key) < self::MIN_KEY_LENGTH) {
            // Excepción dedicada, no genérica
            throw new CryptoException('Encryption key must be at least 32 bytes.');
        }

        $this->key = $key;
    }

    public function encrypt(?string $plaintext): ?string
    {
        // Guard clause: si no hay texto, devolvemos tal cual
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false || $ivLength <= 0) {
            throw new CryptoException('Invalid IV length for cipher.');
        }

        $iv  = random_bytes($ivLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new CryptoException('Error encrypting data');
        }

        // Estructura: [IV][TAG][CIPHERTEXT]
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return $encoded;
        }

        $data = base64_decode($encoded, true);
        if ($data === false) {
            throw new CryptoException('Invalid encoded ciphertext');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false || $ivLength <= 0) {
            throw new CryptoException('Invalid IV length for cipher.');
        }

        if (strlen($data) <= $ivLength + self::TAG_LENGTH) {
            throw new CryptoException('Encoded data is too short to contain IV, tag and ciphertext.');
        }

        $iv         = substr($data, 0, $ivLength);
        $tag        = substr($data, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($data, $ivLength + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new CryptoException('Error decrypting data');
        }

        return $plaintext;
    }
}
