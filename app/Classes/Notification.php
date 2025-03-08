<?php
namespace App\Classes;

class Notification
{
    public static function actions( ...$args )
    {
        return $args;
    }

    public static function action( string $label, string $url, string $message = '', array $data = [] )
    {
        return compact( 'label', 'url', 'message' );
    }
}