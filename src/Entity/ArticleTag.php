<?php

namespace Adshares\CmsBundle\Entity;

enum ArticleTag: string
{
    case AdTech = 'adtech';
    case Announcement = 'announcement';
    case AdServer = 'adserver';
    case Agency = 'agency';
    case Application = 'application';
    case Blockchain = 'blockchain';
    case Coin = 'coin';
    case Community = 'community';
    case DAO = 'dao';
    case Ecosystem = 'ecosystem';
    case Media = 'media';
    case Metaverse = 'metaverse';
    case Protocol = 'protocol';
    case Technical = 'technical';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::AdTech => 'Ad Tech',
            self::AdServer => 'Ad Server',
            default => $this->name,
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->label()] = $case->value;
        }
        return $options;
    }

    public function slug(): string
    {
        return $this->value;
    }

    public static function tryFromSlug(string $slug): ?ArticleTag
    {
        return self::tryFrom($slug);
    }
}
