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
                'label'         =>  __( 'Dashboard' ),                
                'permissions'   =>  [ 'update.core', 'read.dashboard' ],
                'icon'          =>  'la-home',
                'childrens'     =>  [
                    'index'             =>  [
                        'label'         =>  __( 'Home' ),
                        'permissions'   =>  [ 'read.dashboard' ],
                        'href'          =>  url( '/dashboard' ),
                    ],
                    'updates'           =>  [
                        'label'         =>  __( 'Updates'),
                        'permissions'   =>  [ 'update.core' ],
                        'href'          =>  url( '/dashboard/updates' )
                    ], 
                    'about'             =>  [
                        'label'         =>  __( 'About'),
                        'href'          =>  url( '/dashboard/about' )
                    ]
                ]
            ], 
            'pos'   =>  [
                'label' =>  __( 'POS' ),
                'icon'  =>  'la-cash-register',
                'permissions'   =>  [ 'nexopos.create.orders' ],
                'href'  =>  url( '/dashboard/pos' )
            ], 
            'orders'    =>  [
                'label' =>  __( 'Orders' ),
                'permissions'   =>  [ 'nexopos.update.orders', 'nexopos.read.orders' ],
                'icon'  =>  'la-list-ol',
                'href'  =>  url( '/dashboard/orders' )
            ], 
            'customers' =>  [
                'label' =>  __( 'Customers' ),
                'permissions'   =>  [
                    'nexopos.read.customers',
                    'nexopos.create.customers',
                    'nexopos.read.customers-groups',
                    'nexopos.create.customers-groups',
                    'nexopos.import.customers',
                    'nexopos.read.rewards',
                    'nexopos.create.rewards',
                    'nexopos.read.coupons',
                    'nexopos.create.coupons',
                ],
                'icon'  =>  'la-user-friends',
                'childrens'     =>  [
                    'customers' =>  [
                        'label' =>  __( 'List'),
                        'permissions'   =>  [ 'nexopos.read.customers' ],
                        'href'  =>  url( '/dashboard/customers' )
                    ], 
                    'create-customer'  =>   [
                        'label' =>  __( 'Create Customer'),
                        'permissions'   =>  [ 'nexopos.create.customers' ],
                        'href'  =>  url( '/dashboard/customers/create' )
                    ], 
                    'customers-groups'  =>  [
                        'label' =>  __( 'Customers Groups'),
                        'permissions'   =>  [ 'nexopos.read.customers-groups' ],
                        'href'  =>  url( '/dashboard/customers/groups' )
                    ], 
                    'create-customers-group'    =>  [
                        'label' =>  __( 'Create Group'),
                        'permissions'   =>  [ 'nexopos.create.customers-groups' ],
                        'href'  =>  url( '/dashboard/customers/groups/create' )
                    ], 
                    'import-customers'  =>  [
                        'label' =>  __( 'Import Customers'),
                        'permissions'   =>  [ 'nexopos.import.customers' ],
                        'href'  =>  url( '/dashboard/customers/import' )
                    ],
                    'list-reward-system'    =>  [
                        'label' =>  __( 'Reward Systems'),
                        'permissions'   =>  [ 'nexopos.read.rewards' ],
                        'href'  =>  url( '/dashboard/customers/rewards-system' )
                    ],
                    'create-reward-system'    =>  [
                        'label' =>  __( 'Create Reward'),
                        'permissions'   =>  [ 'nexopos.create.rewards' ],
                        'href'  =>  url( '/dashboard/customers/rewards-system/create' )
                    ],
                    'list-coupons'    =>  [
                        'label' =>  __( 'List Coupons'),
                        'permissions'   =>  [ 'nexopos.read.coupons' ],
                        'href'  =>  url( '/dashboard/customers/coupons' )
                    ],
                    'create-coupons'    =>  [
                        'label' =>  __( 'Create Coupon'),
                        'permissions'   =>  [ 'nexopos.create.coupons' ],
                        'href'  =>  url( '/dashboard/customers/coupons/create' )
                    ],
                ]
            ], 
            'providers' =>  [
                'label' =>  __( 'Providers' ),
                'icon'  =>  'la-user-tie',
                'permissions'   =>  [
                    'nexopos.read.providers',
                    'nexopos.create.providers',
                ],
                'childrens'     =>  [
                    'providers' =>  [
                        'label' =>  __( 'List'),
                        'permissions'   =>  [ 'nexopos.read.providers' ],
                        'href'  =>  url( '/dashboard/providers' )
                    ], 
                    'create-provider'   =>  [
                        'label' =>  __( 'Create Providers'),
                        'permissions'   =>  [ 'nexopos.create.providers' ],
                        'href'  =>  url( '/dashboard/providers/create' )
                    ]
                ]
            ], 
            'expenses' =>  [
                'label' =>  __( 'Expenses' ),
                'icon'  =>  'la-money-bill-wave',
                'permissions'   =>  [
                    "nexopos.read.expenses",
                    "nexopos.create.expenses",
                    "nexopos.read.expenses-categories",
                    "nexopos.create.expenses-categories",
                ],
                'childrens'     =>  [
                    'expenses' =>  [
                        'label' =>  __( 'Expenses'),
                        'permissions'   =>  [ 'nexopos.read.expenses' ],
                        'href'  =>  url( '/dashboard/expenses' )
                    ], 
                    'create-provider'   =>  [
                        'label' =>  __( 'Create Expense'),
                        'permissions'   =>  [ 'nexopos.create.expenses' ],
                        'href'  =>  url( '/dashboard/expenses/create' )
                    ],
                    'expenses-categories'   =>  [
                        'label' =>  __( 'Expense Categories'),
                        'permissions'   =>  [ 'nexopos.read.expenses-categories' ],
                        'href'  =>  url( '/dashboard/expenses/categories' )
                    ],
                    'create-expenses-categories'   =>  [
                        'label' =>  __( 'Create Expense Category'),
                        'permissions'   =>  [ 'nexopos.create.expenses-categories' ],
                        'href'  =>  url( '/dashboard/expenses/categories/create' )
                    ]
                ]
            ], 
            'inventory' =>  [
                'label' =>  __( 'Inventory' ),
                'icon'  =>  'la-boxes',
                'permissions'   =>  [
                    'nexopos.read.products',
                    'nexopos.create.products',
                    'nexopos.read.categories',
                    'nexopos.create.categories',
                    'nexopos.read.product-units',
                    'nexopos.create.product-units',
                    'nexopos.read.product-units',
                    'nexopos.create.product-units',
                    'nexopos.make.products-adjustment',
                ],
                'childrens'     =>  [
                    'products'  =>  [
                        'label' =>  __( 'Products' ),
                        'permissions'   =>  [ 'nexopos.read.products' ],
                        'href'  =>  url( '/dashboard/products' )
                    ], 
                    'create-products'   =>  [
                        'label' =>  __( 'Create Product'),
                        'permissions'   =>  [ 'nexopos.create.products' ],
                        'href'  =>  url( '/dashboard/products/create' )
                    ], 
                    'categories'   =>  [
                        'label' =>  __( 'Categories'),
                        'permissions'   =>  [ 'nexopos.read.categories' ],
                        'href'  =>  url( '/dashboard/products/categories' )
                    ], 
                    'create-categories'   =>  [
                        'label' =>  __( 'Create Categories'),
                        'permissions'   =>  [ 'nexopos.create.categories' ],
                        'href'  =>  url( '/dashboard/products/categories/create' )
                    ],
                    'units'   =>  [
                        'label' =>  __( 'Units'),
                        'permissions'   =>  [ 'nexopos.read.product-units' ],
                        'href'  =>  url( '/dashboard/units' )
                    ],
                    'create-units'   =>  [
                        'label' =>  __( 'Create Unit'),
                        'permissions'   =>  [ 'nexopos.create.product-units' ],
                        'href'  =>  url( '/dashboard/units/create' )
                    ],
                    'unit-groups'   =>  [
                        'label' =>  __( 'Unit Groups'),
                        'permissions'   =>  [ 'nexopos.read.product-units' ],
                        'href'  =>  url( '/dashboard/units/groups' )
                    ],
                    'create-unit-groups'   =>  [
                        'label' =>  __( 'Create Unit Groups'),
                        'permissions'   =>  [ 'nexopos.create.product-units' ],
                        'href'  =>  url( '/dashboard/units/groups/create' )
                    ],
                    'create-products'   =>  [
                        'label' =>  __( 'Stock Adjustment'),
                        'permissions'   =>  [ 'nexopos.make.products-adjustment' ],
                        'href'  =>  url( '/dashboard/stock-adjustment' )
                    ],
                ]
            ], 
            'taxes'     =>  [
                'label' =>  __( 'Taxes' ),
                'icon'  =>  'la-balance-scale-left',
                'permissions'           =>  [
                    'nexopos.create.taxes',
                    'nexopos.read.taxes',
                    'nexopos.update.taxes',
                    'nexopos.delete.taxes',
                ],
                'childrens' =>  [
                    'taxes-groups'   =>  [
                        'label'         =>  __( 'Taxes Groups'),
                        'permissions'   =>  [ 'nexopos.read.taxes' ],
                        'href'          =>  url( '/dashboard/taxes/groups' )
                    ],
                    'create-taxes-group'   =>  [
                        'label'         =>  __( 'Create Tax Groups'),
                        'permissions'   =>  [ 'nexopos.create.taxes' ],
                        'href'          =>  url( '/dashboard/taxes/groups/create' )
                    ],
                    'taxes'             =>  [
                        'label'         =>  __( 'Taxes'),
                        'permissions'   =>  [ 'nexopos.read.taxes' ],
                        'href'          =>  url( '/dashboard/taxes' )
                    ],
                    'create-tax'        =>  [
                        'label'         =>  __( 'Create Tax'),
                        'permissions'   =>  [ 'nexopos.create.taxes' ],
                        'href'          =>  url( '/dashboard/taxes/create' )
                    ]
                ]
            ],
            'modules' =>  [
                'label' =>  __( 'Modules' ),
                'icon'  =>  'la-plug',
                'permissions'   =>  [ 'manage.modules' ],
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
                    'profile'  =>  [
                        'label' =>  __( 'List' ),
                        'permissions'   =>  [ 'manage.profile' ],
                        'href'  =>  url( '/dashboard/users/profile' )
                    ], 
                    'users'  =>  [
                        'label' =>  __( 'List' ),
                        'permissions'   =>  [ 'read.users' ],
                        'href'  =>  url( '/dashboard/users' )
                    ], 
                    'create-user'  =>  [
                        'label' =>  __( 'Create User' ),
                        'permissions'   =>  [ 'create.users' ],
                        'href'  =>  url( '/dashboard/users/create' )
                    ], 
                    'roles'  =>  [
                        'label' =>  __( 'Roles' ),
                        'permissions'   =>  [ 'read.roles' ],
                        'href'  =>  url( '/dashboard/users/roles' )
                    ], 
                    'create-role'  =>  [
                        'label' =>  __( 'Create Roles' ),
                        'permissions'   =>  [ 'create.roles' ],
                        'href'  =>  url( '/dashboard/users/roles/create' )
                    ], 
                    'permissions'  =>  [
                        'label' =>  __( 'Permissions Manager' ),
                        'permissions'   =>  [ 'update.roles' ],
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
                'permissions'   =>  [ 'nexopos.read.procurements', 'nexopos.create.procurements' ],
                'childrens'     =>  [
                    'procurements'  =>  [
                        'label'         =>  __( 'Procurements List' ),
                        'permissions'   =>  [ 'nexopos.read.procurements' ],
                        'href'          =>  url( '/dashboard/procurements' )
                    ], 
                    'procurements-create'  =>  [
                        'label' =>  __( 'New Procurement' ),
                        'permissions'   =>  [ 'nexopos.create.procurements' ],
                        'href'  =>  url( '/dashboard/procurements/create' )
                    ], 
                ]
            ],
            'reports'      =>  [
                'label'         =>  __( 'Reports' ),
                'icon'          =>  'la-chart-pie',
                'permissions'   =>  [
                    'nexopos.report.sales',
                    'nexopos.report.best_sales',
                    'nexopos.report.cash_flow',
                    'nexopos.report.yearly',
                    'nexopos.report.customers',
                    'nexopos.report.inventory',
                ],
                'childrens'     =>  [
                    'sales'  =>  [
                        'label' =>  __( 'Sale Report' ),
                        'permissions'   =>  [ 'nexopos.report.sales' ],
                        'href'  =>  url( '/dashboard/reports/sales' )
                    ], 
                    'detailed-report'  =>  [
                        'label' =>  __( 'Detailed report' ),
                        'href'  =>  url( '/dashboard/reports/detailed-report' )
                    ], 
                    'best-sales'  =>  [
                        'label' =>  __( 'Best Sales' ),
                        'permissions'   =>  [ 'nexopos.report.best_sales' ],
                        'href'  =>  url( '/dashboard/reports/best-sales' )
                    ], 
                    'income-losses'  =>  [
                        'label' =>  __( 'Incomes & Loosses' ),
                        'href'  =>  url( '/dashboard/reports/income-losses' )
                    ], 
                    'cash-flow'  =>  [
                        'label' =>  __( 'Cash Flow' ),
                        'permissions'   =>  [ 'nexopos.report.cash_flow' ],
                        'href'  =>  url( '/dashboard/reports/cash-flow' )
                    ], 
                    'annulal-sales'  =>  [
                        'label' =>  __( 'Yearly Sales' ),
                        'permissions'   =>  [ 'nexopos.report.yearly' ],
                        'href'  =>  url( '/dashboard/reports/yearly-sales' )
                    ], 
                    'customers'  =>  [
                        'label' =>  __( 'Customers' ),
                        'permissions'   =>  [ 'nexopos.report.customers' ],
                        'href'  =>  url( '/dashboard/reports/customers' )
                    ], 
                    'providers'  =>  [
                        'label' =>  __( 'Providers' ),
                        'href'  =>  url( '/dashboard/reports/providers' )
                    ], 
                    'inventory-tracking'  =>  [
                        'label' =>  __( 'Inventory Tracking' ),
                        'permissions'   =>  [ 'nexopos.report.inventory' ],
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
                'permissions'   =>  [ 'manage.options' ],
                'childrens'     =>  [
                    'general'   =>  [
                        'label' =>  __( 'General' ),
                        'href'  =>  url( '/dashboard/settings/general' )
                    ], 
                    'pos'       =>  [
                        'label' =>  __( 'POS'),
                        'href'  =>  url( '/dashboard/settings/pos' )
                    ],  
                    'customers' =>  [
                        'label' =>  __( 'Customers'),
                        'href'  =>  url( '/dashboard/settings/customers' )
                    ], 
                    'supplies-delivery'     =>  [
                        'label'             =>  __( 'Supplies & Deliveries'),
                        'href'              =>  url( '/dashboard/settings/supplies-deliveries' )
                    ],
                    'orders'        =>  [
                        'label'     =>  __( 'Orders'),
                        'href'      =>  url( '/dashboard/settings/orders' )
                    ],
                    'stores'        =>  [
                        'label'     =>  __( 'Stores'),
                        'href'      =>  url( '/dashboard/settings/stores' )
                    ],
                    'reports'       =>  [
                        'label'     =>  __( 'Reports'),
                        'href'      =>  url( '/dashboard/settings/reports' )
                    ],
                    'invoice-settings'  =>  [
                        'label'         =>  __( 'Invoice Settings'),
                        'href'          =>  url( '/dashboard/settings/invoice-settings' )
                    ],
                    'service-providers'     =>  [
                        'label'             =>  __( 'Service Providers'),
                        'href'              =>  url( '/dashboard/settings/service-providers' )
                    ],
                    'notifications'     =>  [
                        'label'         =>  __( 'Notifications'),
                        'href'          =>  url( '/dashboard/settings/notifications' )
                    ],
                    'reset'         =>  [
                        'label'     =>  __( 'Reset'),
                        'href'      =>  url( '/dashboard/settings/reset' )
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