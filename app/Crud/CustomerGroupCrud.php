<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Helper;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use App\Models\CustomerGroup;
use App\Models\RewardSystem;
use Exception;

class CustomerGroupCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_customers_groups';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.customers-group.index';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers-group';

    /**
     * Model Used
     */
    protected $model      =   CustomerGroup::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_users', 'nexopos_customers_groups.author', '=', 'nexopos_users.id' ],
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

    protected $permissions = [
        'create' => 'nexopos.create.customers-groups',
        'read' => 'nexopos.read.customers-groups',
        'update' => 'nexopos.update.customers-groups',
        'delete' => 'nexopos.delete.customers-groups',
    ];

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'CustomerGroups List' ),
            'list_description'      =>  __( 'Display all customergroups.' ),
            'no_entry'              =>  __( 'No customergroups has been registered' ),
            'create_new'            =>  __( 'Add a new customergroup' ),
            'create_title'          =>  __( 'Create a new customergroup' ),
            'create_description'    =>  __( 'Register a new customergroup and save it.' ),
            'edit_title'            =>  __( 'Edit customergroup' ),
            'edit_description'      =>  __( 'Modify  Customergroup.' ),
            'back_to_list'          =>  __( 'Return to CustomerGroups' ),
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
                'description'   =>  __( 'Provide a name to the resource.' ),
                'validation'    =>  'required'
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'          =>  'select',
                            'name'          =>  'reward_system_id',
                            'label'         =>  __( 'Reward System' ),
                            'options'       =>  Helper::toJsOptions(
                                RewardSystem::get(), [ 'id', 'name' ]
                            ),
                            'value'         =>  $entry->reward_system_id ?? '',
                            'description'   =>  __( 'Select which Reward system applies to the group' )
                        ], [
                            'type'          =>  'number',
                            'name'          =>  'minimal_credit_payment',
                            'label'         =>  __( 'Minimum Credit Amount' ),
                            'value'         =>  $entry->minimal_credit_payment ?? '',
                            'description'   =>  __( 'Determine in percentage, what is the first minimum credit payment made by all customers on the group, in case of credit order.' )
                        ], [
                            'type'          =>  'textarea',
                            'name'          =>  'description',
                            'value'         =>  $entry->description ?? '',
                            'description'   =>  __( 'A brief description about what this group is about' ),
                            'label'         =>  __( 'Description' )
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
        $inputs[ 'minimal_credit_payment' ]   =   $inputs[ 'minimal_credit_payment' ] === null ? 0 : $inputs[ 'minimal_credit_payment' ];
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, CustomerGroup $entry )
    {
        $inputs[ 'minimal_credit_payment' ]   =   $inputs[ 'minimal_credit_payment' ] === null ? 0 : $inputs[ 'minimal_credit_payment' ];
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

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id ) {
        if ( $namespace == 'ns.customers-group' ) {
            $this->allowedTo( 'delete' );
        }
    }
    /**
     * Before Delete
     * @return  void
     */
    public function beforePost( $request ) {
        $this->allowedTo( 'create' );
    }
    /**
     * Before Delete
     * @return  void
     */
    public function beforePut( $request, $id ) {
        $this->allowedTo( 'delete' );
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
                '$sort'         =>  false,
            ],
            'reward_system_id'  =>  [
                'label'  =>  __( 'Reward System' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'nexopos_users_username'  =>  [
                'label'  =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created On' ),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        $entry->reward_system_id   =    $entry->reward_system_id === 0 ? __( 'N/A' ) : $entry->reward_system_id;
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit_customers_group',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( 'dashboard/customers/groups/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'index'     =>  'id',
                'url'       => ns()->url( '/api/nexopos/v4/customers/groups/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                    'title'     =>  __( 'Delete a licence' )
                ]
            ]
        ];
        $entry->{ '$checked' }  =   false;
        $entry->{ '$toggled' }  =   false;
        $entry->{ '$id' }       =   $entry->id;

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
        $user   =   app()->make( 'App\Services\Users' );

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
                if ( $entity instanceof CustomerGroup ) {
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
            'list'  =>  'ns.customers-group.index',
            'create'    =>  'ns.customers-group.index/create',
            'edit'      =>  'ns.customers-group.index/edit/#'
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