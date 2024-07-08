<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\TransactionOccurrenceCast;
use App\Casts\TransactionTypeCast;
use App\Casts\YesNoBoolCast;
use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Events\TransactionAfterCreatedEvent;
use App\Events\TransactionAfterUpdatedEvent;
use App\Events\TransactionBeforeCreatedEvent;
use App\Events\TransactionBeforeDeleteEvent;
use App\Events\TransactionBeforeUpdateEvent;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\UsersService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class TransactionCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.transactions';

    /**
     * define the base table
     */
    protected $table = 'nexopos_transactions';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.transactions';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.transactions';

    /**
     * Model Used
     */
    protected $model = Transaction::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users as user', 'nexopos_transactions.author', '=', 'user.id' ],
        [ 'nexopos_transactions_accounts as transactions_accounts', 'transactions_accounts.id', '=', 'nexopos_transactions.account_id' ],
    ];

    protected $pick = [
        'user' => [ 'username' ],
        'transactions_accounts' => [ 'name' ],
    ];

    protected $permissions = [
        'create' => 'nexopos.create.transactions',
        'read' => 'nexopos.read.transactions',
        'update' => 'nexopos.update.transactions',
        'delete' => 'nexopos.delete.transactions',
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

    protected $casts = [
        'recurring' => 'boolean',
        'active' => 'boolean',
        'type' => TransactionTypeCast::class,
        'occurrence' => TransactionOccurrenceCast::class,
        'recurring' => YesNoBoolCast::class,
        'value' => CurrencyCast::class,
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
            'list_title' => __( 'Transactions List' ),
            'list_description' => __( 'Display all transactions.' ),
            'no_entry' => __( 'No transactions has been registered' ),
            'create_new' => __( 'Add a new transaction' ),
            'create_title' => __( 'Create a new transaction' ),
            'create_description' => __( 'Register a new transaction and save it.' ),
            'edit_title' => __( 'Edit transaction' ),
            'edit_description' => __( 'Modify  Transaction.' ),
            'back_to_list' => __( 'Return to Transactions' ),
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
                        FormInput::switch(
                            label: __( 'Active' ),
                            name: 'active',
                            description: __( 'determine if the transaction is effective or not. Work for recurring and not recurring transactions.' ),
                            validation: 'required',
                            value: $entry->active ?? '',
                            options: Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
                        ),
                        FormInput::select(
                            label: __( 'Users Group' ),
                            name: 'group_id',
                            value: $entry->group_id ?? '',
                            description: __( 'Assign transaction to users group. the Transaction will therefore be multiplied by the number of entity.' ),
                            options: [
                                [
                                    'label' => __( 'None' ),
                                    'value' => '0',
                                ],
                                ...Helper::toJsOptions( Role::get(), [ 'id', 'name' ] ),
                            ],
                        ),
                        FormInput::searchSelect(
                            label: __( 'Transaction Account' ),
                            name: 'account_id',
                            description: __( 'Assign the transaction to an account430' ),
                            validation: 'required',
                            props: TransactionAccountCrud::getFormConfig(),
                            component: 'nsCrudForm',
                            value: $entry->account_id ?? '',
                            options: Helper::toJsOptions( TransactionAccount::get(), [ 'id', 'name' ] ),
                        ),
                        FormInput::text(
                            label: __( 'Value' ),
                            name: 'value',
                            description: __( 'Is the value or the cost of the transaction.' ),
                            validation: 'required',
                            value: $entry->value ?? '',
                        ),
                        FormInput::switch(
                            label: __( 'Recurring' ),
                            name: 'recurring',
                            description: __( 'If set to Yes, the transaction will trigger on defined occurrence.' ),
                            validation: 'required',
                            value: $entry->recurring ?? '',
                            options: [
                                [
                                    'label' => __( 'Yes' ),
                                    'value' => true,
                                ], [
                                    'label' => __( 'No' ),
                                    'value' => false,
                                ],
                            ],
                        ),
                        FormInput::select(
                            label: __( 'Occurrence' ),
                            name: 'occurrence',
                            description: __( 'Define how often this transaction occurs' ),
                            value: $entry->occurrence ?? '',
                            options: [
                                [
                                    'label' => __( 'Start of Month' ),
                                    'value' => 'month_starts',
                                ], [
                                    'label' => __( 'Mid of Month' ),
                                    'value' => 'month_mids',
                                ], [
                                    'label' => __( 'End of Month' ),
                                    'value' => 'month_ends',
                                ], [
                                    'label' => __( 'X days Before Month Ends' ),
                                    'value' => 'x_before_month_ends',
                                ], [
                                    'label' => __( 'X days After Month Starts' ),
                                    'value' => 'x_after_month_starts',
                                ],
                            ],
                        ),
                        FormInput::text(
                            label: __( 'Occurrence Value' ),
                            name: 'occurrence_value',
                            description: __( 'Must be used in case of X days after month starts and X days before month ends.' ),
                            value: $entry->occurrence_value ?? '',
                        ),
                        FormInput::datetime(
                            label: __( 'Scheduled' ),
                            name: 'scheduled_date',
                            description: __( 'Set the scheduled date.' ),
                            value: $entry->scheduled_date ?? '',
                        ),
                        FormInput::select(
                            label: __( 'Type' ),
                            name: 'type',
                            options: [],
                            description: __( 'Define what is the type of the transactions.' ),
                            value: $entry->type ?? '',
                        ),
                        FormInput::textarea(
                            label: __( 'Description' ),
                            name: 'description',
                            value: $entry->description ?? '',
                        ),
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
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, Transaction $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function beforePost( $inputs )
    {
        $this->allowedTo( 'create' );

        TransactionBeforeCreatedEvent::dispatch( $inputs );

        return $inputs;
    }

    /**
     * After saving a record
     */
    public function afterPost( array $inputs, Transaction $entry ): array
    {
        TransactionAfterCreatedEvent::dispatch( $entry, $inputs );

        return $inputs;
    }

    public function hook( $query ): void
    {
        $query->orderBy( 'id', 'desc' );
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

        TransactionBeforeUpdateEvent::dispatch( $entry, $request );

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
        TransactionAfterUpdatedEvent::dispatch( $entry, $request );

        return $request;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.transactions' ) {
            $this->allowedTo( 'delete' );

            /**
             * Delete all transaction history
             */
            $model->histories()->delete();

            TransactionBeforeDeleteEvent::dispatch( $model );
        }
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( __( 'Name' ), 'name' ),
            CrudTable::column( __( 'Type' ), 'type' ),
            CrudTable::column( __( 'Account Name' ), 'transactions_accounts_name' ),
            CrudTable::column( __( 'Value' ), 'value' ),
            CrudTable::column( __( 'Recurring' ), 'recurring' ),
            CrudTable::column( __( 'Occurrence' ), 'occurrence' ),
            CrudTable::column( __( 'Author' ), 'user_username' ),
            CrudTable::column( __( 'Created At' ), 'created_at' ),
        );
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
            url: ns()->url( '/dashboard/' . 'accounting/transactions' . '/edit/' . $entry->id ),
            type: 'GOTO',
        );

        $entry->action(
            identifier: 'history',
            label: __( 'History' ),
            url: ns()->url( '/dashboard/' . 'accounting/transactions' . '/history/' . $entry->id ),
            type: 'GOTO'
        );

        $entry->action(
            identifier: 'trigger',
            label: __( 'Trigger' ),
            url: ns()->url( '/api/transactions/trigger/' . $entry->id ),
            type: 'GET',
            confirm: [
                'message' => __( 'Would you like to trigger this expense now?' ),
            ],
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            url: ns()->url( '/api/crud/ns.transactions/' . $entry->id ),
            type: 'DELETE',
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ],
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return false|bool
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
                if ( $entity instanceof Transaction ) {
                    $entity->histories()->delete();
                    TransactionBeforeDeleteEvent::dispatch( $entity );
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
        return CrudTable::links(
            list: ns()->url( 'dashboard/' . 'accounting/transactions' ),
            create: ns()->url( 'dashboard/' . 'accounting/transactions/create' ),
            edit: ns()->url( 'dashboard/' . 'accounting/transactions/edit/{id}' ),
            post: ns()->url( 'api/crud/' . 'ns.transactions' ),
            put: ns()->url( 'api/crud/' . 'ns.transactions/' . '{id}' ),
        );
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
