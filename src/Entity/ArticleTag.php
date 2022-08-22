<?php

namespace Adshares\CmsBundle\Entity;

enum ArticleTag: string
{
    case Ecosystem = 'ecosystem';
    case ADS = 'ads';
    case Blockchain = 'blockchain';
    case Wallet = 'wallet';
    case Protocol = 'protocol';
    case Applications = 'applications';
    case AdServer = 'adserver';
    case Metaverse = 'metaverse';
    case Community = 'community';

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
