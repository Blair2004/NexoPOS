<?php

namespace App\Classes;

class Model
{
    /**
     * Define a dependant relationship between two models.
     *
     * @param  string      $local_name    The name of the local model.
     * @param  string      $foreign_index The foreign key index in the related model.
     * @param  string|null $foreign_name  The name of the related model (optional).
     * @param  array       $related       Additional related data (optional).
     * @param  string      $local_index   The local key index (default is 'id').
     * @return array       An associative array defining the dependant relationship.
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
     * Define a related relationship between two models.
     *
     * @param  string        $model         The fully qualified class name of the related model.
     * @param  string        $foreign_index The foreign key index in the related model.
     * @param  string        $local_name    The name of the local model.
     * @param  callable|null $prefix        A callable to modify the relationship prefix (optional).
     * @param  string        $local_index   The local key index (default is 'id').
     * @return array         An associative array defining the related relationship.
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
