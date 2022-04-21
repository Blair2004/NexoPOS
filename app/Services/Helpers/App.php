<?php
namespace App\Services\Helpers;

use App\Classes\Hook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        /**
         * This cache is requested once per request
         * and cleared by the end of the request
         * @see App\Http\Middleware\ClearRequestCacheMiddleware
         */
        return Cache::remember( 'ns-core-installed', 3600, function() {
            try {
                if( DB::connection()->getPdo() ){
                    return Schema::hasTable( 'nexopos_options' );
                }
            } catch (\Exception $e) {
                return false;
            }
        });
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