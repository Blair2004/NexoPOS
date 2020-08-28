<?php
namespace App\Services\Helpers;

use Illuminate\Support\Facades\Storage;

trait Modules {
    /**
     * Returns the url to the module public folder
     * 
     * @param string of relative path to an asset
     * @return string a url to an asset
     */
    public static function moduleAssets( $namespace, string $path )
    {
        return asset( 'modules/' . strtolower( $namespace ) . '/' . $path );
    }
}