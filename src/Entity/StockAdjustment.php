<?php

namespace App\Entity;

use App\Repository\StockAdjustmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockAdjustmentRepository::class)]
class StockAdjustment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'adjustments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $stock = null;

    #[ORM\Column]
    private ?int $previousQuantity = null;

    #[ORM\Column]
    private ?int $newQuantity = null;

    #[ORM\Column(length: 20)]
    private ?string $adjustmentType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $adjustedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $adjustedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPreviousQuantity(): ?int
    {
        return $this->previousQuantity;
    }

    public function setPreviousQuantity(int $previousQuantity): static
    {
        $this->previousQuantity = $previousQuantity;

        return $this;
    }

    public function getNewQuantity(): ?int
    {
        return $this->newQuantity;
    }

    public function setNewQuantity(int $newQuantity): static
    {
        $this->newQuantity = $newQuantity;

        return $this;
    }

    public function getAdjustmentType(): ?string
    {
        return $this->adjustmentType;
    }

    public function setAdjustmentType(string $adjustmentType): static
    {
        $this->adjustmentType = $adjustmentType;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

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

    public function getAdjustedBy(): ?User
    {
        return $this->adjustedBy;
    }

    public function setAdjustedBy(?User $adjustedBy): static
    {
        $this->adjustedBy = $adjustedBy;

        return $this;
    }

    public function getAdjustedAt(): ?\DateTimeImmutable
    {
        return $this->adjustedAt;
    }

    public function setAdjustedAt(\DateTimeImmutable $adjustedAt): static
    {
        $this->adjustedAt = $adjustedAt;

        return $this;
    }
}
