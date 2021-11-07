<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

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
        
        $this->assertTrue(true);
    }
}
