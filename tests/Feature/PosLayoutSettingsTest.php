<?php

namespace Tests\Feature;

use App\Support\PosLayout;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class PosLayoutSettingsTest extends TestCase
{
    use WithAuthentication;

    public function test_layout_setting_exposes_canonical_choices_and_normalizes_legacy_values(): void
    {
        $previousLayout = ns()->option->get( 'ns_pos_layout' );
        ns()->option->set( 'ns_pos_layout', 'grocery_shop' );

        try {
            $layout = require base_path( 'app/Settings/pos/layout.php' );
            $field = collect( $layout['fields'] )->firstWhere( 'name', 'ns_pos_layout' );

            $this->assertSame( PosLayout::Split, $field['value'] );
            $this->assertSame( [ PosLayout::Split, PosLayout::Unified ], collect( $field['options'] )->pluck( 'value' )->all() );
            $this->assertSame( [ __( 'Split Layout' ), __( 'Unified Layout' ) ], collect( $field['options'] )->pluck( 'label' )->all() );
        } finally {
            ns()->option->set( 'ns_pos_layout', $previousLayout );
        }
    }

    public function test_pos_runtime_contains_the_normalized_layout(): void
    {
        $this->attemptAuthenticate();
        $previousLayout = ns()->option->get( 'ns_pos_layout' );
        $previousReader = ns()->option->get( 'ns_pos_barcode_reader_type' );
        ns()->option->set( 'ns_pos_layout', 'clothing_shop' );
        ns()->option->set( 'ns_pos_barcode_reader_type', 'regular' );

        try {
            $this->get( '/dashboard/pos' )
                ->assertOk()
                ->assertSee( '"ns_pos_layout":"split"', false );

            ns()->option->set( 'ns_pos_layout', PosLayout::Unified );

            $this->get( '/dashboard/pos' )
                ->assertOk()
                ->assertSee( '"ns_pos_layout":"unified"', false );
        } finally {
            ns()->option->set( 'ns_pos_layout', $previousLayout );
            ns()->option->set( 'ns_pos_barcode_reader_type', $previousReader );
        }
    }
}
