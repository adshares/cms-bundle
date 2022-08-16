<?php

namespace Adshares\CmsBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NumberExtension extends AbstractExtension
{
    private const KILO = 1000;
    private const MEGA = self::KILO * 1000;
    private const GIGA = self::MEGA * 1000;
    private const TERA = self::GIGA * 1000;

    public function getFilters(): array
    {
        return [
            new TwigFilter('number_readable', [$this, 'formatNumber'], ['needs_environment' => true]),
        ];
    }

    function formatNumber(
        Environment $env,
        float $number,
        ?int $decimal = null,
        ?string $decimalPoint = null,
        ?string $thousandSep = null
    ): string {

        $unit = '';
        if ($number > self::GIGA) {
            $number /= self::GIGA;
            $unit = 'B';
        } elseif ($number > self::MEGA) {
            $number /= self::MEGA;
            $unit = 'M';
        } elseif ($number > self::KILO) {
            $number /= self::KILO;
            $unit = 'k';
        }

        if (null === $decimal && !(empty($unit))) {
            $decimal= 1;
        }

        return twig_number_format_filter($env, $number, $decimal, $decimalPoint, $thousandSep) . $unit;
    }
}