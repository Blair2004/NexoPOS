<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestLanguageFileValidity extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_language_validity()
    {
        $files = Storage::disk( 'ns' )->allFiles( 'lang' );

        foreach ( $files as $file ) {
            $content = file_get_contents( base_path( $file ) );

            $this->assertTrue( $this->checkValidity( $content ), sprintf(
                'The file "%s" is not valid',
                $file
            ) );
        }
    }

    private function checkValidity( $content )
    {
        if ( ! empty( $content ) ) {
            @json_decode( $content );

            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }
}
