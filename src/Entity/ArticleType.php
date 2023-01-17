<?php

namespace Adshares\CmsBundle\Entity;

enum ArticleType: string
{
    case Article = 'article';
    case Event = 'event';
    case FAQ = 'faq';
    case General = 'general';
    case Notice = 'notice';
    case Short = 'short';
    case Term = 'term';
    case Tutorial = 'tutorial';

    public function slug(): string
    {
        return match ($this) {
            self::Article => 'articles',
            self::Event => 'events',
            self::Notice => 'notices',
            self::Short => 'shorts',
            self::Term => 'terms',
            self::Tutorial => 'tutorials',
            default => $this->value,
        };
    }

    public static function tryFromSlug(string $slug): ?ArticleType
    {
        return match ($slug) {
            'articles' => self::Article,
            'events' => self::Event,
            'notices' => self::Notice,
            'shorts' => self::Short,
            'terms' => self::Term,
            'tutorials' => self::Tutorial,
            default => self::tryFrom($slug),
        };
    }
}
