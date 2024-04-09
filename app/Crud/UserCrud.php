<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\GenderCast;
use App\Casts\NotDefinedCast;
use App\Casts\YesNoBoolCast;
use App\Classes\CrudTable;
use App\Classes\JsonResponse;
use App\Events\UserAfterActivationSuccessfulEvent;
use App\Exceptions\NotAllowedException;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerGroup;
use App\Models\CustomerShippingAddress;
use App\Models\Role;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Options;
use App\Services\UsersService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use TorMorten\Eventy\Facades\Events as Hook;

class UserCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.users';

    /**
     * define the base table
     */
    protected $table = 'nexopos_users';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.users';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.users';

    /**
     * Model Used
     */
    protected $model = User::class;

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = true;

    /**
     * Adding relation
     */
    public $relations = [
        'leftJoin' => [
            [ 'nexopos_customers_groups as group', 'nexopos_users.group_id', '=', 'group.id' ],
            [ 'nexopos_users as author', 'nexopos_users.author', '=', 'author.id' ],
        ],
    ];

    public $pick = [
        'author' => [ 'username' ],
        'role' => [ 'name' ],
        'group' => [ 'id', 'name' ],
    ];

    protected $permissions = [
        'create' => 'create.users',
        'read' => 'read.users',
        'update' => 'update.users',
        'delete' => 'delete.users',
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
    public $fillable = [
        'username',
        'email',
        'password',
        'active',
        'role_id',
        'group_id',
        'first_name',
        'last_name',
        'phone',
        'gender',
        'pobox',
        'credit_limit_amount',
    ];

    protected $tabsRelations = [
        'shipping' => [ CustomerShippingAddress::class, 'customer_id', 'id' ],
        'billing' => [ CustomerBillingAddress::class, 'customer_id', 'id' ],
    ];

    protected $casts = [
        'first_name' => NotDefinedCast::class,
        'last_name' => NotDefinedCast::class,
        'phone' => NotDefinedCast::class,
        'owed_amount' => CurrencyCast::class,
        'account_amount' => CurrencyCast::class,
        'purchases_amount' => CurrencyCast::class,
        'gender' => GenderCast::class,
        'active' => YesNoBoolCast::class,
    ];

    private Options $options;

    private UsersService $userService;

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->userService = app()->make( UsersService::class );
        $this->options = app()->make( Options::class );
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
            'list_title' => __( 'Users List' ),
            'list_description' => __( 'Display all users.' ),
            'no_entry' => __( 'No users has been registered' ),
            'create_new' => __( 'Add a new user' ),
            'create_title' => __( 'Create a new user' ),
            'create_description' => __( 'Register a new user and save it.' ),
            'edit_title' => __( 'Edit user' ),
            'edit_description' => __( 'Modify  User.' ),
            'back_to_list' => __( 'Return to Users' ),
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
        return [
            'main' => [
                'label' => __( 'Username' ),
                'name' => 'username',
                'value' => $entry->username ?? '',
                'validation' => $entry === null ? 'required|unique:nexopos_users,username' : [
                    'required',
                    Rule::unique( 'nexopos_users', 'username' )->ignore( $entry->id ),
                ],
                'description' => __( 'Provide a name to the resource.' ),
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'email',
                            'label' => __( 'Email' ),
                            'validation' => $entry === null ? 'required|email|unique:nexopos_users,email' : [
                                'required',
                                'email',
                                Rule::unique( 'nexopos_users', 'email' )->ignore( $entry->id ),
                            ],
                            'description' => __( 'Will be used for various purposes such as email recovery.' ),
                            'value' => $entry->email ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'first_name',
                            'value' => $entry?->first_name,
                            'label' => __( 'First Name' ),
                            'description' => __( 'Provide the user first name.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'last_name',
                            'value' => $entry?->last_name,
                            'label' => __( 'Last Name' ),
                            'description' => __( 'Provide the user last name.' ),
                        ], [
                            'type' => 'password',
                            'name' => 'password',
                            'label' => __( 'Password' ),
                            'validation' => 'sometimes|min:6',
                            'description' => __( 'Make a unique and secure password.' ),
                        ], [
                            'type' => 'password',
                            'name' => 'password_confirm',
                            'validation' => 'sometimes|same:general.password',
                            'label' => __( 'Confirm Password' ),
                            'description' => __( 'Should be the same as the password.' ),
                        ], [
                            'type' => 'switch',
                            'options' => Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
                            'name' => 'active',
                            'label' => __( 'Active' ),
                            'description' => __( 'Define whether the user can use the application.' ),
                            'value' => ( $entry !== null && $entry->active ? 1 : 0 ) ?? 0,
                        ], [
                            'type' => 'multiselect',
                            'options' => Helper::toJsOptions( Role::get(), [ 'id', 'name' ] ),
                            'description' => __( 'Define what roles applies to the user' ),
                            'name' => 'roles',
                            'label' => __( 'Roles' ),
                            'value' => $entry !== null ? ( $entry->roles()->get()->map( fn( $role ) => $role->id )->toArray() ?? '' ) : [],
                        ], [
                            'type' => 'select',
                            'label' => __( 'Group' ),
                            'name' => 'group_id',
                            'value' => $entry->group_id ?? '',
                            'options' => Helper::toJsOptions( CustomerGroup::all(), [ 'id', 'name' ] ),
                            'description' => __( 'Assign the customer to a group' ),
                        ], [
                            'type' => 'datetimepicker',
                            'label' => __( 'Birth Date' ),
                            'name' => 'birth_date',
                            'value' => $entry instanceof User && $entry->birth_date !== null ? Carbon::parse( $entry->birth_date )->format( 'Y-m-d H:i:s' ) : null,
                            'description' => __( 'Displays the customer birth date' ),
                        ], [
                            'type' => 'text',
                            'name' => 'credit_limit_amount',
                            'value' => $entry?->credit_limit_amount,
                            'label' => __( 'Credit Limit' ),
                            'description' => __( 'Set the limit that can\'t be exceeded by the user.' ),
                        ], [
                            'type' => 'select',
                            'name' => 'gender',
                            'value' => $entry?->gender,
                            'label' => __( 'Gender' ),
                            'options' => Helper::kvToJsOptions( [
                                '' => __( 'Not Defined' ),
                                'male' => __( 'Male' ),
                                'female' => __( 'Female' ),
                            ] ),
                            'description' => __( 'Set the user gender.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'phone',
                            'value' => $entry?->phone,
                            'label' => __( 'Phone' ),
                            'validation' => collect( [
                                ns()->option->get( 'ns_customers_force_unique_phone', 'no' ) === 'yes' ? (
                                    $entry instanceof User && ! empty( $entry->phone ) ? Rule::unique( 'nexopos_users', 'phone' )->ignore( $entry->id ) : Rule::unique( 'nexopos_users', 'phone' )
                                ) : '',
                            ] )->toArray(),
                            'description' => __( 'Set the user phone number.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'pobox',
                            'value' => $entry?->pobox,
                            'label' => __( 'PO Box' ),
                            'description' => __( 'Set the user PO Box.' ),
                        ],
                    ],
                ],
                'billing' => [
                    'label' => __( 'Billing Address' ),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'first_name',
                            'value' => $entry->billing->first_name ?? '',
                            'label' => __( 'First Name' ),
                            'description' => __( 'Provide the billing First Name.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'last_name',
                            'value' => $entry->billing->last_name ?? '',
                            'label' => __( 'Last name' ),
                            'description' => __( 'Provide the billing last name.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'phone',
                            'value' => $entry->billing->phone ?? '',
                            'label' => __( 'Phone' ),
                            'description' => __( 'Billing phone number.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'address_1',
                            'value' => $entry->billing->address_1 ?? '',
                            'label' => __( 'Address 1' ),
                            'description' => __( 'Billing First Address.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'address_2',
                            'value' => $entry->billing->address_2 ?? '',
                            'label' => __( 'Address 2' ),
                            'description' => __( 'Billing Second Address.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'country',
                            'value' => $entry->billing->country ?? '',
                            'label' => __( 'Country' ),
                            'description' => __( 'Billing Country.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'city',
                            'value' => $entry->billing->city ?? '',
                            'label' => __( 'City' ),
                            'description' => __( 'City' ),
                        ], [
                            'type' => 'text',
                            'name' => 'pobox',
                            'value' => $entry->billing->pobox ?? '',
                            'label' => __( 'PO.Box' ),
                            'description' => __( 'Postal Address' ),
                        ], [
                            'type' => 'text',
                            'name' => 'company',
                            'value' => $entry->billing->company ?? '',
                            'label' => __( 'Company' ),
                            'description' => __( 'Company' ),
                        ], [
                            'type' => 'text',
                            'name' => 'email',
                            'value' => $entry->billing->email ?? '',
                            'label' => __( 'Email' ),
                            'description' => __( 'Email' ),
                        ],
                    ],
                ],
                'shipping' => [
                    'label' => __( 'Shipping Address' ),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'first_name',
                            'value' => $entry->shipping->first_name ?? '',
                            'label' => __( 'First Name' ),
                            'description' => __( 'Provide the shipping First Name.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'last_name',
                            'value' => $entry->shipping->last_name ?? '',
                            'label' => __( 'Last Name' ),
                            'description' => __( 'Provide the shipping Last Name.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'phone',
                            'value' => $entry->shipping->phone ?? '',
                            'label' => __( 'Phone' ),
                            'description' => __( 'Shipping phone number.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'address_1',
                            'value' => $entry->shipping->address_1 ?? '',
                            'label' => __( 'Address 1' ),
                            'description' => __( 'Shipping First Address.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'address_2',
                            'value' => $entry->shipping->address_2 ?? '',
                            'label' => __( 'Address 2' ),
                            'description' => __( 'Shipping Second Address.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'country',
                            'value' => $entry->shipping->country ?? '',
                            'label' => __( 'Country' ),
                            'description' => __( 'Shipping Country.' ),
                        ], [
                            'type' => 'text',
                            'name' => 'city',
                            'value' => $entry->shipping->city ?? '',
                            'label' => __( 'City' ),
                            'description' => __( 'City' ),
                        ], [
                            'type' => 'text',
                            'name' => 'pobox',
                            'value' => $entry->shipping->pobox ?? '',
                            'label' => __( 'PO.Box' ),
                            'description' => __( 'Postal Address' ),
                        ], [
                            'type' => 'text',
                            'name' => 'company',
                            'value' => $entry->shipping->company ?? '',
                            'label' => __( 'Company' ),
                            'description' => __( 'Company' ),
                        ], [
                            'type' => 'text',
                            'name' => 'email',
                            'value' => $entry->shipping->email ?? '',
                            'label' => __( 'Email' ),
                            'description' => __( 'Email' ),
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
        unset( $inputs[ 'password_confirm' ] );

        /**
         * if the password is not changed, no
         * need to hash it
         */
        $inputs = collect( $inputs )->filter( fn( $input ) => ! empty( $input ) || $input === 0 )->toArray();

        if ( ! empty( $inputs[ 'password' ] ) ) {
            $inputs[ 'password' ] = Hash::make( $inputs[ 'password' ] );
        }

        return collect( $inputs )->map( function ( $value, $key ) {
            if ( $key === 'group_id' && empty( $value ) ) {
                $value = $this->options->get( 'ns_customers_default_group', false );
                $group = CustomerGroup::find( $value );

                if ( ! $group instanceof CustomerGroup ) {
                    throw new NotAllowedException( __( 'The assigned default customer group doesn\'t exist or is not defined.' ) );
                }
            }

            return $value;
        } )->toArray();
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, User $entry )
    {
        unset( $inputs[ 'password_confirm' ] );

        /**
         * if the password is not changed, no
         * need to hash it
         */
        $inputs = collect( $inputs )->filter( fn( $input ) => ! empty( $input ) || $input === 0 )->toArray();

        if ( ! empty( $inputs[ 'password' ] ) ) {
            $inputs[ 'password' ] = Hash::make( $inputs[ 'password' ] );
        }

        return collect( $inputs )->map( function ( $value, $key ) {
            if ( $key === 'group_id' && empty( $value ) ) {
                $value = $this->options->get( 'ns_customers_default_group', false );
                $group = CustomerGroup::find( $value );

                if ( ! $group instanceof CustomerGroup ) {
                    throw new NotAllowedException( __( 'The assigned default customer group doesn\'t exist or is not defined.' ) );
                }
            }

            return $value;
        } )->toArray();
    }

    /**
     * After saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function afterPost( $request, User $entry )
    {
        if ( isset( $request[ 'roles'] ) ) {
            $this->userService
                ->setUserRole(
                    $entry,
                    $request[ 'roles' ]
                );

            $this->userService->createAttribute( $entry );

            /**
             * While creating the user, if we set that user as active
             * we'll dispatch the activation successful event.
             */
            if ( $entry->active ) {
                UserAfterActivationSuccessfulEvent::dispatch( $entry );
            }
        }

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
    public function afterPut( $request, User $entry )
    {
        if ( isset( $request[ 'roles'] ) ) {
            $this->userService
                ->setUserRole(
                    $entry,
                    $request[ 'roles' ]
                );

            $this->userService->createAttribute( $entry );

            /**
             * While creating the user, if we set that user as active
             * we'll dispatch the activation successful event.
             */
            if ( $entry->active ) {
                UserAfterActivationSuccessfulEvent::dispatch( $entry );
            }
        }

        return $request;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, int $id, $model )
    {
        if ( $namespace == 'ns.users' ) {
            $this->allowedTo( 'delete' );

            if ( $id === Auth::id() ) {
                throw new NotAllowedException( __( 'You cannot delete your own account.' ) );
            }
        }
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                identifier: 'username',
                label: __( 'Username' ),
                attributes: CrudTable::attributes(
                    CrudTable::attribute(
                        column: 'active',
                        label: __( 'Active' )
                    ),
                    CrudTable::attribute(
                        column: 'email',
                        label: __( 'Email' )
                    )
                )
            ),
            CrudTable::column(
                label: __( 'Wallet' ),
                identifier: 'account_amount',
            ),
            CrudTable::column(
                label: __( 'Owed' ),
                identifier: 'owed_amount'
            ),
            CrudTable::column(
                label: __( 'Purchases' ),
                identifier: 'purchases_amount'
            ),
            CrudTable::column(
                label: __( 'Roles' ),
                identifier: 'rolesNames',
                sort: false
            ),
            CrudTable::column(
                label: __( 'Created At' ),
                identifier: 'created_at'
            )
        );
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->action(
            identifier: 'edit_customers_group',
            label: __( 'Edit' ),
            url: ns()->url( 'dashboard/users/edit/' . $entry->id ),
        );

        $entry->action(
            identifier: 'customers_orders',
            label: __( 'Orders' ),
            url: ns()->url( 'dashboard/users/' . $entry->id . '/orders' ),
        );

        $entry->action(
            identifier: 'customers_rewards',
            label: __( 'Rewards' ),
            url: ns()->url( 'dashboard/users/' . $entry->id . '/rewards' ),
        );

        $entry->action(
            identifier: 'customers_coupons',
            label: __( 'Coupons' ),
            url: ns()->url( 'dashboard/users/' . $entry->id . '/coupons' ),
        );

        $entry->action(
            identifier: 'customers_history',
            label: __( 'Wallet History' ),
            url: ns()->url( 'dashboard/users/' . $entry->id . '/account-history' ),
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.users/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
                'title' => __( 'Delete a user' ),
            ],
        );

        $roles = User::find( $entry->id )->roles()->get();
        $entry->rolesNames = $roles->map( fn( $role ) => $role->name )->join( ', ' ) ?: __( 'Not Assigned' );

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
            return JsonResponse::error(
                message: __( 'You\'re not allowed to do this operation' )
            );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status = [
                'success' => 0,
                'error' => 0,
            ];

            /**
             * @temp
             */
            if ( Auth::user()->role->namespace !== 'admin' ) {
                throw new Exception( __( 'Access Denied' ) );
            }

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof User ) {
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
            'list' => ns()->url( 'dashboard/' . 'users' ),
            'create' => ns()->url( 'dashboard/' . 'users/create' ),
            'edit' => ns()->url( 'dashboard/' . 'users/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.users' ),
            'put' => ns()->url( 'api/crud/' . 'ns.users/{id}' . '' ),
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
