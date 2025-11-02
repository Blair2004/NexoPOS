<?php

namespace App\Services;

use App\Classes\AsideMenu;
use App\Classes\Menu;
use Illuminate\Support\Facades\Gate;
use TorMorten\Eventy\Facades\Eventy as Hook;

class MenuService
{
    protected $menus;
    protected $accountMenus     =    [];

    public function buildMenus()
    {
        $this->menus = AsideMenu::wrapper(
            AsideMenu::menu(
                label: __( 'Dashboard' ),
                icon: 'la-home',
                identifier: 'dashboard',
                permissions: [ 'read.dashboard' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Home' ),
                        identifier: 'index',
                        permissions: [ 'read.dashboard' ],
                        href: ns()->url( '/dashboard' )
                    )
                ),
            ),
            AsideMenu::menu(
                label: __( 'POS' ),
                icon: 'la-cash-register',
                identifier: 'pos',
                permissions: [ 'nexopos.create.orders' ],
                href: ns()->url( '/dashboard/pos' ),
            ),
            AsideMenu::menu(
                label: __( 'Orders' ),
                icon: 'la-list-ol',
                identifier: 'orders',
                permissions: [ 'nexopos.read.orders', 'nexopos.deliver.orders' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Orders List' ),
                        identifier: 'order-list',
                        permissions: [ 'nexopos.read.orders' ],
                        href: ns()->url( '/dashboard/orders' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Payment Types' ),
                        identifier: 'payment-type',
                        permissions: [ 'nexopos.manage-payments-types' ],
                        href: ns()->url( '/dashboard/orders/payments-types' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Assignated Orders' ),
                        identifier: 'assignated-orders',
                        permissions: [ 'nexopos.deliver.orders' ],
                        href: ns()->url( '/dashboard/orders/assignated' )
                    )
                ),
            ),
            AsideMenu::menu(
                label: __( 'Medias' ),
                icon: 'la-photo-video',
                identifier: 'medias',
                permissions: [ 'nexopos.upload.medias', 'nexopos.see.medias' ],
                href: ns()->url( '/dashboard/medias' ),
            ),
            AsideMenu::menu(
                label: __( 'Customers' ),
                icon: 'la-user-friends',
                identifier: 'customers',
                permissions: [
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
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'List' ),
                        identifier: 'customers',
                        permissions: [ 'nexopos.read.customers' ],
                        href: ns()->url( '/dashboard/customers' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Customer' ),
                        identifier: 'create-customer',
                        permissions: [ 'nexopos.create.customers' ],
                        href: ns()->url( '/dashboard/customers/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Customers Groups' ),
                        identifier: 'customers-groups',
                        permissions: [ 'nexopos.read.customers-groups' ],
                        href: ns()->url( '/dashboard/customers/groups' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Group' ),
                        identifier: 'create-customers-group',
                        permissions: [ 'nexopos.create.customers-groups' ],
                        href: ns()->url( '/dashboard/customers/groups/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Reward Systems' ),
                        identifier: 'list-reward-system',
                        permissions: [ 'nexopos.read.rewards' ],
                        href: ns()->url( '/dashboard/customers/rewards-system' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Reward' ),
                        identifier: 'create-reward-system',
                        permissions: [ 'nexopos.create.rewards' ],
                        href: ns()->url( '/dashboard/customers/rewards-system/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'List Coupons' ),
                        identifier: 'list-coupons',
                        permissions: [ 'nexopos.read.coupons' ],
                        href: ns()->url( '/dashboard/customers/coupons' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Coupon' ),
                        identifier: 'create-coupons',
                        permissions: [ 'nexopos.create.coupons' ],
                        href: ns()->url( '/dashboard/customers/coupons/create' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Providers' ),
                icon: 'la-user-tie',
                identifier: 'providers',
                permissions: [
                    'nexopos.read.providers',
                    'nexopos.create.providers',
                ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'List' ),
                        identifier: 'providers',
                        permissions: [ 'nexopos.read.providers' ],
                        href: ns()->url( '/dashboard/providers' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create A Provider' ),
                        identifier: 'create-provider',
                        permissions: [ 'nexopos.create.providers' ],
                        href: ns()->url( '/dashboard/providers/create' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Accounting' ),
                icon: 'la-stream',
                identifier: 'accounting',
                permissions: [
                    'nexopos.read.transactions',
                    'nexopos.create.transactions',
                    'nexopos.read.transactions-account',
                    'nexopos.create.transactions-account',
                ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Expenses' ),
                        identifier: 'transactions',
                        permissions: [ 'nexopos.read.transactions' ],
                        href: ns()->url( '/dashboard/accounting/transactions' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Expense' ),
                        identifier: 'create-transaction',
                        permissions: [ 'nexopos.create.transactions' ],
                        href: ns()->url( '/dashboard/accounting/transactions/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Transaction History' ),
                        identifier: 'transactions-history',
                        permissions: [ 'nexopos.read.transactions-history' ],
                        href: ns()->url( '/dashboard/accounting/transactions/history' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Rules' ),
                        identifier: 'transacations-rules',
                        permissions: [ 'nexopos.create.transactions' ],
                        href: ns()->url( '/dashboard/accounting/rules' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Accounts' ),
                        identifier: 'transactions-account',
                        permissions: [ 'nexopos.read.transactions-account' ],
                        href: ns()->url( '/dashboard/accounting/accounts' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Account' ),
                        identifier: 'create-transactions-account',
                        permissions: [ 'nexopos.create.transactions-account' ],
                        href: ns()->url( '/dashboard/accounting/accounts/create' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Inventory' ),
                icon: 'la-boxes',
                identifier: 'inventory',
                permissions: [
                    'nexopos.read.products',
                    'nexopos.create.products',
                    'nexopos.read.categories',
                    'nexopos.create.categories',
                    'nexopos.read.products-units',
                    'nexopos.create.products-units',
                    'nexopos.read.products-units',
                    'nexopos.create.products-units',
                    'nexopos.make.products-adjustments',
                ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Products' ),
                        identifier: 'products',
                        permissions: [ 'nexopos.read.products' ],
                        href: ns()->url( '/dashboard/products' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Product' ),
                        identifier: 'create-products',
                        permissions: [ 'nexopos.create.products' ],
                        href: ns()->url( '/dashboard/products/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Print Labels' ),
                        identifier: 'labels-printing',
                        href: ns()->url( '/dashboard/products/print-labels' ),
                        permissions: [ 'nexopos.create.products-labels' ]
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Categories' ),
                        identifier: 'categories',
                        permissions: [ 'nexopos.read.categories' ],
                        href: ns()->url( '/dashboard/products/categories' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Category' ),
                        identifier: 'create-categories',
                        permissions: [ 'nexopos.create.categories' ],
                        href: ns()->url( '/dashboard/products/categories/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Units' ),
                        identifier: 'units',
                        permissions: [ 'nexopos.read.products-units' ],
                        href: ns()->url( '/dashboard/units' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Unit' ),
                        identifier: 'create-units',
                        permissions: [ 'nexopos.create.products-units' ],
                        href: ns()->url( '/dashboard/units/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Unit Groups' ),
                        identifier: 'unit-groups',
                        permissions: [ 'nexopos.read.products-units' ],
                        href: ns()->url( '/dashboard/units/groups' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Unit Groups' ),
                        identifier: 'create-unit-groups',
                        permissions: [ 'nexopos.create.products-units' ],
                        href: ns()->url( '/dashboard/units/groups/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Stock Adjustment' ),
                        identifier: 'stock-adjustment',
                        permissions: [ 'nexopos.make.products-adjustments' ],
                        href: ns()->url( '/dashboard/products/stock-adjustment' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Stock Flow Records' ),
                        identifier: 'product-history',
                        permissions: [ 'nexopos.read.products' ],
                        href: ns()->url( '/dashboard/products/stock-flow-records' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Taxes' ),
                icon: 'la-balance-scale-left',
                identifier: 'taxes',
                permissions: [
                    'nexopos.create.taxes',
                    'nexopos.read.taxes',
                    'nexopos.update.taxes',
                    'nexopos.delete.taxes',
                ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Taxes Groups' ),
                        identifier: 'taxes-groups',
                        permissions: [ 'nexopos.read.taxes' ],
                        href: ns()->url( '/dashboard/taxes/groups' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Tax Groups' ),
                        identifier: 'create-taxes-group',
                        permissions: [ 'nexopos.create.taxes' ],
                        href: ns()->url( '/dashboard/taxes/groups/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Taxes' ),
                        identifier: 'taxes',
                        permissions: [ 'nexopos.read.taxes' ],
                        href: ns()->url( '/dashboard/taxes' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Tax' ),
                        identifier: 'create-tax',
                        permissions: [ 'nexopos.create.taxes' ],
                        href: ns()->url( '/dashboard/taxes/create' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Modules' ),
                icon: 'la-plug',
                identifier: 'modules',
                permissions: [ 'manage.modules' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'List' ),
                        identifier: 'modules',
                        href: ns()->url( '/dashboard/modules' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Upload Module' ),
                        identifier: 'upload-module',
                        href: ns()->url( '/dashboard/modules/upload' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Users' ),
                icon: 'la-users',
                identifier: 'users',
                permissions: [ 'read.users', 'manage.profile', 'create.users' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'My Profile' ),
                        identifier: 'profile',
                        permissions: [ 'manage.profile' ],
                        href: ns()->url( '/dashboard/users/profile' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Users List' ),
                        identifier: 'users',
                        permissions: [ 'read.users' ],
                        href: ns()->url( '/dashboard/users' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create User' ),
                        identifier: 'create-user',
                        permissions: [ 'create.users' ],
                        href: ns()->url( '/dashboard/users/create' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Roles' ),
                icon: 'la-shield-alt',
                identifier: 'roles',
                permissions: [ 'read.roles', 'create.roles', 'update.roles' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Roles' ),
                        identifier: 'all-roles',
                        permissions: [ 'read.roles' ],
                        href: ns()->url( '/dashboard/users/roles' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Create Roles' ),
                        identifier: 'create-role',
                        permissions: [ 'create.roles' ],
                        href: ns()->url( '/dashboard/users/roles/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Permissions Manager' ),
                        identifier: 'permissions',
                        permissions: [ 'update.roles' ],
                        href: ns()->url( '/dashboard/users/roles/permissions-manager' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Procurements' ),
                icon: 'la-truck-loading',
                identifier: 'procurements',
                permissions: [ 'nexopos.read.procurements', 'nexopos.create.procurements' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Procurements List' ),
                        identifier: 'procurements',
                        permissions: [ 'nexopos.read.procurements' ],
                        href: ns()->url( '/dashboard/procurements' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'New Procurement' ),
                        identifier: 'procurements-create',
                        permissions: [ 'nexopos.create.procurements' ],
                        href: ns()->url( '/dashboard/procurements/create' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Products' ),
                        identifier: 'procurements-products',
                        permissions: [ 'nexopos.update.procurements' ],
                        href: ns()->url( '/dashboard/procurements/products' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Reports' ),
                icon: 'la-chart-pie',
                identifier: 'reports',
                permissions: [
                    'nexopos.reports.sales',
                    'nexopos.reports.best_sales',
                    'nexopos.reports.transactions',
                    'nexopos.reports.yearly',
                    'nexopos.reports.customers',
                    'nexopos.reports.inventory',
                    'nexopos.reports.payment-types',
                ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'Sale Report' ),
                        identifier: 'sales',
                        permissions: [ 'nexopos.reports.sales' ],
                        href: ns()->url( '/dashboard/reports/sales' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Sales Progress' ),
                        identifier: 'products-report',
                        permissions: [ 'nexopos.reports.products-report' ],
                        href: ns()->url( '/dashboard/reports/sales-progress' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Customers Statement' ),
                        identifier: 'customers-statement',
                        permissions: [ 'nexopos.reports.customers-statement' ],
                        href: ns()->url( '/dashboard/reports/customers-statement' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Stock Report' ),
                        identifier: 'low-stock',
                        permissions: [ 'nexopos.reports.low-stock' ],
                        href: ns()->url( '/dashboard/reports/low-stock' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Stock History' ),
                        identifier: 'stock-history',
                        permissions: [ 'nexopos.reports.stock-history' ],
                        href: ns()->url( '/dashboard/reports/stock-history' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Sold Stock' ),
                        identifier: 'sold-stock',
                        href: ns()->url( '/dashboard/reports/sold-stock' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Incomes & Loosses' ),
                        identifier: 'profit',
                        href: ns()->url( '/dashboard/reports/profit' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Transactions' ),
                        identifier: 'transactions',
                        permissions: [ 'nexopos.reports.transactions' ],
                        href: ns()->url( '/dashboard/reports/transactions' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Annual Report' ),
                        identifier: 'annulal-sales',
                        permissions: [ 'nexopos.reports.yearly' ],
                        href: ns()->url( '/dashboard/reports/annual-report' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Sales By Payments' ),
                        identifier: 'payment-types',
                        permissions: [ 'nexopos.reports.payment-types' ],
                        href: ns()->url( '/dashboard/reports/payment-types' )
                    ),
                ),
            ),
            AsideMenu::menu(
                label: __( 'Settings' ),
                icon: 'la-cogs',
                identifier: 'settings',
                permissions: [ 'manage.options' ],
                childrens: AsideMenu::childrens(
                    AsideMenu::subMenu(
                        label: __( 'General' ),
                        identifier: 'general',
                        href: ns()->url( '/dashboard/settings/general' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'POS' ),
                        identifier: 'pos',
                        href: ns()->url( '/dashboard/settings/pos' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Customers' ),
                        identifier: 'customers',
                        href: ns()->url( '/dashboard/settings/customers' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Orders' ),
                        identifier: 'orders',
                        href: ns()->url( '/dashboard/settings/orders' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Accounting' ),
                        identifier: 'accounting',
                        href: ns()->url( '/dashboard/settings/accounting' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Reports' ),
                        identifier: 'reports',
                        href: ns()->url( '/dashboard/settings/reports' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Invoices' ),
                        identifier: 'invoices',
                        href: ns()->url( '/dashboard/settings/invoices' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'Reset' ),
                        identifier: 'reset',
                        href: ns()->url( '/dashboard/settings/reset' )
                    ),
                    AsideMenu::subMenu(
                        label: __( 'About' ),
                        identifier: 'about',
                        href: ns()->url( '/dashboard/settings/about' )
                    ),
                ),
            ),
        );
    }

    /**
     * returns the list of available menus
     *
     * @return array of menus
     */
    public function getMenus()
    {
        $this->buildMenus();
        $this->menus = Hook::filter( 'ns-dashboard-menus', $this->menus );
        $this->toggleActive();

        return collect( $this->menus )->filter( function ( $menu ) {
            return ( ! isset( $menu[ 'permissions' ] ) || Gate::any( $menu[ 'permissions' ] ) ) && ( ! isset( $menu[ 'show' ] ) || $menu[ 'show' ] === true );
        } )->map( function ( $menu ) {
            $menu[ 'childrens' ] = collect( $menu[ 'childrens' ] ?? [] )->filter( function ( $submenu ) {
                return ! isset( $submenu[ 'permissions' ] ) || Gate::any( $submenu[ 'permissions' ] );
            } )->toArray();

            return $menu;
        } );
    }

    /**
     * Will make sure active menu
     * is toggled
     *
     * @return void
     */
    public function toggleActive()
    {
        foreach ( $this->menus as $identifier => &$menu ) {
            if ( isset( $menu[ 'href' ] ) && $menu[ 'href' ] === url()->current() ) {
                $menu[ 'toggled' ] = true;
            }

            if ( isset( $menu[ 'childrens' ] ) ) {
                foreach ( $menu[ 'childrens' ] as $subidentifier => &$submenu ) {
                    if ( $submenu[ 'href' ] === url()->current() ) {
                        $menu[ 'toggled' ] = true;
                        $submenu[ 'active' ] = true;
                    }
                }
            }
        }
    }

    /**
     * Adds an account menu
     *
     * @param string $identifier
     * @param string $label
     * @param string $icon
     * @param string $href
     */
    public function setAccountMenu( $identifier, $label, $icon, $href )
    {
        $this->accountMenus[ $identifier ] = AsideMenu::menu(
            label: $label,
            icon: $icon,
            identifier: $identifier,
            href: $href,
        );
    }

    /**
     * Returns the account menus
     * @return array
     */
    public function getAccountMenus(): array
    {
        $this->accountMenus = Hook::filter( 'ns-account-menus', Menu::wrapper(
            Menu::item(
                label: __( 'Profile' ),
                identifier: 'profile',
                icon: 'la-user-tie',
                href: ns()->route( 'ns.dashboard.users.profile' ),
                permissions: [ 'manage.profile' ],
            ),
            Menu::item(
                label: __( 'Logout' ),
                identifier: 'logout',
                icon: 'la-sign-out-alt',
                href: ns()->route( 'ns.logout' ),
            ),
        ) );

        return collect( $this->accountMenus )->filter( function ( $menu ) {
            return ( ! isset( $menu[ 'permissions' ] ) || Gate::any( $menu[ 'permissions' ] ) ) && ( ! isset( $menu[ 'show' ] ) || $menu[ 'show' ] === true );
        } )->toArray();
    }

    /**
     * Remove an account menu by its identifier
     * @param string $identifier
     */
    public function removeAccountMenu( $identifier )
    {
        if ( isset( $this->accountMenus[ $identifier ] ) ) {
            unset( $this->accountMenus[ $identifier ] );
        }
    }
}
