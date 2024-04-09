<?php

namespace App\Crud;

use App\Classes\CrudTable;
use App\Models\CustomerGroup;
use App\Models\RewardSystem;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class CustomerGroupCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.customers-groups';

    /**
     * define the base table
     */
    protected $table = 'nexopos_customers_groups';

    /**
     * base route name
     */
    protected $mainRoute = '/dashboard/customers/groups';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.customers-groups';

    /**
     * Model Used
     */
    protected $model = CustomerGroup::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users', 'nexopos_customers_groups.author', '=', 'nexopos_users.id' ],
        'leftJoin' => [
            [ 'nexopos_rewards_system as reward', 'reward.id', '=', 'nexopos_customers_groups.reward_system_id' ],
        ],
    ];

    public $pick = [
        'nexopos_users' => [ 'username' ],
        'reward' => [ 'name' ],
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
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
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
     *
     * @return array
     **/
    public function getLabels()
    {
        return CrudTable::labels(
            list_title: __( 'Customer Groups List' ),
            list_description: __( 'Display all Customers Groups.' ),
            no_entry: __( 'No Customers Groups has been registered' ),
            create_new: __( 'Add a new Customers Group' ),
            create_title: __( 'Create a new Customers Group' ),
            create_description: __( 'Register a new Customers Group and save it.' ),
            edit_title: __( 'Edit Customers Group' ),
            edit_description: __( 'Modify Customers group.' ),
            back_to_list: __( 'Return to Customers Groups' ),
        );
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
                            'name' => 'reward_system_id',
                            'label' => __( 'Reward System' ),
                            'options' => Helper::toJsOptions(
                                RewardSystem::get(), [ 'id', 'name' ]
                            ),
                            'value' => $entry->reward_system_id ?? '',
                            'description' => __( 'Select which Reward system applies to the group' ),
                        ], [
                            'type' => 'number',
                            'name' => 'minimal_credit_payment',
                            'label' => __( 'Minimum Credit Amount' ),
                            'value' => $entry->minimal_credit_payment ?? '',
                            'description' => __( 'Determine in percentage, what is the first minimum credit payment made by all customers on the group, in case of credit order. If left to "0", no minimal credit amount is required.' ),
                        ], [
                            'type' => 'textarea',
                            'name' => 'description',
                            'value' => $entry->description ?? '',
                            'description' => __( 'A brief description about what this group is about' ),
                            'label' => __( 'Description' ),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Let's sort the customers
     *
     * @param Builder $query
     */
    public function hook( $query ): void
    {
        $query->orderBy( 'updated_at', 'desc' );
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        $inputs[ 'minimal_credit_payment' ] = $inputs[ 'minimal_credit_payment' ] === null ? 0 : $inputs[ 'minimal_credit_payment' ];

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, CustomerGroup $entry )
    {
        $inputs[ 'minimal_credit_payment' ] = $inputs[ 'minimal_credit_payment' ] === null ? 0 : $inputs[ 'minimal_credit_payment' ];

        return $inputs;
    }

    /**
     * After Crud POST
     *
     * @param  object entry
     * @return void
     */
    public function afterPost( $inputs )
    {
        return $inputs;
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
     * After Crud PUT
     *
     * @param  object entry
     * @return void
     */
    public function afterPut( $inputs )
    {
        return $inputs;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, $id )
    {
        if ( $namespace == 'ns.customers-groups' ) {
            $this->allowedTo( 'delete' );
        }
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforePost( $request )
    {
        $this->allowedTo( 'create' );
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforePut( $request, $id )
    {
        $this->allowedTo( 'delete' );
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( __( 'Name' ), 'name' ),
            CrudTable::column( __( 'Reward System' ), 'reward_name' ),
            CrudTable::column( __( 'Author' ), 'nexopos_users_username' ),
            CrudTable::column( __( 'Created On' ), 'created_at' )
        );
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->reward_system_id = $entry->reward_system_id === 0 ? __( 'N/A' ) : $entry->reward_system_id;

        $entry->action(
            identifier: 'edit_customers_group',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( 'dashboard/customers/groups/edit/' . $entry->id )
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.customers-groups/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
                'title' => __( 'Delete a licence' ),
            ]
        );

        $entry->reward_name = $entry->reward_name ?: __( 'N/A' );

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
        $user = app()->make( 'App\Services\UsersService' );

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
                if ( $entity instanceof CustomerGroup ) {
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
            'list' => ns()->url( 'dashboard/customers/groups' ),
            'create' => ns()->url( 'dashboard/customers/groups/create' ),
            'edit' => ns()->url( 'dashboard/customers/groups/edit' ),
            'post' => ns()->url( 'api/crud/' . 'ns.customers-groups' ),
            'put' => ns()->url( 'api/crud/' . 'ns.customers-groups/{id}' . '' ),
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
