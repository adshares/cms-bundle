<?php

namespace Adshares\CmsBundle\Cms;

use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private const IMAGES_DIR = 'build/images';
    private const UPLOAD_DIR = 'uploads';

    public function __construct(
        private readonly string $publicDirectory,
        private readonly SluggerInterface $slugger,
        private readonly Packages $packages,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getAssets(): array
    {
        return array_merge(
            $this->listFiles(self::IMAGES_DIR),
            $this->listFiles(self::UPLOAD_DIR, true),
        );
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . substr(md5($file->getContent()), 0, 8) . '.' . $file->guessExtension();

        try {
            $file->move($this->publicDirectory . DIRECTORY_SEPARATOR . self::UPLOAD_DIR, $fileName);
        } catch (FileException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $this->packages->getUrl(self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName);
    }

    private function listFiles(string $dir, bool $editable = false): array
    {
        $files = [];
        $path = $this->publicDirectory . DIRECTORY_SEPARATOR . $dir;
        if (file_exists($path)) {
            foreach (array_diff(scandir($path), ['.', '..']) as $fileName) {
                $files[] = [
                    'ext' => pathinfo($fileName, PATHINFO_EXTENSION),
                    'name' => $fileName,
                    'size' => filesize($path . DIRECTORY_SEPARATOR . $fileName),
                    'location' => $this->packages->getUrl($dir . DIRECTORY_SEPARATOR . $fileName),
                    'editable' => $editable,
                ];
            }
        }
        return $files;
    }
}
