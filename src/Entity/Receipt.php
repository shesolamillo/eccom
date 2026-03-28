<?php

namespace App\Entity;

use App\Repository\ReceiptRepository;
use Doctrine\ORM\Mapping as ORM;

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
            normalizationContext: ['groups' => ['receipt:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['receipt:read']]
        ),
        new Post(),
        new Put(),
        new Delete()
    ]
)]

#[ORM\Entity(repositoryClass: ReceiptRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Receipt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $receiptNumber = null;

    #[ORM\OneToOne(inversedBy: 'receipt', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderRef = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $issuedDate = null;

    #[ORM\Column(length: 20)]
    private ?string $paymentMethod = 'cash';

    #[ORM\Column]
    private ?float $subtotal = 0.0;

    #[ORM\Column]
    private ?float $totalAmount = 0.0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $printedAt = null;

    #[ORM\ManyToOne]
    private ?User $printedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->issuedDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    public function setReceiptNumber(string $receiptNumber): static
    {
        $this->receiptNumber = $receiptNumber;
        return $this;
    }

    #[ORM\PrePersist]
    public function generateReceiptNumber(): void
    {
        if (!$this->receiptNumber) {
            $year = date('Y');
            $random = strtoupper(substr(md5(uniqid()), 0, 6));
            $this->receiptNumber = "LAUNDRY-{$year}-{$random}";
        }
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(Order $orderRef): static
    {
        $this->orderRef = $orderRef;
        
        // Copy values from order
        $this->subtotal = $orderRef->getTotalAmount() - ($orderRef->getDeliveryFee() ?? 0);
        $this->totalAmount = $orderRef->getTotalAmount();
        $this->paymentMethod = $orderRef->getPaymentMethod();
        
        return $this;
    }

    public function getIssuedDate(): ?\DateTimeImmutable
    {
        return $this->issuedDate;
    }

    public function setIssuedDate(\DateTimeImmutable $issuedDate): static
    {
        $this->issuedDate = $issuedDate;
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

    public function getSubtotal(): ?float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): static
    {
        $this->subtotal = $subtotal;
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

    public function getPrintedAt(): ?\DateTimeImmutable
    {
        return $this->printedAt;
    }

    public function setPrintedAt(?\DateTimeImmutable $printedAt): static
    {
        $this->printedAt = $printedAt;
        return $this;
    }

    public function getPrintedBy(): ?User
    {
        return $this->printedBy;
    }

    public function setPrintedBy(?User $printedBy): static
    {
        $this->printedBy = $printedBy;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFileUrl(): ?string
    {
        if (!$this->filePath) {
            return null;
        }
        return '/uploads/receipts/' . $this->filePath;
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

    public function markAsPrinted(User $user): void
    {
        $this->printedAt = new \DateTimeImmutable();
        $this->printedBy = $user;
    }

    public function isPrinted(): bool
    {
        return $this->printedAt !== null;
    }
}