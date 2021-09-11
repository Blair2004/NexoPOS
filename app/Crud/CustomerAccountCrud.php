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
use App\Models\CustomerAccountHistory;
use App\Services\CustomerService;
use App\Services\Helper;

class CustomerAccountCrud extends CrudService
{
    /**
     * define the base table
     * @param  string
     */
    protected $table      =   'nexopos_customers_account_history';

    /**
     * default slug
     * @param  string
     */
    protected $slug   =   '/customers/account-history';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers-account-history';

    /**
     * Model Used
     * @param  string
     */
    protected $model      =   CustomerAccountHistory::class;

    /**
     * Define permissions
     * @param  array
     */
    protected $permissions  =   [
        'create'    =>  'nexopos.customers.manage-account-history',
        'read'      =>  'nexopos.customers.manage-account-history',
        'update'    =>  'nexopos.customers.manage-account-history',
        'delete'    =>  'nexopos.customers.manage-account-history',
    ];

    /**
     * We would like to manually
     * save the data from the crud class
     */
    public $disablePost  =   true;

    public $disablePut   =   false;

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     * @param  array
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_customers_account_history.author' ],
        'leftJoin'  =>  [
            [ 'nexopos_orders as order', 'order.id', '=', 'nexopos_customers_account_history.order_id' ]
        ]
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
        'order'     =>  [ 'code' ],
        'user'      =>  [ 'username' ],
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
     * @param CustomerService;
     */
    protected $customerService;

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );

        /**
         * @var CustomerService
         */
        $this->customerService  =   app()->make( CustomerService::class );
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Customer Accounts List' ),
            'list_description'      =>  __( 'Display all customer accounts.' ),
            'no_entry'              =>  __( 'No customer accounts has been registered' ),
            'create_new'            =>  __( 'Add a new customer account' ),
            'create_title'          =>  __( 'Create a new customer account' ),
            'create_description'    =>  __( 'Register a new customer account and save it.' ),
            'edit_title'            =>  __( 'Edit customer account' ),
            'edit_description'      =>  __( 'Modify  Customer Account.' ),
            'back_to_list'          =>  __( 'Return to Customer Accounts' ),
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
                'description'   =>  __( 'This will be ignored.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'amount',
                            'label' =>  __( 'Amount' ),
                            'validation'    =>  'required',
                            'description'   =>  __( 'Define the amount of the transaction' ),
                            'value' =>  $entry->amount ?? '',
                        ], [
                            'type'  =>  'select',
                            'options'   =>  Helper::kvToJsOptions([
                                CustomerAccountHistory::OPERATION_DEDUCT    =>  __( 'Deduct' ),
                                CustomerAccountHistory::OPERATION_ADD       =>  __( 'Add' ),
                            ]),
                            'description'   =>  __( 'Define what operation will occurs on the customer account.' ),
                            'name'  =>  'operation',
                            'validation'    =>  'required',
                            'label' =>  __( 'Operation' ),
                            'value' =>  $entry->operation ?? '',
                        ], [
                            'type'  =>  'textarea',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
                        ]
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
    public function filterPutInputs( $inputs, CustomerAccountHistory $entry )
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
     * @param  CustomerAccountHistory $entry
     * @return  void
     */
    public function afterPost( $request, CustomerAccountHistory $entry )
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
        if ( $namespace == 'ns.customers-account-history' ) {
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
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'amount'  =>  [
                'label'  =>  __( 'Amount' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'operation'  =>  [
                'label'  =>  __( 'Operation' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'order_code'  =>  [
                'label'  =>  __( 'Order' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
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

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        // Don't overwrite
        $entry->{ '$checked' }  =   false;
        $entry->{ '$toggled' }  =   false;
        $entry->{ '$id' }       =   $entry->id;

        $entry->{ 'order_code' }    =   $entry->{ 'order_code' } === null ? __( 'N/A' ) : $entry->{ 'order_code' };
        $entry->operation           =   $this->customerService->getCustomerAccountOperationLabel( $entry->operation );
        $entry->amount              =   ( string ) ns()->currency->define( $entry->amount );

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
                'url'       =>  ns()->url( '/api/nexopos/v4/crud/ns.customers-account-history/' . $entry->id ),
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
                if ( $entity instanceof CustomerAccountHistory ) {
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
            'list'      =>  ns()->url( 'dashboard/' . 'customers/' . '/account-history' ),
            'create'    =>  ns()->url( 'dashboard/' . 'customers/' . '/account-history/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'customers/' . '/account-history/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.customers-account-history' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.customers-account-history/{id}' ),
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