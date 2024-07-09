<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Classes\CrudTable;
use App\Exceptions\NotAllowedException;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class TransactionsHistoryCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.transactions-history';

    /**
     * define the base table
     *
     * @param string
     */
    protected $table = 'nexopos_transactions_histories';

    /**
     * default slug
     *
     * @param string
     */
    protected $slug = 'accounting/transactions/histories';

    /**
     * Define namespace
     *
     * @param string
     */
    protected $namespace = 'ns.transactions-history';

    /**
     * Model Used
     *
     * @param string
     */
    protected $model = TransactionHistory::class;

    /**
     * Define permissions
     *
     * @param array
     */
    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.read.transactions-history',
        'update' => false,
        'delete' => 'nexopos.delete.transactions-history',
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param array
     */
    public $relations = [
        'leftJoin' => [
            [ 'nexopos_transactions as transaction', 'transaction.id', '=', 'nexopos_transactions_histories.transaction_id' ],
        ],
        [ 'nexopos_users as users', 'users.id', '=', 'nexopos_transactions_histories.author' ],
        [ 'nexopos_transactions_accounts as transactions_accounts', 'transactions_accounts.id', '=', 'nexopos_transactions_histories.transaction_account_id' ],
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
     * Restrict columns you retrieve from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick = [
        'transactions_accounts' => [ 'name' ],
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

    protected $casts = [
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
        return CrudTable::labels(
            list_title: __( 'Transactions History List' ),
            list_description: __( 'Display all transaction history.' ),
            no_entry: __( 'No transaction history has been registered' ),
            create_new: __( 'Add a new transaction history' ),
            create_title: __( 'Create a new transaction history' ),
            create_description: __( 'Register a new transaction history and save it.' ),
            edit_title: __( 'Edit transaction history' ),
            edit_description: __( 'Modify  Transactions history.' ),
            back_to_list: __( 'Return to Transactions History' ),
        );
    }

    public function hook( $query ): void
    {
        $query->orderBy( 'updated_at', 'DESC' );

        if ( ! empty( request()->query( 'transaction_id' ) ) ) {
            $query->where( 'transaction_id', request()->query( 'transaction_id' ) );
        }
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
     * @param object/null
     * @return array of field
     */
    public function getForm( $entry = null )
    {
        return [
            // ...
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
    public function filterPutInputs( $inputs, TransactionHistory $entry )
    {
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
     * @param  Request $request
     * @return void
     */
    public function afterPost( $request, TransactionHistory $entry )
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
        switch ( $param ) {
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
        if ( $namespace == 'ns.transactions-history' ) {
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
        return CrudTable::columns(
            CrudTable::column( __( 'Name' ), 'name' ),
            CrudTable::column( __( 'Status' ), 'status' ),
            CrudTable::column( __( 'Account Name' ), 'transactions_accounts_name' ),
            CrudTable::column( __( 'Operation' ), 'operation' ),
            CrudTable::column( __( 'Value' ), 'value' ),
            CrudTable::column( __( 'Author' ), 'users_username' ),
            CrudTable::column( __( 'Triggered On' ), 'trigger_date' ),
        );
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        if ( $entry->status === TransactionHistory::STATUS_PENDING ) {
            $entry->addClass( 'info' );
        }

        $entry->action(
            label: '<i class="mr-2 las la-trash"></i> ' . __( 'Delete' ),
            identifier: 'delete',
            url: ns()->url( 'api/crud/' . self::IDENTIFIER . '/' . $entry->id ),
            type: 'DELETE',
            confirm: [
                'message' => __( 'Are you sure you want to delete this transaction history?' ),
            ]
        );

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
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof TransactionHistory ) {
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
            'list' => ns()->url( 'dashboard/' . 'history' ),
            'create' => ns()->url( 'dashboard/' . 'history/create' ),
            'edit' => ns()->url( 'dashboard/' . 'history/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.transactions-history' ),
            'put' => ns()->url( 'api/crud/' . 'ns.transactions-history/{id}' . '' ),
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
