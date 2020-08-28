<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Options;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Exception;
use TorMorten\Eventy\Facades\Events as Hook;

class CustomerCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_customers';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.customers.index';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers';

    /**
     * Model Used
     */
    protected $model      =   \App\Models\Customer::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_customers_groups', 'nexopos_customers.group_id', '=', 'nexopos_customers_groups.id' ],
        [ 'nexopos_users', 'nexopos_customers.author', '=', 'nexopos_users.id' ],
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

        $this->options      =   app()->make( Options::class );

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
            'list_title'            =>  __( 'Customers List' ),
            'list_description'      =>  __( 'Display all customers.' ),
            'no_entry'              =>  __( 'No customers has been registered' ),
            'create_new'            =>  __( 'Add a new customer' ),
            'create_title'          =>  __( 'Create a new customer' ),
            'create_description'    =>  __( 'Register a new customer and save it.' ),
            'edit_title'            =>  __( 'Edit customer' ),
            'edit_description'      =>  __( 'Modify  Customer.' ),
            'back_to_list'          =>  __( 'Return to Customers' ),
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
    public function getForm( Customer $entry = null ) 
    {
        return [
            'main'  =>  [
                'label' =>  __( 'Customer Name' ),
                'name'  =>  'name',
                'validation'    =>  'required',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a unique name for the customer.' )
            ], 
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'          =>  'text',
                            'label'         =>  __( 'Surname' ),
                            'name'          =>  'surname',
                            'value'         =>  $entry->surname ?? '',
                            'description'   =>  __( 'Provide the customer surname' )
                        ], [
                            'type'          =>  'select',
                            'label'         =>  __( 'Group' ),
                            'name'          =>  'group_id',
                            'value'         =>  $entry->group_id ?? '',
                            'options'       =>  Helper::toJsOptions( CustomerGroup::all(), [ 'id', 'name' ]),
                            'description'   =>  __( 'Assign the customer to a group' )
                        ], [
                            'type'          =>  'email',
                            'label'         =>  __( 'Email' ),
                            'name'          =>  'email',
                            'value'         =>  $entry->email ?? '',
                            'validation'    =>  [
                                'required',
                                'email',
                                $entry instanceof Customer ? Rule::unique( 'nexopos_customers', 'email' )->ignore( $entry->id ) : Rule::unique( 'nexopos_customers', 'email' )
                            ],
                            'description'   =>  __( 'Provide the customer email' )
                        ], [
                            'type'          =>  'text',
                            'label'         =>  __( 'Phone Number' ),
                            'name'          =>  'phone',
                            'value'         =>  $entry->phone ?? '',
                            'description'   =>  __( 'Provide the customer phone number' )
                        ], [
                            'type'          =>  'text',
                            'label'         =>  __( 'PO Box' ),
                            'name'          =>  'pobox',
                            'value'         =>  $entry->pobox ?? '',
                            'description'   =>  __( 'Provide the customer PO.Box' )
                        ], [
                            'type'          =>  'select',
                            'options'       =>  Helper::kvToJsOptions([
                                ''          =>  __( 'Not Defined' ),
                                'male'      =>  __( 'Male' ),
                                'female'    =>  __( 'Female' )          
                            ]),
                            'label'         =>  __( 'Gender' ),
                            'name'          =>  'gender',
                            'value'         =>  $entry->gender ?? '',
                            'description'   =>  __( 'Provide the customer PO.Box' )
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
        return collect( $inputs )->map( function( $value, $key ) {
            if ( $key === 'group_id' && empty( $value ) ) {
                $value    =   $this->options->get( 'ns_customers_default_group', false );
                if ( $value === false ) {
                    throw new Exception( __( 'No group selected and no default group configured.' ) );
                }
            }
            return $value;
        });
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, \App\Models\Customer $entry )
    {
        return collect( $inputs )->map( function( $value, $key ) {
            if ( $key === 'group_id' && empty( $value ) ) {
                $value    =   $this->options->get( 'ns_customers_default_group', false );
                if ( $value === false ) {
                    throw new Exception( __( 'No group selected and no default group configured.' ) );
                }
            }
            return $value;
        });
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

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id ) {
        if ( $namespace == 'ns.customers' ) {
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
            'name'  =>  [
                'label'  =>  __( 'Name' )
            ],
            'surname'  =>  [
                'label'  =>  __( 'Surname' )
            ],
            'nexopos_customers_groups_name'  =>  [
                'label'  =>  __( 'Group' )
            ],
            'email'  =>  [
                'label'  =>  __( 'Email' )
            ],
            'gender'  =>  [
                'label'  =>  __( 'Gender' )
            ],
            'nexopos_users_username'  =>  [
                'label'  =>  __( 'Author' )
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created At' )
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
                'namespace'     =>      'edit_customers_group',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      url( 'dashboard/customers/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'index'     =>  'id',
                'url'       =>  url( '/api/nexopos/v4/crud/ns.customers/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                    'title'     =>  __( 'Delete a customers' )
                ]
            ]
        ];

        $entry->{ '$checked' }          =   false;
        $entry->{ '$toggled' }          =   false;
        $entry->{ '$id' }               =   $entry->id;
        $entry->surname                 =   $entry->surname ?? __( 'Not Defined' );
        $entry->pobox                   =   $entry->pobox ?? __( 'Not Defined' );
        $entry->reward_system_id        =   $entry->reward_system_id ?? __( 'Not Defined' );
        
        switch( $entry->gender ) {
            case 'male': $entry->gender = __( 'Male' );break;
            case 'female': $entry->gender = __( 'Female' );break;
            default: $entry->gender = __( 'Not Defined' );break;
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
                if ( $entity instanceof Customer ) {
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
            'list'  =>  'ns.customers.index',
            'create'    =>  'ns.customers.index/create',
            'edit'      =>  'ns.customers.index/edit/#'
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
                'label'         =>  __( 'Delete Selected Customers' ),
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