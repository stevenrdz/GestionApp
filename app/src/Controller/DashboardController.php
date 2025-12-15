<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dashboard')]
class DashboardController extends AbstractController
{
    private const DQL_COUNT_ID = 'COUNT(o.id)';

    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository
    ) {
    }

    #[Route('', name: 'api_dashboard_stats', methods: ['GET'])]
    #[OA\Get(
        summary: 'Dashboard de estadísticas',
        description: 'Devuelve métricas globales de pedidos, clientes activos y actividad diaria/mensual.',
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estadísticas generales de pedidos y clientes',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'totalOrders', type: 'integer', example: 120),
                        new OA\Property(property: 'completedOrders', type: 'integer', example: 80),
                        new OA\Property(property: 'pendingOrders', type: 'integer', example: 30),
                        new OA\Property(property: 'activeClients', type: 'integer', example: 45),
                        new OA\Property(
                            property: 'dailyActivity',
                            type: 'array',
                            description: 'Actividad por día (últimos 30 días)',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2025-12-01'),
                                    new OA\Property(property: 'total', type: 'integer', example: 5),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'monthlyActivity',
                            type: 'array',
                            description: 'Actividad por mes (últimos 12 meses)',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'month', type: 'string', example: '2025-12'),
                                    new OA\Property(property: 'total', type: 'integer', example: 70),
                                ]
                            )
                        ),
                    ]
                )
            )
        ]
    )]
    public function stats(): JsonResponse
    {
        [$totalOrders, $completedOrders, $pendingOrders, $activeClients] = $this->getOrderTotals();
        [$dailyActivity, $monthlyActivity] = $this->getOrderActivity();

        return $this->json([
            'totalOrders'     => $totalOrders,
            'completedOrders' => $completedOrders,
            'pendingOrders'   => $pendingOrders,
            'activeClients'   => $activeClients,
            'dailyActivity'   => $dailyActivity,
            'monthlyActivity' => $monthlyActivity,
        ]);
    }

    private function getOrderTotals(): array
    {
        $totalOrders = (int) $this->orderRepository
            ->createQueryBuilder('o')
            ->select(self::DQL_COUNT_ID)
            ->getQuery()
            ->getSingleScalarResult();

        $completedOrders = (int) $this->orderRepository
            ->createQueryBuilder('o')
            ->select(self::DQL_COUNT_ID)
            ->where('o.status = :status')
            ->setParameter('status', 'COMPLETED')
            ->getQuery()
            ->getSingleScalarResult();

        $pendingOrders = (int) $this->orderRepository
            ->createQueryBuilder('o')
            ->select(self::DQL_COUNT_ID)
            ->where('o.status = :status')
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getSingleScalarResult();

        $activeClients = (int) $this->orderRepository
            ->createQueryBuilder('o')
            ->select('COUNT(DISTINCT c.id)')
            ->join('o.client', 'c')
            ->getQuery()
            ->getSingleScalarResult();

        return [$totalOrders, $completedOrders, $pendingOrders, $activeClients];
    }

    private function getOrderActivity(): array
    {
        $now  = new \DateTimeImmutable('now');
        $conn = $this->em->getConnection();

        // Actividad diaria (últimos 30 días)
        $fromDaily    = $now->modify('-29 days')->setTime(0, 0);
        $fromDailyStr = $fromDaily->format('Y-m-d H:i:s');

        $sqlDaily = "
            SELECT
                CONVERT(date, order_date) AS [date],
                COUNT(id) AS total
            FROM [orders]
            WHERE order_date >= :from
            GROUP BY CONVERT(date, order_date)
            ORDER BY CONVERT(date, order_date) ASC
        ";

        $dailyRaw = $conn->executeQuery($sqlDaily, ['from' => $fromDailyStr])->fetchAllAssociative();

        $dailyActivity = array_map(
            static function (array $row): array {
                return [
                    'date'  => (string) $row['date'],
                    'total' => (int) $row['total'],
                ];
            },
            $dailyRaw
        );

        // Actividad mensual (últimos 12 meses)
        $fromMonthly    = $now->modify('-11 months')->setTime(0, 0);
        $fromMonthlyStr = $fromMonthly->format('Y-m-d H:i:s');

        $sqlMonthly = "
            SELECT
                FORMAT(order_date, 'yyyy-MM') AS [month],
                COUNT(id) AS total
            FROM [orders]
            WHERE order_date >= :from
            GROUP BY FORMAT(order_date, 'yyyy-MM')
            ORDER BY [month] ASC
        ";

        $monthlyRaw = $conn->executeQuery($sqlMonthly, ['from' => $fromMonthlyStr])->fetchAllAssociative();

        $monthlyActivity = array_map(
            static function (array $row): array {
                return [
                    'month' => (string) $row['month'],
                    'total' => (int) $row['total'],
                ];
            },
            $monthlyRaw
        );

        return [$dailyActivity, $monthlyActivity];
    }
}
