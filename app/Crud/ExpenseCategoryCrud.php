<?php

namespace App\Crud;

use App\Models\AccountType;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Users;
use Exception;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ExpenseCategoryCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table = 'nexopos_expenses_categories';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.accounting-accounts';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.accounting-accounts';

    /**
     * Model Used
     */
    protected $model = AccountType::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users', 'nexopos_expenses_categories.author', '=', 'nexopos_users.id' ],
    ];

    /**
     * Define where statement
     *
     * @var  array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var  array
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

    protected $permissions = [
        'create' => 'nexopos.create.expenses-categories',
        'read' => 'nexopos.read.expenses-categories',
        'update' => 'nexopos.update.expenses-categories',
        'delete' => 'nexopos.delete.expenses-categories',
    ];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return  array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Accounts List' ),
            'list_description' => __( 'Display All Accounts.' ),
            'no_entry' => __( 'No Account has been registered' ),
            'create_new' => __( 'Add a new Account' ),
            'create_title' => __( 'Create a new Account' ),
            'create_description' => __( 'Register a new Account and save it.' ),
            'edit_title' => __( 'Edit Account' ),
            'edit_description' => __( 'Modify An Account.' ),
            'back_to_list' => __( 'Return to Accounts' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     *
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Fields
     *
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null )
    {
        return [
            'main' => [
                'label' => __( 'Name' ),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'description' => __( 'Provide a name to the resource.' ),
                'validation' => 'required',
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'select',
                            'name' => 'operation',
                            'label' => __( 'Operation' ),
                            'description' => __( 'All entities attached to this category will either produce a "credit" or "debit" to the cash flow history.' ),
                            'validation' => 'required',
                            'options' => Helper::kvToJsOptions([
                                'credit' => __( 'Credit' ),
                                'debit' => __( 'Debit' ),
                            ]),
                            'value' => $entry->operation ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'account',
                            'label' => __( 'Account' ),
                            'description' => __( 'Provide the accounting number for this category.' ),
                            'value' => $entry->account ?? '',
                            'validation' => 'required',
                        ], [
                            'type' => 'textarea',
                            'name' => 'description',
                            'label' => __( 'Description' ),
                            'value' => $entry->description ?? '',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, AccountType $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
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
     *
     * @param  Request $request
     * @return  void
     */
    public function afterPost( $request, AccountType $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param  string
     * @return  mixed
     */
    public function get( $param )
    {
        switch ( $param ) {
            case 'model': return $this->model;
                break;
        }
    }

    /**
     * Before updating a record
     *
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
     *
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
     *
     * @param  array { namespace, id, type }
     * @return  array | throw Exception
     **/
    public function canAccess( $fields )
    {
        $users = app()->make( Users::class );

        if ( $users->is([ 'admin' ]) ) {
            return [
                'status' => 'success',
                'message' => __( 'The access is granted.' ),
            ];
        }

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.accounting-accounts' ) {
            $this->allowedTo( 'delete' );
        }
    }

    /**
     * Define Columns
     *
     * @return  array of columns configuration
     */
    public function getColumns()
    {
        return [
            'name' => [
                'label' => __( 'Name' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'account' => [
                'label' => __( 'Account' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'operation' => [
                'label' => __( 'Operation' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'nexopos_users_username' => [
                'label' => __( 'Author' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'Created At' ),
                '$direction' => '',
                '$sort' => false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry, $namespace )
    {
        // you can make changes here
        $entry->addAction( 'edit', [
            'label' => __( 'Edit' ),
            'namespace' => 'edit',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'accounting/accounts' . '/edit/' . $entry->id ),
        ]);

        $entry->addAction( 'delete', [
            'label' => __( 'Delete' ),
            'namespace' => 'delete',
            'type' => 'DELETE',
            'url' => ns()->url( '/api/nexopos/v4/crud/ns.accounting-accounts/' . $entry->id ),
            'confirm' => [
                'message' => __( 'Would you like to delete this ?' ),
            ],
        ]);

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user = app()->make( Users::class );
        if ( ! $user->is([ 'admin', 'supervisor' ]) ) {
            return response()->json([
                'status' => 'failed',
                'message' => __( 'You\'re not allowed to do this operation' ),
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status = [
                'success' => 0,
                'failed' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof AccountType ) {
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
     * @return  array of links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url( 'dashboard/' . 'accounting/accounts' ),
            'create' => ns()->url( 'dashboard/' . 'accounting/accounts/create' ),
            'edit' => ns()->url( 'dashboard/' . 'accounting/accounts/edit/' ),
            'post' => ns()->url( 'api/nexopos/v4/crud/ns.accounting-accounts' ),
            'put' => ns()->url( 'api/nexopos/v4/crud/ns.accounting-accounts/{id}' ),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return  array of actions
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
     * @return  array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
