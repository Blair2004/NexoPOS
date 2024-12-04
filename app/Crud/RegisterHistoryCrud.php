<?php

namespace App\Crud;

use App\Classes\Output;
use App\Exceptions\NotAllowedException;
use App\Models\RegisterHistory;
use App\Models\User;
use App\Services\CashRegistersService;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class RegisterHistoryCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.cash-registers-history';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_registers_history';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'registers-history';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.cash-registers-history';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = RegisterHistory::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.read.registers-history',
        'update' => false,
        'delete' => false,
    ];

    /**
     * Adding relation
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_registers_history.author' ],
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations = [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

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
        'user' => [ 'username' ],
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

    /**
     * @param CashRegistersService;
     */
    private $registerService;

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->registerService = app()->make( CashRegistersService::class );
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
            'list_title' => __( 'Register History List' ),
            'list_description' => __( 'Display all register histories.' ),
            'no_entry' => __( 'No register histories has been registered' ),
            'create_new' => __( 'Add a new register history' ),
            'create_title' => __( 'Create a new register history' ),
            'create_description' => __( 'Register a new register history and save it.' ),
            'edit_title' => __( 'Edit register history' ),
            'edit_description' => __( 'Modify  Registerhistory.' ),
            'back_to_list' => __( 'Return to Register History' ),
        ];
    }

    public function hook( $query ): void
    {
        if ( ! empty( request()->query( 'register_id' ) ) ) {
            $query->where( 'register_id', request()->query( 'register_id' ) );
        }

        $query->orderBy( 'id', 'desc' );
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
                            'name' => 'register_id',
                            'label' => __( 'Register Id' ),
                            'value' => $entry->register_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'action',
                            'label' => __( 'Action' ),
                            'value' => $entry->action ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'author',
                            'label' => __( 'Author' ),
                            'value' => $entry->author ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'value',
                            'label' => __( 'Value' ),
                            'value' => $entry->value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'uuid',
                            'label' => __( 'Uuid' ),
                            'value' => $entry->uuid ?? '',
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
                        ], [
                            'type' => 'text',
                            'name' => 'description',
                            'label' => __( 'Description' ),
                            'value' => $entry->description ?? '',
                        ],                     ],
                ],
            ],
        ];
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
    public function filterPutInputs( $inputs, RegisterHistory $entry )
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
    public function afterPost( $request, RegisterHistory $entry )
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
     */
    public function getColumns(): array
    {
        return [
            // 'register_name'  =>  [
            //     'label'         =>  __( 'Register Name' ),
            //     '$direction'    =>  '',
            //     '$sort'         =>  false
            // ],
            'action' => [
                'label' => __( 'Action' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __( 'Author' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'balance_before' => [
                'label' => __( 'Initial Balance' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'value' => [
                'label' => __( 'Value' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'balance_after' => [
                'label' => __( 'New Balance' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'transaction_type' => [
                'label' => __( 'Transaction Type' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'Done At' ),
                '$direction' => '',
                '$sort' => false,
            ],
        ];
    }

    public function getTableFooter( Output $output ): Output
    {
        $output->addView( 'pages.dashboard.cash-registers.history.footer' );

        return $output;
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        switch ( $entry->action ) {
            case RegisterHistory::ACTION_ORDER_PAYMENT:
                $entry->{ '$cssClass' } = 'success border';
                break;
            case RegisterHistory::ACTION_CASHING:
                $entry->{ '$cssClass' } = 'success border';
                break;
            case RegisterHistory::ACTION_ACCOUNT_PAY:
                $entry->{ '$cssClass' } = 'success border';
                break;
            case RegisterHistory::ACTION_OPENING:
                $entry->{ '$cssClass' } = 'info border';
                break;
            case RegisterHistory::ACTION_CASHOUT:
                $entry->{ '$cssClass' } = 'warning border';
                break;
            case RegisterHistory::ACTION_ACCOUNT_CHANGE:
                $entry->{ '$cssClass' } = 'warning border';
                break;
            case RegisterHistory::ACTION_ORDER_CHANGE:
                $entry->{ '$cssClass' } = 'warning border';
                break;
        }

        if ( $entry->action === RegisterHistory::ACTION_CLOSING && (float) $entry->balance_after != 0 ) {
            // $entry->{ '$cssClass' } = 'error border';
        }

        if ( $entry->action === RegisterHistory::ACTION_CLOSING && $entry->transaction_type === 'unchanged' ) {
            $entry->{ '$cssClass' } = 'success border';
        } elseif ( $entry->action === RegisterHistory::ACTION_CLOSING && $entry->transaction_type === 'positive' ) {
            $entry->{ '$cssClass' } = 'warning border';
        } elseif ( $entry->action === RegisterHistory::ACTION_CLOSING && $entry->transaction_type === 'negative' ) {
            $entry->{ '$cssClass' } = 'warning border';
        }

        $entry->action(
            label: __( 'Details' ),
            identifier: 'view-details',
            type: 'POPUP'
        );

        $entry->action = $this->registerService->getActionLabel( $entry->action );
        $entry->created_at = ns()->date->getFormatted( $entry->created_at );
        $entry->value = (string) ns()->currency->define( $entry->value );
        $entry->balance_before = (string) ns()->currency->define( $entry->balance_before );
        $entry->balance_after = (string) ns()->currency->define( $entry->balance_after );
        $entry->transaction_type = $this->getHumanTransactionType( $entry->transaction_type );

        return $entry;
    }

    public function getHumanTransactionType( $type )
    {
        switch ( $type ) {
            case 'unchanged': return __( 'Unchanged' );
                break;
            case 'negative': return __( 'Shortage' );
                break;
            case 'positive': return __( 'Overage' );
                break;
            default: return __( 'N/A' );
                break;
        }
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
                if ( $entity instanceof RegisterHistory ) {
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
            'list' => ns()->url( 'dashboard/' . 'registers-history' ),
            'create' => false,
            'edit' => false,
            'post' => false,
            'put' => false,
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
