<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Casts\NotDefinedCast;
use App\Casts\OrderDeliveryCast;
use App\Casts\OrderPaymentCast;
use App\Casts\OrderProcessCast;
use App\Casts\OrderTypeCast;
use App\Classes\CrudTable;
use App\Exceptions\NotAllowedException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Register;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\OrdersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TorMorten\Eventy\Facades\Events as Hook;

class OrderCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.orders';

    /**
     * define the base table
     */
    protected $table = 'nexopos_orders';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.orders';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.orders';

    /**
     * Model Used
     */
    protected $model = Order::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users as author', 'nexopos_orders.author', '=', 'author.id' ],
        [ 'nexopos_users as customer', 'nexopos_orders.customer_id', '=', 'customer.id' ],
    ];

    public $pick = [
        'author' => [ 'username' ],
        'customer' => [ 'first_name', 'phone' ],
    ];

    public $queryFilters = [];

    /**
     * Define where statement
     *
     * @var array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var array
     */
    protected $whereIn = [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = true;

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

    protected $permissions = [
        'create' => 'nexopos.create.orders',
        'read' => 'nexopos.read.orders',
        'update' => 'nexopos.update.orders',
        'delete' => 'nexopos.delete.orders',
    ];

    protected $casts = [
        'customer_phone' => NotDefinedCast::class,
        'total' => CurrencyCast::class,
        'tax_value' => CurrencyCast::class,
        'discount' => CurrencyCast::class,
        'delivery_status' => OrderDeliveryCast::class,
        'process_status' => OrderProcessCast::class,
        'type' => OrderTypeCast::class,
        'payment_status' => OrderPaymentCast::class,
        'created_at' => DateCast::class,
        'updated_at' => DateCast::class,
    ];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        /**
         * This will allow module to change the bound
         * class for the default User model.
         */
        $UserClass = app()->make( User::class );

        /**
         * Let's define the query filters
         * we would like to apply to the crud
         */
        $this->queryFilters = [
            [
                'type' => 'daterangepicker',
                'name' => 'nexopos_orders.created_at',
                'description' => __( 'Restrict the orders by the creation date.' ),
                'label' => __( 'Created Between' ),
            ], [
                'type' => 'select',
                'label' => __( 'Payment Status' ),
                'name' => 'payment_status',
                'description' => __( 'Restrict the orders by the payment status.' ),
                'options' => Helper::kvToJsOptions( [
                    Order::PAYMENT_PAID => __( 'Paid' ),
                    Order::PAYMENT_HOLD => __( 'Hold' ),
                    Order::PAYMENT_PARTIALLY => __( 'Partially Paid' ),
                    Order::PAYMENT_PARTIALLY_REFUNDED => __( 'Partially Refunded' ),
                    Order::PAYMENT_REFUNDED => __( 'Refunded' ),
                    Order::PAYMENT_UNPAID => __( 'Unpaid' ),
                    Order::PAYMENT_VOID => __( 'Voided' ),
                    Order::PAYMENT_DUE => __( 'Due' ),
                    Order::PAYMENT_PARTIALLY_DUE => __( 'Due With Payment' ),
                ] ),
            ], [
                'type' => 'select',
                'label' => __( 'Author' ),
                'name' => 'nexopos_orders.author',
                'description' => __( 'Filter the orders by the author.' ),
                'options' => Helper::toJsOptions( $UserClass::get(), [ 'id', 'username' ] ),
            ], [
                'type' => 'select',
                'label' => __( 'Customer' ),
                'name' => 'customer_id',
                'description' => __( 'Filter the orders by the customer.' ),
                'options' => Helper::toJsOptions( Customer::get(), [ 'id', 'first_name' ] ),
            ], [
                'type' => 'text',
                'label' => __( 'Customer Phone' ),
                'name' => 'phone',
                'operator' => 'like',
                'description' => __( 'Filter orders using the customer phone number.' ),
                'options' => Helper::toJsOptions( Customer::get(), [ 'id', 'phone' ] ),
            ], [
                'type' => 'select',
                'label' => __( 'Cash Register' ),
                'name' => 'register_id',
                'description' => __( 'Filter the orders to the cash registers.' ),
                'options' => Helper::toJsOptions( Register::get(), [ 'id', 'name' ] ),
            ],
        ];
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return array
     **/
    public function getLabels()
    {
        return CrudTable::labels(
            list_title: __( 'Orders List' ),
            list_description: __( 'Display all orders.' ),
            no_entry: __( 'No orders has been registered' ),
            create_new: __( 'Add a new order' ),
            create_title: __( 'Create a new order' ),
            create_description: __( 'Register a new order and save it.' ),
            edit_title: __( 'Edit order' ),
            edit_description: __( 'Modify  Order.' ),
            back_to_list: __( 'Return to Orders' ),
        );
    }

    /**
     * Check whether a feature is enabled
     *
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, Order $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function beforePost( $request )
    {
        $this->allowedTo( 'create' );

        return $request;
    }

    /**
     * After saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function afterPost( $request, Order $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param  string
     * @return mixed
     */
    public function get( $param )
    {
        switch ( $param ) {
            case 'model': return $this->model;
                break;
        }
    }

    /**
     * Before updating a record
     *
     * @param Request $request
     * @param  object entry
     * @return void
     */
    public function beforePut( $request, $entry )
    {
        $this->allowedTo( 'update' );

        return $request;
    }

    /**
     * After updating a record
     *
     * @param Request $request
     * @param  object entry
     * @return void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.orders' ) {
            $this->allowedTo( 'delete' );

            /**
             * @var OrdersService
             */
            $orderService = app()->make( OrdersService::class );
            $orderService->deleteOrder( $model );

            return [
                'status' => 'success',
                'message' => __( 'The order and the attached products has been deleted.' ),
            ];
        }
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                label: __( 'Code' ),
                identifier: 'code',
                width: '170px'
            ),
            CrudTable::column( label: __( 'Type' ), identifier: 'type', width: '100px' ),
            CrudTable::column( label: __( 'Customer' ), identifier: 'customer_first_name', width: '100px' ),
            CrudTable::column( label: __( 'Delivery' ), identifier: 'delivery_status', width: '150px' ),
            CrudTable::column( label: __( 'Payment' ), identifier: 'payment_status', width: '150px' ),
            CrudTable::column( label: __( 'Tax' ), identifier: 'tax_value', width: '100px' ),
            CrudTable::column( label: __( 'Total' ), identifier: 'total', width: '100px' ),
            CrudTable::column( label: __( 'Author' ), identifier: 'author_username', width: '150px' ),
            CrudTable::column( label: __( 'Created At' ), identifier: 'created_at', width: '150px' ),
        );
    }

    public function hook( $query ): void
    {
        if ( empty( request()->query( 'direction' ) ) ) {
            $query->orderBy( 'id', 'desc' );
        }
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->{ '$cssClass' } = match ( $entry->__raw->payment_status ) {
            Order::PAYMENT_PAID => 'success border text-sm',
            Order::PAYMENT_UNPAID => 'danger border text-sm',
            Order::PAYMENT_PARTIALLY => 'info border text-sm',
            Order::PAYMENT_HOLD => 'danger border text-sm',
            Order::PAYMENT_VOID => 'error border text-sm',
            Order::PAYMENT_REFUNDED => 'default border text-sm',
            Order::PAYMENT_PARTIALLY_REFUNDED => 'default border text-sm',
            Order::PAYMENT_DUE => 'danger border text-sm',
            Order::PAYMENT_PARTIALLY_DUE => 'danger border text-sm',
            default => ''
        };

        $entry->action(
            identifier: 'ns.order-options',
            label: '<i class="mr-2 las la-cogs"></i> ' . __( 'Options' ),
            type: 'POPUP',
            url: ns()->url( '/dashboard/' . 'orders' . '/edit/' . $entry->id )
        );

        /**
         * We'll check if the order has refunds
         * to add a refund receipt for printing
         */
        $refundCount = DB::table( Hook::filter( 'ns-model-table', 'nexopos_orders_refunds' ) )
            ->where( 'order_id', $entry->id )
            ->count();

        $hasRefunds = $refundCount > 0;

        if ( $hasRefunds ) {
            $entry->action(
                identifier: 'ns.order-refunds',
                label: '<i class="mr-2 las la-receipt"></i> ' . __( 'Refund Receipt' ),
                type: 'POPUP',
                url: ns()->url( '/dashboard/' . 'orders' . '/refund-receipt/' . $entry->id ),
            );
        }

        $entry->action(
            identifier: 'invoice',
            label: '<i class="mr-2 las la-file-invoice-dollar"></i> ' . __( 'Invoice' ),
            url: ns()->url( '/dashboard/' . 'orders' . '/invoice/' . $entry->id ),
        );

        $entry->action(
            identifier: 'receipt',
            label: '<i class="mr-2 las la-receipt"></i> ' . __( 'Receipt' ),
            url: ns()->url( '/dashboard/' . 'orders' . '/receipt/' . $entry->id ),
        );

        $entry->action(
            identifier: 'delete',
            label: '<i class="mr-2 las la-trash"></i> ' . __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.orders/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ],
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return  false/array
     */
    public function bulkAction( Request $request )
    {
        if ( $request->input( 'action' ) == 'delete_selected' ) {

            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException( __( 'Deleting has been explicitely disabled on this component.' ) );
            }

            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof Order ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'error' ]++;
                }
            }

            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     *
     * @return array of links
     */
    public function getLinks(): array
    {
        return [
            'list' => 'ns.orders',
            'create' => ns()->route( 'ns.dashboard.pos' ),
            'edit' => 'ns.orders/edit/#',
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label' => __( 'Delete Selected Groups' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ] ),
            ],
        ] );
    }

    /**
     * get exports
     *
     * @return array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
