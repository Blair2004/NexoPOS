<?php

namespace App\Classes;

class Notice
{
    /**
     * Generates an informational notice with a title and description.
     *
     * @param  string $title       The title of the notice.
     * @param  string $description The description of the notice.
     * @return array  An associative array containing the title, description, and color of the notice.
     */
    public static function info( $title, $description )
    {
        $color = 'info';

        return compact( 'title', 'description', 'color' );
    }

    /**
     * Generates a warning notice with a title and description.
     *
     * @param  string $title       The title of the notice.
     * @param  string $description The description of the notice.
     * @return array  An associative array containing the title, description, and color of the notice.
     */
    public static function warning( $title, $description )
    {
        $color = 'warning';

        return compact( 'title', 'description', 'color' );
    }

    /**
     * Generates an error notice with a title and description.
     *
     * @param  string $title       The title of the notice.
     * @param  string $description The description of the notice.
     * @return array  An associative array containing the title, description, and color of the notice.
     */
    public static function error( $title, $description )
    {
        $color = 'error';

        return compact( 'title', 'description', 'color' );
    }

    /**
     * Generates a success notice with a title and description.
     *
     * @param  string $title       The title of the notice.
     * @param  string $description The description of the notice.
     * @return array  An associative array containing the title, description, and color of the notice.
     */
    public static function success( $title, $description )
    {
        $color = 'success';

        return compact( 'title', 'description', 'color' );
    }
}
