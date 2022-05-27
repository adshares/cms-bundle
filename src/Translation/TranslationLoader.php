<?php

namespace Adshares\CmsBundle\Translation;

use Adshares\CmsBundle\Repository\ContentRepository;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationLoader implements LoaderInterface
{
    public function __construct(private readonly ContentRepository $contentRepository)
    {
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $messages = [];
        foreach ($this->contentRepository->findByLocale($locale) as $content) {
            $messages[$content->getName()] = $content->getValue();
        }
        $catalogue->add($messages, $domain);
        if (class_exists(FileResource::class)) {
            $catalogue->addResource(new FileResource($resource));
        }
        return $catalogue;
    }
}
