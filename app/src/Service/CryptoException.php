<?php

namespace App\Service;

/**
 * Excepción específica para errores de cifrado/descifrado.
 * Esto cumple con la regla de Sonar de no usar excepciones genéricas.
 */
class CryptoException extends \RuntimeException
{
}
