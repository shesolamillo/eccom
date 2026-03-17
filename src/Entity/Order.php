<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DECLINED = 'declined';

    const DELIVERY_PICKUP = 'pickup';
    const DELIVERY_DELIVERY = 'delivery';

    const PAYMENT_CASH = 'cash';
    const PAYMENT_ONLINE = 'online';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isUrgent = false;

    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    private Collection $orderItems;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 20)]
    private ?string $deliveryType = self::DELIVERY_PICKUP;

    #[ORM\Column(length: 20)]
    private ?string $paymentMethod = self::PAYMENT_CASH;

    #[ORM\Column]
    private ?bool $isPaid = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(nullable: true)]
    private ?float $deliveryFee = 0.0;

    #[ORM\Column]
    private ?float $totalAmount = 0.0;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToOne(mappedBy: 'orderRef', cascade: ['persist', 'remove'])]
    private ?Receipt $receipt = null;

    #[ORM\ManyToOne]
    private ?User $processedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pickupDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveryDate = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->generateOrderNumber();
    }

    public function isUrgent(): bool
    {
        return $this->isUrgent;
    }

    public function setIsUrgent(bool $isUrgent): static
    {
        $this->isUrgent = $isUrgent;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    private function generateOrderNumber(): void
    {
        $this->orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrderRef($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrderRef() === $this) {
                $orderItem->setOrderRef(null);
            }
        }
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        if ($status === self::STATUS_COMPLETED) {
            $this->completedAt = new \DateTimeImmutable();
        }
        
        if ($status === self::STATUS_CANCELLED || $status === self::STATUS_DECLINED) {
            $this->isPaid = false;
        }
        
        return $this;
    }

    public function getDeliveryType(): ?string
    {
        return $this->deliveryType;
    }

    public function setDeliveryType(string $deliveryType): static
    {
        $this->deliveryType = $deliveryType;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function isIsPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;
        if ($isPaid) {
            $this->paidAt = new \DateTimeImmutable();
        } else {
            $this->paidAt = null;
        }
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getDeliveryFee(): ?float
    {
        return $this->deliveryFee;
    }

    public function setDeliveryFee(?float $deliveryFee): static
    {
        $this->deliveryFee = $deliveryFee;
        $this->calculateTotal();
        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getReceipt(): ?Receipt
    {
        return $this->receipt;
    }

    public function setReceipt(?Receipt $receipt): static
    {
        if ($receipt === null && $this->receipt !== null) {
            $this->receipt->setOrderRef(null);
        }

        if ($receipt !== null && $receipt->getOrderRef() !== $this) {
            $receipt->setOrderRef($this);
        }

        $this->receipt = $receipt;
        return $this;
    }

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): static
    {
        $this->processedBy = $processedBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getPickupDate(): ?\DateTimeImmutable
    {
        return $this->pickupDate;
    }

    public function setPickupDate(?\DateTimeImmutable $pickupDate): static
    {
        $this->pickupDate = $pickupDate;
        return $this;
    }

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?\DateTimeImmutable $deliveryDate): static
    {
        $this->deliveryDate = $deliveryDate;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateTotal(): void
    {
        $total = 0;
        foreach ($this->orderItems as $item) {
            $total += $item->getTotalPrice();
        }
        $this->totalAmount = $total + ($this->deliveryFee ?? 0);
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'badge-status-pending',
            self::STATUS_ACCEPTED => 'badge-status-processing',
            self::STATUS_PROCESSING => 'badge-status-processing',
            self::STATUS_COMPLETED => 'badge-status-completed',
            self::STATUS_CANCELLED, self::STATUS_DECLINED => 'badge-status-cancelled',
            default => 'badge-secondary'
        };
    }

    public function getStatusText(): string
    {
        return ucfirst($this->status);
    }

    public function canBeEditedBy(User $user): bool
    {
        // Admin can edit all orders
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Staff can edit orders they processed or pending orders
        if (in_array('ROLE_STAFF', $user->getRoles())) {
            return $this->status === self::STATUS_PENDING || 
                   $this->processedBy === $user;
        }

        // Users can edit their own pending orders
        return $this->customer === $user && $this->status === self::STATUS_PENDING;
    }
}