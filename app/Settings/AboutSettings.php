<?php

namespace App\Settings;

use App\Services\SettingsPage;
use Illuminate\Support\Facades\View;

class AboutSettings extends SettingsPage
{
    /**
     * The form will be automatically loaded.
     * You might prevent this by setting "autoload" to false.
     */
    const AUTOLOAD = true;

    /**
     * A unique identifier provided to the form,
     * that helps NexoPOS distinguish it among other forms.
     */
    const IDENTIFIER = 'about';

    public function getView()
    {
        return View::make( 'pages.dashboard.about', [
            'title' => __( 'About' ),
            'description' => __( 'Details about the environment.' ),
            'details' => [
                __( 'Core Version' ) => config( 'nexopos.version' ),
                __( 'Laravel Version' ) => app()->version(),
                __( 'PHP Version' ) => phpversion(),
            ],
            'extensions' => [
                __( 'Mb String Enabled' ) => extension_loaded( 'mbstring' ),
                __( 'Zip Enabled' ) => extension_loaded( 'zip' ),
                __( 'Curl Enabled' ) => extension_loaded( 'curl' ),
                __( 'Math Enabled' ) => extension_loaded( 'bcmath' ),
                __( 'XML Enabled' ) => extension_loaded( 'xml' ),
                __( 'XDebug Enabled' ) => extension_loaded( 'xdebug' ),
            ],
            'configurations' => [
                __( 'File Upload Enabled' ) => ( (bool) ini_get( 'file_uploads' ) ) ? __( 'Yes' ) : __( 'No' ),
                __( 'File Upload Size' ) => ini_get( 'upload_max_filesize' ),
                __( 'Post Max Size' ) => ini_get( 'post_max_size' ),
                __( 'Max Execution Time' ) => sprintf( __( '%s Second(s)' ), ini_get( 'max_execution_time' ) ),
                __( 'Memory Limit' ) => ini_get( 'memory_limit' ),
            ],
            'developpers' => [
                __( 'User' ) => exec( 'whoami' ),
                __( 'Path' ) => base_path(),
            ],
        ] );
    }
}
