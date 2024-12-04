<?php

namespace App\Classes;

class Notice
{
    public static function info( $title, $description )
    {
        $color = 'info';

        return compact( 'title', 'description', 'color' );
    }

    public static function warning( $title, $description )
    {
        $color = 'warning';

        return compact( 'title', 'description', 'color' );
    }

    public static function error( $title, $description )
    {
        $color = 'error';

        return compact( 'title', 'description', 'color' );
    }

    public static function success( $title, $description )
    {
        $color = 'success';

        return compact( 'title', 'description', 'color' );
    }
}
