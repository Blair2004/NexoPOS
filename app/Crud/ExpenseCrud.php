<?php
namespace App\Crud;

use App\Events\ExpenseAfterCreateEvent;
use App\Events\ExpenseAfterUpdateEvent;
use App\Events\ExpenseBeforeCreateEvent;
use App\Events\ExpenseBeforeDeleteEvent;
use App\Events\ExpenseBeforeUpdateEvent;
use App\Models\AccountType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Services\Helper;

class ExpenseCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_expenses';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.expenses';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.expenses';

    /**
     * Model Used
     */
    protected $model      =   Expense::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'nexopos_expenses.author', '=', 'user.id' ],
        [ 'nexopos_expenses_categories as expense_category', 'expense_category.id', '=', 'nexopos_expenses.category_id' ],
    ];

    protected $pick     =   [
        'user'                  =>  [ 'username' ],
        'expense_category'      =>  [ 'name' ],
    ];

    protected $permissions = [
        'create' => 'nexopos.create.expenses',
        'read' => 'nexopos.read.expenses',
        'update' => 'nexopos.update.expenses',
        'delete' => 'nexopos.delete.expenses',
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
            'list_title'            =>  __( 'Expenses List' ),
            'list_description'      =>  __( 'Display all expenses.' ),
            'no_entry'              =>  __( 'No expenses has been registered' ),
            'create_new'            =>  __( 'Add a new expense' ),
            'create_title'          =>  __( 'Create a new expense' ),
            'create_description'    =>  __( 'Register a new expense and save it.' ),
            'edit_title'            =>  __( 'Edit expense' ),
            'edit_description'      =>  __( 'Modify  Expense.' ),
            'back_to_list'          =>  __( 'Return to Expenses' ),
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
                            'type'          =>  'switch',
                            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                            'name'          =>  'active',
                            'label'         =>  __( 'Active' ),
                            'description'   =>  __( 'determine if the expense is effective or not. Work for recurring and not reccuring expenses.' ),
                            'validation'    =>  'required',
                            'value'         =>  $entry->active ?? '',
                        ], [
                            'type'  =>  'datetimepicker',
                            'name'  =>  'created_at',
                            'label' =>  __( 'Created At' ),
                            'value' =>  $entry->created_at ?? '',
                            'description'   =>  __( 'Will set when the expense should be active.' ),
                        ], [
                            'type'          =>  'select',
                            'name'          =>  'group_id',
                            'label'         =>  __( 'Users Group' ),
                            'value'         =>  $entry->group_id ?? '',
                            'description'   =>  __( 'Assign expense to users group. Expense will therefore be multiplied by the number of entity.' ),
                            'options'       =>  [ 
                                [
                                    'label' =>  __( 'None' ),
                                    'value' =>  '0',                                     
                                ], 
                                ...Helper::toJsOptions( Role::get(), [ 'id', 'name' ]) 
                            ],
                        ], [
                            'type'          =>  'select',
                            'options'       =>  Helper::toJsOptions( AccountType::get(), [ 'id', 'name' ]),
                            'name'          =>  'category_id',
                            'label'         =>  __( 'Expense Category' ),
                            'description'   =>  __( 'Assign the expense to a category' ),
                            'validation'    =>  'required',
                            'value'         =>  $entry->category_id ?? '',
                        ], [
                            'type'          =>  'text',
                            'name'          =>  'value',
                            'description'   =>  __( 'Is the value or the cost of the expense.' ),
                            'label'         =>  __( 'Value' ),
                            'value'         =>  $entry->value ?? '',
                        ], [
                            'type'          =>  'switch',
                            'name'          =>  'recurring',
                            'description'   =>  __( 'If set to Yes, the expense will trigger on defined occurence.' ),
                            'label'         =>  __( 'Recurring' ),
                            'options'       =>  [
                                [
                                    'label' =>  __( 'Yes' ),
                                    'value' =>  true
                                ], [
                                    'label' =>  __( 'No' ),
                                    'value' =>  false
                                ]
                            ],
                            'value' =>  $entry->recurring ?? '',
                        ], [
                            'type'          =>  'select',
                            'options'       =>  [
                                [
                                    'label' =>  __( 'Start of Month' ),
                                    'value' =>  'month_starts',
                                ], [
                                    'label' =>  __( 'Mid of Month' ),
                                    'value' =>  'month_mids',
                                ], [
                                    'label' =>  __( 'End of Month' ),
                                    'value' =>  'month_ends',
                                ], [
                                    'label' =>  __( 'X days Before Month Ends' ),
                                    'value' =>  'x_before_month_ends',
                                ], [
                                    'label' =>  __( 'X days After Month Starts' ),
                                    'value' =>  'x_after_month_starts',
                                ]
                            ],
                            'name'          =>  'occurence',
                            'label'         =>  __( 'Occurence' ),
                            'description'   =>  __( 'Define how often this expenses occurs' ),
                            'value'         =>  $entry->occurence ?? '',
                        ], [
                            'type'          =>  'text',
                            'name'          =>  'occurence_value',
                            'label'         =>  __( 'Occurence Value' ),
                            'description'   =>  __( 'Must be used in case of X days after month starts and X days before month ends.' ),
                            'value'         =>  $entry->occurence_value ?? '',
                        ], [
                            'type'  =>  'textarea',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
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
    public function filterPutInputs( $inputs, Expense $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     * @param  Request $request
     * @return  void
     */
    public function beforePost( $inputs )
    {
        $this->allowedTo( 'create' );

        event( new ExpenseBeforeCreateEvent( $inputs ) );

        return $inputs;
    }

    /**
     * After saving a record
     * @param  Request $request
     * @param  Expense $entry
     * @return  void
     */
    public function afterPost( $inputs, Expense $entry )
    {
        event( new ExpenseAfterCreateEvent( $entry, $inputs ) );

        return $inputs;
    }

    public function hook( $query )
    {
        $query->orderBy( 'id', 'desc' );
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

        event( new ExpenseBeforeUpdateEvent( $entry, $request ) );

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
        event( new ExpenseAfterUpdateEvent( $entry, $request ) );

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
        if ( $namespace == 'ns.expenses' ) {
            
            $this->allowedTo( 'delete' );

            event( new ExpenseBeforeDeleteEvent( $model ) );
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
            'expense_category_name'  =>  [
                'label'  =>  __( 'Category' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'value'  =>  [
                'label'  =>  __( 'Value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'recurring'  =>  [
                'label'  =>  __( 'Recurring' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'occurence'  =>  [
                'label'  =>  __( 'Occurence' ),
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

        $entry->value           =   ( string ) ns()->currency->value( $entry->value );
        $entry->recurring       =   ( bool ) $entry->recurring ? __( 'Yes' ) : __( 'No' );

        switch( $entry->occurence ) {
            case 'month_start' : $entry->occurence = __( 'Month Starts' );break;
            case 'month_mid' : $entry->occurence = __( 'Month Middle' );break;
            case 'month_end' : $entry->occurence = __( 'Month Ends' );break;
            case 'x_after_month_starts' : $entry->occurence = __( 'X Days Before Month Starts' );break;
            case 'x_before_month_ends' : $entry->occurence = __( 'X Days Before Month Ends' );break;
            default: $entry->occurence = __( 'Unknown Occurance' ); break;
        }

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'expenses' . '/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.expenses/' . $entry->id ),
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
            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Expense ) {
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
            'list'      => ns()->url( 'dashboard/' . 'expenses' ),
            'create'    => ns()->url( 'dashboard/' . 'expenses/create' ),
            'edit'      => ns()->url( 'dashboard/' . 'expenses/edit/{id}' ),
            'post'      => ns()->url( 'api/nexopos/v4/crud/' . 'ns.expenses' ),
            'put'       => ns()->url( 'api/nexopos/v4/crud/' . 'ns.expenses/' . '{id}' ),
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