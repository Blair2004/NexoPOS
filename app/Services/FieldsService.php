<?php

namespace App\Services;

class FieldsService
{
    protected $fields = [];

    public function get()
    {
        return $this->fields;
    }

    public static function getIdentifier(): string
    {
        if ( isset( get_called_class()::$identifier ) ) {
            return get_called_class()::$identifier;
        }

        /**
         * @todo set as a migration measure.
         * identifier must be set statically to avoid any
         * field build while just retreiving the identifier.
         */
        if ( isset( ( new self )->identifier ) ) {
            return get_called_class()::$identifier;
        }
    }
}
