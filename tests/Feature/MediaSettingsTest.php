<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Settings\MediaSettings;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaSettingsTest extends TestCase
{
    public function test_media_settings_can_switch_media_library_layout(): void
    {
        ns()->option->delete( 'ns_media_library_layout' );

        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $settings = new MediaSettings;

        $this->assertSame( 'media', $settings->getIdentifier() );
        $this->assertSame( 'modern', $settings->getForm()['tabs']['general']['fields'][0]['value'] );

        $response = $this
            ->withSession( $this->app['session']->all() )
            ->json( 'POST', '/api/settings/media', [
                'general' => [
                    'ns_media_library_layout' => 'legacy',
                ],
            ] );

        $response->assertJsonPath( 'status', 'success' );
        $this->assertSame( 'legacy', ns()->option->get( 'ns_media_library_layout' ) );
    }
}
