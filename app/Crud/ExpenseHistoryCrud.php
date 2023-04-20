<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\CashFlow;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ExpenseHistoryCrud extends CrudService
{
    /**
     * define the base table
     *
     * @param string
     */
    protected $table = 'nexopos_cash_flow';

    /**
     * default slug
     *
     * @param string
     */
    protected $slug = 'expenses/history';

    /**
     * Define namespace
     *
     * @param string
     */
    protected $namespace = 'ns.expense-history';

    /**
     * Model Used
     *
     * @param string
     */
    protected $model = CashFlow::class;

    /**
     * Define permissions
     *
     * @param array
     */
    protected $permissions = [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param array
     */
    public $relations = [
        [ 'nexopos_expenses as expense', 'expense.id', '=', 'nexopos_cash_flow.expense_id' ],
        [ 'nexopos_users as users', 'users.id', '=', 'nexopos_cash_flow.author' ],
        [ 'nexopos_expenses_categories as expense_category', 'expense_category.id', '=', 'nexopos_cash_flow.expense_category_id' ],
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations = [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    /**
     * Export Columns defines the columns that
     * should be included on the exported csv file.
     */
    protected $exportColumns = []; // @getColumns will be used by default.

    /**
     * Pick
     * Restrict columns you retreive from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick = [
        'expense' => [ 'name' ],
        'expense_category' => [ 'name' ],
    ];

    /**
     * Define where statement
     *
     * @var array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var array
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */

    /**
     * If few fields should only be filled
     * those should be listed here.
     */
    public $fillable = [];

    /**
     * If fields should be ignored during saving
     * those fields should be listed here
     */
    public $skippable = [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = false;

    protected $showOptions = false;

    /**
     * Define Constructor
     *
     * @param
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'addActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Expense History List' ),
            'list_description' => __( 'Display all expense history.' ),
            'no_entry' => __( 'No expense history has been registered' ),
            'create_new' => __( 'Add a new expense history' ),
            'create_title' => __( 'Create a new expense history' ),
            'create_description' => __( 'Register a new expense history and save it.' ),
            'edit_title' => __( 'Edit expense history' ),
            'edit_description' => __( 'Modify  Expense history.' ),
            'back_to_list' => __( 'Return to Expense History' ),
        ];
    }

    public function hook( $query ): void
    {
        $query->where( 'expense_id', request()->query( 'expense_id' ) );
    }

    /**
     * Check whether a feature is enabled
     *
     * @return bool
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Fields
     *
     * @param object/null
     * @return array of field
     */
    public function getForm( $entry = null )
    {
        return [
            'main' => [
                'label' => __( 'Name' ),
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description' => __( 'Provide a name to the resource.' ),
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'id',
                            'label' => __( 'Id' ),
                            'value' => $entry->id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'expense_category_name',
                            'label' => __( 'Category Name' ),
                            'value' => $entry->expense_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'operation',
                            'label' => __( 'Operation' ),
                            'value' => $entry->operation ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'expense_category_id',
                            'label' => __( 'Expense_category_id' ),
                            'value' => $entry->expense_category_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'procurement_id',
                            'label' => __( 'Procurement_id' ),
                            'value' => $entry->procurement_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'order_refund_id',
                            'label' => __( 'Order_refund_id' ),
                            'value' => $entry->order_refund_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'order_id',
                            'label' => __( 'Order_id' ),
                            'value' => $entry->order_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'register_history_id',
                            'label' => __( 'Register_history_id' ),
                            'value' => $entry->register_history_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'customer_account_history_id',
                            'label' => __( 'Customer_account_history_id' ),
                            'value' => $entry->customer_account_history_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'name',
                            'label' => __( 'Name' ),
                            'value' => $entry->name ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'status',
                            'label' => __( 'Status' ),
                            'value' => $entry->status ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'value',
                            'label' => __( 'Value' ),
                            'value' => $entry->value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'author',
                            'label' => __( 'Author' ),
                            'value' => $entry->author ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'created_at',
                            'label' => __( 'Created_at' ),
                            'value' => $entry->created_at ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'updated_at',
                            'label' => __( 'Updated_at' ),
                            'value' => $entry->updated_at ?? '',
                        ],                     ],
                ],
            ],
        ];
    }

    /**
     * Filter POST input fields
     *
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, CashFlow $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param Request $request
     * @return void
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
     *
     * @param Request $request
     * @param CashFlow $entry
     * @return void
     */
    public function afterPost( $request, CashFlow $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param string
     * @return mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model': return $this->model;
            break;
        }
    }

    /**
     * Before updating a record
     *
     * @param Request $request
     * @param object entry
     * @return void
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
     *
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.expense-history' ) {
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
     */
    public function getColumns(): array
    {
        return [
            'name' => [
                'label' => __( 'Name' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'expense_category_name' => [
                'label' => __( 'Account Name' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'operation' => [
                'label' => __( 'Operation' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'value' => [
                'label' => __( 'Value' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'users_username' => [
                'label' => __( 'Author' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'Created_at' ),
                '$direction' => '',
                '$sort' => false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function addActions( CrudEntry $entry, $namespace )
    {
        $entry->value = (string) ns()->currency->define( $entry->value );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param  object Request with object
     * @return  false/array
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

            $status = [
                'success' => 0,
                'failed' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
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
     *
     * @return array of links
     */
    public function getLinks(): array
    {
        return  [
            'list' => ns()->url( 'dashboard/' . 'history' ),
            'create' => ns()->url( 'dashboard/' . 'history/create' ),
            'edit' => ns()->url( 'dashboard/' . 'history/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.expense-history' ),
            'put' => ns()->url( 'api/crud/' . 'ns.expense-history/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label' => __( 'Delete Selected Groups' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ]);
    }

    /**
     * get exports
     *
     * @return array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
