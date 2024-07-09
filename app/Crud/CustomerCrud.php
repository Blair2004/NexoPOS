<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Casts\GenderCast;
use App\Casts\NotDefinedCast;
use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Events\CustomerAfterCreatedEvent;
use App\Events\CustomerAfterUpdatedEvent;
use App\Events\CustomerBeforeDeletedEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Customer;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerGroup;
use App\Models\CustomerShippingAddress;
use App\Models\Role;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\CustomerService;
use App\Services\Helper;
use App\Services\Options;
use App\Services\UsersService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use TorMorten\Eventy\Facades\Events as Hook;

class CustomerCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.customers';

    /**
     * define the base table
     */
    protected $table = 'nexopos_users';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.customers.index';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.customers';

    /**
     * Model Used
     */
    protected $model = Customer::class;

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = true;

    protected $pick = [
        'user' => [ 'id', 'username' ],
        'group' => [ 'id', 'name' ],
    ];

    /**
     * Adding relation
     */
    public $relations = [
        'leftJoin' => [
            [ 'nexopos_customers_groups as group', 'nexopos_users.group_id', '=', 'group.id' ],
        ],
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_users.author' ],
    ];

    /**
     * all tabs mentionned on the tabs relation
     * are ignored on the parent model.
     */
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
        'created_at' => DateCast::class,
        'updated_at' => DateCast::class,
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
        'create' => 'nexopos.create.customers',
        'read' => 'nexopos.read.customers',
        'update' => 'nexopos.update.customers',
        'delete' => 'nexopos.delete.customers',
    ];

    private Options $options;

    private CustomerService $customerService;

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->options = app()->make( Options::class );
        $this->customerService = app()->make( CustomerService::class );
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
            list_title: __( 'Customers List' ),
            list_description: __( 'Display all customers.' ),
            no_entry: __( 'No customers has been registered' ),
            create_new: __( 'Add a new customer' ),
            create_title: __( 'Create a new customer' ),
            create_description: __( 'Register a new customer and save it.' ),
            edit_title: __( 'Edit customer' ),
            edit_description: __( 'Modify  Customer.' ),
            back_to_list: __( 'Return to Customers' ),
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

    public function hook( $query ): void
    {
        $query->join( 'nexopos_users_roles_relations', 'nexopos_users.id', '=', 'nexopos_users_roles_relations.user_id' );
        $query->join( 'nexopos_roles', 'nexopos_roles.id', '=', 'nexopos_users_roles_relations.role_id' );
        $query->where( 'nexopos_roles.namespace', Role::STORECUSTOMER );
        $query->orderBy( 'updated_at', 'desc' );
    }

    /**
     * Fields
     *
     * @param  object/null
     * @return array of field
     */
    public function getForm( ?Customer $entry = null )
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __( 'Customer Name' ),
                name: 'first_name',
                validation: 'required',
                value: $entry->first_name ?? '',
                description: __( 'Provide a unique name for the customer.' ),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: CrudForm::fields(
                        FormInput::text(
                            label: __( 'Last Name' ),
                            name: 'last_name',
                            value: $entry->last_name ?? '',
                            description: __( 'Provide the customer last name' ),
                        ),
                        FormInput::number(
                            label: __( 'Credit Limit' ),
                            name: 'credit_limit_amount',
                            value: $entry->credit_limit_amount ?? '',
                            description: __( 'Set what should be the limit of the purchase on credit.' ),
                        ),
                        FormInput::searchSelect(
                            label: __( 'Group' ),
                            name: 'group_id',
                            value: $entry->group_id ?? '',
                            validation: 'required',
                            component: 'nsCrudForm',
                            props: CustomerGroupCrud::getFormConfig(),
                            options: Helper::toJsOptions( CustomerGroup::all(), [ 'id', 'name' ] ),
                            description: __( 'Assign the customer to a group' ),
                        ),
                        FormInput::datetime(
                            label: __( 'Birth Date' ),
                            name: 'birth_date',
                            value: $entry instanceof Customer && $entry->birth_date !== null ? Carbon::parse( $entry->birth_date )->format( 'Y-m-d H:i:s' ) : null,
                            description: __( 'Displays the customer birth date' ),
                        ),
                        FormInput::email(
                            label: __( 'Email' ),
                            name: 'email',
                            value: $entry->email ?? '',
                            validation: collect( [
                                function ( $attribute, $value, $fail ) {
                                    if ( strlen( $value ) > 0 ) {
                                        /**
                                         * let's check if $value is a
                                         * valid email using preg_match
                                         */
                                        if ( preg_match( '/^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$/', $value ) === 0 ) {
                                            return $fail( __( "The \"$attribute\" provided is not valid." ) );
                                        }
                                    }
                                },
                            ] )->filter()->toArray(),
                            description: __( 'Provide the customer email.' ),
                        ),
                        FormInput::text(
                            label: __( 'Phone Number' ),
                            name: 'phone',
                            value: $entry->phone ?? '',
                            validation: collect( [
                                ns()->option->get( 'ns_customers_force_unique_phone', 'no' ) === 'yes' ? (
                                    $entry instanceof Customer && ! empty( $entry->phone ) ? Rule::unique( 'nexopos_users', 'phone' )->ignore( $entry->id ) : Rule::unique( 'nexopos_users', 'phone' )
                                ) : '',
                            ] )->toArray(),
                            description: __( 'Provide the customer phone number' ),
                        ),
                        FormInput::text(
                            label: __( 'PO Box' ),
                            name: 'pobox',
                            value: $entry->pobox ?? '',
                            description: __( 'Provide the customer PO.Box' ),
                        ),
                        FormInput::select(
                            options: Helper::kvToJsOptions( [
                                '' => __( 'Not Defined' ),
                                'male' => __( 'Male' ),
                                'female' => __( 'Female' ),
                            ] ),
                            label: __( 'Gender' ),
                            name: 'gender',
                            value: $entry->gender ?? '',
                            description: __( 'Provide the customer gender' )
                        )
                    )
                ),
                CrudForm::tab(
                    label: __( 'Billing Address' ),
                    identifier: 'billing',
                    fields: $this->customerService->getAddressFields( $entry->billing ?? null )
                ),
                CrudForm::tab(
                    label: __( 'Shipping Address' ),
                    identifier: 'shipping',
                    fields: $this->customerService->getAddressFields( $entry->shipping ?? null )
                ),
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
        unset( $inputs[ 'password_confirm' ] );

        /**
         * if the password is not changed, no
         * need to hash it
         */
        $inputs = collect( $inputs )->filter( fn( $input ) => ! empty( $input ) || $input === 0 )->toArray();

        if ( ! empty( $inputs[ 'password' ] ) ) {
            $inputs[ 'password' ] = Hash::make( $inputs[ 'password' ] );
        } else {
            $inputs[ 'password' ] = Hash::make( Str::random( 10 ) );
        }

        /**
         * if no email is provided, then we'll generate a random
         * email for the customer based on the domain and the last customer id.
         */
        if ( empty( $inputs[ 'email' ] ) ) {
            $domain = parse_url( url( '/' ) );
            $lastCustomer = User::orderBy( 'nexopos_users.id', 'desc' )->first();

            if ( $lastCustomer instanceof User ) {
                $lastCustomerId = $lastCustomer->id + 1;
            } else {
                $lastCustomerId = 1;
            }

            $inputs[ 'email' ] = 'customer-' . $lastCustomerId + 1 . '@' . ( $domain[ 'host' ] ?? 'nexopos.com' );
        }

        /**
         * if the username is empty, it will match the email.
         */
        if ( empty( $inputs[ 'username' ] ) ) {
            $inputs[ 'username' ] = $inputs[ 'email' ];
        }

        return collect( $inputs )->map( function ( $value, $key ) {
            if ( $key === 'group_id' && empty( $value ) ) {
                $value = $this->options->get( 'ns_customers_default_group', false );
                $group = CustomerGroup::find( $value );

                if ( ! $group instanceof CustomerGroup ) {
                    throw new NotAllowedException( __( 'The assigned default customer group doesn\'t exist or has been deleted.' ) );
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
    public function filterPutInputs( $inputs, Customer $entry )
    {
        unset( $inputs[ 'password_confirm' ] );

        /**
         * if the password is not changed, no
         * need to hash it
         */
        $inputs = collect( $inputs )->filter( fn( $input ) => ! empty( $input ) || $input === 0 )->toArray();

        if ( ! empty( $inputs[ 'password' ] ) ) {
            $inputs[ 'password' ] = Hash::make( $inputs[ 'password' ] );
        } else {
            $inputs[ 'password' ] = Hash::make( Str::random( 10 ) );
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
     * After Crud POST
     */
    public function afterPost( array $inputs, Customer $customer ): array
    {
        CustomerAfterCreatedEvent::dispatch( $customer );

        /**
         * @var UsersService $usersService
         */
        $usersService = app()->make( UsersService::class );
        $usersService->setUserRole( User::find( $customer->id ), [ Role::namespace( Role::STORECUSTOMER )->id ] );

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
     */
    public function afterPut( array $inputs, Customer $customer ): array
    {
        CustomerAfterUpdatedEvent::dispatch( $customer );

        return $inputs;
    }

    /**
     * Before Delete
     */
    public function beforeDelete( string $namespace, int $id, Customer $customer ): void
    {
        if ( $namespace == 'ns.customers' ) {
            $this->allowedTo( 'delete' );

            CustomerBeforeDeletedEvent::dispatch( $customer );
        }
    }

    /**
     * before creating
     */
    public function beforePost( $inputs ): void
    {
        $this->allowedTo( 'create' );

        /**
         * @var CustomerService
         */
        $customerService = app()->make( CustomerService::class );
        $customerService->precheckCustomers( $inputs );
    }

    /**
     * before updating
     */
    public function beforePut( $inputs, $customer ): void
    {
        $this->allowedTo( 'update' );

        /**
         * @var CustomerService
         */
        $customerService = app()->make( CustomerService::class );
        $customerService->precheckCustomers( $inputs, $customer->id );
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                label: __( 'First Name' ),
                identifier: 'first_name',
                attributes: CrudTable::attributes(
                    CrudTable::attribute( __( 'Group' ), 'group_name' ),
                    CrudTable::attribute( __( 'Gender' ), 'gender' ),
                )
            ),
            CrudTable::column( __( 'Last name' ), 'last_name' ),
            CrudTable::column( __( 'Phone' ), 'phone' ),
            CrudTable::column( __( 'Email' ), 'email' ),
            CrudTable::column( __( 'Account Credit' ), 'account_amount' ),
            CrudTable::column( __( 'Owed Amount' ), 'owed_amount' ),
            CrudTable::column( __( 'Purchase Amount' ), 'purchases_amount' ),
            CrudTable::column( __( 'Author' ), 'user_username' ),
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
            type: 'GOTO',
            url: ns()->url( 'dashboard/customers/edit/' . $entry->id ),
        );

        $entry->action(
            identifier: 'customers_orders',
            label: __( 'Orders' ),
            type: 'GOTO',
            url: ns()->url( 'dashboard/customers/' . $entry->id . '/orders' ),
        );

        $entry->action(
            identifier: 'customers_rewards',
            label: __( 'Rewards' ),
            type: 'GOTO',
            url: ns()->url( 'dashboard/customers/' . $entry->id . '/rewards' ),
        );

        $entry->action(
            identifier: 'customers_coupons',
            label: __( 'Coupons' ),
            type: 'GOTO',
            url: ns()->url( 'dashboard/customers/' . $entry->id . '/coupons' ),
        );

        $entry->action(
            identifier: 'customers_history',
            label: __( 'Wallet History' ),
            type: 'GOTO',
            url: ns()->url( 'dashboard/customers/' . $entry->id . '/account-history' ),
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.customers/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
                'title' => __( 'Delete a customers' ),
            ],
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
         * Will control if the user has the permissoin to do that.
         */
        if ( $this->permissions[ 'delete' ] !== false ) {
            ns()->restrict( $this->permissions[ 'delete' ] );
        } else {
            throw new NotAllowedException;
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof Customer ) {
                    /**
                     * We want to check if we're allowed to delete
                     * the selected customer by checking his dependencies.
                     */
                    $this->handleDependencyForDeletion( $entity );

                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'error' ]++;
                }
            }

            return $status;
        }

        return false;
    }

    /**
     * get Links
     *
     * @return array of links
     */
    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url( '/dashboard/customers' ),
            create: ns()->url( '/dashboard/customers/create' ),
            edit: ns()->url( '/dashboard/customers/edit/{id}' ),
            post: ns()->url( '/api/crud/ns.customers' ),
            put: ns()->url( '/api/crud/ns.customers/{id}' ),
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
                'label' => __( 'Delete Selected Customers' ),
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
