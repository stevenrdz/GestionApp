<?php

namespace App\Tests\Service;

use App\Service\CryptoException;
use App\Service\CryptoService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\CryptoService
 */
final class CryptoServiceTest extends TestCase
{
    private const VALID_KEY = '8B3F2F9A72E4D6C19EC4B318A7C52F9C8BF3E7126AD9FC9B43F2A16CE9D3A8F1';

    private CryptoService $crypto;

    protected function setUp(): void
    {
        $this->crypto = new CryptoService(self::VALID_KEY);
    }

    public function testEncryptDecryptBasic(): void
    {
        $plaintext = 'hola@test.com';

        $cipher = $this->crypto->encrypt($plaintext);

        $this->assertNotSame($plaintext, $cipher, 'El cifrado no debe ser igual al texto plano.');
        $this->assertNotEmpty($cipher, 'El cifrado no debe ser vacío.');

        $decrypted = $this->crypto->decrypt($cipher);

        $this->assertSame($plaintext, $decrypted, 'El descifrado debe devolver exactamente el texto original.');
    }

    public function testEncryptWithNullReturnsNull(): void
    {
        $result = $this->crypto->encrypt(null);
        $this->assertNull($result, 'Si el texto es null, encrypt debe devolver null.');
    }

    public function testEncryptWithEmptyStringReturnsEmptyString(): void
    {
        $result = $this->crypto->encrypt('');
        $this->assertSame('', $result, 'Si el texto es cadena vacía, encrypt debe devolver cadena vacía.');
    }

    public function testDecryptWithNullReturnsNull(): void
    {
        $result = $this->crypto->decrypt(null);
        $this->assertNull($result, 'Si el valor codificado es null, decrypt debe devolver null.');
    }

    public function testDecryptWithEmptyStringReturnsEmptyString(): void
    {
        $result = $this->crypto->decrypt('');
        $this->assertSame('', $result, 'Si el valor codificado es cadena vacía, decrypt debe devolver cadena vacía.');
    }

    public function testConstructorThrowsExceptionForShortKey(): void
    {
        $this->expectException(CryptoException::class);
        $this->expectExceptionMessage('Encryption key must be at least 32 bytes.');

        new CryptoService('clave-demasiado-corta');
    }

    public function testDecryptThrowsExceptionForInvalidBase64(): void
    {
        $this->expectException(CryptoException::class);
        $this->expectExceptionMessage('Invalid encoded ciphertext');
        $this->crypto->decrypt('@@@esto-no-es-base64@@@');
    }

    public function testDecryptThrowsExceptionForCorruptedCiphertext(): void
    {
        $plaintext = 'texto original';
        $cipher = $this->crypto->encrypt($plaintext);
        $this->assertNotEmpty($cipher);
        $corrupted = substr($cipher, 0, -2) . '==';
        $this->expectException(CryptoException::class);
        $this->crypto->decrypt($corrupted);
    }

    public function testEncryptGeneratesDifferentCipherForSamePlaintext(): void
    {
        $plaintext = 'mismo-texto';
        $cipher1 = $this->crypto->encrypt($plaintext);
        $cipher2 = $this->crypto->encrypt($plaintext);
        $this->assertNotSame($cipher1, $cipher2, 'Dos cifrados del mismo texto deben ser distintos (IV aleatorio).');
        $this->assertSame($plaintext, $this->crypto->decrypt($cipher1));
        $this->assertSame($plaintext, $this->crypto->decrypt($cipher2));
    }
}
