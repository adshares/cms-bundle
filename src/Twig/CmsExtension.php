<?php

namespace Adshares\CmsBundle\Twig;

use Adshares\CmsBundle\Cms\Cms;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class CmsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Cms $cms,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
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
            '<div class="cms-content" data-cms-name="%s" data-cms-locale="%s">%s</div>',
            $name,
            $this->translator->getLocale(),
            $content
        ) : $content;
    }

    public function getGlobals(): array
    {
        return [
            'cms' => [
                'editMode' => $this->cms->isEditMode(),
                'appUrl' => $this->generateUrl(
                    preg_replace('/^cms_/', '', $this->cms->getRoute()),
                    $this->cms->getRouteParams()
                ),
                'cmsUrl' => $this->generateUrl('cms_' . $this->cms->getRoute(), $this->cms->getRouteParams()),
            ]
        ];
    }

    private function generateUrl(string $name, array $parameters = []): ?string
    {
        try {
            return $this->router->generate($name, $parameters);
        } catch (RouteNotFoundException $exception) {
        }
        return null;
    }
}
