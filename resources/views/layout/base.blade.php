<?php

use App\Classes\Hook;
use App\Classes\Output;
use App\Models\UserAttribute;
use App\Services\DateService;
use App\Services\Helper;
use Illuminate\Support\Facades\Auth;

if ( Auth::check() && Auth::user()->attribute instanceof UserAttribute ) {
    $theme  =   Auth::user()->attribute->theme ?: ns()->option->get( 'ns_default_theme', 'light' );
} else {
    $theme  =   ns()->option->get( 'ns_default_theme', 'light' );
}
?>

@inject( 'dateService', 'App\Services\DateService' )
<!DOCTYPE html>
<html lang="en" class="{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! $title ?? __( 'Unamed Page' ) !!}</title>
    <?php 
        $output     =   new Output;
        Hook::action( "ns-dashboard-header", $output );
        echo ( string ) $output;
    ?>
    @vite([
        'resources/scss/line-awesome/1.3.0/scss/line-awesome.scss',
        'resources/scss/grid.scss',
        'resources/scss/fonts.scss',
        'resources/scss/animations.scss',
        'resources/scss/typography.scss',
        'resources/scss/app.scss',
        'resources/scss/' . $theme . '.scss'
    ])
    @yield( 'layout.base.header' )
    <script>
        /**
         * constant where is registered
         * global custom components
         * @param {Object}
         */
        window.nsExtraComponents     =   new Object;

        /**
         * describe a global NexoPOS object
         * @param {object} ns
         */
        window.ns =   { nsExtraComponents };

        /**
         * store the server date
         * @param {string}
         */
        window.ns.date                     =   {
            current : '{{ app()->make( DateService::class )->toDateTimeString() }}',
            serverDate : '{{ app()->make( DateService::class )->toDateTimeString() }}',
            timeZone: '{{ ns()->option->get( "ns_datetime_timezone", "Europe/London" ) }}',
            format: `{{ $dateService->convertFormatToMomment( ns()->option->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) ) }}`
        }

        /**
         * define the current language selected by the user or
         * the language that applies to the system by default.
         */
        window.ns.language      =   '{{ app()->getLocale() }}';
        window.ns.langFiles     =   <?php echo json_encode( Hook::filter( 'ns.langFiles', [
            'NexoPOS'   =>  asset( "/lang/" . app()->getLocale() . ".json" ),
        ]));?>
    </script>
    @vite([ 'resources/ts/lang-loader.ts' ])
</head>
<body>
    @yield( 'layout.base.body' )
    @section( 'layout.base.footer' )
        @include( 'common.footer' )
    @show
</body>
</html>
