<?php

namespace Tests\Feature;

use App\Models\Option;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class DeleteExpiredOptionsTest extends TestCase
{
    use WithAuthentication;

    public function test_it_deletes_expired_options_and_keeps_active_options(): void
    {
        $this->attemptAuthenticate();

        ns()->option->set( '_expired_option', 'expired', now()->subMinute() );
        ns()->option->set( '_active_option', 'active', now()->addMinute() );

        $this->assertSame( ns()->option, ns()->options );
        $this->assertSame( 1, ns()->options->deleteExpired() );

        $this->assertNull( ns()->option->get( '_expired_option' ) );
        $this->assertSame( 'active', ns()->option->get( '_active_option' ) );
        $this->assertFalse( Option::where( 'key', '_expired_option' )->exists() );
        $this->assertTrue( Option::where( 'key', '_active_option' )->exists() );

        ns()->option->delete( '_active_option' );
    }
}
