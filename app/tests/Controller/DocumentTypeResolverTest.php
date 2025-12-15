<?php

namespace App\Tests\Service;

use App\Service\DocumentTypeResolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DocumentTypeResolverTest extends TestCase
{
    private DocumentTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DocumentTypeResolver();
    }

    public function testCedulaTenDigits(): void
    {
        $this->assertSame('CED', $this->resolver->determineTypeDocument('0912345678'));
    }

    public function testRucThirteenDigits(): void
    {
        $this->assertSame('RUC', $this->resolver->determineTypeDocument('0912345678001'));
    }

    public function testPassportTenAlphanumeric(): void
    {
        $this->assertSame('PAS', $this->resolver->determineTypeDocument('AB12CD34EF'));
    }

    public function testInvalidDocumentThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->determineTypeDocument('ABC');
    }
}
