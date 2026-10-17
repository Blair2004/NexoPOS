<?php

namespace App\Support;

class PosLayout
{
    public const Split = 'split';

    public const Unified = 'unified';

    public static function normalize( mixed $layout ): string
    {
        return $layout === self::Unified ? self::Unified : self::Split;
    }
}
