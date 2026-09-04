<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SetupCommandTest extends TestCase
{
    public function test_interactive_language_choice_is_mapped_to_its_language_code(): void
    {
        Cache::put( 'ns-core-installed', false, 60 );

        $this->artisan( 'ns:setup' )
            ->expectsChoice(
                __( 'In which language would you like to install NexoPOS ?' ),
                'English',
                array_values( config( 'nexopos.languages' ) )
            )
            ->expectsQuestion( __( 'What is the store name ? [Q] to quit.' ), 'NexoPOS Store' )
            ->expectsQuestion( __( 'What is the administrator username ? [Q] to quit.' ), 'admin' )
            ->expectsQuestion( __( 'What is the administrator password ? [Q] to quit.' ), 'password' )
            ->expectsQuestion( __( 'What is the administrator email ? [Q] to quit.' ), 'admin@example.com' )
            ->expectsQuestion( 'Everything seems ready. Would you like to proceed ? [Y]/[N]', 'n' )
            ->expectsOutput( 'The installation has been aborted.' )
            ->assertSuccessful();
    }
}
