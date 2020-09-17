<?php
namespace App\Services;

use App\Models\BannedIP;

class IPBanner 
{
    public static function isBanned( $ip )
    {
        $client     =   self::getClient( $ip );
        if ( $client instanceof BannedIP ) {
            return $client->banned;
        }
        return false;
    }

    public static function getClient( $ip )
    {
        return BannedIP::searchIP( $ip )->first();
    }

    public static function saveClient( $ip, $user_agent )
    {
        $client                 =   new BannedIP;
        $client->ip             =   $ip;
        $client->location       =   '';
        $client->user_agent     =   $user_agent;
        $client->save();

        return $client;
    }

    public static function refreshDenyOnHtaccess()
    {
        $htaccessPath       =   config( 'tendoo.ip-banner.htaccess-path' );

        if ( is_file( $htaccessPath ) ) {
            $content        =   file_get_contents( $htaccessPath );
            $finalString    =   self::__getClientIPSet();

            if ( preg_match( '/\#IP-BANNER-START\#/', $content ) === 0 ) {
                file_put_contents( $htaccessPath, $content . "\n" . 
                    '#IP-BANNER-START#' .
                    "\nOrder Deny,Allow\n" .
                    $finalString .
                    '#IP-BANNER-END#'
                );
            } else {
                $content    =   preg_replace( '/(\#IP-BANNER-START\#((?:\n)*(.*)(?:\n)*)*\#IP-BANNER-END\#)/', '', $content );
                file_put_contents( $htaccessPath, $content . "\n" . 
                    '#IP-BANNER-START#' .
                    "\nOrder Deny,Allow\n" .
                    $finalString .
                    '#IP-BANNER-END#'
                );
            }
        }
    }

    public static function __getClientIPSet()
    {
        $clients        =   BannedIP::where( 'banned', true )->get();
        $finalString    =   '';

        $clients->each( function( $client ) use ( &$finalString ) {
            $finalString    .= 'Deny from ' . $client->ip . "\n";
        });

        return $finalString;
    }
}