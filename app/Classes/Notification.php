<?php

namespace App\Classes;

class Notification
{
    /**
     * Returns an array of actions passed as arguments.
     *
     * @param  mixed ...$args The actions to include in the array.
     * @return array The array of actions.
     */
    public static function actions( ...$args )
    {
        return $args;
    }

    /**
     * Creates an action with a label, URL, and optional message and data.
     *
     * @param  string $label   The label for the action.
     * @param  string $url     The URL for the action.
     * @param  string $message Optional message for the action.
     * @param  array  $data    Optional additional data for the action.
     * @return array  An associative array containing the action details.
     */
    public static function action( string $label, string $url, string $message = '', array $data = [] )
    {
        return compact( 'label', 'url', 'message' );
    }
}
