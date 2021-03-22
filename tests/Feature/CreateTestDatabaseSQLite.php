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
    public function test_example()
    {
        DotenvEditor::deleteKey( 'NS_VERSION' );
        DotenvEditor::save();

        file_put_contents( dirname( __FILE__ ) . '/../database.sqlite', '' );
        
        $this->assertTrue(true);
    }
}
