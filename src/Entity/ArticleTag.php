<?php

namespace Adshares\CmsBundle\Entity;

enum ArticleTag: string
{
    case AdTech = 'AdTech';
    case Announcement = 'Announcement';
    case AdServer = 'adserver';
    case Application = 'application';
    case Blockchain = 'blockchain';
    case Coin = 'coin';
    case Community = 'community';
    case Ecosystem = 'ecosystem';
    case Media = 'media';
    case Metaverse = 'metaverse';
    case Protocol = 'protocol';
    case Technical = 'Technical';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::Ecosystem => 'Ecosystem/Fundamentals',
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
}
