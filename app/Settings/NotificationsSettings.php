<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class NotificationsSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'products'      =>  include( dirname( __FILE__ ) . '/notifications/products.php' ),
                'orders'        =>  include( dirname( __FILE__ ) . '/notifications/orders.php' ),
                // 'expenses'      =>  include( dirname( __FILE__ ) . '/notifications/expenses.php' ),
                'registration'  =>  include( dirname( __FILE__ ) . '/notifications/registration.php' ),
            ]
        ];
    }
}