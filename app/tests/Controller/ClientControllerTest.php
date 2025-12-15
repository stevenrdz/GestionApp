<?php

namespace App\Tests\Controller;

use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ClientControllerTest extends WebTestCase
{
    private KernelBrowser $clientHttp;
    private EntityManagerInterface $em;
    private ClientRepository $clientRepo;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $this->clientHttp = static::createClient();
        $container        = static::getContainer();

        $this->em         = $container->get(EntityManagerInterface::class);
        $this->clientRepo = $container->get(ClientRepository::class);

        // Limpiamos tablas relevantes antes de cada test
        $conn = $this->em->getConnection();
        $conn->executeStatement('DELETE FROM [orders]');
        $conn->executeStatement('DELETE FROM [clients]');
    }

    /**
     * Helper para crear un cliente vía API y devolver status + payload.
     *
     * @return array{status:int,json:array<string,mixed>}
     */
    private function createClientViaApi(array $override = []): array
    {
        $payload = array_merge([
            'firstName'  => 'Juan',
            'lastName'   => 'Pérez',
            'email'      => 'juan.perez@example.com',
            'document'   => '0912345678',
            'phone'      => '0999999999',
            'nationalId' => '1234567890',
            'address'    => 'Av. Siempre Viva',
        ], $override);

        $this->clientHttp->request(
            'POST',
            '/api/clients',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $this->clientHttp->getResponse();

        return [
            'status' => $response->getStatusCode(),
            'json'   => json_decode($response->getContent(), true),
        ];
    }

    public function testIndexEmptyReturns200AndEmptyArray(): void
    {
        $this->clientHttp->request('GET', '/api/clients');

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->clientHttp->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function testCreateInvalidPayloadReturns400(): void
    {
        $payload = ['firstName' => ''];

        $this->clientHttp->request(
            'POST',
            '/api/clients',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($this->clientHttp->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('Datos inválidos', $data['message']);
    }

    public function testCreateValidClientReturns201AndPersistsInDatabase(): void
    {
        $result = $this->createClientViaApi();

        $this->assertSame(201, $result['status']);
        $this->assertArrayHasKey('id', $result['json']);
        $this->assertArrayHasKey('message', $result['json']);
        $this->assertSame('Cliente creado correctamente', $result['json']['message']);

        $clientId = $result['json']['id'];

        $client = $this->clientRepo->find($clientId);
        $this->assertNotNull($client);
        $this->assertSame('Juan', $client->getFirstName());
        $this->assertSame('Pérez', $client->getLastName());
        $this->assertSame('ACTIVE', $client->getStatus());
    }

    public function testCreateDuplicateDocumentReturns409(): void
    {
        $first = $this->createClientViaApi();
        $this->assertSame(201, $first['status']);

        $duplicate = $this->createClientViaApi([
            'email'    => 'otro@example.com',
            'document' => '0912345678',
        ]);

        $this->assertSame(409, $duplicate['status']);
        $this->assertArrayHasKey('message', $duplicate['json']);
        $this->assertSame('Ya existe un cliente con ese documento', $duplicate['json']['message']);
    }

    public function testShowExistingClientReturns200AndClientData(): void
    {
        $created = $this->createClientViaApi();
        $this->assertSame(201, $created['status']);

        $clientId = $created['json']['id'];

        $this->clientHttp->request('GET', '/api/clients/'.$clientId);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->clientHttp->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame($clientId, $data['id']);
        $this->assertSame('Juan', $data['firstName']);
        $this->assertSame('Pérez', $data['lastName']);
        $this->assertSame('CED', $data['typeDocument']);
    }

    public function testShowNonExistingClientReturns404(): void
    {
        $this->clientHttp->request('GET', '/api/clients/999999');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($this->clientHttp->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('Cliente no encontrado', $data['message']);
    }

    public function testDeleteClientRemovesIt(): void
    {
        $created = $this->createClientViaApi();
        $this->assertSame(201, $created['status']);

        $clientId = $created['json']['id'];

        $this->clientHttp->request('DELETE', '/api/clients/'.$clientId);

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->clientHttp->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('Cliente eliminado correctamente', $data['message']);

        $this->clientHttp->request('GET', '/api/clients/'.$clientId);
        $this->assertResponseStatusCodeSame(404);

        $this->assertNull($this->clientRepo->find($clientId));
    }
}
