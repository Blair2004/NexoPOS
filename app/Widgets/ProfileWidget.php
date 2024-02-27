<?php

namespace App\Widgets;

use App\Services\WidgetService;

class ProfileWidget extends WidgetService
{
    protected $vueComponent = 'nsProfileWidget';

    public function __construct()
    {
        $this->name = __( 'Profile' );
        $this->description = __( 'Will display a profile widget with user stats.' );
        $this->permission = 'nexopos.see.profile-widget';
    }
}
