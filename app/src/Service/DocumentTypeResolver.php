<?php

namespace App\Service;

class DocumentTypeResolver
{
    public function determineTypeDocument(string $document): string
    {
        if (preg_match('/^\d{10}$/', $document) === 1) {
            return 'CED';
        }

        if (preg_match('/^\d{13}$/', $document) === 1) {
            return 'RUC';
        }

        if (preg_match('/^[A-Za-z0-9]{10}$/', $document) === 1) {
            return 'PAS';
        }

        throw new \InvalidArgumentException('Formato de documento inválido. Debe ser cédula (10 dígitos), RUC (13 dígitos) o pasaporte (10 caracteres alfanuméricos).');
    }
}
