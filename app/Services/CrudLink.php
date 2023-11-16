<?php

namespace App\Services;

use JsonSerializable;

class CrudLink implements JsonSerializable
{
    const TYPE = 'link';

    public function __construct(
        private string $label,
        private string $href
    ) {
        // ...
    }

    public function jsonSerialize()
    {
        return [
            'type' => self::TYPE,
            'href' => $this->href,
            'label' => $this->label,
        ];
    }
}
