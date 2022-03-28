<?php
namespace App\Crud;

use App\Events\CashFlowHistoryBeforeDeleteEvent;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Exceptions\NotAllowedException;
use App\Models\CashFlow;
use TorMorten\Eventy\Facades\Events as Hook;

class CashFlowHistoryCrud extends CrudService
{
    /**
     * define the base table
     * @param  string
     */
    protected $table      =   'nexopos_cash_flow';

    /**
     * default identifier
     * @param  string
     */
    protected $identifier   =   'cash-flow/history';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.cash-flow-history';

    /**
     * Model Used
     * @param  string
     */
    protected $model      =   CashFlow::class;

    /**
     * Define permissions
     * @param  array
     */
    protected $permissions  =   [
        'create'    =>  false, // 'nexopos.create.cash-flow-history',
        'read'      =>  'nexopos.read.cash-flow-history',
        'update'    =>  false,
        'delete'    =>  'nexopos.delete.cash-flow-history',
    ];

    /**
     * Adding relation
     * @param  array
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'nexopos_cash_flow.author', '=', 'user.id' ]
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
            'list_title'            =>  __( 'Cash Flow List' ),
            'list_description'      =>  __( 'Display all Cash Flow.' ),
            'no_entry'              =>  __( 'No Cash Flow has been registered' ),
            'create_new'            =>  __( 'Add a new Cash Flow' ),
            'create_title'          =>  __( 'Create a new Cash Flow' ),
            'create_description'    =>  __( 'Register a new Cash Flow and save it.' ),
            'edit_title'            =>  __( 'Edit Cash Flow' ),
            'edit_description'      =>  __( 'Modify  Cash Flow.' ),
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
                'name'          =>  'name',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
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
                            'name'  =>  'value',
                            'label' =>  __( 'Value' ),
                            'value' =>  $entry->value ?? '',
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
    public function filterPutInputs( $inputs, CashFlow $entry )
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
     * @param  CashFlow $entry
     * @return  void
     */
    public function afterPost( $request, CashFlow $entry )
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
        if ( $namespace == 'ns.cash-flow-history' ) {
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

            if ( $model->status !== CashFlow::STATUS_ACTIVE ) {
                throw new NotAllowedException( __( 'This expense history does\'nt have a status that allow deletion.' ) );
            }

            event( new CashFlowHistoryBeforeDeleteEvent( CashFlow::find( $model->id ) ) );

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
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'value'  =>  [
                'label'  =>  __( 'Value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'operation'  =>  [
                'label'  =>  __( 'Operation' ),
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

        $entry->value           =   ns()->currency->define( $entry->value )->format();
        

        switch( $entry->operation ) {
            case CashFlow::OPERATION_CREDIT : 
                $entry->{ '$cssClass' }             =   'success border text-sm';
            break;
            case CashFlow::OPERATION_DEBIT : 
                $entry->{ '$cssClass' }             =   'error border text-sm';
            break;
        }

        switch( $entry->operation ) {
            case CashFlow::OPERATION_CREDIT : 
                $entry->operation             =   "<span class='bg-green-400 text-white rounded-full px-2 py-1 text-sm'>" . __( 'Credit' ) . '</span>';
            break;
            case CashFlow::OPERATION_DEBIT : 
                $entry->operation             =   "<span class='bg-red-400 text-white rounded-full px-2 py-1 text-sm'>" . __( 'Debit' ) . '</span>';
            break;
        }

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.cash-flow-history/' . $entry->id ),
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
                if ( $entity instanceof CashFlow ) {
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
            'list'      => ns()->url( 'dashboard/' . 'cash-flow/history' ),
            'create'    => ns()->url( 'dashboard/' . 'cash-flow/history/create' ),
            'edit'      => ns()->url( 'dashboard/' . 'cash-flow/history/edit/' ),
            'post'      => ns()->url( 'api/nexopos/v4/crud/' . 'ns.cash-flow-history' ),
            'put'       => ns()->url( 'api/nexopos/v4/crud/' . 'ns.cash-flow-history/{id}' . '' ),
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