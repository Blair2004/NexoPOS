<?php

namespace App\Widgets;

use App\Services\WidgetService;

class MyNexoPosWidget extends WidgetService
{
    protected $vueComponent = 'nsMyNexoPosWidget';

    protected string $layout = '1x3';

    protected string $layoutPolicy = 'restricted';

    protected array $supportedLayouts = [ '1x3', '1x2', '2x3' ];

    public function __construct()
    {
        $this->name = __( 'My NexoPOS' );
        $this->description = __( 'Invite users to connect this installation to My NexoPOS and unlock marketplace extensions.' );
        $this->permission = 'manage.modules';
    }

    public function getData(): array
    {
        return [
            'isConnected' => ! empty( ns()->option->get( 'mynexopos_access_token' ) ) && ! empty( ns()->option->get( 'mynexopos_refresh_token' ) ),
        ];
    }
}
