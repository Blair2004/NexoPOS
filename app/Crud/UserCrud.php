<?php
namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Models\User;
use App\Models\UserRoleRelation;
use App\Services\Helper;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_users';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.users';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.users';

    /**
     * Model Used
     */
    protected $model      =   User::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        'leftJoin' =>  [
            [ 'nexopos_users as author', 'nexopos_users.author', '=', 'author.id' ],
        ],
    ];

    public $pick        =   [
        'author'        =>  [ 'username' ],
        'role'          =>  [ 'name' ]
    ];

    protected $permissions = [
        'create' => 'create.users',
        'read' => 'read.users',
        'update' => 'update.users',
        'delete' => 'delete.users',
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
    public $fillable    =   [ 
        'username',
        'email',
        'password',
        'active',
        'role_id',
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
            'list_title'            =>  __( 'Users List' ),
            'list_description'      =>  __( 'Display all users.' ),
            'no_entry'              =>  __( 'No users has been registered' ),
            'create_new'            =>  __( 'Add a new user' ),
            'create_title'          =>  __( 'Create a new user' ),
            'create_description'    =>  __( 'Register a new user and save it.' ),
            'edit_title'            =>  __( 'Edit user' ),
            'edit_description'      =>  __( 'Modify  User.' ),
            'back_to_list'          =>  __( 'Return to Users' ),
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
                'label'         =>  __( 'Username' ),
                'name'          =>  'username',
                'value'         =>  $entry->username ?? '',
                'validation'    =>  $entry === null ? 'required|unique:nexopos_users,username' : [
                    'required',
                    Rule::unique( 'nexopos_users', 'username' )->ignore( $entry->id )
                ],
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'          =>  'text',
                            'name'          =>  'email',
                            'label'         =>  __( 'Email' ),
                            'validation'    =>  $entry === null ? 'required|email|unique:nexopos_users,email' : [
                                'required',
                                'email',
                                Rule::unique( 'nexopos_users', 'email' )->ignore( $entry->id )
                            ],
                            'description'   =>  __( 'Will be used for various purposes such as email recovery.' ),
                            'value'         =>  $entry->email ?? '',
                        ], [
                            'type'          =>  'password',
                            'name'          =>  'password',
                            'label'         =>  __( 'Password' ),
                            'validation'    =>  'sometimes|min:6',
                            'description'   =>  __( 'Make a unique and secure password.' ),
                        ], [
                            'type'          =>  'password',
                            'name'          =>  'password_confirm',
                            'validation'    =>  'sometimes|same:general.password',
                            'label'         =>  __( 'Confirm Password' ),
                            'description'   =>  __( 'Should be the same as the password.' ),
                        ], [
                            'type'          =>  'switch',
                            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                            'name'          =>  'active',
                            'label'         =>  __( 'Active' ),
                            'description'   =>  __( 'Define wether the user can use the application.' ),
                            'value'         =>  ( $entry !== null && $entry->active ? 1 : 0 ) ?? 0,
                        ], [
                            'type'          =>  'multiselect',
                            'options'       =>  Helper::toJsOptions( Role::get(), [ 'id', 'name' ] ),
                            'description'   =>  __( 'Define what roles applies to the user' ),
                            'name'          =>  'roles',
                            'label'         =>  __( 'Roles' ),
                            'value'         =>  $entry !== null ? ( $entry->roles()->get()->map( fn( $role ) => $role->id )->toArray() ?? '' ) : [],
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
        unset( $inputs[ 'roles' ] );

        if ( ! empty( $inputs[ 'password' ] ) ) {
            $inputs[ 'password' ]   =   Hash::make( $inputs[ 'password' ] );
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, User $entry )
    {
        unset( $inputs[ 'roles' ] );
        
        /**
         * if the password is not changed, no
         * need to hash it
         */
        $inputs  =   collect( $inputs )->filter( fn( $input ) => ! empty( $input ) || $input === 0 )->toArray();

        if ( ! empty( $inputs[ 'password' ] ) ) {
            $inputs[ 'password' ]   =   Hash::make( $inputs[ 'password' ] );
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
     * @param  User $entry
     * @return  void
     */
    public function afterPost( $request, User $entry )
    {
        if ( isset( $request[ 'roles'] ) ) {
            
            UserRoleRelation::where( 'user_id', $entry->id )->delete();
            
            foreach( $request[ 'roles' ] as $role ) {
                $role   =   Role::find( $role );

                if ( $role instanceof Role ) {
                    $relation           =   new UserRoleRelation;
                    $relation->user_id  =   $entry->id;
                    $relation->role_id  =   $role->id;
                    $relation->save();
                }
            }
        }

        

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
        if ( isset( $request[ 'roles'] ) ) {
            
            UserRoleRelation::where( 'user_id', $entry->id )->delete();
            
            foreach( $request[ 'roles' ] as $role ) {
                $role   =   Role::find( $role );

                if ( $role instanceof Role ) {
                    $relation           =   new UserRoleRelation;
                    $relation->user_id  =   $entry->id;
                    $relation->role_id  =   $role->id;
                    $relation->save();
                }
            }
        }

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
        if ( $namespace == 'ns.users' ) {
            $this->allowedTo( 'delete' );

            if ( $id === Auth::id() ) {
                throw new NotAllowedException( __( 'You cannot delete your own account.' ) );
            }
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'username'  =>  [
                'label'         =>  __( 'Username' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'active'  =>  [
                'label'         =>  __( 'Active' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'email'  =>  [
                'label'         =>  __( 'Email' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'rolesNames'  =>  [
                'label'         =>  __( 'Roles' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'         =>  __( 'Created At' ),
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

        $entry->active          =   ( bool ) $entry->active ? __( 'Yes' ) : __( 'No' );
        $roles                  =   User::find( $entry->id )->roles()->get();
        $entry->rolesNames      =   $roles->map( fn( $role ) => $role->name )->join( ', ' ) ?: __( 'Not Assigned' );

        // you can make changes here
        $entry->{'$actions'}    =   Hook::filter( 'ns-users-actions', [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'users' . '/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.users/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                ]
            ]
        ], $entry );

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

            /**
             * @temp
             */
            if ( Auth::user()->role->namespace !== 'admin' ) {
                throw new Exception( __( 'Access Denied' ) );
            }

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof User ) {
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
            'list'      =>  ns()->url( 'dashboard/' . 'users' ),
            'create'    =>  ns()->url( 'dashboard/' . 'users/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'users/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.users' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.users/{id}' . '' ),
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