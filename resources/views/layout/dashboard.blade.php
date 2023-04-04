<?php

use App\Classes\Hook;
use App\Services\Helper;
use App\Services\DateService;
use Illuminate\Support\Facades\Auth;

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
    <title>{!! Helper::pageTitle( $title ?? __( 'Unnamed Page' ) ) !!}</title>
    <link rel="stylesheet" href="{{ loadcss( 'grid.css' ) }}">
    <link rel="stylesheet" href="{{ loadcss( 'fonts.css' ) }}">
    <link rel="stylesheet" href="{{ loadcss( 'animations.css' ) }}">
    <link rel="stylesheet" href="{{ loadcss( 'typography.css' ) }}">
    <link rel="stylesheet" href="{{ loadcss( 'app.css' ) }}">
    <link rel="stylesheet" href="{{ asset( 'css/line-awesome.css' ) }}">
    <link rel="stylesheet" href="{{ loadcss( $theme . '.css' ) }}">
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
            timeZone: '{{ ns()->option->get( "ns_datetime_timezone" ) }}',
            format: `{{ ns()->option->get( 'ns_datetime_format' ) }}`
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

        window.ns.cssFiles      =   <?php echo file_get_contents( base_path( 'public/css-manifest.json' ) );?>;
    </script>
    <script src="{{ asset( ns()->isProduction() ? 'js/lang-loader.min.js' : 'js/lang-loader.js' ) }}"></script>
@include( 'common.header-socket' )
</head>
<body <?php echo in_array( app()->getLocale(), config( 'nexopos.rtl-languages' ) ) ? 'dir="rtl"' : "";?>>
    <div class="h-full w-full flex flex-col">
        <div id="dashboard-body" class="overflow-hidden flex flex-auto">
            <div id="dashboard-aside" v-cloak v-if="sidebar === 'visible'" class="w-64 z-50 absolute md:static flex-shrink-0 h-full flex-col overflow-hidden">
                <div class="ns-scrollbar overflow-y-auto h-full text-sm">
                    <div class="logo py-4 flex justify-center items-center">
                        @if ( ns()->option->get( 'ns_store_rectangle_logo' ) )
                        <img src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" class="w-11/12" alt="logo"/>
                        @else
                        <h1 class="font-black text-transparent bg-clip-text bg-gradient-to-b from-blue-200 to-indigo-400 text-3xl">NexoPOS</h1>
                        @endif
                    </div>
                    <ul>
                        @foreach( $menus->getMenus() as $identifier => $menu )
                            @if ( isset( $menu[ 'permissions' ] ) && Auth::user()->allowedTo( $menu[ 'permissions' ], 'some' ) || ! isset( $menu[ 'permissions' ] ) )
                            <ns-menu identifier="{{ $identifier }}" toggled="{{ $menu[ 'toggled' ] ?? '' }}" label="{{ @$menu[ 'label' ] }}" icon="{{ @$menu[ 'icon' ] }}" href="{{ @$menu[ 'href' ] }}" notification="{{ isset( $menu[ 'notification' ] ) ? $menu[ 'notification' ] : 0 }}" id="menu-{{ $identifier }}">
                                @if ( isset( $menu[ 'childrens' ] ) )
                                    @foreach( $menu[ 'childrens' ] as $identifier => $menu )
                                        @if ( isset( $menu[ 'permissions' ] ) && Auth::user()->allowedTo( $menu[ 'permissions' ], 'some' ) || ! isset( $menu[ 'permissions' ] ) )
                                    <ns-submenu :active="{{ ( isset( $menu[ 'active' ] ) ? ( $menu[ 'active' ] ? 'true' : 'false' ) : 'false' ) }}" href="{{ $menu[ 'href' ] }}" id="submenu-{{ $identifier }}">{{ $menu[ 'label' ] }}</ns-submenu>
                                        @endif
                                    @endforeach
                                @endif
                            </ns-menu>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            <div id="dashboard-overlay" v-if="sidebar === 'visible'" @click="closeMenu()" class="z-40 w-full h-full md:hidden absolute" style="background: rgb(51 51 51 / 25%)"></div>
            <div class="flex flex-auto flex-col overflow-hidden" id="dashboard-body">
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
                <div class="p-2 text-xs flex justify-end text-gray-500">
                    {!!
                        Hook::filter( 'ns-footer-signature', sprintf( __( 'You\'re using <a tager="_blank" href="%s" class="hover:text-blue-400 mx-1 inline-block">NexoPOS %s</a>' ), 'https://my.nexopos.com/en', config( 'nexopos.version' ) ) )
                    !!}
                </div>
            </div>
        </div>
    </div>
    @section( 'layout.dashboard.footer' )
        @include( '../common/footer' )
        <script defer src="{{ asset( ns()->isProduction() ? 'js/app.min.js' : 'js/app.js' ) }}"></script>
    @show
</body>
</html>
