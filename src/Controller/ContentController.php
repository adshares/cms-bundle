<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Entity\Content;
use Adshares\CmsBundle\Repository\ContentRepository;
use Adshares\CmsBundle\Twig\CmsExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;

class ContentController extends ViewController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $cacheDir,
        Environment $twig,
    ) {
        parent::__construct($twig);
    }

    public function history(
        Request $request,
        ContentRepository $contentRepository,
        CmsExtension $cmsExtension
    ): Response {
        $locale = $request->get('_locale') ?? $request->getLocale();
        $referral = $request->get('_ref') ?? '';
        $currentUrl = $this->getRequestUri($request);
        $names = $request->get('names');

        $history = [];
        foreach ($contentRepository->getHistory($names, $locale) as $name => $logs) {
            $refUrl = $this->getReferralUrl($cmsExtension, $referral, $name);
            $changes = [];
            foreach ($logs as $log) {
                $changes[] = [
                    'version' => $log->getVersion(),
                    'date' => $log->getLoggedAt(),
                    'action' => $log->getAction(),
                    'username' => $log->getUsername(),
                    'previewLink' => $this->getPreviewUrl(
                        $cmsExtension,
                        $referral,
                        $name,
                        'remove' === $log->getAction() ? 0 : $log->getVersion(),
                        $currentUrl
                    ),
                    'refUrl' => $refUrl,
                ];
            }
            $changes[] = [
                'version' => 0,
                'action' => 'origin',
                'previewLink' => $this->getPreviewUrl(
                    $cmsExtension,
                    $referral,
                    $name,
                    0,
                    $currentUrl
                ),
                'refUrl' => $refUrl,
            ];
            $history[$name] = $changes;
        }

        return $this->render('cms/history.html.twig', [
            'locale' => $locale,
            'history' => $history,
        ]);
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
            self::validate($validator, self::getContentConstraints(), $entry);
        }

        $entities = [];
        foreach ($list as $entry) {
            $content = $repository->findOneWithTrashed($entry['name'], $entry['locale']);
            if (null === $content) {
                $content = new Content();
                $content->setName($entry['name'], $entry['locale']);
            }
            $content->setValue($entry['value']);
            $content->restore();
            $entities[] = $content;
        }

        try {
            dump($entities);
            $repository->addAll($entities);
        } catch (Throwable $exception) {
            throw new UnprocessableEntityHttpException(sprintf('Unexpected error: %s', $exception->getMessage()));
        }
        $this->reloadTransactions();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function rollback(
        Request $request,
        ValidatorInterface $validator,
        ContentRepository $repository
    ) {
        $list = json_decode($request->getContent(), true);
        if (!is_array($list) || empty($list)) {
            throw new UnprocessableEntityHttpException('Invalid content');
        }
        foreach ($list as $entry) {
            self::validate($validator, self::getRollbackConstraints(), $entry);
        }

        $entities = [];
        foreach ($list as $entry) {
            $content = $repository->findOneWithVersion($entry['name'], $entry['locale'], $entry['version']);
            if (null === $content) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Cannot find entity %s [%s]', $entry['name'], $entry['locale'])
                );
            }
            if (0 === $entry['version']) {
                $repository->remove($content);
            } else {
                $content->restore();
                $entities[] = $content;
            }
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

    private static function getContentConstraints(): Collection
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

    private static function getRollbackConstraints(): Collection
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
            'version' => [
                new Type(['type' => 'int']),
                new PositiveOrZero(),
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

    private function getRequestUri(Request $request): string
    {
        if (null !== $qs = $request->getQueryString()) {
            $qs = '?' . $qs;
        }
        return $request->getPathInfo() . $qs;
    }

    private function getPreviewUrl(
        CmsExtension $cmsExtension,
        string $route,
        string $name,
        int $version,
        string $ref
    ): ?string {
        $url = $cmsExtension->getAppUrl($route, [
            '_preview' => [$name => $version],
            '_ref' => $ref
        ]);
        return null !== $url ? sprintf('%s#cms_%s', $url, $name) : null;
    }

    private function getReferralUrl(
        CmsExtension $cmsExtension,
        string $route,
        string $name
    ): ?string {
        $url = $cmsExtension->getCmsUrl($route);
        return null !== $url ? sprintf('%s#cms_%s', $url, $name) : null;
    }
}
