<?php

namespace App\Guides;

use App\Classes\Guide;

class DashboardGuide
{
    public static function init( $guideService )
    {
        return $guideService->register(
            'dashboard.guide',
            Guide::experience(
                id: 'dashboard.guide',
                title: __( 'Welcome to NexoPOS' ),
                description: __(
                    'Take a quick tour of your dashboard and discover the main areas of NexoPOS.'
                ),
                permissions: ['manage.options'],
                required_routes: ['ns.dashboard.home'],
                steps: Guide::steps(

                    Guide::step(
                        id: 'dashboard-overview',
                        element: '#aside-menu',
                        popover: Guide::popover(
                            title: __( 'Your Menu' ),

                            description: __(
                                'Use this side menu to access various areas within NexoPOS including settings, inventory, orders, customers and users.'
                            ),

                            side: 'bottom',
                            align: 'center'
                        ),
                    ),

                    Guide::step(
                        id: 'main-navigation',
                        element: '[data-widget-identifier="nsIncompleteSaleCardWidget"]',
                        popover: Guide::popover(
                            title: __( 'Dashboard Widgets' ),

                            description: __(
                                'These are dashboard widgets that provide quick insights into your store\'s performance.'
                            ),

                            side: 'left',
                            align: 'center'
                        ),

                        nextAction: [
                            'type' => 'click',
                            'element' => '#menu-settings a',
                        ],
                    ),

                    Guide::step(
                        id: 'settings',

                        element: '#submenu-general',

                        popover: Guide::popover(
                            title: __( 'Configure your store' ),

                            description: __(
                                'You probably want to get started by configuring your store, including: your store name, currency, datetime format.'
                            ),
                            side: 'right',
                            align: 'center'
                        ),

                        waitForElement: 1000,

                        nextAction: [
                            'type' => 'click',
                            'element' => '#menu-inventory a',
                        ],

                    ),

                    Guide::step(
                        id: 'inventory-menu',

                        element: '#menu-inventory',

                        popover: Guide::popover(
                            title: __( 'Inventory' ),

                            description: __(
                                'Inventory contains everything you need to manage products, categories, units, suppliers, procurements, and stock.'
                            ),

                            side: 'right',
                            align: 'center'
                        ),

                        nextAction: [
                            'type' => 'click',
                            'element' => '#menu-orders a',
                        ],
                    ),

                    Guide::step(
                        id: 'orders-menu',

                        element: '#menu-orders',

                        popover: Guide::popover(
                            title: __( 'Orders' ),

                            description: __(
                                'Every sale created with NexoPOS is recorded as an order. You can review previous transactions from here.'
                            ),

                            side: 'right',
                            align: 'center'
                        ),

                        nextAction: [
                            'type' => 'click',
                            'element' => '#menu-customers a',
                        ],
                    ),

                    Guide::step(
                        id: 'customers-menu',

                        element: '#menu-customers',

                        popover: Guide::popover(
                            title: __( 'Customers' ),

                            description: __(
                                'Manage your customers and keep track of their purchases and account information from this section.'
                            ),

                            side: 'right',
                            align: 'center'
                        ),

                        nextAction: [
                            'type' => 'click',
                            'element' => '#menu-reports a',
                        ],
                    ),

                    Guide::step(
                        id: 'reports-menu',

                        element: '#menu-reports',

                        popover: Guide::popover(
                            title: __( 'Reports' ),

                            description: __(
                                'Reports help you understand how your store is performing by providing information about sales, inventory, customers, and other activities.'
                            ),

                            side: 'right',
                            align: 'center'
                        ),
                    ),
                )
            )
        );
    }
}
