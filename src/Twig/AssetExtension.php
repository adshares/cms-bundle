<?php

namespace Adshares\CmsBundle\Twig;

use Adshares\CmsBundle\Cms\ImageProcessor;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{

    public function __construct(
        private readonly Packages $packages,
        private readonly ParameterBagInterface $params,
        private readonly ImageProcessor $imageProcessor
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('embed', [$this, 'getAssetContent'], ['is_safe' => ['all']]),
            new TwigFunction('embed64', [$this, 'getAssetBase64'], ['is_safe' => ['all']]),
            new TwigFunction('img', [$this, 'getImage'], ['is_safe' => ['all']]),
        ];
    }

    public function getAssetContent(string $name, ?string $packageName = null): ?string
    {
        if (null === ($path = $this->getAssetPath($name, $packageName))) {
            return null;
        }
        return file_get_contents($path);
    }

    public function getAssetBase64(string $name, ?string $packageName = null, ?string $alt = null): ?string
    {
        if (null === ($path = $this->getAssetPath($name, $packageName))) {
            return null;
        }
        return sprintf(
            '<img src="data:%s;base64,%s" alt="%s">',
            mime_content_type($path),
            base64_encode(file_get_contents($path)),
            $alt ?? $name
        );
    }

    public function getImage(string $filename, int $width, int $height, ?string $alt = null): ?string
    {
        $sourcePath = $this->getAssetPath($filename);
        $destinationFilename = preg_replace('/^(.*)\.([^.]+)$/', sprintf('$1_%d_%d.$2', $width, $height), $filename);
        $destinationPath = $this->getAssetPath($destinationFilename);

        if (!file_exists($destinationPath)) {
            $this->imageProcessor->crop($sourcePath, $destinationPath, $width, $height);
        }

        return sprintf(
            '<img src="%s" alt="%s">',
            $destinationFilename,
            $alt
        );
    }

    private function getAssetPath(string $name, ?string $packageName = null): ?string
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
