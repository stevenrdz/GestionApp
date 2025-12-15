<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\ClientRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    // Mensajes
    private const MSG_ORDER_CREATED        = 'Pedido creado correctamente';
    private const MSG_INVALID_DATA         = 'Datos inválidos';
    private const MSG_CLIENT_NOT_FOUND     = 'Cliente no encontrado';
    private const MSG_ORDER_NOT_FOUND      = 'Pedido no encontrado';
    private const MSG_ORDER_COMPLETED      = 'Pedido marcado como completado';
    private const MSG_ORDER_CANCELED       = 'Pedido cancelado';
    private const MSG_ORDER_DELETED        = 'Pedido eliminado correctamente';

    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'api_order_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Crear pedido',
        tags: ['Pedidos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['clientId', 'totalAmount'],
                properties: [
                    new OA\Property(property: 'clientId', type: 'integer', example: 1),
                    new OA\Property(property: 'totalAmount', type: 'number', format: 'float', example: 25.5),
                    new OA\Property(property: 'description', type: 'string', example: 'Pedido de prueba', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: self::MSG_ORDER_CREATED,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_CREATED),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: self::MSG_INVALID_DATA,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_INVALID_DATA),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_CLIENT_NOT_FOUND,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_CLIENT_NOT_FOUND),
                    ]
                )
            ),
        ]
    )]
    public function create(Request $request, ClientRepository $clientRepo): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $violations = $this->validator->validate($payload, new Assert\Collection([
            'clientId'    => [new Assert\NotBlank(), new Assert\Type('integer')],
            'totalAmount' => [new Assert\NotBlank(), new Assert\Positive()],
            'description' => new Assert\Optional([new Assert\Length(max: 255)]),
        ]));

        if ($violations->count() > 0) {
            return $this->json(['message' => self::MSG_INVALID_DATA], 400);
        }

        $client = $clientRepo->find($payload['clientId']);
        if (!$client) {
            return $this->json(['message' => self::MSG_CLIENT_NOT_FOUND], 404);
        }

        $order = new Order();
        $order
            ->setClient($client)
            ->setTotalAmount((string) $payload['totalAmount'])
            ->setDescription($payload['description'] ?? null);

        $this->em->persist($order);
        $this->em->flush();

        return $this->json([
            'id'      => $order->getId(),
            'message' => self::MSG_ORDER_CREATED,
        ], 201);
    }

    #[Route('', name: 'api_order_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Listar pedidos con filtros',
        tags: ['Pedidos']
    )]
    public function index(Request $request, OrderRepository $repo): JsonResponse
    {
        $qb = $repo->createQueryBuilder('o')
            ->join('o.client', 'c')
            ->addSelect('c');

        if ($request->query->get('status')) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $request->query->get('status'));
        }

        if ($request->query->get('clientId')) {
            $qb->andWhere('c.id = :clientId')
               ->setParameter('clientId', $request->query->get('clientId'));
        }

        if ($request->query->get('fromDate')) {
            $qb->andWhere('o.orderDate >= :from')
               ->setParameter('from', new \DateTimeImmutable($request->query->get('fromDate')));
        }

        if ($request->query->get('toDate')) {
            $qb->andWhere('o.orderDate <= :to')
               ->setParameter('to', new \DateTimeImmutable($request->query->get('toDate')));
        }

        $orders = $qb->getQuery()->getResult();

        $data = array_map(fn (Order $o) => [
            'id'          => $o->getId(),
            'clientId'    => $o->getClient()->getId(),
            'status'      => $o->getStatus(),
            'totalAmount' => $o->getTotalAmount(),
            'orderDate'   => $o->getOrderDate()->format('Y-m-d H:i:s'),
        ], $orders);

        return $this->json($data);
    }

    #[Route('/{id}/complete', name: 'api_order_complete', methods: ['POST'])]
    #[OA\Post(
        summary: 'Marcar pedido como completado',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del pedido a completar',
                schema: new OA\Schema(type: 'integer', example: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: self::MSG_ORDER_COMPLETED,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_COMPLETED),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_ORDER_NOT_FOUND,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_NOT_FOUND),
                    ]
                )
            )
        ]
    )]
    public function complete(int $id, OrderRepository $repo): JsonResponse
    {
        $order = $repo->find($id);
        if (!$order) {
            return $this->json(['message' => self::MSG_ORDER_NOT_FOUND], 404);
        }

        $order
            ->setStatus('COMPLETED')
            ->setCompletedAt(new \DateTimeImmutable())
            ->setCanceledAt(null);

        $this->em->flush();

        return $this->json(['message' => self::MSG_ORDER_COMPLETED]);
    }

    #[Route('/{id}/cancel', name: 'api_order_cancel', methods: ['POST'])]
    #[OA\Post(
        summary: 'Cancelar un pedido',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del pedido a cancelar',
                schema: new OA\Schema(type: 'integer', example: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: self::MSG_ORDER_CANCELED,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_CANCELED),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_ORDER_NOT_FOUND,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_NOT_FOUND),
                    ]
                )
            )
        ]
    )]
    public function cancel(int $id, OrderRepository $repo): JsonResponse
    {
        $order = $repo->find($id);
        if (!$order) {
            return $this->json(['message' => self::MSG_ORDER_NOT_FOUND], 404);
        }

        $order
            ->setStatus('CANCELED')
            ->setCanceledAt(new \DateTimeImmutable())
            ->setCompletedAt(null);

        $this->em->flush();

        return $this->json(['message' => self::MSG_ORDER_CANCELED]);
    }

    #[Route('/{id}', name: 'api_order_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Eliminar un pedido',
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del pedido a eliminar',
                schema: new OA\Schema(type: 'integer', example: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: self::MSG_ORDER_DELETED,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_DELETED),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: self::MSG_ORDER_NOT_FOUND,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: self::MSG_ORDER_NOT_FOUND),
                    ]
                )
            )
        ]
    )]
    public function delete(int $id, OrderRepository $repo): JsonResponse
    {
        $order = $repo->find($id);
        if (!$order) {
            return $this->json(['message' => self::MSG_ORDER_NOT_FOUND], 404);
        }

        $this->em->remove($order);
        $this->em->flush();

        return $this->json(['message' => self::MSG_ORDER_DELETED]);
    }
}
