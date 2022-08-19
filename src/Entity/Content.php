<?php

namespace Adshares\CmsBundle\Entity;

use Adshares\CmsBundle\Repository\ContentRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(ContentRepository::class)]
#[ORM\UniqueConstraint(name: "content_unique", columns: ["name", "locale"])]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt", timeAware: false, hardDelete: true)]
#[Gedmo\Loggable]
class Content
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 252)]
    private string $name;

    #[ORM\Column(type: "string", length: 2, options: ["default" => "en"])]
    private string $locale = 'en';

    #[ORM\Column(type: "text")]
    #[Gedmo\Versioned]
    private string $value;

    #[ORM\Column(type: "datetime")]
    #[Gedmo\Timestampable(on: "create")]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime")]
    #[Gedmo\Timestampable(on: "update")]
    private DateTimeInterface $updatedAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $deletedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name, ?string $locale = null): self
    {
        $this->name = $name;
        if (null !== $locale) {
            $this->locale = $locale;
        }
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function restore(): self
    {
        $this->deletedAt = null;
        return $this;
    }
}
