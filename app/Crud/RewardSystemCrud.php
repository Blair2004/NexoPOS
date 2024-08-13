<?php

namespace App\Crud;

use App\Models\Coupon;
use App\Models\RewardSystem;
use App\Models\RewardSystemRule;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TorMorten\Eventy\Facades\Events as Hook;

class RewardSystemCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.rewards-system';

    /**
     * define the base table
     */
    protected $table = 'nexopos_rewards_system';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.rewards-system';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.rewards-system';

    protected $slug = 'customers/{customer_id}/rewards';

    /**
     * Model Used
     */
    protected $model = RewardSystem::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users', 'nexopos_rewards_system.author', '=', 'nexopos_users.id' ],
        [ 'nexopos_coupons as coupon', 'coupon.id', '=', 'nexopos_rewards_system.coupon_id' ],
    ];

    public $pick = [
        'nexopos_users' => [ 'username' ],
        'coupon' => [ 'name' ],
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

    public $skippable = [ 'rules' ];

    protected $permissions = [
        'create' => 'nexopos.create.rewards',
        'read' => 'nexopos.read.rewards',
        'update' => 'nexopos.update.rewards',
        'delete' => 'nexopos.delete.rewards',
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
            'list_title' => __( 'Reward Systems List' ),
            'list_description' => __( 'Display all reward systems.' ),
            'no_entry' => __( 'No reward systems has been registered' ),
            'create_new' => __( 'Add a new reward system' ),
            'create_title' => __( 'Create a new reward system' ),
            'create_description' => __( 'Register a new reward system and save it.' ),
            'edit_title' => __( 'Edit reward system' ),
            'edit_description' => __( 'Modify  Reward System.' ),
            'back_to_list' => __( 'Return to Reward Systems' ),
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
        $ruleForm = [
            [
                'name' => 'id',
                'type' => 'hidden',
            ], [
                'label' => __( 'From' ),
                'name' => 'from',
                'description' => __( 'The interval start here.' ),
                'type' => 'number',
            ], [
                'label' => __( 'To' ),
                'name' => 'to',
                'description' => __( 'The interval ends here.' ),
                'type' => 'number',
            ], [
                'label' => __( 'Points' ),
                'name' => 'reward',
                'description' => __( 'Points earned.' ),
                'type' => 'number',
            ],
        ];

        return [
            'main' => [
                'label' => __( 'Name' ),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'validation' => 'required',
                'description' => __( 'Provide a name to the resource.' ),
            ],

            /**
             * this is made to restore rules
             * by populating the form used for the rules
             */
            'rules' => $entry ? ( collect( $entry->rules )->map( function ( $rule ) use ( $ruleForm ) {
                return collect( $ruleForm )->map( function ( $field ) use ( $rule ) {
                    $field[ 'value' ] = $rule[ $field[ 'name' ] ] ?? '';

                    return $field;
                } );
            } ) ?? [] ) : [],
            'ruleForm' => $ruleForm,
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'search-select',
                            'name' => 'coupon_id',
                            'component' => 'nsCreateCoupons',
                            'props' => CouponCrud::getFormConfig(),
                            'value' => $entry->coupon_id ?? '',
                            'label' => __( 'Coupon' ),
                            'options' => Helper::toJsOptions( Coupon::get(), [ 'id', 'name' ] ),
                            'validation' => 'required',
                            'description' => __( 'Decide which coupon you would apply to the system.' ),
                        ], [
                            'type' => 'number',
                            'name' => 'target',
                            'validation' => 'required',
                            'value' => $entry->target ?? '',
                            'label' => __( 'Target' ),
                            'description' => __( 'This is the objective that the user should reach to trigger the reward.' ),
                        ], [
                            'type' => 'textarea',
                            'name' => 'description',
                            'value' => $entry->description ?? '',
                            'label' => __( 'Description' ),
                            'description' => __( 'A short description about this system' ),
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
    public function filterPutInputs( $inputs, RewardSystem $entry )
    {
        return $inputs;
    }

    /**
     * After Crud POST
     *
     * @param  object entry
     * @return void
     */
    public function afterPost( $request, RewardSystem $entry )
    {
        foreach ( $request[ 'rules' ] as $rule ) {
            $newRule = new RewardSystemRule;
            $newRule->from = $rule[ 'from' ];
            $newRule->to = $rule[ 'to' ];
            $newRule->reward = $rule[ 'reward' ];
            $newRule->reward_id = $entry->id;
            $newRule->author = Auth::id();
            $newRule->save();
        }
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
    public function afterPut( $request, $entry )
    {
        $rules = $request[ 'rules' ];

        /**
         * we filter the rules that are posted
         * with their original ID. Those not posted
         * are deleted.
         */
        $ids = collect( $rules )->filter( function ( $rule ) {
            return isset( $rule[ 'id' ] );
        } )->map( function ( $rule ) {
            return $rule[ 'id' ];
        } );

        /**
         * Delete all rules that aren't submitted
         */
        RewardSystemRule::attachedTo( $entry->id )
            ->whereNotIn( 'id', $ids )
            ->delete();

        /**
         * Update old rules
         * create new rules
         */
        foreach ( $rules as $rule ) {
            if ( isset( $rule[ 'id' ] ) ) {
                $existingRule = RewardSystemRule::findOrFail( $rule[ 'id' ] );
                $existingRule->from = $rule[ 'from' ];
                $existingRule->to = $rule[ 'to' ];
                $existingRule->reward = $rule[ 'reward' ];
                $existingRule->author = Auth::id();
                $existingRule->save();
            } else {
                $newRule = new RewardSystemRule;
                $newRule->from = $rule[ 'from' ];
                $newRule->to = $rule[ 'to' ];
                $newRule->reward = $rule[ 'reward' ];
                $newRule->reward_id = $entry->id;
                $newRule->author = Auth::id();
                $newRule->save();
            }
        }
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, $id )
    {
        if ( $namespace == 'ns.rewards_system' ) {
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
    public function beforePut( $request, $rewardSystem )
    {
        $this->allowedTo( 'update' );
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
            'target' => [
                'label' => __( 'Target' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'coupon_name' => [
                'label' => __( 'Coupon' ),
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
        $entry->name = $entry->name . ' (' . RewardSystem::find( $entry->id )->rules()->count() . ')';

        // you can make changes here
        $entry->action(
            identifier: 'edit.rewards',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/customers/rewards-system/edit/' . $entry->id )
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.rewards-system/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this reward system ?' ),
                'title' => __( 'Delete a licence' ),
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
                if ( $entity instanceof RewardSystem ) {
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
            'list' => ns()->url( '/dashboard/customers/rewards-system' ),
            'create' => ns()->url( '/dashboard/customers/rewards-system/create' ),
            'edit' => ns()->url( '/dashboard/customers/rewards-system/edit/{id}' ),
            'post' => ns()->url( '/api/crud/' . $this->getMainRoute() ),
            'put' => ns()->url( '/api/crud/' . $this->getMainRoute() . '/{id}' ),
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
                'label' => __( 'Delete Selected Rewards' ),
                'confirm' => __( 'Would you like to delete selected rewards?' ),
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
