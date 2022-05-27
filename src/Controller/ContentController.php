<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Cms\FileUploader;
use Adshares\CmsBundle\Entity\Content;
use Adshares\CmsBundle\Repository\ContentRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class ContentController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $cacheDir,
    ) {
    }

    public function patch(
        Request $request,
        ValidatorInterface $validator,
        ContentRepository $repository
    ): Response {
        $list = json_decode($request->getContent(), true);
        if (!is_array($list) || empty($list)) {
            throw new UnprocessableEntityHttpException('Invalid content');
        }
        foreach ($list as $entry) {
            self::validate($validator, self::getConstraints(), $entry);
        }

        $entities = [];
        foreach ($list as $entry) {
            $content = $repository->find(['name' => $entry['name'], 'locale' => $entry['locale']]);
            if (null === $content) {
                $content = new Content();
                $content->setName($entry['name'], $entry['locale']);
            }
            $content->setValue($entry['value']);
            $entities[] = $content;
        }

        try {
            $repository->addAll($entities);
        } catch (Throwable $exception) {
            throw new UnprocessableEntityHttpException(sprintf('Unexpected error: %s', $exception->getMessage()));
        }
        $this->reloadTransactions();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private static function validate(ValidatorInterface $validator, Collection $constraints, $entry): void
    {
        $errors = $validator->validate($entry, $constraints);
        if (count($errors) > 0) {
            throw new UnprocessableEntityHttpException((string)$errors);
        }
    }

    private static function getConstraints(): Collection
    {
        return new Collection([
            'name' => [
                new Type(['type' => 'string']),
                new Length(['min' => 1, 'max' => 252]),
            ],
            'locale' => [
                new Type(['type' => 'string']),
                new Length(['min' => 2, 'max' => 2]),
            ],
            'value' => [
                new Type(['type' => 'string']),
            ],
        ]);
    }

    private function reloadTransactions(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->cacheDir . DIRECTORY_SEPARATOR . 'translations');
        if ($this->translator instanceof WarmableInterface) {
            $this->translator->warmUp($this->cacheDir);
        }
    }
}
