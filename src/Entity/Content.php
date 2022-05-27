<?php

namespace Adshares\CmsBundle\Entity;

use Adshares\CmsBundle\Repository\ContentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ContentRepository::class)
 */
class Content
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=252)
     */
    private string $name;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=2, options={"default": "en"})
     */
    private string $locale = 'en';

    /**
     * @ORM\Column(type="text")
     */
    private string $value;

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
}
