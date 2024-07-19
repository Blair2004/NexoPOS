<?php

namespace App\Crud;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Models\TransactionAccount;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\UsersService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class TransactionAccountCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.transactions-accounts';

    /**
     * define the base table
     */
    protected $table = 'nexopos_transactions_accounts';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.transactions-accounts';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.transactions-accounts';

    /**
     * Model Used
     */
    protected $model = TransactionAccount::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users', 'nexopos_transactions_accounts.author', '=', 'nexopos_users.id' ],
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
    public $fillable = [];

    protected $permissions = [
        'create' => 'nexopos.create.transactions-account',
        'read' => 'nexopos.read.transactions-account',
        'update' => 'nexopos.update.transactions-account',
        'delete' => 'nexopos.delete.transactions-account',
    ];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
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
     * @return array of field
     */
    public function getForm( $entry = null )
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __( 'Name' ),
                name: 'name',
                value: $entry->name ?? '',
                description: __( 'Provide a name to the resource.' ),
                validation: 'required',
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: CrudForm::fields(
                        FormInput::select(
                            label: __( 'Operation' ),
                            name: 'operation',
                            description: __( 'All entities attached to this category will either produce a "credit" or "debit" to the cash flow history.' ),
                            validation: 'required',
                            options: Helper::kvToJsOptions( [
                                'credit' => __( 'Credit' ),
                                'debit' => __( 'Debit' ),
                            ] ),
                            value: $entry->operation ?? '',
                        ),
                        FormInput::searchSelect(
                            label: __( 'Counter Account' ),
                            name: 'counter_account_id',
                            options: [],
                            description: __( 'For double bookeeping purpose, which account is affected by all transactions on this account?' ),
                            value: $entry->counter_account_id ?? '',
                        ),
                        FormInput::text(
                            label: __( 'Account' ),
                            name: 'account',
                            description: __( 'Provide the accounting number for this category.' ),
                            value: $entry->account ?? '',
                        ),
                        FormInput::textarea(
                            label: __( 'Description' ),
                            name: 'description',
                            value: $entry->description ?? '',
                        )
                    )
                )
            )
        );
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        if ( empty( $inputs[ 'account' ] ) ) {
            $inputs[ 'account' ] = str_pad( TransactionAccount::count() + 1, 5, '0', STR_PAD_LEFT );
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, TransactionAccount $entry )
    {
        if ( empty( $inputs[ 'account' ] ) ) {
            $inputs[ 'account' ] = str_pad( $entry->id, 5, '0', STR_PAD_LEFT );
        }

        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request $request
     * @return void
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
     * @return void
     */
    public function afterPost( $request, TransactionAccount $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param  string
     * @return mixed
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
     * @param Request $request
     * @param  object entry
     * @return void
     */
    public function beforePut( $request, $entry )
    {
        $this->allowedTo( 'update' );

        return $request;
    }

    /**
     * After updating a record
     *
     * @param Request $request
     * @param  object entry
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
        if ( $namespace == 'ns.transactions-accounts' ) {
            $this->allowedTo( 'delete' );
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
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        // you can make changes here
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . 'accounting/accounts' . '/edit/' . $entry->id )
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.transactions-accounts/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ]
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return  false/array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user = app()->make( UsersService::class );
        if ( ! $user->is( [ 'admin', 'supervisor' ] ) ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'You\'re not allowed to do this operation' ),
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof TransactionAccount ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'error' ]++;
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
        return [
            'list' => ns()->url( 'dashboard/' . 'accounting/accounts' ),
            'create' => ns()->url( 'dashboard/' . 'accounting/accounts/create' ),
            'edit' => ns()->url( 'dashboard/' . 'accounting/accounts/edit/' ),
            'post' => ns()->url( 'api/crud/ns.transactions-accounts' ),
            'put' => ns()->url( 'api/crud/ns.transactions-accounts/{id}' ),
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
                ] ),
            ],
        ] );
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
