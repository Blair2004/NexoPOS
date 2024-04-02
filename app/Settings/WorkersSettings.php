<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class WorkersSettings extends SettingsPage
{
    const IDENTIFIER = 'workers';

    const AUTOLOAD = true;

    public function __construct()
    {
        $options = app()->make( Options::class );

        $this->form = [
            'title' => __( 'Workers Settings' ),
            'description' => __( 'Configure how background operations works.' ),
            'tabs' => [
                'general' => include ( dirname( __FILE__ ) . '/workers/general.php' ),
            ],
        ];
    }
}
