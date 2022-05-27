<?php

namespace Adshares\CmsBundle\Twig;

use Adshares\CmsBundle\Cms\Cms;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;

final class CmsExtension extends AbstractExtension
{
    public function __construct(
        private readonly Cms $cms,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getTokenParsers(): array
    {
        return [new ContentTagTokenParser()];
    }

    public function getContent(string $name, string $default, array $parameters = []): string
    {
        $editMode = $this->cms->isEditMode();
        $content = $this->translator->trans($name, $editMode ? [] : $parameters);
        if ($content === $name) {
            $content = $this->translator->trans($default, $editMode ? [] : $parameters);
        }

        return $editMode ? sprintf(
            '<div class="a-content" data-a-name="%s" data-a-locale="%s">%s</div>',
            $name,
            $this->translator->getLocale(),
            $content
        ) : $content;
    }
}
