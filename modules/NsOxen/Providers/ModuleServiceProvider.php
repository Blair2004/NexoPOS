<?php

namespace Modules\NsOxen\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Classes\AsideMenu;
use App\Services\SettingsPage;
use Modules\NsOxen\Settings\OxenSettings;
use TorMorten\Eventy\Facades\Events as Hook;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\ToolRegistry;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton( ToolRegistry::class );
        $this->app->singleton( OpenAIProvider::class );
        Hook::addFilter( "ns.settings", function ( SettingsPage|string|false $class, string $identifier ): SettingsPage|string|false {
            return $identifier === OxenSettings::IDENTIFIER ? new OxenSettings : $class;
        }, 10, 2 );
    }

    public function boot(): void
    {
        RateLimiter::for( 'oxen-mcp', fn( Request $request ) => [Limit::perMinute( 120 )->by( 'read:' . ( $request->user()?->id ?: $request->ip() ) ), Limit::perMinute( 20 )->by( 'write:' . ( $request->user()?->id ?: $request->ip() ) )] );
        RateLimiter::for( 'oxen-assistant', fn( Request $request ) => Limit::perMinute( 20 )->by( (string) ( $request->user()?->id ?: $request->ip() ) ) );
        Hook::addFilter( 'ns-dashboard-menus', function ( array $menus ): array {
            if ( isset( $menus['settings'] ) ) {
                $menus['settings']['childrens'] = [ ...$menus['settings']['childrens'], ...AsideMenu::subMenu( label: __m( 'Oxen Settings', 'NsOxen' ), identifier: OxenSettings::IDENTIFIER, href: ns()->route( 'ns.dashboard.settings', ['settings' => OxenSettings::IDENTIFIER] ), permissions: ['ns.oxen.manage'] ) ];
            }
            return $menus;
        }, 30 );
    }
}
