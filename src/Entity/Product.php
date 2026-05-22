<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['product:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['product:read']]
        ),
        new Post(),
        new Put(),
        new Delete()
    ]
)]

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['product:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $photo = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read'])]
    private ?ProductType $productType = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?ClothesCategory $clothesCategory = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    #[Groups(['product:read'])]
    private ?Stock $stock = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: OrderItem::class)]
    #[Groups(['product:read'])]
    private Collection $orderItems;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    #[Groups(['product:read'])]
    private ?string $sku = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?User $createdBy = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?bool $isAvailable = true;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

     #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?float $costPrice = null;

     #[ORM\Column(length: 50, nullable: true)]
     #[Groups(['product:read'])]
     private ?string $size = null;

     #[ORM\Column(length: 50, nullable: true)]
     #[Groups(['product:read'])]
     private ?string $color = null;

     #[ORM\Column(length: 100, nullable: true)]
     #[Groups(['product:read'])]
     private ?string $material = null;

     #[ORM\Column(nullable: true)]
     #[Groups(['product:read'])]
     private ?float $weight = null;

     #[ORM\Column(length: 100, nullable: true)]
     #[Groups(['product:read'])]
     private ?string $dimensions = null;

     #[ORM\Column(length: 255, nullable: true)]
     #[Groups(['product:read'])]
     private ?string $tags = null;


    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        if (!$this->photo) {
            return null;
        }
        return '/uploads/products/' . $this->photo;
    }

    public function getProductType(): ?ProductType
    {
        return $this->productType;
    }

    public function setProductType(?ProductType $productType): static
    {
        $this->productType = $productType;
        return $this;
    }

    public function getClothesCategory(): ?ClothesCategory
    {
        return $this->clothesCategory;
    }

    public function setClothesCategory(?ClothesCategory $clothesCategory): static
    {
        $this->clothesCategory = $clothesCategory;
        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): static
    {
        if ($stock === null && $this->stock !== null) {
            $this->stock->setProduct(null);
        }

        if ($stock !== null && $stock->getProduct() !== $this) {
            $stock->setProduct($this);
        }

        $this->stock = $stock;
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
            $orderItem->setProduct($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function isIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;
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

    public function __toString(): string
    {
        return $this->name ?? '';
    }


    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;
        return $this;
    }

    public function getCostPrice(): ?float
    {
        return $this->costPrice;
    }

    public function setCostPrice(?float $costPrice): static
    {
        $this->costPrice = $costPrice;
        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getMaterial(): ?string
    {
        return $this->material;
    }

    public function setMaterial(?string $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): static
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }

    public function setTags(?string $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getFirstOrderDate(): ?\DateTimeInterface
{
    if ($this->orderItems->isEmpty()) {
        return null;
    }

    $dates = $this->orderItems->map(fn($item) => $item->getOrderRef()->getCreatedAt())->toArray();
    sort($dates);
    return $dates[0] ?? null;
}

    // lastOrderDate
public function getLastOrderDate(): ?\DateTimeInterface
{
    if ($this->orderItems->isEmpty()) {
        return null;
    }
    $dates = $this->orderItems->map(fn($item) => $item->getOrderRef()->getCreatedAt())->toArray();
    rsort($dates);
    return $dates[0] ?? null;
}

// totalSold
public function getTotalSold(): int
{
    $total = 0;
    foreach ($this->orderItems as $item) {
        $total += $item->getQuantity();
    }
    return $total;
}


}