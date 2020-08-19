<?php
namespace App\Services;

class MenuService
{
    protected $menus;

    public function __construct()
    {
        $this->buildMenus();
        $this->toggleActive();
    }

    public function buildMenus()
    {
        $this->menus    =   [
            'dashboard' =>  [
                'label' =>  __( 'Dashboard' ),
                'href'  =>  url( '/dashboard' ),
                'icon'  =>  'la-home',
                'childrens'     =>  [
                    'updates'   =>  [
                        'label' =>  __( 'Updates'),
                        'href'  =>  url( '/dashboard/updates' )
                    ], 
                    'about'     =>  [
                        'label' =>  __( 'About'),
                        'href'  =>  url( '/dashboard/about' )
                    ]
                ]
            ], 
            'pos'   =>  [
                'label' =>  __( 'POS' ),
                'icon'  =>  'la-cash-register',
                'href'  =>  url( '/dashboard/pos' )
            ], 
            'orders'    =>  [
                'label' =>  __( 'Orders' ),
                'icon'  =>  'la-list-ol',
                'href'  =>  url( '/dashboard/orders' )
            ], 
            'customers' =>  [
                'label' =>  __( 'Customers' ),
                'icon'  =>  'la-user-friends',
                'childrens'     =>  [
                    'customers' =>  [
                        'label' =>  __( 'List'),
                        'href'  =>  url( '/dashboard/customers' )
                    ], 
                    'create-customer'  =>   [
                        'label' =>  __( 'Create Customer'),
                        'href'  =>  url( '/dashboard/customers/create' )
                    ], 
                    'customers-groups'  =>  [
                        'label' =>  __( 'Customers Groups'),
                        'href'  =>  url( '/dashboard/customers/groups' )
                    ], 
                    'create-customers-group'    =>  [
                        'label' =>  __( 'Create Group'),
                        'href'  =>  url( '/dashboard/customers/groups/create' )
                    ], 
                    'import-customers'  =>  [
                        'label' =>  __( 'Import Customers'),
                        'href'  =>  url( '/dashboard/customers/import' )
                    ],
                    'list-reward-system'    =>  [
                        'label' =>  __( 'Reward Systems'),
                        'href'  =>  url( '/dashboard/customers/rewards-system' )
                    ],
                    'create-reward-system'    =>  [
                        'label' =>  __( 'Create Reward'),
                        'href'  =>  url( '/dashboard/customers/rewards-system/create' )
                    ],
                    'list-coupons'    =>  [
                        'label' =>  __( 'List Coupons'),
                        'href'  =>  url( '/dashboard/customers/coupons' )
                    ],
                    'create-coupons'    =>  [
                        'label' =>  __( 'Create Coupon'),
                        'href'  =>  url( '/dashboard/customers/coupons/create' )
                    ],
                ]
            ], 
            'providers' =>  [
                'label' =>  __( 'Providers' ),
                'icon'  =>  'la-user-tie',
                'childrens'     =>  [
                    'providers' =>  [
                        'label' =>  __( 'List'),
                        'href'  =>  url( '/dashboard/providers' )
                    ], 
                    'create-provider'   =>  [
                        'label' =>  __( 'Create Providers'),
                        'href'  =>  url( '/dashboard/providers/create' )
                    ]
                ]
            ], 
            'expenses' =>  [
                'label' =>  __( 'Expenses' ),
                'icon'  =>  'la-money-bill-wave',
                'childrens'     =>  [
                    'expenses' =>  [
                        'label' =>  __( 'Expenses'),
                        'href'  =>  url( '/dashboard/expenses' )
                    ], 
                    'create-provider'   =>  [
                        'label' =>  __( 'Create Expense'),
                        'href'  =>  url( '/dashboard/expenses/create' )
                    ],
                    'expenses-categories'   =>  [
                        'label' =>  __( 'Expense Categories'),
                        'href'  =>  url( '/dashboard/expenses/categories' )
                    ],
                    'create-expenses-categories'   =>  [
                        'label' =>  __( 'Create Expense Category'),
                        'href'  =>  url( '/dashboard/expenses/categories/create' )
                    ]
                ]
            ], 
            'inventory' =>  [
                'label' =>  __( 'Inventory' ),
                'icon'  =>  'la-boxes',
                'childrens'     =>  [
                    'products'  =>  [
                        'label' =>  __( 'Products' ),
                        'href'  =>  url( '/dashboard/products' )
                    ], 
                    'create-products'   =>  [
                        'label' =>  __( 'Create Product'),
                        'href'  =>  url( '/dashboard/products/create' )
                    ], 
                    'categories'   =>  [
                        'label' =>  __( 'Categories'),
                        'href'  =>  url( '/dashboard/products/categories' )
                    ], 
                    'create-categories'   =>  [
                        'label' =>  __( 'Create Categories'),
                        'href'  =>  url( '/dashboard/products/categories/create' )
                    ],
                    'units'   =>  [
                        'label' =>  __( 'Units'),
                        'href'  =>  url( '/dashboard/units' )
                    ],
                    'create-units'   =>  [
                        'label' =>  __( 'Create Unit'),
                        'href'  =>  url( '/dashboard/units/create' )
                    ],
                    'unit-groups'   =>  [
                        'label' =>  __( 'Unit Groups'),
                        'href'  =>  url( '/dashboard/units/groups' )
                    ],
                    'create-unit-groups'   =>  [
                        'label' =>  __( 'Create Unit Groups'),
                        'href'  =>  url( '/dashboard/units/groups/create' )
                    ],
                    'create-products'   =>  [
                        'label' =>  __( 'Stock Adjustment'),
                        'href'  =>  url( '/dashboard/stock-adjustment' )
                    ],
                ]
            ], 
            'taxes'     =>  [
                'label' =>  __( 'Taxes' ),
                'icon'  =>  'la-balance-scale-left',
                'childrens' =>  [
                    'taxes-groups'   =>  [
                        'label' =>  __( 'Taxes Groups'),
                        'href'  =>  url( '/dashboard/taxes/groups' )
                    ],
                    'create-taxes-group'   =>  [
                        'label' =>  __( 'Create Tax Groups'),
                        'href'  =>  url( '/dashboard/taxes/groups/create' )
                    ],
                    'taxes'   =>  [
                        'label' =>  __( 'Taxes'),
                        'href'  =>  url( '/dashboard/taxes' )
                    ],
                    'create-tax'   =>  [
                        'label' =>  __( 'Create Tax'),
                        'href'  =>  url( '/dashboard/taxes/create' )
                    ]
                ]
            ],
            'modules' =>  [
                'label' =>  __( 'Modules' ),
                'icon'  =>  'la-plug',
                'childrens'     =>  [
                    'modules'  =>  [
                        'label' =>  __( 'List' ),
                        'href'  =>  url( '/dashboard/modules' )
                    ], 
                    'upload-module'   =>  [
                        'label' =>  __( 'Upload Module'),
                        'href'  =>  url( '/dashboard/modules/upload' )
                    ], 
                ]
            ], 
            'users'      =>  [
                'label'         =>  __( 'Users' ),
                'icon'          =>  'la-users',
                'childrens'     =>  [
                    'users'  =>  [
                        'label' =>  __( 'List' ),
                        'href'  =>  url( '/dashboard/users' )
                    ], 
                    'create-user'  =>  [
                        'label' =>  __( 'Create User' ),
                        'href'  =>  url( '/dashboard/users/create' )
                    ], 
                    'roles'  =>  [
                        'label' =>  __( 'Roles' ),
                        'href'  =>  url( '/dashboard/users/roles' )
                    ], 
                    'create-role'  =>  [
                        'label' =>  __( 'Create Roles' ),
                        'href'  =>  url( '/dashboard/users/roles/create' )
                    ], 
                    'permissions'  =>  [
                        'label' =>  __( 'Permissions Manager' ),
                        'href'  =>  url( '/dashboard/users/roles/permissions-manager' )
                    ], 
                    'profile'  =>  [
                        'label' =>  __( 'Profile' ),
                        'href'  =>  url( '/dashboard/users/profile' )
                    ], 
                ]
            ],
            'procurements'      =>  [
                'label'         =>  __( 'Procurements' ),
                'icon'          =>  'la-truck-loading',
                'childrens'     =>  [
                    'procurements'  =>  [
                        'label' =>  __( 'Procurements List' ),
                        'href'  =>  url( '/dashboard/procurements' )
                    ], 
                    'procurements-create'  =>  [
                        'label' =>  __( 'New Procurement' ),
                        'href'  =>  url( '/dashboard/procurements/create' )
                    ], 
                ]
            ],
            'reports'      =>  [
                'label'         =>  __( 'Reports' ),
                'icon'          =>  'la-chart-pie',
                'childrens'     =>  [
                    'sales'  =>  [
                        'label' =>  __( 'Sale Report' ),
                        'href'  =>  url( '/dashboard/reports/sales' )
                    ], 
                    'detailed-report'  =>  [
                        'label' =>  __( 'Detailed report' ),
                        'href'  =>  url( '/dashboard/reports/detailed-report' )
                    ], 
                    'best-sales'  =>  [
                        'label' =>  __( 'Best Sales' ),
                        'href'  =>  url( '/dashboard/reports/best-sales' )
                    ], 
                    'income-losses'  =>  [
                        'label' =>  __( 'Incomes & Loosses' ),
                        'href'  =>  url( '/dashboard/reports/income-losses' )
                    ], 
                    'cash-flow'  =>  [
                        'label' =>  __( 'Cash Flow' ),
                        'href'  =>  url( '/dashboard/reports/cash-flow' )
                    ], 
                    'annulal-sales'  =>  [
                        'label' =>  __( 'Yearly Sales' ),
                        'href'  =>  url( '/dashboard/reports/yearly-sales' )
                    ], 
                    'customers'  =>  [
                        'label' =>  __( 'Customers' ),
                        'href'  =>  url( '/dashboard/reports/customers' )
                    ], 
                    'inventory-tracking'  =>  [
                        'label' =>  __( 'Inventory Tracking' ),
                        'href'  =>  url( '/dashboard/reports/inventory-tracking' )
                    ], 
                    'activity-log'  =>  [
                        'label' =>  __( 'Activity Log' ),
                        'href'  =>  url( '/dashboard/reports/activity-log' )
                    ], 
                    'expenses'  =>  [
                        'label' =>  __( 'Expenses' ),
                        'href'  =>  url( '/dashboard/reports/expenses' )
                    ], 
                ]
            ],
            'settings'      =>  [
                'label'         =>  __( 'Settings' ),
                'icon'          =>  'la-cogs',
                'childrens'     =>  [
                    'general'  =>  [
                        'label' =>  __( 'General' ),
                        'href'  =>  url( '/dashboard/settings/general' )
                    ], 
                    'pos'   =>  [
                        'label' =>  __( 'POS'),
                        'href'  =>  url( '/dashboard/settings/pos' )
                    ],  
                    'customers'   =>  [
                        'label' =>  __( 'Customers'),
                        'href'  =>  url( '/dashboard/settings/customers' )
                    ], 
                    'supplies-delivery'   =>  [
                        'label' =>  __( 'Supplies & Deliveries'),
                        'href'  =>  url( '/dashboard/settings/supplies-deliveries' )
                    ],
                    'orders'   =>  [
                        'label' =>  __( 'Orders'),
                        'href'  =>  url( '/dashboard/settings/orders' )
                    ],
                    'stores'   =>  [
                        'label' =>  __( 'Stores'),
                        'href'  =>  url( '/dashboard/settings/stores' )
                    ],
                    'reports'   =>  [
                        'label' =>  __( 'Reports'),
                        'href'  =>  url( '/dashboard/settings/reports' )
                    ],
                    'invoice-settings'   =>  [
                        'label' =>  __( 'Invoice Settings'),
                        'href'  =>  url( '/dashboard/settings/invoice-settings' )
                    ],
                    'service-providers'   =>  [
                        'label' =>  __( 'Service Providers'),
                        'href'  =>  url( '/dashboard/settings/service-providers' )
                    ],
                    'notifications'   =>  [
                        'label' =>  __( 'Notifications'),
                        'href'  =>  url( '/dashboard/settings/notifications' )
                    ],
                    'reset'   =>  [
                        'label' =>  __( 'Reset'),
                        'href'  =>  url( '/dashboard/settings/reset' )
                    ]
                ]
            ],
        ];
    }

    /**
     * returns the list of available menus
     * @return Array of menus
     */
    public function getMenus()
    {
        return $this->menus;
    }

    /**
     * Will make sure active menu
     * is toggled
     * @return void
     */
    public function toggleActive()
    {
        foreach( $this->menus as $identifier => &$menu ) {
            if ( isset( $menu[ 'href' ] ) && $menu[ 'href' ] === url()->current() ) {
                $menu[ 'toggled' ]  =   true;
            }

            if ( isset( $menu[ 'childrens' ] ) ) {
                foreach( $menu[ 'childrens' ] as $subidentifier => &$submenu ) {
                    if ( $submenu[ 'href' ] === url()->current() ) {
                        $menu[ 'toggled' ]      =   true;
                        $submenu[ 'active' ]    =   true;
                    }
                }
            }
        }
    }
}