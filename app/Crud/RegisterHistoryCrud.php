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
use App\Models\RegisterHistory;
use App\Services\CashRegistersService;

class RegisterHistoryCrud extends CrudService
{
    /**
     * define the base table
     * @param  string
     */
    protected $table      =   'nexopos_registers_history';

    /**
     * default slug
     * @param  string
     */
    protected $slug   =   'registers-history';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.registers-hitory';

    /**
     * Model Used
     * @param  string
     */
    protected $model      =   RegisterHistory::class;

    /**
     * Define permissions
     * @param  array
     */
    protected $permissions  =   [
        'create'    =>  false,
        'read'      =>  true,
        'update'    =>  false,
        'delete'    =>  false,
    ];

    /**
     * Adding relation
     * @param  array
     */
    public $relations   =  [
        // [ 'nexopos_registers as register', 'register.id', '=', 'nexopos_registers_history.register_id' ],
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_registers_history.author' ],
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
        // 'register'  =>  [ 'name' ],
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
     * @param CashRegistersService;
     */
    private $registerService;

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );

        $this->registerService      =   app()->make( CashRegistersService::class );
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Register History List' ),
            'list_description'      =>  __( 'Display all register histories.' ),
            'no_entry'              =>  __( 'No register histories has been registered' ),
            'create_new'            =>  __( 'Add a new register history' ),
            'create_title'          =>  __( 'Create a new register history' ),
            'create_description'    =>  __( 'Register a new register history and save it.' ),
            'edit_title'            =>  __( 'Edit register history' ),
            'edit_description'      =>  __( 'Modify  Registerhistory.' ),
            'back_to_list'          =>  __( 'Return to Register History' ),
        ];
    }

    public function hook( $query )
    {
        if ( ! empty( request()->query( 'register_id' ) ) ) {
            $query->where( 'register_id', request()->query( 'register_id' ) );
        }

        $query->orderBy( 'id', 'desc' );
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
                            'name'  =>  'id',
                            'label' =>  __( 'Id' ),
                            'value' =>  $entry->id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'register_id',
                            'label' =>  __( 'Register Id' ),
                            'value' =>  $entry->register_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'action',
                            'label' =>  __( 'Action' ),
                            'value' =>  $entry->action ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'author',
                            'label' =>  __( 'Author' ),
                            'value' =>  $entry->author ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'value',
                            'label' =>  __( 'Value' ),
                            'value' =>  $entry->value ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'uuid',
                            'label' =>  __( 'Uuid' ),
                            'value' =>  $entry->uuid ?? '',
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
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
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
    public function filterPutInputs( $inputs, RegisterHistory $entry )
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
     * @param  RegisterHistory $entry
     * @return  void
     */
    public function afterPost( $request, RegisterHistory $entry )
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
        if ( $namespace == 'ns.registers-hitory' ) {
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
            // 'register_name'  =>  [
            //     'label'         =>  __( 'Register Name' ),
            //     '$direction'    =>  '',
            //     '$sort'         =>  false
            // ],
            'action'  =>  [
                'label'  =>  __( 'Action' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
                'label'  =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'value'  =>  [
                'label'  =>  __( 'Value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Done At' ),
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
        
        switch( $entry->action ) {
            case RegisterHistory::ACTION_SALE: 
                $entry->{ '$cssClass' }    =   'bg-green-100 border-b border-green-200';
            break;
            case RegisterHistory::ACTION_CASHING: 
                $entry->{ '$cssClass' }    =   'bg-green-100 border-b border-green-200';
            break;
            case RegisterHistory::ACTION_OPENING: 
                $entry->{ '$cssClass' }    =   'bg-blue-100 border-b border-blue-200';
            break;
            case RegisterHistory::ACTION_CASHOUT: 
                $entry->{ '$cssClass' }    =   'bg-red-100 border-b border-red-200';
            break;
            case RegisterHistory::ACTION_CASHOUT: 
                $entry->{ '$cssClass' }    =   'bg-red-100 border-b border-red-200';
            break;
            case RegisterHistory::ACTION_CLOSING: 
                $entry->{ '$cssClass' }    =   'bg-orange-100 border-b border-orange-200';
            break;
        }

        $entry->action      =   $this->registerService->getActionLabel( $entry->action );
        $entry->created_at  =   ns()->date->getFormatted( $entry->created_at );
        $entry->value       =   ( string ) ns()->currency->define( $entry->value );

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( '/dashboard/' . '' . '/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       =>  ns()->url( '/api/nexopos/v4/crud/ns.registers-hitory/' . $entry->id ),
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
                if ( $entity instanceof RegisterHistory ) {
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
            'list'      =>  ns()->url( 'dashboard/' . 'registers-history' ),
            'create'    =>  ns()->url( 'dashboard/' . 'registers-history/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'registers-history/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.registers-hitory' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.registers-hitory/{id}' . '' ),
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