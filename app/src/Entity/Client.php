<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'clients')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 255)]
    private string $emailEncrypted;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneEncrypted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationalIdEncrypted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $addressEncrypted = null;

    // NUEVOS CAMPOS
    #[ORM\Column(length: 255)]
    private string $documentEncrypted;

    #[ORM\Column(length: 255)]
    private string $typeDocumentEncrypted;

    #[ORM\Column(length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'client')]
    private Collection $orders;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'ACTIVE';
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmailEncrypted(): string
    {
        return $this->emailEncrypted;
    }

    public function setEmailEncrypted(string $emailEncrypted): self
    {
        $this->emailEncrypted = $emailEncrypted;

        return $this;
    }

    public function getPhoneEncrypted(): ?string
    {
        return $this->phoneEncrypted;
    }

    public function setPhoneEncrypted(?string $phoneEncrypted): self
    {
        $this->phoneEncrypted = $phoneEncrypted;

        return $this;
    }

    public function getNationalIdEncrypted(): ?string
    {
        return $this->nationalIdEncrypted;
    }

    public function setNationalIdEncrypted(?string $nationalIdEncrypted): self
    {
        $this->nationalIdEncrypted = $nationalIdEncrypted;

        return $this;
    }

    public function getAddressEncrypted(): ?string
    {
        return $this->addressEncrypted;
    }

    public function setAddressEncrypted(?string $addressEncrypted): self
    {
        $this->addressEncrypted = $addressEncrypted;

        return $this;
    }

    public function getDocumentEncrypted(): string
    {
        return $this->documentEncrypted;
    }

    public function setDocumentEncrypted(string $documentEncrypted): self
    {
        $this->documentEncrypted = $documentEncrypted;

        return $this;
    }

    public function getTypeDocumentEncrypted(): string
    {
        return $this->typeDocumentEncrypted;
    }

    public function setTypeDocumentEncrypted(string $typeDocumentEncrypted): self
    {
        $this->typeDocumentEncrypted = $typeDocumentEncrypted;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setClient($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if (
            $this->orders->removeElement($order)
            && $order->getClient() === $this
        ) {
            // set the owning side to null
            $order->setClient(null);
        }

        return $this;
    }

}
