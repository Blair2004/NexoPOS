<?php

namespace Tests\Feature;

use App\Services\WidgetService;
use App\Widgets\MyNexoPosWidget;
use Tests\TestCase;

class WidgetServiceTest extends TestCase
{
    /**
     * The dashboard exposes the My NexoPOS invite widget to eligible users.
     */
    public function test_my_nexopos_widget_is_registered(): void
    {
        $widgets = app( WidgetService::class )->getAllWidgets();

        $widget = $widgets->firstWhere( 'class-name', MyNexoPosWidget::class );

        $this->assertNotNull( $widget );
        $this->assertSame( 'My NexoPOS', $widget->name );
        $this->assertSame( 'nsMyNexoPosWidget', $widget->{'component-name'} );
        $this->assertSame( 'manage.modules', $widget->instance->getPermission() );
        $this->assertIsArray( $widget->data );
        $this->assertArrayHasKey( 'isConnected', $widget->data );
        $this->assertIsBool( $widget->data[ 'isConnected' ] );
    }
}
