<?php

namespace App\Services\Helpers;

use App\Classes\Hook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait App
{
    /**
     * Is installed
     *
     * @return bool
     */
    public static function installed()
    {
        if ( ! Cache::has( 'ns-core-installed' ) ) {
            Cache::set( 'ns-core-installed', (bool) self::checkDatabaseExistence(), 60 );
        }

        return (bool) Cache::get( 'ns-core-installed' );
    }

    private static function checkDatabaseExistence()
    {
        try {
            if ( DB::connection()->getPdo() ) {
                return Schema::hasTable( 'nexopos_options' );
            }
        } catch ( \Exception $e ) {
            return false;
        }
    }

    public static function pageTitle( $string )
    {
        $storeName = ns()->option->get( 'ns_store_name' ) ?: 'NexoPOS';

        return sprintf(
            Hook::filter( 'ns-page-title', __( '%s &mdash; %s' ) ),
            $string,
            $storeName
        );
    }

    public static function tableHasIndex( $table, $indexName )
    {
        $databaseName = DB::getDatabaseName();

        return DB::table( 'information_schema.statistics' )
            ->where( 'table_schema', $databaseName )
            ->where( 'table_name', $table )
            ->where( 'index_name', $indexName )
            ->exists();
    }

    /**
     * Checks if the "back" query parameter
     * has a valid URL otherwise uses the
     * previous URL stored on the session.
     */
    public static function getValidPreviousUrl( Request $request ): string
    {
        if ( $request->has( 'back' ) ) {
            $backUrl = $request->query( 'back' );
            $parsedUrl = parse_url( $backUrl );
            $host = $parsedUrl['host'];

            if ( filter_var( $backUrl, FILTER_VALIDATE_URL ) && $host === parse_url( env( 'APP_URL' ), PHP_URL_HOST ) ) {
                return urldecode( $backUrl );
            }
        }

        return url()->previous();
    }
}
