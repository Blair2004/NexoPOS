<?php
use App\Services\Helper;
use App\Services\DateService;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{!! Helper::pageTitle( $title ?? __( 'Unamed Page' ) ) !!}</title>
    <link rel="stylesheet" href="{{ asset( 'css/app.css' ) }}">
    @yield( 'layout.dashboard.header' )
    <script>
        /**
         * constant where is registered
         * global custom components
         * @param {Object}
         */
        const nsExtraComponents     =   new Object;

        /**
         * describe a global NexoPOS object
         * @param {object} ns
         */
        const ns =   { nsExtraComponents };

        /**
         * store the server date
         * @param {string}
         */
        ns.date                     =   {
            current : '{{ app()->make( DateService::class )->toDateTimeString() }}',
        }
    </script>
</head>
<body>
    <div class="h-full w-full flex flex-col">
        <div id="dashboard-body" class="overflow-hidden flex flex-auto">
            <div id="dashboard-aside" v-cloak v-if="sidebar === 'visible'" class="w-64 z-10 absolute md:static flex-shrink-0 bg-gray-900 h-full flex-col overflow-hidden">
                <div class="overflow-y-auto h-full text-sm">
                    <div class="logo py-4 flex justify-center items-center">
                        <h1 class="font-black text-transparent bg-clip-text bg-gradient-to-b from-blue-400 to-purple-400 text-3xl">NexoPOS</h1>
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
            <div id="dashboard-overlay" v-if="sidebar === 'visible'" @click="closeMenu()" class="w-full h-full md:hidden absolute" style="background: rgb(51 51 51 / 25%)"></div>
            <div class="flex flex-auto overflow-hidden bg-gray-200">
                <div class="overflow-y-auto flex-auto">
                    @yield( 'layout.dashboard.body', View::make( 'common.dashboard.with-header' ) )
                </div>
            </div>
        </div>
    </div>
    @section( 'layout.dashboard.footer' )
        @include( '../common/footer' )
        <script src="{{ asset( 'js/app.js' ) }}"></script>
    @show
</body>
</html>