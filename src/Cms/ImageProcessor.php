<?php

namespace Adshares\CmsBundle\Cms;

use GdImage;
use RuntimeException;

class ImageProcessor
{
    public function crop(string $filename, string $destinationFilename, int $width, int $height): string
    {
        $prop = getimagesize($filename);
        $srcWidth = $prop[0];
        $srcHeight = $prop[1];
        $mimeType = $prop['mime'];

        $source = $this->readImage($filename, $mimeType);

        $scale = max($width / $srcWidth, $height / $srcHeight);
        $scaled = imagescale($source, $srcWidth * $scale, $srcHeight * $scale, IMG_BILINEAR_FIXED);

        if (false === $scaled) {
            throw new RuntimeException(sprintf('Cannot scale image "%s"', $filename));
        }

        $destination = imagecreatetruecolor($width, $height);
        $result = imagecopyresized(
            $destination,
            $scaled,
            0,
            0,
            ($srcWidth * $scale - $width) / 2,
            ($srcHeight * $scale - $height) / 2,
            $width,
            $height,
            $width,
            $height
        );

        if (false === $result) {
            throw new RuntimeException(sprintf('Cannot crop image "%s"', $filename));
        }

        $this->saveImage($destination, $destinationFilename, $mimeType);

        return $destinationFilename;
    }

    private function readImage($filename, $mimeType): GdImage
    {
        $image = match ($mimeType) {
            'image/png' => imagecreatefrompng($filename),
            'image/jpeg' => imagecreatefromjpeg($filename),
            'image/webp' => imagecreatefromwebp($filename),
            'image/gif' => imagecreatefromgif($filename),
            default => throw new RuntimeException(sprintf('Unsupported image mime type %s', $mimeType)),
        };
        if (false === $image) {
            throw new RuntimeException(sprintf('Cannot read image "%s"', $filename));
        }
        return $image;
    }

    private function saveImage(GdImage $image, $filename, $mimeType): GdImage
    {
        $result = match ($mimeType) {
            'image/png' => imagepng($image, $filename, 9),
            'image/jpeg' => imagejpeg($image, $filename, 90),
            'image/webp' => imagewebp($image, $filename, 90),
            'image/gif' => imagegif($image, $filename),
            default => throw new RuntimeException(sprintf('Unsupported image mime type %s', $mimeType)),
        };
        if (false === $result) {
            throw new RuntimeException(sprintf('Cannot save image "%s"', $filename));
        }
        return $image;
    }
}