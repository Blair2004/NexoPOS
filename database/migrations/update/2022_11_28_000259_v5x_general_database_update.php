<?php

use App\Classes\Schema;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerAddress;
use App\Models\CustomerCoupon;
use App\Models\CustomerReward;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\CoreService;
use App\Services\DoctorService;
use App\Services\UsersService;
use App\Services\WidgetService;
use App\Widgets\ProfileWidget;
use Faker\Factory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as CoreSchema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * @var UsersService $usersService
         */
        $usersService = app()->make( UsersService::class );

        /**
         * @var DoctorService $doctorService
         */
        $doctorService = app()->make( DoctorService::class );

        /**
         * @var CoreService $coreService
         */
        $coreService = app()->make( CoreService::class );

        Schema::table( 'nexopos_roles', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_roles', 'dashid' ) ) {
                $table->removeColumn( 'dashid' );
            }
        } );

        /**
         * let's create a constant which will allow the creation,
         * since these files are included as migration file
         */
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        /**
         * We're deleting here all permissions that are
         * no longer used by the system.
         */
        Permission::where( 'namespace', 'like', '%.expense' )->each( function ( Permission $permission ) {
            $permission->removeFromRoles();
        } );

        /**
         * let's include the files that will create permissions
         * for all the declared widgets.
         */
        include_once base_path() . '/database/permissions/widgets.php';
        include_once base_path() . '/database/permissions/transactions.php';
        include_once base_path() . '/database/permissions/transactions-accounts.php';
        include_once base_path() . '/database/permissions/reports.php';

        /**
         * We'll now defined default permissions
         */
        $admin = Role::namespace( Role::ADMIN );
        $storeAdmin = Role::namespace( Role::STOREADMIN );
        $storeCashier = Role::namespace( Role::STORECASHIER );

        $admin->addPermissions( Permission::includes( '-widget' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.transactions' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.transactions-account' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.reports' )->get()->map( fn( $permission ) => $permission->namespace ) );
        //
        $storeAdmin->addPermissions( Permission::includes( '-widget' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.transactions' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.transactions-account' )->get()->map( fn( $permission ) => $permission->namespace ) );

        $storeCashier->addPermissions( Permission::whereIn( 'namespace', [
            ( new ProfileWidget )->getPermission(),
        ] )->get()->map( fn( $permission ) => $permission->namespace ) );

        /**
         * We need to register the permissions as gates,
         * to be sure while using Gate::allows those are defined.
         */
        $coreService->registerGatePermissions();

        /**
         * We're introducing a customer role.
         */
        include_once base_path() . '/database/permissions/store-customer-role.php';

        /**
         * to all roles available, we'll make all available widget added
         * to their dashboard
         *
         * @var WidgetService $widgetService
         */
        $widgetService = app()->make( WidgetService::class );

        User::get()->each( fn( $user ) => $widgetService->addDefaultWidgetsToAreas( $user ) );

        /**
         * We're make the users table to be able to receive customers
         */
        CoreSchema::table( 'nexopos_users', function ( Blueprint $table ) {
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'birth_date' ) ) {
                $table->datetime( 'birth_date' )->nullable();
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'purchases_amount' ) ) {
                $table->float( 'purchases_amount' )->default( 0 );
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'owed_amount' ) ) {
                $table->float( 'owed_amount' )->default( 0 );
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'credit_limit_amount' ) ) {
                $table->float( 'credit_limit_amount' )->default( 0 );
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'account_amount' ) ) {
                $table->float( 'account_amount' )->default( 0 );
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'first_name' ) ) {
                $table->string( 'first_name' )->nullable();
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'last_name' ) ) {
                $table->string( 'last_name' )->nullable();
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'gender' ) ) {
                $table->string( 'gender' )->nullable();
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'phone' ) ) {
                $table->string( 'phone' )->nullable();
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'pobox' ) ) {
                $table->string( 'pobox' )->nullable();
            }
            if ( ! CoreSchema::hasColumn( 'nexopos_users', 'group_id' ) ) {
                $table->integer( 'group_id' )->nullable();
            }
        } );

        /**
         * Coupons can now be added to
         * customer groups.
         */
        Schema::table( 'nexopos_coupons', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_coupons', 'groups_id' ) ) {
                $table->string( 'groups_id' )->nullable();
            }

            if ( ! Schema::hasColumn( 'nexopos_coupons', 'customers_id' ) ) {
                $table->string( 'customers_id' )->nullable();
            }
        } );

        if ( ! Schema::hasTable( 'nexopos_coupons_customers' ) ) {
            Schema::create( 'nexopos_coupons_customers', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'coupon_id' );
                $table->integer( 'customer_id' );
            } );
        }

        if ( ! Schema::hasTable( 'nexopos_coupons_customers_groups' ) ) {
            Schema::create( 'nexopos_coupons_customers_groups', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'coupon_id' );
                $table->integer( 'group_id' );
            } );
        }

        /**
         * rename the provider columns
         */
        Schema::table( 'nexopos_providers', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_providers', 'name' ) ) {
                $table->renameColumn( 'name', 'first_name' );
            }
            if ( Schema::hasColumn( 'nexopos_providers', 'surname' ) ) {
                $table->renameColumn( 'surname', 'last_name' );
            }
        } );

        Schema::table( 'nexopos_cash_flow', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_cash_flow', 'order_refund_product_id' ) ) {
                $table->integer( 'order_refund_product_id' );
            }
            if ( Schema::hasColumn( 'nexopos_cash_flow', 'order_product_id' ) ) {
                $table->integer( 'order_product_id' );
            }
            if ( Schema::hasColumn( 'nexopos_cash_flow', 'transaction_id' ) ) {
                $table->renameColumn( 'transaction_id', 'transaction_id' );
            }
        } );

        Schema::table( 'nexopos_procurements_products', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_procurements_products', 'convert_unit_id' ) ) {
                $table->integer( 'convert_unit_id' )->nullable();
            }
        } );

        Schema::table( 'nexopos_customers_addresses', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_customers_addresses', 'name' ) ) {
                $table->renameColumn( 'name', 'first_name' );
            }
            if ( Schema::hasColumn( 'nexopos_customers_addresses', 'surname' ) ) {
                $table->renameColumn( 'surname', 'last_name' );
            }
        } );

        Schema::table( 'nexopos_orders_coupons', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders_coupons', 'counted' ) ) {
                $table->boolean( 'counted' )->default( false );
            }
        } );

        Schema::table( 'nexopos_orders_addresses', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_orders_addresses', 'name' ) ) {
                $table->renameColumn( 'name', 'first_name' );
            }
            if ( Schema::hasColumn( 'nexopos_orders_addresses', 'surname' ) ) {
                $table->renameColumn( 'surname', 'last_name' );
            }
        } );

        Schema::table( 'nexopos_products_unit_quantities', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'convert_unit_id' ) ) {
                $table->integer( 'convert_unit_id' )->nullable();
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'visible' ) ) {
                $table->integer( 'visible' )->nullable();
            }

            if ( ! Schema::hasColumn( 'nexopos_products_unit_quantities', 'cogs' ) ) {
                $table->integer( 'cogs' )->nullable();
            }
        } );

        Schema::table( 'nexopos_products', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_products', 'auto_cogs' ) ) {
                $table->boolean( 'auto_cogs' )->default( true );
            }
        } );

        /**
         * Let's convert customers into users
         */
        $firstAdministrator = Role::namespace( Role::ADMIN )->users()->first();
        $faker = ( new Factory )->create();

        if ( Schema::hasTable( 'nexopos_customers' ) ) {
            $users = DB::table( 'nexopos_customers' )->get( '*' )->map( function ( $customer ) use ( $faker, $usersService, $doctorService, $firstAdministrator ) {
                $user = User::where( 'email', $customer->email )
                    ->orWhere( 'username', $customer->email )
                    ->firstOrNew();

                $user->birth_date = $customer->birth_date;
                $user->username = ( $customer->email ?? 'user-' ) . $faker->randomNumber( 5 );
                $user->email = ( $customer->email ?? $user->username ) . '@nexopos.com';
                $user->purchases_amount = $customer->purchases_amount ?: 0;
                $user->owed_amount = $customer->owed_amount ?: 0;
                $user->credit_limit_amount = $customer->credit_limit_amount ?: 0;
                $user->account_amount = $customer->account_amount ?: 0;
                $user->first_name = $customer->name ?: '';
                $user->last_name = $customer->surname ?: '';
                $user->gender = $customer->gender ?: '';
                $user->phone = $customer->phone ?: '';
                $user->pobox = $customer->pobox ?: '';
                $user->group_id = $customer->group_id;
                $user->author = $firstAdministrator->id;
                $user->active = true;
                $user->password = Hash::make( Str::random( 10 ) ); // every customer has a random password.
                $user->save();

                /**
                 * We'll assign the user to the role that was created based.
                 */
                $usersService->setUserRole( $user, [ Role::namespace( Role::STORECUSTOMER )->id ] );
                $doctorService->createAttributeForUser( $user );

                return [
                    'old_id' => $customer->id,
                    'new_id' => $user->id,
                    'user' => $user,
                ];
            } );

            /**
             * Every models that was pointing to the old customer id
             * must be update to support the new customer id which is not
             * set on the users table.
             */
            $users->each( function ( $data ) {
                foreach ( [ Order::class, CustomerAccountHistory::class, CustomerCoupon::class, CustomerAddress::class, CustomerReward::class ] as $class ) {
                    $class::where( 'customer_id', $data[ 'old_id' ] )->get()->each( function ( $address ) use ( $data ) {
                        $address->customer_id = $data[ 'new_id' ];
                        $address->save();
                    } );
                }
            } );

            Schema::drop( 'nexopos_customers' );
            Schema::drop( 'nexopos_customers_metas' );
        }

        /**
         * We noticed taxes were saved as a negative value on order products
         * this will fix those value by storing absolute values.
         */
        OrderProduct::get( 'tax_value' )->each( function ( $orderProduct ) {
            $orderProduct->tax_value = abs( $orderProduct->tax_value );
            $orderProduct->save();
        } );

        /**
         * We'll drop permissions we no longer use
         */

        /**
         * 1: This permission is a duplicate one, and can easilly be confused
         * with "nexopos.customers.manage-account-history"
         */
        $permission = Permission::namespace( 'nexopos.customers.manage-account' );

        if ( $permission instanceof Permission ) {
            RolePermission::where( 'permission_id', $permission->id )->delete();
            $permission->delete();
        }

        /**
         * Fix options
         */
        $options = Option::get();

        $options->each( function ( $option ) {
            $json = json_decode( $option->value, true );

            if ( preg_match( '/^[0-9]{1,}$/', $option->value ) ) {
                $option->value = (int) $option->value;
            } elseif ( preg_match( '/^[0-9]{1,}\.[0-9]{1,}$/', $option->value ) ) {
                $option->value = (float) $option->value;
            } elseif ( json_last_error() == JSON_ERROR_NONE ) {
                $option->value = $json;
                $option->array = 1;
            }

            $option->save();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
