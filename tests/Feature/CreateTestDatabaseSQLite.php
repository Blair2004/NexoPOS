<?php

namespace Tests\Feature;

use Tests\TestCase;

class CreateTestDatabaseSQLite extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_sql_database()
    {
        file_put_contents( base_path( 'tests/database.sqlite' ), '' );

        $this->assertTrue( true );
    }
}
