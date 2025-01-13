<?php

namespace App\Classes;

class Model
{
    /**
     * Define a dependant relationship.
     */
    public static function dependant( string $local_name, string $foreign_index, ?string $foreign_name = null, array $related = [], string $local_index = 'id' )
    {
        return [
            'local_index' => $local_index,
            'local_name' => $local_name,
            'foreign_index' => $foreign_index,
            'foreign_name' => $foreign_name,
            'related' => $related,
        ];
    }

    /**
     * Define a related relationship.
     */
    public static function related( string $model, string $foreign_index, string $local_name, ?callable $prefix = null, string $local_index = 'id' )
    {
        return [
            'model' => $model,
            'local_index' => $local_index,
            'foreign_index' => $foreign_index,
            'local_name' => $local_name,
            'prefix' => $prefix,
        ];
    }
}
