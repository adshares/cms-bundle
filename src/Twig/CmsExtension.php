<?php

namespace Adshares\CmsBundle\Twig;

use Adshares\CmsBundle\Cms\Cms;
use Adshares\CmsBundle\Repository\ContentRepository;
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
        private readonly ContentRepository $contentRepository,
    ) {
    }

    public function getTokenParsers(): array
    {
        return [new ContentTagTokenParser()];
    }

    public function getContent(string $name, string $default, array $parameters = []): string
    {
        $editMode = $this->cms->isEditMode();
        $preview = $this->cms->getPreviewParams();

        $default = sprintf('<div id="cms_%s">%s</div>', $name, $default);

        $content = null;
        if (!$editMode && array_key_exists($name, $preview)) {
            if (0 === (int)$preview[$name]) {
                $content = $this->translator->trans($default, $parameters, null, $this->translator->getLocale());
            } else {
                $historicalContent = $this->contentRepository->findOneWithVersion(
                    $name,
                    $this->translator->getLocale(),
                    (int)$preview[$name]
                );
                if (null !== $historicalContent) {
                    $content = $this->translator->trans($historicalContent->getValue(), $parameters, null, $this->translator->getLocale());
                }
            }
        }

        if (null === $content) {
            $content = $this->translator->trans($name, $editMode ? [] : $parameters, null, $this->translator->getLocale());
            if ($content === $name) {
                $content = $this->translator->trans($default, $editMode ? [] : $parameters, null, $this->translator->getLocale());
            }
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
        $globals = [
            'historyMode' => false,
            'previewMode' => false,
            'editMode' => false,
        ];

        $params = $this->cms->getRouteParams();
        $ref = $params['_ref'] ?? '';
        unset($params['_ref']);

        if ($this->isHistoryPage()) {
            unset($params['names']);
            $globals = array_merge($globals, [
                'historyMode' => true,
                'appUrl' => $this->generateUrl($ref, $params),
            ]);
        } else if ($this->isArticlePage()) {
            $globals = array_merge($globals, [
                'articleMode' => true,
                'appUrl' => $this->generateUrl($ref, $params) ?? $params['ref'] ?? '/',
            ]);
        } else if ($this->isPreviewPage()) {
            unset($params['_preview']);
            $globals = array_merge($globals, [
                'previewMode' => true,
                'appUrl' => $ref,
                'saveUrl' => $this->generateUrl('cms_content_rollback'),
                'refUrl' => $this->getCmsUrl($this->cms->getRoute(), $params),
                'state' => $this->cms->getPreviewParams(),
            ]);
        } else if (null !== $this->cms->getRoute()) {
            $globals = array_merge($globals, [
                'editMode' => $this->cms->isEditMode(),
                'appUrl' => $this->cms->isEditMode() ? $this->getAppUrl($this->cms->getRoute(), $this->cms->getRouteParams()) : null,
                'cmsUrl' => $this->getCmsUrl($this->cms->getRoute(), $this->cms->getRouteParams()),
                'saveUrl' => $this->generateUrl('cms_content_patch'),
                'historyUrl' => $this->getUrlWithRef('cms_content_history', $this->cms->getRoute(), $this->cms->getRouteParams()),
                'articlesUrl' => $this->getUrlWithRef('cms_articles', $this->cms->getRoute(), $this->cms->getRouteParams()),
            ]);
        }

        return ['cms' => $globals];
    }

    public function getAppUrl(string $route, array $routeParams = []): ?string
    {
        return $this->generateUrl(
                preg_replace('/^_cms_/', '_i18n_', $route),
                $routeParams
            ) ?? $this->generateUrl(
                preg_replace('/^_cms_/', '', $route),
                $routeParams
            );
    }

    public function getCmsUrl(string $route, array $routeParams = []): ?string
    {
        return $this->generateUrl(
            preg_replace('/^(_i18n_|_cms_|)/', '_cms_', $route),
            $routeParams
        );
    }

    public function getUrlWithRef(string $name, string $ref, array $routeParams = []): ?string
    {
        return $this->generateUrl($name, array_merge(['_ref' => $ref], $routeParams));
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

    private function isArticlePage(): bool
    {
        return str_starts_with($this->cms->getRoute(), 'cms_article');
    }

    private function isPreviewPage(): bool
    {
        return !empty($this->cms->getPreviewParams());
    }
}
