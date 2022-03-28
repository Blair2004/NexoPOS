<?php
namespace App\Crud;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Order;
use App\Models\Register;
use App\Services\Helper;
use App\Services\OrdersService;
use Illuminate\Support\Facades\DB;

class OrderCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_orders';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.orders';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.orders';

    /**
     * Model Used
     */
    protected $model      =   Order::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_users', 'nexopos_orders.author', '=', 'nexopos_users.id' ],
        [ 'nexopos_customers', 'nexopos_customers.id', '=', 'nexopos_orders.customer_id' ],
    ];

    public $pick                =   [
        'nexopos_users'         =>  [ 'username' ],
        'nexopos_customers'     =>  [ 'name', 'phone' ]
    ];

    public $queryFilters    =   [];

    /**
     * Define where statement
     * @var  array
    **/
    protected $listWhere    =   [];

    /**
     * Define where in statement
     * @var  array
     */
    protected $whereIn      =   [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions     =   true;

    /**
     * Fields which will be filled during post/put
     */
    public $fillable    =   [];

    protected $permissions = [
        'create'    => 'nexopos.create.orders',
        'read'      => 'nexopos.read.orders',
        'update'    => 'nexopos.update.orders',
        'delete'    => 'nexopos.delete.orders',
    ];

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );

        /**
         * This will allow module to change the bound
         * class for the default User model.
         */
        $UserClass              =   app()->make( User::class );

        /**
         * Let's define the query filters
         * we would like to apply to the crud
         */
        $this->queryFilters     =   [
            [
                'type'  =>  'daterangepicker',
                'name'  =>  'nexopos_orders.created_at',
                'description'   =>  __( 'Restrict the orders by the creation date.' ),
                'label' =>  __( 'Created Between' )
            ], [
                'type'      =>  'select',
                'label'     =>  __( 'Payment Status' ),
                'name'      =>  'payment_status',
                'description'   =>  __( 'Restrict the orders by the payment status.' ),
                'options'   =>  Helper::kvToJsOptions([
                    Order::PAYMENT_PAID                 =>  __( 'Paid' ),
                    Order::PAYMENT_HOLD                 =>  __( 'Hold' ),
                    Order::PAYMENT_PARTIALLY            =>  __( 'Partially Paid' ),
                    Order::PAYMENT_PARTIALLY_REFUNDED   =>  __( 'Partially Refunded' ),
                    Order::PAYMENT_REFUNDED             =>  __( 'Refunded' ),
                    Order::PAYMENT_UNPAID               =>  __( 'Unpaid' ),
                    Order::PAYMENT_VOID                 =>  __( 'Voided' ),
                    Order::PAYMENT_DUE                  =>  __( 'Due' ),
                    Order::PAYMENT_PARTIALLY_DUE        =>  __( 'Due With Payment' ),
                ])
            ], [
                'type'      =>  'select',
                'label'     =>  __( 'Author' ),
                'name'      =>  'nexopos_orders.author',
                'description'   =>  __( 'Restrict the orders by the author.' ),
                'options'   =>  Helper::toJsOptions( $UserClass::get(), [ 'id', 'username' ])
            ], [
                'type'      =>  'select',
                'label'     =>  __( 'Customer' ),
                'name'      =>  'customer_id',
                'description'   =>  __( 'Restrict the orders by the customer.' ),
                'options'   =>  Helper::toJsOptions( Customer::get(), [ 'id', 'name' ])
            ], [
                'type'      =>  'text',
                'label'     =>  __( 'Customer Phone' ),
                'name'      =>  'phone',
                'operator'  =>  'like',
                'description'   =>  __( 'Restrict orders using the customer phone number.' ),
                'options'   =>  Helper::toJsOptions( Customer::get(), [ 'id', 'phone' ])
            ], [
                'type'      =>  'select',
                'label'     =>  __( 'Cash Register' ),
                'name'      =>  'register_id',
                'description'   =>  __( 'Restrict the orders to the cash registers.' ),
                'options'   =>  Helper::toJsOptions( Register::get(), [ 'id', 'name' ])
            ]
        ];
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Orders List' ),
            'list_description'      =>  __( 'Display all orders.' ),
            'no_entry'              =>  __( 'No orders has been registered' ),
            'create_new'            =>  __( 'Add a new order' ),
            'create_title'          =>  __( 'Create a new order' ),
            'create_description'    =>  __( 'Register a new order and save it.' ),
            'edit_title'            =>  __( 'Edit order' ),
            'edit_description'      =>  __( 'Modify  Order.' ),
            'back_to_list'          =>  __( 'Return to Orders' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     * @return  boolean
    **/
    public function isEnabled( $feature )
    {
        return false; // by default
    }

    /**
     * Fields
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null ) 
    {
        return [
            'main' =>  [
                'label'         =>  __( 'Name' ),
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'author',
                            'label' =>  __( 'Author' ),
                            'value' =>  $entry->author ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'change',
                            'label' =>  __( 'Change' ),
                            'value' =>  $entry->change ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'code',
                            'label' =>  __( 'Code' ),
                            'value' =>  $entry->code ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'created_at',
                            'label' =>  __( 'Created At' ),
                            'value' =>  $entry->created_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'customer_id',
                            'label' =>  __( 'Customer Id' ),
                            'value' =>  $entry->customer_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'delivery_status',
                            'label' =>  __( 'Delivery Status' ),
                            'value' =>  $entry->delivery_status ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'discount',
                            'label' =>  __( 'Discount' ),
                            'value' =>  $entry->discount ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'discount_rate',
                            'label' =>  __( 'Discount Rate' ),
                            'value' =>  $entry->discount_rate ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'discount_type',
                            'label' =>  __( 'Discount Type' ),
                            'value' =>  $entry->discount_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'gross_total',
                            'label' =>  __( 'Gross Total' ),
                            'value' =>  $entry->gross_total ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'id',
                            'label' =>  __( 'Id' ),
                            'value' =>  $entry->id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'net_total',
                            'label' =>  __( 'Net Total' ),
                            'value' =>  $entry->net_total ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'payment_status',
                            'label' =>  __( 'Payment Status' ),
                            'value' =>  $entry->payment_status ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'process_status',
                            'label' =>  __( 'Process Status' ),
                            'value' =>  $entry->process_status ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'shipping',
                            'label' =>  __( 'Shipping' ),
                            'value' =>  $entry->shipping ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'shipping_rate',
                            'label' =>  __( 'Shipping Rate' ),
                            'value' =>  $entry->shipping_rate ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'shipping_type',
                            'label' =>  __( 'Shipping Type' ),
                            'value' =>  $entry->shipping_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'tendered',
                            'label' =>  __( 'Tendered' ),
                            'value' =>  $entry->tendered ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'title',
                            'label' =>  __( 'Title' ),
                            'value' =>  $entry->title ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'total',
                            'label' =>  __( 'Total' ),
                            'value' =>  $entry->total ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'type',
                            'label' =>  __( 'Type' ),
                            'value' =>  $entry->type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'updated_at',
                            'label' =>  __( 'Updated At' ),
                            'value' =>  $entry->updated_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'uuid',
                            'label' =>  __( 'Uuid' ),
                            'value' =>  $entry->uuid ?? '',
                        ],                     ]
                ]
            ]
        ];
    }

    /**
     * Filter POST input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, Order $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     * @param  Request $request
     * @return  void
     */
    public function beforePost( $request )
    {
        $this->allowedTo( 'create' );
        return $request;
    }

    /**
     * After saving a record
     * @param  Request $request
     * @param  Order $entry
     * @return  void
     */
    public function afterPost( $request, Order $entry )
    {
        return $request;
    }

    
    /**
     * get
     * @param  string
     * @return  mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model' : return $this->model ; break;
        }
    }

    /**
     * Before updating a record
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function beforePut( $request, $entry )
    {
        $this->allowedTo( 'update' );
        return $request;
    }

    /**
     * After updating a record
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }
    
    /**
     * Protect an access to a specific crud UI
     * @param  array { namespace, id, type }
     * @return  array | throw Exception
    **/
    public function canAccess( $fields )
    {
        $users      =   app()->make( Users::class );
        
        if ( $users->is([ 'admin' ]) ) {
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The access is granted.' )
            ];
        }

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id, $model ) {
        if ( $namespace == 'ns.orders' ) {
            $this->allowedTo( 'delete' );

            /**
             * @var OrdersService
             */
            $orderService   =   app()->make( OrdersService::class );
            $orderService->deleteOrder( $model );

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The order and the attached products has been deleted.' )
            ];
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'code'  =>  [
                'label'  =>  __( 'Code' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
                'width'        =>  '120px',
            ],
            'nexopos_customers_name'  =>  [
                'label'         =>  __( 'Customer' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
                'width'        =>  '120px',
            ],
            'nexopos_customers_phone'   =>  [
                'label'         =>  __( 'Phone' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'discount'  =>  [
                'label'  =>  __( 'Discount' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'delivery_status'  =>  [
                'label'  =>  __( 'Delivery Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'payment_status'  =>  [
                'label'  =>  __( 'Payment Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'process_status'  =>  [
                'label'  =>  __( 'Process Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'total'  =>  [
                'label'  =>  __( 'Total' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'type'  =>  [
                'label'  =>  __( 'Type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'nexopos_users_username'  =>  [
                'label'  =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created At' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
        ];
    }

    public function hook( $query )
    {
        if ( empty( request()->query( 'direction' ) ) ) {
            $query->orderBy( 'id', 'desc' );
        }
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        /**
         * @var OrdersService
         */
        $orderService           =   app()->make( OrdersService::class );

        // Don't overwrite
        $entry->{ '$checked' }  =   false;
        $entry->{ '$toggled' }  =   false;
        $entry->{ '$id' }       =   $entry->id;
        $entry->nexopos_customers_phone     =   $entry->nexopos_customers_phone ?: __( 'N/A' );
        $entry->total                       =   ( string ) ns()->currency->define( $entry->total );
        $entry->discount                    =   ( string ) ns()->currency->define( $entry->discount );

        $entry->delivery_status         =   $orderService->getShippingLabel( $entry->delivery_status );
        $entry->process_status          =   $orderService->getProcessStatus( $entry->process_status );
        $entry->type                    =   $orderService->getTypeLabel( $entry->type );

        switch( $entry->payment_status ) {
            case Order::PAYMENT_PAID : 
                $entry->{ '$cssClass' }             =   'success border text-sm';
            break;
            case Order::PAYMENT_UNPAID : 
                $entry->{ '$cssClass' }             =   'danger border text-sm';
            break;
            case Order::PAYMENT_PARTIALLY : 
                $entry->{ '$cssClass' }             =   'info border text-sm';
            break;
            case Order::PAYMENT_HOLD : 
                $entry->{ '$cssClass' }             =   'danger border text-sm';
            break;
            case Order::PAYMENT_VOID : 
                $entry->{ '$cssClass' }             =   'error border text-sm';
            break;
            case Order::PAYMENT_REFUNDED : 
                $entry->{ '$cssClass' }             =   'default border text-sm';
            break;
            case Order::PAYMENT_PARTIALLY_REFUNDED : 
                $entry->{ '$cssClass' }             =   'default border text-sm';
            break;
            case Order::PAYMENT_DUE : 
                $entry->{ '$cssClass' }             =   'danger border text-sm';
            break;
            case Order::PAYMENT_PARTIALLY_DUE : 
                $entry->{ '$cssClass' }             =   'danger border text-sm';
            break;
        }

        $entry->payment_status  =   ns()->order->getPaymentLabel( $entry->payment_status );

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      '<i class="mr-2 las la-cogs"></i> ' . __( 'Options' ),
                'namespace'     =>      'ns.order-options',
                'type'          =>      'POPUP',
                'url'           =>     ns()->url( '/dashboard/' . 'orders' . '/edit/' . $entry->id )
            ], [
                'label'         =>      '<i class="mr-2 las la-file-invoice-dollar"></i> ' . __( 'Invoice' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'url'           =>     ns()->url( '/dashboard/' . 'orders' . '/invoice/' . $entry->id )
            ], [
                'label'         =>      '<i class="mr-2 las la-receipt"></i> ' . __( 'Receipt' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'url'           =>     ns()->url( '/dashboard/' . 'orders' . '/receipt/' . $entry->id )
            ], [
                'label'     =>  '<i class="mr-2 las la-trash"></i> ' . __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.orders/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                ]
            ]
        ];

        /**
         * We'll check if the order has refunds 
         * to add a refund receipt for printing
         */
        $refundCount    =   DB::table( Hook::filter( 'ns-model-table', 'nexopos_orders_refunds' ) )
            ->where( 'order_id', $entry->id )
            ->count();
            
        $hasRefunds     =   $refundCount > 0;

        if ( $hasRefunds ) {
            array_splice( $entry->{ '$actions' }, 3, 0, [
                [
                    'label'         =>  '<i class="mr-2 las la-receipt"></i> ' . __( 'Refund Receipt' ),
                    'type'          =>  'POPUP',
                    'namespace'     =>  'ns.order-refunds',
                    'url'           =>  ns()->url( '/dashboard/' . 'orders' . '/refund-receipt/' . $entry->id ),
                ]
            ]);
        }

        return $entry;
    }

    
    /**
     * Bulk Delete Action
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction( Request $request ) 
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user   =   app()->make( Users::class );
        if ( ! $user->is([ 'admin', 'supervisor' ]) ) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  __( 'You\'re not allowed to do this operation' )
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Order ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }
            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     * @return  array of links
     */
    public function getLinks()
    {
        return  [
            'list'      =>  'ns.orders',
            'create'    =>  ns()->route( 'ns.dashboard.pos' ),
            'edit'      =>  'ns.orders/edit/#'
        ];
    }

    /**
     * Get Bulk actions
     * @return  array of actions
    **/
    public function getBulkActions()
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label'         =>  __( 'Delete Selected Groups' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace
                ])
            ]
        ]);
    }

    /**
     * get exports
     * @return  array of export formats
    **/
    public function getExports()
    {
        return [];
    }
}