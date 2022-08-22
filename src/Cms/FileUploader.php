<?php

namespace Adshares\CmsBundle\Cms;

use Adshares\CmsBundle\Entity\Article;
use DateTimeImmutable;
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

    public function upload(UploadedFile $file, ?string $subpath = null): string
    {
        $fileName = substr(md5($file->getContent()), 0, 16) . '.' . $file->guessExtension();

        $path = self::UPLOAD_DIR;
        if (null !== $subpath) {
            $path .= DIRECTORY_SEPARATOR . $subpath;
        }

        try {
            $file->move($this->publicDirectory . DIRECTORY_SEPARATOR . $path, $fileName);
        } catch (FileException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }

        return $this->packages->getUrl($path . DIRECTORY_SEPARATOR . $fileName);
    }

    public function uploadArticle(UploadedFile $file): string
    {
        $now = new DateTimeImmutable();
        $path = 'articles' . DIRECTORY_SEPARATOR . '_' .$now->format('Y-m');
        return $this->upload($file, $path);
    }

    private function listFiles(string $dir, bool $editable = false): array
    {
        $files = [];
        $path = $this->publicDirectory . DIRECTORY_SEPARATOR . $dir;
        if (file_exists($path)) {
            foreach (array_diff(scandir($path), ['.', '..']) as $fileName) {
                $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
                $publicFilePath = $dir . DIRECTORY_SEPARATOR . $fileName;
                if (preg_match('/\.[0-9a-f]+\./', $fileName)) {
                    continue;
                }
                if (is_dir($filePath)) {
                    $files[$fileName] = $this->listFiles($publicFilePath, $editable);
                } else {
                    $files[] = [
                        'ext' => pathinfo($fileName, PATHINFO_EXTENSION),
                        'name' => $fileName,
                        'size' => filesize($filePath),
                        'location' => $this->packages->getUrl($publicFilePath),
                        'editable' => $editable,
                    ];
                }
            }
        }
        return $files;
    }
}
