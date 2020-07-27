<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Models\User;
use App\Models\Order;
use Hook;

class OrderCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_orders';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.orders.index';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.orders';

    /**
     * Model Used
     */
    protected $model      =   \App\Models\Order::class;

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
        public $fillable    =   "";

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( 'crud.entry', [ $this, 'setActions' ], 10, 2 );
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
    public function getFields( $entry = null ) 
    {
        return [
            // your field here
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
    public function filterPutInputs( $inputs, \App\Models\Order $entry )
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
     * @return  array | throw AccessDeniedException
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

        throw new AccessDeniedException( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id ) {
        if ( $namespace == 'ns.orders' ) {
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
            'customer_id'  =>  [
                'label'  =>  __( 'Customer' )
            ],
            'code'  =>  [
                'label'  =>  __( 'Code' )
            ],
            'discount'  =>  [
                'label'  =>  __( 'Discount' )
            ],
            'discount_rate'  =>  [
                'label'  =>  __( 'Discount Rate' )
            ],
            'shipping_rate'  =>  [
                'label'  =>  __( 'Ship. Rate' )
            ],
            'gross_total'  =>  [
                'label'  =>  __( 'Gross Total' )
            ],
            'total'  =>  [
                'label'  =>  __( 'Total' )
            ],
            'net_total'  =>  [
                'label'  =>  __( 'Net Total' )
            ],
            'author'  =>  [
                'label'  =>  __( 'Author' )
            ],
            'delivery_status'  =>  [
                'label'  =>  __( 'Delivered On' )
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created At' )
            ],
            'type'  =>  [
                'label'  =>  __( 'Type' )
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit.licence',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      '/dashboard/crud/ns.orders/edit/#'
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'index'     =>  'id',
                'url'       =>  'tendoo/crud/ns.orders' . '/#',
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
    public function bulkDelete( Request $request ) 
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
                if ( $entity instanceof App\Models\Order ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }
            return $status;
        }
        return false;
    }

    /**
     * get Links
     * @return  array of links
     */
    public function getLinks()
    {
        return  [
            'list'  =>  'ns.orders.index',
            'create'    =>  'ns.orders.index/create',
            'edit'      =>  'ns.orders.index/edit/#'
        ];
    }

    /**
     * Get Bulk actions
     * @return  array of actions
    **/
    public function getBulkActions()
    {
        return [];
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