<?php

namespace Adshares\CmsBundle\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{

    public function __construct(private readonly Packages $packages, private readonly ParameterBagInterface $params)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('embed', [$this, 'getAssetContent'], ['is_safe' => ['all']]),
            new TwigFunction('embed64', [$this, 'getAssetBase64'], ['is_safe' => ['all']]),
        ];
    }

    public function getAssetContent($name, $packageName = null): ?string
    {
        if (null === ($path = $this->getAssetPath($name, $packageName))) {
            return null;
        }
        return file_get_contents($path);
    }

    public function getAssetBase64($name, $packageName = null): ?string
    {
        if (null === ($path = $this->getAssetPath($name, $packageName))) {
            return null;
        }
        return sprintf(
            '<img src="data:%s;base64,%s"/ >',
            mime_content_type($path),
            base64_encode(file_get_contents($path))
        );
    }

    private function getAssetPath($name, $packageName = null): ?string
    {
        $url = $this->packages->getUrl($name, $packageName);

        if (empty($url)) {
            return null;
        }

        if ($url[0] === '/') {
            $url = $this->params->get('kernel.project_dir') . '/public' . $url;
        }

        return $url;
    }
}
