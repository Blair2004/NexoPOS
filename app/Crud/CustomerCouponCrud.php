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
use App\Models\CustomerCoupon;

class CustomerCouponCrud extends CrudService
{
    /**
     * define the base table
     * @param  string
     */
    protected $table      =   'nexopos_customers_coupons';

    /**
     * default slug
     * @param  string
     */
    protected $slug   =   'customers/coupons-generated';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers-coupons';

    /**
     * Model Used
     * @param  string
     */
    protected $model      =   CustomerCoupon::class;

    /**
     * Define permissions
     * @param  array
     */
    protected $permissions  =   [
        'create'    =>  false,
        'read'      =>  'nexopos.read.coupons',
        'update'    =>  'nexopos.update.coupons',
        'delete'    =>  'nexopos.delete.coupons',
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     * @param  array
     */
    public $relations   =  [
        'leftJoin'  =>  [
            [ 'nexopos_users as user', 'user.id', '=', 'nexopos_customers_coupons.author' ]
        ],
        [ 'nexopos_customers as customer', 'customer.id', '=', 'nexopos_customers_coupons.customer_id' ],
        [ 'nexopos_coupons as coupon', 'coupon.id', '=', 'nexopos_customers_coupons.coupon_id' ],
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations    =   [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    /**
     * Pick
     * Restrict columns you retreive from relation.
     * Should be an array of associative keys, where 
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick        =   [
        'user'      =>  [ 'username' ],
        'customer'  =>  [ 'name' ],
        'coupon'    =>  [ 'type', 'discount_value' ],
    ];

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
     * Fields which will be filled during post/put
     */
        public $fillable    =   [];

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Customer Coupons List' ),
            'list_description'      =>  __( 'Display all customer coupons.' ),
            'no_entry'              =>  __( 'No customer coupons has been registered' ),
            'create_new'            =>  __( 'Add a new customer coupon' ),
            'create_title'          =>  __( 'Create a new customer coupon' ),
            'create_description'    =>  __( 'Register a new customer coupon and save it.' ),
            'edit_title'            =>  __( 'Edit customer coupon' ),
            'edit_description'      =>  __( 'Modify  Customer Coupon.' ),
            'back_to_list'          =>  __( 'Return to Customer Coupons' ),
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
                'name'          =>  'name',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'usage',
                            'label' =>  __( 'Usage' ),
                            'description'   =>  __( 'Define how many time the coupon has been used.' ),
                            'value' =>  $entry->usage ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'limit_usage',
                            'label' =>  __( 'Limit' ),
                            'description'   =>  __( 'Define the maximum usage possible for this coupon.' ),
                            'value' =>  $entry->limit_usage ?? '',
                        ], 
                    ]
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
    public function filterPutInputs( $inputs, CustomerCoupon $entry )
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
     * @param  CustomerCoupon $entry
     * @return  void
     */
    public function afterPost( $request, CustomerCoupon $entry )
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
        if ( $namespace == 'ns.customers-coupons' ) {
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

    public function hook( $query )
    {
        if ( ! empty( request()->query( 'customer_id' ) ) ) {
            $query->where( 'customer_id', request()->query( 'customer_id' ) );
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'customer_name'  =>  [
                'label'  =>  __( 'Customer' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'coupon_type'  =>  [
                'label'         =>  __( 'Type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'code'  =>  [
                'label'         =>  __( 'Code' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'coupon_discount_value'  =>  [
                'label'         =>  __( 'Value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'usage'  =>  [
                'label'  =>  __( 'Usage' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'limit_usage'  =>  [
                'label'  =>  __( 'Limit' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
                'label'  =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Date' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        // Don't overwrite
        $entry->{ '$checked' }  =   false;
        $entry->{ '$toggled' }  =   false;
        $entry->{ '$id' }       =   $entry->id;
        $entry->user_username    =   $entry->user_username ?: __( 'N/A' );

        switch( $entry->coupon_type ) {
            case 'percentage_discount':
                $entry->coupon_discount_value = $entry->coupon_discount_value . '%';
            break;
            case 'flat_discount':
                $entry->coupon_discount_value = ns()->currency->define( $entry->coupon_discount_value );
            break;
        }
        
        $entry->coupon_type     =   $entry->coupon_type === 'percentage_discount' ? __( 'Percentage' ) : __( 'Flat' );


        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       =>  ns()->url( '/api/nexopos/v4/crud/ns.customers-coupons/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                ]
            ]
        ];

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
                if ( $entity instanceof CustomerCoupon ) {
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
            'list'      =>  ns()->route( 'ns.dashboard.customers-coupons-generated-list' ),
            'create'    =>  '#', // ns()->url( 'dashboard/' . 'customers/' . request()->query( 'customer_id' ) . '/coupons/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'customers/' . request()->query( 'customer_id' ) . '/coupons/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.customers-coupons' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.customers-coupons/{id}' ),
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