<?php
namespace App\Crud;

use App\Events\ExpenseHistoryBeforeDeleteEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Exceptions\NotAllowedException;
use App\Models\Expense;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\ExpenseHistory;

class ExpenseHistoryCrud extends CrudService
{
    /**
     * define the base table
     * @param  string
     */
    protected $table      =   'nexopos_expenses_history';

    /**
     * default identifier
     * @param  string
     */
    protected $identifier   =   'expenses/history';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.expenses-history';

    /**
     * Model Used
     * @param  string
     */
    protected $model      =   ExpenseHistory::class;

    /**
     * Define permissions
     * @param  array
     */
    protected $permissions  =   [
        'create'    =>  false,
        'read'      =>  'nexopos.read.expenses-history',
        'update'    =>  false,
        'delete'    =>  'nexopos.delete.expenses-history',
    ];

    /**
     * Adding relation
     * @param  array
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'nexopos_expenses_history.author', '=', 'user.id' ]
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
        'user'  =>  [ 'username', 'id' ]
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
            'list_title'            =>  __( 'Expenses History List' ),
            'list_description'      =>  __( 'Display all Expenses History.' ),
            'no_entry'              =>  __( 'No Expense History has been registered' ),
            'create_new'            =>  __( 'Add a new Expense history' ),
            'create_title'          =>  __( 'Create a new Expense History' ),
            'create_description'    =>  __( 'Register a new Expense History and save it.' ),
            'edit_title'            =>  __( 'Edit Expense History' ),
            'edit_description'      =>  __( 'Modify  Expense History.' ),
            'back_to_list'          =>  __( 'Return to Expenses Histories' ),
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
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'author',
                            'label' =>  __( 'Author' ),
                            'value' =>  $entry->author ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'created_at',
                            'label' =>  __( 'Created At' ),
                            'value' =>  $entry->created_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'expense_category_name',
                            'label' =>  __( 'Expense Category Name' ),
                            'value' =>  $entry->expense_category_name ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'expense_id',
                            'label' =>  __( 'Expense ID' ),
                            'value' =>  $entry->expense_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'expense_name',
                            'label' =>  __( 'Expense Name' ),
                            'value' =>  $entry->expense_name ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'id',
                            'label' =>  __( 'Id' ),
                            'value' =>  $entry->id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'updated_at',
                            'label' =>  __( 'Updated At' ),
                            'value' =>  $entry->updated_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'value',
                            'label' =>  __( 'Value' ),
                            'value' =>  $entry->value ?? '',
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
    public function filterPutInputs( $inputs, ExpenseHistory $entry )
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

    public function hook( $query )
    {
        $query->orderBy( 'id', 'desc' );
    }

    /**
     * After saving a record
     * @param  Request $request
     * @param  ExpenseHistory $entry
     * @return  void
     */
    public function afterPost( $request, ExpenseHistory $entry )
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
        if ( $namespace == 'ns.expenses-history' ) {
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

            if ( $model->status !== ExpenseHistory::STATUS_ACTIVE ) {
                throw new NotAllowedException( __( 'This expense history does\'nt have a status that allow deletion.' ) );
            }

            event( new ExpenseHistoryBeforeDeleteEvent( ExpenseHistory::find( $model->id ) ) );

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The expense history is about to be deleted.' )
            ];
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'expense_name'  =>  [
                'label'  =>  __( 'Expense Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'expense_category_name'  =>  [
                'label'  =>  __( 'Category Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'value'  =>  [
                'label'  =>  __( 'Value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
                'label'  =>  __( 'By' ),
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

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.expenses-history/' . $entry->id ),
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
                if ( $entity instanceof ExpenseHistory ) {
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
            'list'      => ns()->url( 'dashboard/' . 'expenses/history' ),
            'create'    => ns()->url( 'dashboard/' . 'expenses/history/create' ),
            'edit'      => ns()->url( 'dashboard/' . 'expenses/history/edit/' ),
            'post'      => ns()->url( 'api/nexopos/v4/crud/' . 'ns.expenses-history' ),
            'put'       => ns()->url( 'api/nexopos/v4/crud/' . 'ns.expenses-history/{id}' . '' ),
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