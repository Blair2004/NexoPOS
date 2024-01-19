<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class OrdersSettings extends SettingsPage
{
    const IDENTIFIER = 'orders';

    const AUTOLOAD = true;

    public function __construct()
    {
        $options = app()->make(Options::class);

        $this->form = [
            'title' => __('Orders Settings'),
            'description' => __('configure settings that applies to orders.'),
            'tabs' => [
                'layout' => include(dirname(__FILE__) . '/orders/general.php'),
            ],
        ];
    }
}
