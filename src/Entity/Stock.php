<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'stock', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $quantity = 0;

    #[ORM\Column]
    private ?int $minimumThreshold = 10;

    #[ORM\Column]
    private ?bool $isLowStock = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastRestockedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastRestockedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->checkLowStock();
        return $this;
    }

    public function addQuantity(int $quantity): static
    {
        $this->quantity += $quantity;
        $this->lastRestockedAt = new \DateTimeImmutable();
        $this->checkLowStock();
        return $this;
    }

    public function subtractQuantity(int $quantity): static
    {
        $this->quantity = max(0, $this->quantity - $quantity);
        $this->checkLowStock();
        return $this;
    }

    public function getMinimumThreshold(): ?int
    {
        return $this->minimumThreshold;
    }

    public function setMinimumThreshold(int $minimumThreshold): static
    {
        $this->minimumThreshold = $minimumThreshold;
        $this->checkLowStock();
        return $this;
    }

    public function isIsLowStock(): ?bool
    {
        return $this->isLowStock;
    }

    public function setIsLowStock(bool $isLowStock): static
    {
        $this->isLowStock = $isLowStock;
        return $this;
    }

    public function getLastRestockedAt(): ?\DateTimeImmutable
    {
        return $this->lastRestockedAt;
    }

    public function setLastRestockedAt(\DateTimeImmutable $lastRestockedAt): static
    {
        $this->lastRestockedAt = $lastRestockedAt;
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

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function checkLowStock(): void
    {
        $this->isLowStock = $this->quantity <= $this->minimumThreshold;
    }

    public function isAvailable(): bool
    {
        return $this->quantity > 0;
    }

    public function getAvailabilityStatus(): string
    {
        if ($this->quantity <= 0) {
            return 'Out of Stock';
        }
        if ($this->isLowStock) {
            return 'Low Stock';
        }
        return 'In Stock';
    }
}