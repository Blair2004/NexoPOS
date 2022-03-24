<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Role;
use App\Services\Helper;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RolesCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_roles';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.roles';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.roles';

    /**
     * Model Used
     */
    protected $model      =   Role::class;

    /**
     * Adding relation
     */
    public $relations   =  [
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
    public $pick        =   [];

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

    protected $permissions = [
        'create' => 'create.roles',
        'read' => 'read.roles',
        'update' => 'update.roles',
        'delete' => 'delete.roles',
    ];

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
            'list_title'            =>  __( 'Roles List' ),
            'list_description'      =>  __( 'Display all roles.' ),
            'no_entry'              =>  __( 'No role has been registered.' ),
            'create_new'            =>  __( 'Add a new role' ),
            'create_title'          =>  __( 'Create a new role' ),
            'create_description'    =>  __( 'Create a new role and save it.' ),
            'edit_title'            =>  __( 'Edit role' ),
            'edit_description'      =>  __( 'Modify  Role.' ),
            'back_to_list'          =>  __( 'Return to Roles' ),
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
                'description'   =>  __( 'Provide a name to the role.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'namespace',
                            'label' =>  __( 'Namespace' ),
                            'validation'    =>  $entry === null ? 'required|unique:nexopos_roles,namespace' : [
                                'required',
                                Rule::unique( 'nexopos_roles', 'namespace' )->ignore( $entry->id )
                            ],
                            'description'   =>  __( 'Should be a unique value with no spaces or special character' ),
                            'value' =>  $entry->namespace ?? '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'dashid',
                            'label' =>  __( 'Dashboard Identifier' ),
                            'validation'    =>  'required',
                            'options'       =>  Helper::kvToJsOptions( Hook::filter( 'ns-dashboard-identifiers', [
                                'store'     =>  __( 'Store Dashboard' ),
                                'cashier'   =>  __( 'Cashier Dashboard' ),
                                'default'   =>  __( 'Default Dashboard' ),
                            ])),
                            'description'   =>  __( 'Define what should be the home page of the dashboard.' ),
                            'value' =>  $entry->dashid ?? '',
                        ], [
                            'type'          =>  'textarea',
                            'name'          =>  'description',
                            'label'         =>  __( 'Description' ),
                            'description'   =>  __( 'Provide more details about what this role is about.' ),
                            'value'         =>  $entry->description ?? '',
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
        /**
         * the default role namespace can't be changed.
         */
        if ( ! in_array( $inputs[ 'namespace' ], [
            Role::ADMIN,
            Role::STOREADMIN,
            Role::STORECASHIER,
            Role::USER
        ] )) {
            $inputs[ 'namespace' ]  =   Str::replace( ' ', '-', $inputs[ 'namespace' ] );
        }

        $inputs[ 'locked' ]     =   false;
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, Role $entry )
    {
        /**
         * the default role namespace can't be changed.
         */
        if ( ! in_array( $inputs[ 'namespace' ], [
            Role::ADMIN,
            Role::STOREADMIN,
            Role::STORECASHIER,
            Role::USER
        ] )) {
            $inputs[ 'namespace' ]  =   Str::replace( ' ', '-', $inputs[ 'namespace' ] );
        }
        
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
     * @param  Role $entry
     * @return  void
     */
    public function afterPost( $request, Role $entry )
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
        if ( $namespace == 'ns.roles' ) {
            $this->allowedTo( 'delete' );

            if ( $model->locked ) {
                throw new Exception( __( 'Unable to delete a system role.' ) );
            }
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'namespace'  =>  [
                'label'  =>  __( 'Namespace' ),
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
        $entry->locked          =   ( bool ) $entry->locked;
        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'users/roles' . '/edit/' . $entry->id )
            ], [
                'label'         =>      __( 'Clone' ),
                'namespace'     =>      'clone',
                'type'          =>      'GET',
                'confirm'       =>      [
                    'message'   =>  __( 'Would you like to clone this role ?' ),
                ],
                'index'         =>      'id',
                'url'           =>     ns()->url( '/api/nexopos/v4/' . 'users/roles/' . $entry->id . '/clone' )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.roles/' . $entry->id ),
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
        $user   =   app()->make( Users::class );
        if ( ! $user->is([ 'admin', 'supervisor' ]) ) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  __( 'You\'re not allowed to do this operation' )
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            
            ns()->restrict(
                [ 'delete.roles' ],
                __( 'You do not have enough permissions to perform this action.' )
            );

            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );

                /**
                 * make sure system roles can't be deleted
                 */
                if ( $entity instanceof Role ) {
                    if ( $entity->locked ) {
                        $status[ 'failed' ]++;
                    } else {
                        $entity->delete();
                        $status[ 'success' ]++;
                    }
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
            'list'      =>  ns()->url( 'dashboard/' . 'users/roles' ),
            'create'    =>  ns()->url( 'dashboard/' . 'users/roles/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'users/roles/edit/{id}' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.roles' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.roles/{id}' . '' ),
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