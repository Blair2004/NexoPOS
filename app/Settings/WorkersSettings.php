<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class WorkersSettings extends SettingsPage
{
    protected $identifier = 'ns.workers';

    public function __construct()
    {
        $options = app()->make( Options::class );

        $this->form = [
            'tabs' => [
                'general' => include( dirname( __FILE__ ) . '/workers/general.php' ),
            ],
        ];
    }
}
