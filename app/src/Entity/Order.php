<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount;

    #[ORM\Column]
    private \DateTimeImmutable $orderDate;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private Client $client;

    public function __construct()
    {
        $this->orderDate = new \DateTimeImmutable();
        $this->status = 'PENDING';
    }

    public function getId(): ?int { return $this->id; }

    public function getStatus(): string { return $this->status; }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getTotalAmount(): string { return $this->totalAmount; }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getOrderDate(): \DateTimeImmutable { return $this->orderDate; }

    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable { return $this->canceledAt; }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): self
    {
        $this->canceledAt = $canceledAt;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getClient(): Client { return $this->client; }

    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }
}
