<?php
namespace App\Services\Helpers;

use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

trait App {
    /**
     * Is installed
     * @return boolean
     */
    static function installed()
    {
        $env    =   DotenvEditor::getKeys();
        return @$env[ 'NS_VERSION' ][ 'value' ] !== null;
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
        return sprintf( __( '%s &mdash; NexoPOS 4' ), $string );
    }
}