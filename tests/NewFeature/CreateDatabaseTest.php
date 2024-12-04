<?php

namespace Tests\NewFeature;

use Tests\TestCase;

class CreateDatabaseTest extends TestCase
{
    public function test_create_sql_lite_database()
    {
        file_put_contents( base_path( 'tests/database.sqlite' ), '' );
        $this->assertTrue( true );
    }
}
