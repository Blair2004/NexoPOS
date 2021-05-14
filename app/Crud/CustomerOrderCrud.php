<?php
namespace App\Crud;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Order;

class CustomerOrderCrud extends OrderCrud
{
    /**
     * default identifier
     * @param  string
     */
    protected $identifier   =   'dashboard/customers/orders';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers-orders';

    /**
     * Model Used
     * @param  string
     */
    protected $model      =   Order::class;

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Customer Orders List' ),
            'list_description'      =>  __( 'Display all customer orders.' ),
            'no_entry'              =>  __( 'No customer orders has been registered' ),
            'create_new'            =>  __( 'Add a new customer order' ),
            'create_title'          =>  __( 'Create a new customer order' ),
            'create_description'    =>  __( 'Register a new customer order and save it.' ),
            'edit_title'            =>  __( 'Edit customer order' ),
            'edit_description'      =>  __( 'Modify  Customer Order.' ),
            'back_to_list'          =>  __( 'Return to Customer Orders' ),
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

    public function hook( $query )
    {
        if ( empty( request()->query( 'direction' ) ) ) {
            $query->orderBy( 'id', 'desc' );
        }

        if ( ! empty( request()->query( 'customer_id' ) ) ) {
            $query->where( 'customer_id', request()->query( 'customer_id' ) );
        }
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
                            'label' =>  __( 'Created at' ),
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
                            'name'  =>  'discount_percentage',
                            'label' =>  __( 'Discount Percentage' ),
                            'value' =>  $entry->discount_percentage ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'discount_type',
                            'label' =>  __( 'Discount Type' ),
                            'value' =>  $entry->discount_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'final_payment_date',
                            'label' =>  __( 'Final Payment Date' ),
                            'value' =>  $entry->final_payment_date ?? '',
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
                            'name'  =>  'subtotal',
                            'label' =>  __( 'Sub Total' ),
                            'value' =>  $entry->subtotal ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'tax_value',
                            'label' =>  __( 'Tax Value' ),
                            'value' =>  $entry->tax_value ?? '',
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
                            'name'  =>  'total_instalments',
                            'label' =>  __( 'Total installments' ),
                            'value' =>  $entry->total_instalments ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'type',
                            'label' =>  __( 'Type' ),
                            'value' =>  $entry->type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'updated_at',
                            'label' =>  __( 'Updated at' ),
                            'value' =>  $entry->updated_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'uuid',
                            'label' =>  __( 'Uuid' ),
                            'value' =>  $entry->uuid ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'voidance_reason',
                            'label' =>  __( 'Voidance Reason' ),
                            'value' =>  $entry->voidance_reason ?? '',
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
        if ( $this->permissions[ 'create' ] !== false ) {
            ns()->restrict( $this->permissions[ 'create' ] );
        } else {
            throw new NotAllowedException;
        }

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
        if ( $this->permissions[ 'update' ] !== false ) {
            ns()->restrict( $this->permissions[ 'update' ] );
        } else {
            throw new NotAllowedException;
        }

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
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id, $model ) {
        if ( $namespace == 'ns.customers.orders' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
            **/
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }
        }
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

        if ( $request->input( 'action' ) == 'delete_selected' ) {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }

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
            'list'      =>  ns()->url( 'dashboard/' . 'dashboard/customers/orders' ),
            'create'    =>  ns()->url( 'dashboard/' . 'dashboard/customers/orders/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'dashboard/customers/orders/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.customers.orders' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.customers.orders/{id}' . '' ),
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