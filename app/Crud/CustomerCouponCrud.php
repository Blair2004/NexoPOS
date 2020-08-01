<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Models\User;
use Hook;
use Exception;
use App\Models\CustomerCoupon;

class CustomerCouponCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_customers_coupons';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.customers-coupons';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers-coupons';

    /**
     * Model Used
     */
    protected $model      =   CustomerCoupon::class;

    /**
     * Adding relation
     */
    public $relations   =  [
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
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'id',
                            'label' =>  __( 'Id' ),
                            'value' =>  $entry->id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'label' =>  __( 'Name' ),
                            'value' =>  $entry->name ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'type',
                            'label' =>  __( 'Type' ),
                            'value' =>  $entry->type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'discount_value',
                            'label' =>  __( 'Discount_value' ),
                            'value' =>  $entry->discount_value ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'valid_until',
                            'label' =>  __( 'Valid_until' ),
                            'value' =>  $entry->valid_until ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'minimum_cart_value',
                            'label' =>  __( 'Minimum_cart_value' ),
                            'value' =>  $entry->minimum_cart_value ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'maximum_cart_value',
                            'label' =>  __( 'Maximum_cart_value' ),
                            'value' =>  $entry->maximum_cart_value ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'valid_hours_start',
                            'label' =>  __( 'Valid_hours_start' ),
                            'value' =>  $entry->valid_hours_start ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'valid_hours_end',
                            'label' =>  __( 'Valid_hours_end' ),
                            'value' =>  $entry->valid_hours_end ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'customer_id',
                            'label' =>  __( 'Customer_id' ),
                            'value' =>  $entry->customer_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'assigned',
                            'label' =>  __( 'Assigned' ),
                            'value' =>  $entry->assigned ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'limit_usage',
                            'label' =>  __( 'Limit_usage' ),
                            'value' =>  $entry->limit_usage ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'created_at',
                            'label' =>  __( 'Created_at' ),
                            'value' =>  $entry->created_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'updated_at',
                            'label' =>  __( 'Updated_at' ),
                            'value' =>  $entry->updated_at ?? '',
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
    public function filterPutInputs( $inputs, CustomerCoupon $entry )
    {
        return $inputs;
    }

    /**
     * After Crud POST
     * @param  object entry
     * @return  void
     */
    public function afterPost( $inputs )
    {
        return $inputs;
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
     * After Crud PUT
     * @param  object entry
     * @return  void
     */
    public function afterPut( $inputs )
    {
        return $inputs;
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
    public function beforeDelete( $namespace, $id ) {
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
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'id'  =>  [
                'label'  =>  __( 'Id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'type'  =>  [
                'label'  =>  __( 'Type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'discount_value'  =>  [
                'label'  =>  __( 'Discount_value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'valid_until'  =>  [
                'label'  =>  __( 'Valid_until' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'minimum_cart_value'  =>  [
                'label'  =>  __( 'Minimum_cart_value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'maximum_cart_value'  =>  [
                'label'  =>  __( 'Maximum_cart_value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'valid_hours_start'  =>  [
                'label'  =>  __( 'Valid_hours_start' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'valid_hours_end'  =>  [
                'label'  =>  __( 'Valid_hours_end' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'customer_id'  =>  [
                'label'  =>  __( 'Customer_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'assigned'  =>  [
                'label'  =>  __( 'Assigned' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'limit_usage'  =>  [
                'label'  =>  __( 'Limit_usage' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created_at' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'updated_at'  =>  [
                'label'  =>  __( 'Updated_at' ),
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

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit.licence',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      '/dashboard/crud/ns.customers-coupons/edit/#'
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'index'     =>  'id',
                'url'       =>  'tendoo/crud/ns.customers-coupons' . '/#',
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                    'title'     =>  __( 'Delete a licence' )
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
        $user   =   app()->make( 'Tendoo\Core\Services\Users' );
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

            foreach ( $request->input( 'entries_id' ) as $id ) {
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
            'list'      =>  'ns.customers-coupons',
            'create'    =>  'ns.customers-coupons/create',
            'edit'      =>  'ns.customers-coupons/edit/#'
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
                'label'         =>  __( 'Delete Selected Coupons' ),
                'confirm'       =>  __( 'Would you like to delete selected coupons?' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  route( 'crud.bulk-actions', [
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