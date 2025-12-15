<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/clients')]
class ClientController extends AbstractController
{
    // Rutas
    private const ROUTE_ID = '/{id}';

    // Mensajes de respuesta
    private const MSG_INVALID_DATA      = 'Datos inválidos';
    private const MSG_CLIENT_NOT_FOUND  = 'Cliente no encontrado';
    private const MSG_CLIENT_CONFLICT   = 'Ya existe un cliente con ese documento';
    private const MSG_CLIENT_CREATED    = 'Cliente creado correctamente';
    private const MSG_CLIENT_UPDATED    = 'Cliente actualizado correctamente';
    private const MSG_CLIENT_DELETED    = 'Cliente eliminado correctamente';

    // Ejemplos (para OpenAPI)
    private const EXAMPLE_FIRST_NAME   = 'Juan';
    private const EXAMPLE_LAST_NAME    = 'Pérez';
    private const EXAMPLE_EMAIL        = 'juan.perez@example.com';
    private const EXAMPLE_PHONE        = '0999999999';
    private const EXAMPLE_NATIONAL_ID  = '1234567890';
    private const EXAMPLE_ADDRESS      = 'Av. Siempre Viva';
    private const EXAMPLE_DOCUMENT     = '0912345678';

    public function __construct(
        private EntityManagerInterface $em,
        private CryptoService $crypto,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_clients_index', methods: ['GET'])]
    #[OA\Get(
        summary: 'Listar clientes',
        tags: ['Clientes'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de clientes obtenido correctamente',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'firstName', type: 'string', example: self::EXAMPLE_FIRST_NAME),
                            new OA\Property(property: 'lastName', type: 'string', example: self::EXAMPLE_LAST_NAME),
                            new OA\Property(property: 'email', type: 'string', example: self::EXAMPLE_EMAIL),
                            new OA\Property(property: 'phone', type: 'string', nullable: true, example: self::EXAMPLE_PHONE),
                            new OA\Property(property: 'nationalId', type: 'string', nullable: true, example: self::EXAMPLE_NATIONAL_ID),
                            new OA\Property(property: 'address', type: 'string', nullable: true, example: self::EXAMPLE_ADDRESS),
                            new OA\Property(property: 'document', type: 'string', example: self::EXAMPLE_DOCUMENT),
                            new OA\Property(property: 'typeDocument', type: 'string', example: 'CED'),
                            new OA\Property(property: 'status', type: 'string', example: 'ACTIVE'),
                            new OA\Property(property: 'createdAt', type: 'string', example: '2025-12-12 10:15:30'),
                            new OA\Property(property: 'updatedAt', type: 'string', nullable: true, example: null),
                        ]
                    )
                )
            )
        ]
    )]
    public function index(ClientRepository $repo): JsonResponse
    {
        $clients = $repo->findAll();

        $data = [];
        foreach ($clients as $c) {
            $data[] = $this->serializeClient($c);
        }

        return $this->json($data);
    }

    #[Route('', name: 'api_clients_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Crear nuevo cliente',
        tags: ['Clientes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['firstName', 'lastName', 'email', 'document'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: self::EXAMPLE_FIRST_NAME),
                    new OA\Property(property: 'lastName', type: 'string', example: self::EXAMPLE_LAST_NAME),
                    new OA\Property(property: 'email', type: 'string', example: self::EXAMPLE_EMAIL),
                    new OA\Property(property: 'document', type: 'string', example: self::EXAMPLE_DOCUMENT),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: self::EXAMPLE_PHONE),
                    new OA\Property(property: 'nationalId', type: 'string', nullable: true, example: self::EXAMPLE_NATIONAL_ID),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: self::EXAMPLE_ADDRESS),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: self::MSG_CLIENT_CREATED,
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_CREATED),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: self::MSG_INVALID_DATA,
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_INVALID_DATA),
                        new OA\Property(
                            property: 'errors',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'field', type: 'string', example: 'email'),
                                    new OA\Property(property: 'message', type: 'string', example: 'Este valor no es un correo electrónico válido.'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Conflicto: ya existe un cliente con el mismo documento',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_CONFLICT),
                    ]
                )
            ),
        ]
    )]
    public function create(Request $request, ClientRepository $repo): JsonResponse
    {
        $status = 201;
        $data   = [];

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            $status = 400;
            $data   = ['message' => self::MSG_INVALID_DATA];
        } else {
            $violations = $this->validator->validate($payload, $this->getCreateConstraints());

            if ($violations->count() > 0) {
                $status = 400;
                $data   = [
                    'message' => self::MSG_INVALID_DATA,
                    'errors'  => $this->formatValidationErrors($violations),
                ];
            } else {
                $document = (string) $payload['document'];

                try {
                    $typeDocument = $this->determineTypeDocument($document);

                    if ($this->existsClientWithDocument($repo, $document)) {
                        $status = 409;
                        $data   = ['message' => self::MSG_CLIENT_CONFLICT];
                    } else {
                        $client = $this->buildClientFromPayload($payload, $document, $typeDocument);

                        $this->em->persist($client);
                        $this->em->flush();

                        $data = [
                            'id'      => $client->getId(),
                            'message' => self::MSG_CLIENT_CREATED,
                        ];
                    }
                } catch (\InvalidArgumentException $e) {
                    $status = 400;
                    $data   = ['message' => $e->getMessage()];
                }
            }
        }

        return $this->json($data, $status);
    }

    #[Route(self::ROUTE_ID, name: 'api_clients_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Detalle de cliente',
        tags: ['Clientes'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle de cliente obtenido correctamente',
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_CLIENT_NOT_FOUND,
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_NOT_FOUND),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id, ClientRepository $repo): JsonResponse
    {
        $client = $repo->find($id);
        if ($client === null) {
            return $this->json(['message' => self::MSG_CLIENT_NOT_FOUND], 404);
        }

        return $this->json($this->serializeClient($client));
    }

    #[Route(self::ROUTE_ID, name: 'api_clients_update', methods: ['PUT'])]
    #[OA\Put(
        summary: 'Editar información de cliente',
        tags: ['Clientes'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: self::EXAMPLE_FIRST_NAME),
                    new OA\Property(property: 'lastName', type: 'string', example: self::EXAMPLE_LAST_NAME),
                    new OA\Property(property: 'email', type: 'string', example: self::EXAMPLE_EMAIL),
                    new OA\Property(property: 'document', type: 'string', example: self::EXAMPLE_DOCUMENT),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: self::EXAMPLE_PHONE),
                    new OA\Property(property: 'nationalId', type: 'string', nullable: true, example: self::EXAMPLE_NATIONAL_ID),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: self::EXAMPLE_ADDRESS),
                    new OA\Property(property: 'status', type: 'string', example: 'ACTIVE'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cliente actualizado correctamente',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_UPDATED),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: self::MSG_INVALID_DATA
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_CLIENT_NOT_FOUND,
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_NOT_FOUND),
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Conflicto: ya existe un cliente con el mismo documento'
            ),
        ]
    )]
    public function update(int $id, Request $request, ClientRepository $repo): JsonResponse
    {
        $status = 200;
        $data   = [];

        $client = $repo->find($id);
        if ($client === null) {
            $status = 404;
            $data   = ['message' => self::MSG_CLIENT_NOT_FOUND];
        } else {
            $payload = json_decode($request->getContent(), true);

            if (!is_array($payload)) {
                $status = 400;
                $data   = ['message' => self::MSG_INVALID_DATA];
            } else {
                $violations = $this->validator->validate($payload, $this->getUpdateConstraints());

                if ($violations->count() > 0) {
                    $status = 400;
                    $data   = [
                        'message' => self::MSG_INVALID_DATA,
                        'errors'  => $this->formatValidationErrors($violations),
                    ];
                } else {
                    try {
                        $this->applyUpdatePayload($client, $payload, $repo);
                        $client->setUpdatedAt(new \DateTimeImmutable());
                        $this->em->flush();

                        $data = ['message' => self::MSG_CLIENT_UPDATED];
                    } catch (\InvalidArgumentException $e) {
                        $status = 400;
                        $data   = ['message' => $e->getMessage()];
                    }
                }
            }
        }

        return $this->json($data, $status);
    }

    #[Route(self::ROUTE_ID, name: 'api_clients_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Eliminar cliente',
        tags: ['Clientes'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cliente eliminado correctamente',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_DELETED),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_CLIENT_NOT_FOUND,
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_NOT_FOUND),
                    ]
                )
            ),
        ]
    )]
    public function delete(int $id, ClientRepository $repo): JsonResponse
    {
        $client = $repo->find($id);
        if ($client === null) {
            return $this->json(['message' => self::MSG_CLIENT_NOT_FOUND], 404);
        }

        $this->em->remove($client);
        $this->em->flush();

        return $this->json(['message' => self::MSG_CLIENT_DELETED]);
    }

    // ======================
    // Métodos privados
    // ======================

    private function getCreateConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'firstName'  => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'lastName'   => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'email'      => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
            'document'   => [new Assert\NotBlank(), new Assert\Length(min: 10, max: 13)],
            'phone'      => new Assert\Optional([new Assert\Length(max: 50)]),
            'nationalId' => new Assert\Optional([new Assert\Length(max: 50)]),
            'address'    => new Assert\Optional([new Assert\Length(max: 255)]),
        ]);
    }

    private function getUpdateConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'firstName'  => new Assert\Optional([new Assert\NotBlank(), new Assert\Length(max: 100)]),
            'lastName'   => new Assert\Optional([new Assert\NotBlank(), new Assert\Length(max: 100)]),
            'email'      => new Assert\Optional([new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)]),
            'document'   => new Assert\Optional([new Assert\NotBlank(), new Assert\Length(min: 10, max: 13)]),
            'phone'      => new Assert\Optional([new Assert\Length(max: 50)]),
            'nationalId' => new Assert\Optional([new Assert\Length(max: 50)]),
            'address'    => new Assert\Optional([new Assert\Length(max: 255)]),
            'status'     => new Assert\Optional([
                new Assert\Choice(choices: ['ACTIVE', 'INACTIVE']),
            ]),
        ]);
    }

    /** @return array<int, array{field:string, message:string}> */
    private function formatValidationErrors(iterable $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field'   => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }

    private function safeEncrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->crypto->encrypt($value);
    }

    private function safeDecrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->crypto->decrypt($value);
    }

    private function serializeClient(Client $client): array
    {
        return [
            'id'           => $client->getId(),
            'firstName'    => $client->getFirstName(),
            'lastName'     => $client->getLastName(),
            'email'        => $this->safeDecrypt($client->getEmailEncrypted()),
            'phone'        => $this->safeDecrypt($client->getPhoneEncrypted()),
            'nationalId'   => $this->safeDecrypt($client->getNationalIdEncrypted()),
            'address'      => $this->safeDecrypt($client->getAddressEncrypted()),
            'document'     => $this->safeDecrypt($client->getDocumentEncrypted()),
            'typeDocument' => $this->safeDecrypt($client->getTypeDocumentEncrypted()),
            'status'       => $client->getStatus(),
            'createdAt'    => $client->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt'    => $client->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function existsClientWithDocument(ClientRepository $repo, string $document): bool
    {
        $existingClients = $repo->findAll();
        foreach ($existingClients as $existing) {
            $existingDoc = $this->safeDecrypt($existing->getDocumentEncrypted());
            if ($existingDoc === $document) {
                return true;
            }
        }

        return false;
    }

    private function applyUpdatePayload(Client $client, array $payload, ClientRepository $repo): void
    {
        if (array_key_exists('firstName', $payload)) {
            $client->setFirstName($payload['firstName']);
        }
        if (array_key_exists('lastName', $payload)) {
            $client->setLastName($payload['lastName']);
        }
        if (array_key_exists('email', $payload)) {
            $client->setEmailEncrypted($this->safeEncrypt($payload['email']));
        }
        if (array_key_exists('phone', $payload)) {
            $client->setPhoneEncrypted($this->safeEncrypt($payload['phone']));
        }
        if (array_key_exists('nationalId', $payload)) {
            $client->setNationalIdEncrypted($this->safeEncrypt($payload['nationalId']));
        }
        if (array_key_exists('address', $payload)) {
            $client->setAddressEncrypted($this->safeEncrypt($payload['address']));
        }
        if (array_key_exists('status', $payload)) {
            $client->setStatus($payload['status']);
        }

        if (array_key_exists('document', $payload)) {
            $newDocument = (string) $payload['document'];

            $typeDocument = $this->determineTypeDocument($newDocument);

            if ($this->existsOtherClientWithDocument($repo, $client, $newDocument)) {
                throw new \InvalidArgumentException(self::MSG_CLIENT_CONFLICT);
            }

            $client->setDocumentEncrypted($this->safeEncrypt($newDocument));
            $client->setTypeDocumentEncrypted($this->safeEncrypt($typeDocument));
        }
    }

    private function existsOtherClientWithDocument(ClientRepository $repo, Client $current, string $document): bool
    {
        $existingClients = $repo->findAll();
        foreach ($existingClients as $existing) {
            if ($existing->getId() === $current->getId()) {
                continue;
            }
            $existingDoc = $this->safeDecrypt($existing->getDocumentEncrypted());
            if ($existingDoc === $document) {
                return true;
            }
        }

        return false;
    }

    private function determineTypeDocument(string $document): string
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

        throw new \InvalidArgumentException(
            'Formato de documento inválido. Debe ser cédula (10 dígitos), RUC (13 dígitos) o pasaporte (10 caracteres alfanuméricos).'
        );
    }

    private function buildClientFromPayload(
        array $payload,
        string $document,
        string $typeDocument
    ): Client {
        $client = new Client();
    
        $client
            ->setFirstName($payload['firstName'])
            ->setLastName($payload['lastName'])
            ->setEmailEncrypted($this->safeEncrypt($payload['email']))
            ->setPhoneEncrypted($this->safeEncrypt($payload['phone'] ?? null))
            ->setNationalIdEncrypted($this->safeEncrypt($payload['nationalId'] ?? null))
            ->setAddressEncrypted($this->safeEncrypt($payload['address'] ?? null))
            ->setDocumentEncrypted($this->safeEncrypt($document))
            ->setTypeDocumentEncrypted($this->safeEncrypt($typeDocument))
            ->setStatus('ACTIVE')
            ->setCreatedAt(new \DateTimeImmutable());
    
        return $client;
    }
    
}
