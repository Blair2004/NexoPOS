<?php

use App\Classes\Hook;
use App\Classes\Output;
use App\Services\DateService;
use App\Services\Helper;
use App\Services\MenuService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

/**
 * @var MenuService $menus
 */
$menus  =   app()->make( MenuService::class );

/**
 * @var MenuService $menus
 */
$dateService  =   app()->make( DateService::class );

if ( Auth::check() ) {
    $theme  =   Auth::user()->attribute->theme ?: ns()->option->get( 'ns_default_theme', 'light' );
} else {
    $theme  =   ns()->option->get( 'ns_default_theme', 'light' );
}
?>
<!DOCTYPE html>
<html lang="en" class="{{ $theme }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! Helper::pageTitle( $title ?? __( 'Unamed Page' ) ) !!}</title>
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
    @yield( 'layout.dashboard.header' )
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
        window.ns   =   { nsExtraComponents };

        /**
         * store the server date
         * @param {string}
         */
        window.ns.date  =   {
            current : '{{ app()->make( DateService::class )->toDateTimeString() }}',
            serverDate : '{{ app()->make( DateService::class )->toDateTimeString() }}',
            timeZone: '{{ ns()->option->get( "ns_datetime_timezone", "Europe/London" ) }}',
            format: `{{ $dateService->convertFormatToMomment( ns()->option->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) ) }}`
        }

        /**
         * Let's define the actul theme used
         */
        window.ns.theme     =   `{{ $theme }}`;

        /**
         * define the current language selected by the user or
         * the language that applies to the system by default.
         */
        window.ns.language      =   '{{ app()->getLocale() }}';
        window.ns.langFiles     =   <?php echo json_encode( Hook::filter( 'ns.langFiles', [
            'NexoPOS'   =>  asset( "/lang/" . app()->getLocale() . ".json" ),
        ]));?>

        /**
         * We display only fillable values for the
         * logged user. The password might be displayed on an encrypted form.
         */
        window.ns.user              =   <?php echo json_encode( ns()->getUserDetails() );?>;
        window.ns.user.attributes   =   <?php echo json_encode( Auth::user()->attribute->first() );?>;
        window.ns.cssFiles          =   <?php echo json_encode( ns()->simplifyManifest() );?>;

        /**
         * We'll store here the file mime types
         * that are supported by the media manager.
         */
        window.ns.medias            =   {
            mimes:  <?php echo json_encode( ns()->mediaService->getMimes() )?>,
            imageMimes: <?php echo json_encode( ns()->mediaService->getImageMimes() );?>
        }
    </script>
    @vite([ 'resources/ts/lang-loader.ts' ])
</head>
<body <?php echo in_array( app()->getLocale(), config( 'nexopos.rtl-languages' ) ) ? 'dir="rtl"' : "";?>>
    <div class="h-full w-full flex flex-col">
        <div class="overflow-hidden flex flex-auto">
            <div id="dashboard-body" class="flex flex-auto flex-col overflow-hidden">
                <div class="overflow-y-auto flex-auto">
                    @hasSection( 'layout.dashboard.body' )
                        @yield( 'layout.dashboard.body' )
                    @endif

                    @hasSection( 'layout.dashboard.body.with-header' )
                        @include( 'common.dashboard.with-header' )
                    @endif

                    @hasSection( 'layout.dashboard.with-header' )
                        @include( 'common.dashboard.with-header' )
                    @endif

                    @hasSection( 'layout.dashboard.body.with-title' )
                        @include( 'common.dashboard.with-title' )
                    @endif

                    @hasSection( 'layout.dashboard.with-title' )
                        @include( 'common.dashboard.with-title' )
                    @endif
                </div>
            </div>
        </div>
    </div>
    @section( 'layout.dashboard.footer' )
        @include( 'common.popups' )
        @include( 'common.dashboard-footer' )
        @vite([ 'resources/ts/app.ts' ])
    @show
</body>
</html>
