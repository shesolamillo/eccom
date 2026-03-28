<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
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
            normalizationContext: ['groups' => ['settings:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['settings:read']]
        ),
        new Post(),
        new Put(),
        new Delete()
    ]
)]


#[ORM\Entity(repositoryClass: SettingsRepository::class)]
#[ORM\Table(name: 'settings')]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $settingKey = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $settingValue = null;

    #[ORM\Column(length: 50)]
    private ?string $dataType = 'string';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $isPublic = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): ?string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): static
    {
        $this->settingKey = $settingKey;
        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): static
    {
        $this->settingValue = $settingValue;
        return $this;
    }

    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): static
    {
        $this->dataType = $dataType;
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

    public function isIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
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

    public function getTypedValue()
    {
        if ($this->settingValue === null) {
            return null;
        }

        return match($this->dataType) {
            'integer' => (int) $this->settingValue,
            'float' => (float) $this->settingValue,
            'boolean' => filter_var($this->settingValue, FILTER_VALIDATE_BOOLEAN),
            'array' => json_decode($this->settingValue, true),
            'json' => json_decode($this->settingValue),
            default => $this->settingValue,
        };
    }

    public function setTypedValue($value): static
    {
        if (is_array($value) || is_object($value)) {
            $this->dataType = 'json';
            $this->settingValue = json_encode($value);
        } elseif (is_bool($value)) {
            $this->dataType = 'boolean';
            $this->settingValue = $value ? 'true' : 'false';
        } elseif (is_int($value)) {
            $this->dataType = 'integer';
            $this->settingValue = (string) $value;
        } elseif (is_float($value)) {
            $this->dataType = 'float';
            $this->settingValue = (string) $value;
        } else {
            $this->dataType = 'string';
            $this->settingValue = (string) $value;
        }

        return $this;
    }
}