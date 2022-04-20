<?php
namespace App\Services\Helpers;

use App\Classes\Hook;
use Illuminate\Support\Facades\DB;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait App {
    /**
     * Is installed
     * @return boolean
     */
    static function installed()
    {
        return env( 'NS_VERSION', false ) !== false;
    }

    /**
     * Load application interfaces
     * @param string interface path
     * @return View interface
     */
    static function LoadInterface( $path, $data = [] )
    {
        return View::make( 'tendoo::interfaces.' . $path, $data );
    }

    static function pageTitle( $string )
    {
        return sprintf( 
            Hook::filter( 'ns-page-title', __( '%s &mdash; NexoPOS 4' ) ), 
        $string );
    }
}