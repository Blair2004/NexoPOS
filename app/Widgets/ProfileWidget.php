<?php

namespace App\Widgets;

use App\Services\WidgetService;

class ProfileWidget extends WidgetService
{
    protected $vueComponent = 'nsProfileWidget';

    protected string $layout = '1x3';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x3', '1x2' ];

    public function __construct()
    {
        $this->name = __( 'Profile' );
        $this->description = __( 'Will display a profile widget with user stats.' );
        $this->permission = 'nexopos.see.profile-widget';
    }
}
