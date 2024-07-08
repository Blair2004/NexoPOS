<?php

namespace Tests\NewFeature;

use Tests\TestCase;

class CreateDatabaseTest extends TestCase
{
    public function testCreateSqlLiteDatabase()
    {
        file_put_contents( base_path( 'tests/database.sqlite' ), '' );
        $this->assertTrue( true );
    }
}
