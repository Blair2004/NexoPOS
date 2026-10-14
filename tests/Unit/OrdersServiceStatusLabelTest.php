<?php

namespace Tests\Unit;

use App\Services\OrdersService;
use Illuminate\Contracts\Translation\Translator;
use Mockery;
use Tests\TestCase;

class OrdersServiceStatusLabelTest extends TestCase
{
    public function test_unknown_order_status_labels_use_the_application_translator(): void
    {
        $translator = Mockery::mock( Translator::class );
        $translator->shouldReceive( 'get' )
            ->byDefault()
            ->andReturnUsing( fn ( string $key ): string => $key );
        $translator->shouldReceive( 'get' )
            ->twice()
            ->with( 'Unknown Status (%s)', [], null )
            ->andReturn( 'Translated Status (%s)' );
        $translator->shouldReceive( 'get' )
            ->once()
            ->with( 'Unknown Delivery (%s)', [], null )
            ->andReturn( 'Translated Delivery (%s)' );
        $this->app->instance( 'translator', $translator );

        $ordersService = app( OrdersService::class );

        $this->assertSame( 'Translated Status (missing)', $ordersService->getShippingLabel( 'missing' ) );
        $this->assertSame( 'Translated Status (missing)', $ordersService->getProcessStatus( 'missing' ) );
        $this->assertSame( 'Translated Delivery (missing)', $ordersService->getDeliveryStatus( 'missing' ) );
    }
}
