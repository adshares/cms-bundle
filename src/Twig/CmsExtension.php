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
        if ($this->isHistoryPage()) {
            $params = $this->cms->getRouteParams();
            $route = $params['_ref'] ?? '';
            unset($params['_ref'], $params['names']);
            return [
                'cms' => [
                    'editMode' => true,
                    'appUrl' => $this->generateUrl($route, $params),
                    'cmsUrl' => null,
                    'saveUrl' => null,
                    'historyUrl' => null,
                ]
            ];
        }

        return [
            'cms' => [
                'editMode' => $this->cms->isEditMode(),
                'appUrl' => $this->getAppUrl($this->cms->getRoute(), $this->cms->getRouteParams()),
                'cmsUrl' => $this->getCmsUrl($this->cms->getRoute(), $this->cms->getRouteParams()),
                'saveUrl' => $this->generateUrl('cms_content_patch'),
                'historyUrl' => $this->getHistoryUrl($this->cms->getRoute(), $this->cms->getRouteParams()),
            ]
        ];
    }

    public function getAppUrl(string $route, array $routeParams): ?string
    {
        return $this->generateUrl(
                preg_replace('/^cms_/', 'i18n_', $route),
                $routeParams
            ) ?? $this->generateUrl(
                preg_replace('/^cms_/', '', $route),
                $routeParams
            );
    }

    public function getCmsUrl(string $route, array $routeParams): ?string
    {
        return $this->generateUrl(
            preg_replace('/^(i18n_|)/', 'cms_', $route),
            $routeParams
        );
    }

    public function getHistoryUrl(string $route, array $routeParams): ?string
    {
        return $this->generateUrl('cms_content_history', array_merge(['_ref' => $route], $routeParams));
    }

    private function generateUrl(string $name, array $parameters = []): ?string
    {
        if (null !== $this->router->getRouteCollection()->get($name)) {
            return $this->router->generate($name, $parameters);
        }
        return null;
    }

    private function isHistoryPage(): bool
    {
        return 'cms_content_history' === $this->cms->getRoute();
    }
}
