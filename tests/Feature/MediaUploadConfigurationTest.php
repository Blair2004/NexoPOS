<?php

namespace Tests\Feature;

use Tests\TestCase;

class MediaUploadConfigurationTest extends TestCase
{
    public function test_public_media_uploads_exclude_active_content_extensions(): void
    {
        $extensions = array_keys( config( 'medias.mimes' ) );

        $this->assertSame( [], array_values( array_intersect( [
            'css', 'htm', 'html', 'js', 'svg', 'swf', 'xhtml', 'xml', 'xul',
        ], $extensions ) ) );
        $this->assertContains( 'jpg', $extensions );
        $this->assertContains( 'png', $extensions );
        $this->assertContains( 'pdf', $extensions );
    }
}
