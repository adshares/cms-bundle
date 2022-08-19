<?php

namespace Adshares\CmsBundle\Entity;

use Adshares\CmsBundle\Repository\ArticleRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(ArticleRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt", timeAware: false, hardDelete: true)]
#[Gedmo\Loggable]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 16, enumType: ArticleType::class, options: ["default" => ArticleType::Article])]
    private ArticleType $type = ArticleType::Article;

    #[ORM\Column(type: "json")]
    private array $tags = [];

    #[ORM\Column(type: "datetime")]
    private DateTimeInterface $startAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $endAt;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    private User $author;

    #[ORM\Column(type: "string", length: 2, options: ["default" => "en"])]
    private string $locale = 'en';

    #[ORM\Column(type: "string", length: 1024)]
    private string $title;

    #[ORM\Column(type: "text")]
    #[Gedmo\Versioned]
    private string $content;

    #[ORM\Column(type: "string", length: 64, nullable: true)]
    private ?string $image;

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

    public function getType(): ArticleType
    {
        return $this->type;
    }

    public function setType(ArticleType $type): Article
    {
        $this->type = $type;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): Article
    {
        $this->tags = $tags;
        return $this;
    }

    public function getStartAt(): DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeInterface $startAt): Article
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?DateTimeInterface $endAt): Article
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): Article
    {
        $this->author = $author;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): Article
    {
        $this->locale = $locale;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Article
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): Article
    {
        $this->content = $content;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): Article
    {
        $this->image = $image;
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
