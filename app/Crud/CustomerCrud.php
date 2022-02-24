<?php
namespace App\Crud;

use App\Events\CustomerAfterCreatedEvent;
use App\Events\CustomerAfterUpdatedEvent;
use App\Events\CustomerBeforeDeletedEvent;
use App\Exceptions\NotAllowedException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Options;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerGroup;
use App\Models\CustomerShippingAddress;
use App\Services\Users;
use Carbon\Carbon;
use Exception;
use TorMorten\Eventy\Facades\Events as Hook;

class CustomerCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_customers';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.customers.index';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.customers';

    /**
     * Model Used
     */
    protected $model      =   \App\Models\Customer::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_customers_groups', 'nexopos_customers.group_id', '=', 'nexopos_customers_groups.id' ],
        [ 'nexopos_users', 'nexopos_customers.author', '=', 'nexopos_users.id' ],
    ];

    /**
     * all tabs mentionned on the tabs relation
     * are ignored on the parent model.
     */
    protected $tabsRelations    =   [
        'shipping'      =>      [ CustomerShippingAddress::class, 'customer_id', 'id' ],
        'billing'       =>      [ CustomerBillingAddress::class, 'customer_id', 'id' ],
    ];

    /**
     * Define where statement
     * @var  array
    **/
    protected $listWhere    =   [];

    /**
     * Define where in statement
     * @var  array
     */
    protected $whereIn      =   [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable    =   [];

    protected $permissions = [
        'create' => 'nexopos.create.customers',
        'read' => 'nexopos.read.customers',
        'update' => 'nexopos.update.customers',
        'delete' => 'nexopos.delete.customers',
    ];

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        $this->options      =   app()->make( Options::class );

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Customers List' ),
            'list_description'      =>  __( 'Display all customers.' ),
            'no_entry'              =>  __( 'No customers has been registered' ),
            'create_new'            =>  __( 'Add a new customer' ),
            'create_title'          =>  __( 'Create a new customer' ),
            'create_description'    =>  __( 'Register a new customer and save it.' ),
            'edit_title'            =>  __( 'Edit customer' ),
            'edit_description'      =>  __( 'Modify  Customer.' ),
            'back_to_list'          =>  __( 'Return to Customers' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     * @return  boolean
    **/
    public function isEnabled( $feature )
    {
        return false; // by default
    }

    public function hook( $query )
    {
        $query->orderBy( 'updated_at', 'desc' );
    }

    /**
     * Fields
     * @param  object/null
     * @return  array of field
     */
    public function getForm( Customer $entry = null ) 
    {
        return [
            'main'  =>  [
                'label' =>  __( 'Customer Name' ),
                'name'          =>  'name',
                'validation'    =>  'required',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a unique name for the customer.' )
            ], 
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'          =>  'text',
                            'label'         =>  __( 'Surname' ),
                            'name'          =>  'surname',
                            'value'         =>  $entry->surname ?? '',
                            'description'   =>  __( 'Provide the customer surname' )
                        ], [
                            'type'          =>  'number',
                            'label'         =>  __( 'Credit Limit' ),
                            'name'          =>  'credit_limit_amount',
                            'value'         =>  $entry->credit_limit_amount ?? '',
                            'description'   =>  __( 'Set what should be the limit of the purchase on credit.' )
                        ], [
                            'type'          =>  'select',
                            'label'         =>  __( 'Group' ),
                            'name'          =>  'group_id',
                            'value'         =>  $entry->group_id ?? '',
                            'options'       =>  Helper::toJsOptions( CustomerGroup::all(), [ 'id', 'name' ]),
                            'description'   =>  __( 'Assign the customer to a group' )
                        ], [
                            'type'          =>  'datetimepicker',
                            'label'         =>  __( 'Birth Date' ),
                            'name'          =>  'birth_date',
                            'value'         =>  $entry instanceof Customer && $entry->birth_date !== null ? Carbon::parse( $entry->birth_date )->format( 'Y-m-d H:i:s' ) : null, 
                            'description'   =>  __( 'Displays the customer birth date' )
                        ], [
                            'type'          =>  'email',
                            'label'         =>  __( 'Email' ),
                            'name'          =>  'email',
                            'value'         =>  $entry->email ?? '',
                            'validation'    =>  collect([
                                ns()->option->get( 'ns_customers_force_valid_email', 'no' ) === 'yes' ? 'email' : '',
                                ns()->option->get( 'ns_customers_force_valid_email', 'no' ) === 'yes' ? (
                                    $entry instanceof Customer && ! empty( $entry->email ) ? Rule::unique( 'nexopos_customers', 'email' )->ignore( $entry->id ) : Rule::unique( 'nexopos_customers', 'email' )
                                ) : ''
                            ])->filter()->toArray(),
                            'description'   =>  __( 'Provide the customer email.' )
                        ], [
                            'type'          =>  'text',
                            'label'         =>  __( 'Phone Number' ),
                            'name'          =>  'phone',
                            'value'         =>  $entry->phone ?? '',
                            'description'   =>  __( 'Provide the customer phone number' )
                        ], [
                            'type'          =>  'text',
                            'label'         =>  __( 'PO Box' ),
                            'name'          =>  'pobox',
                            'value'         =>  $entry->pobox ?? '',
                            'description'   =>  __( 'Provide the customer PO.Box' )
                        ], [
                            'type'          =>  'select',
                            'options'       =>  Helper::kvToJsOptions([
                                ''          =>  __( 'Not Defined' ),
                                'male'      =>  __( 'Male' ),
                                'female'    =>  __( 'Female' )          
                            ]),
                            'label'         =>  __( 'Gender' ),
                            'name'          =>  'gender',
                            'value'         =>  $entry->gender ?? '',
                            'description'   =>  __( 'Provide the customer PO.Box' )
                        ]
                    ]
                ],
                'billing'  =>  [
                    'label'     =>  __( 'Billing Address' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'value' =>  $entry->billing->name ?? '',
                            'label' =>  __( 'Name' ),
                            'description'   =>  __( 'Provide the billing name.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'surname',
                            'value' =>  $entry->billing->surname ?? '',
                            'label' =>  __( 'Surname' ),
                            'description'   =>  __( 'Provide the billing surname.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'phone',
                            'value' =>  $entry->billing->phone ?? '',
                            'label' =>  __( 'Phone' ),
                            'description'   =>  __( 'Billing phone number.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'address_1',
                            'value' =>  $entry->billing->address_1 ?? '',
                            'label' =>  __( 'Address 1' ),
                            'description'   =>  __( 'Billing First Address.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'address_2',
                            'value' =>  $entry->billing->address_2 ?? '',
                            'label' =>  __( 'Address 2' ),
                            'description'   =>  __( 'Billing Second Address.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'country',
                            'value' =>  $entry->billing->country ?? '',
                            'label' =>  __( 'Country' ),
                            'description'   =>  __( 'Billing Country.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'city',
                            'value' =>  $entry->billing->city ?? '',
                            'label' =>  __( 'City' ),
                            'description'   =>  __( 'City' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'pobox',
                            'value' =>  $entry->billing->pobox ?? '',
                            'label' =>  __( 'PO.Box' ),
                            'description'   =>  __( 'Postal Address' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'company',
                            'value' =>  $entry->billing->company ?? '',
                            'label' =>  __( 'Company' ),
                            'description'   =>  __( 'Company' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'email',
                            'value' =>  $entry->billing->email ?? '',
                            'label' =>  __( 'Email' ),
                            'description'   =>  __( 'Email' )
                        ], 
                    ]
                ],
                'shipping'  =>  [
                    'label'     =>  __( 'Shipping Address' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'value' =>  $entry->shipping->name ?? '',
                            'label' =>  __( 'Name' ),
                            'description'   =>  __( 'Provide the shipping name.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'surname',
                            'value' =>  $entry->shipping->surname ?? '',
                            'label' =>  __( 'Surname' ),
                            'description'   =>  __( 'Provide the shipping surname.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'phone',
                            'value' =>  $entry->shipping->phone ?? '',
                            'label' =>  __( 'Phone' ),
                            'description'   =>  __( 'Shipping phone number.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'address_1',
                            'value' =>  $entry->shipping->address_1 ?? '',
                            'label' =>  __( 'Address 1' ),
                            'description'   =>  __( 'Shipping First Address.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'address_2',
                            'value' =>  $entry->shipping->address_2 ?? '',
                            'label' =>  __( 'Address 2' ),
                            'description'   =>  __( 'Shipping Second Address.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'country',
                            'value' =>  $entry->shipping->country ?? '',
                            'label' =>  __( 'Country' ),
                            'description'   =>  __( 'Shipping Country.' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'city',
                            'value' =>  $entry->shipping->city ?? '',
                            'label' =>  __( 'City' ),
                            'description'   =>  __( 'City' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'pobox',
                            'value' =>  $entry->shipping->pobox ?? '',
                            'label' =>  __( 'PO.Box' ),
                            'description'   =>  __( 'Postal Address' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'company',
                            'value' =>  $entry->shipping->company ?? '',
                            'label' =>  __( 'Company' ),
                            'description'   =>  __( 'Company' )
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'email',
                            'value' =>  $entry->shipping->email ?? '',
                            'label' =>  __( 'Email' ),
                            'description'   =>  __( 'Email' )
                        ], 
                    ]
                ],

            ]
        ];
    }

    /**
     * Filter POST input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return collect( $inputs )->map( function( $value, $key ) {
            
            if ( $key === 'group_id' && empty( $value ) ) {
                
                $value      =   $this->options->get( 'ns_customers_default_group', false );
                $group      =   CustomerGroup::find( $value );
    
                if ( ! $group instanceof CustomerGroup ) {
                    throw new NotAllowedException( __( 'The assigned default customer group doesn\'t exist or is not defined.' ) );
                }
            }

            return $value;

        })->toArray();
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, \App\Models\Customer $entry )
    {
        return collect( $inputs )->map( function( $value, $key ) {
            
            if ( $key === 'group_id' && empty( $value ) ) {

                $value      =   $this->options->get( 'ns_customers_default_group', false );
                $group      =   CustomerGroup::find( $value );
    
                if ( ! $group instanceof CustomerGroup ) {
                    throw new NotAllowedException( __( 'The assigned default customer group doesn\'t exist or is not defined.' ) );
                }
            }

            return $value;

        })->toArray();
    }

    /**
     * After Crud POST
     * @param  object entry
     * @return  void
     */
    public function afterPost( $inputs, Customer $customer )
    {
        CustomerAfterCreatedEvent::dispatch( $customer );

        return $inputs;
    }

    
    /**
     * get
     * @param  string
     * @return  mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model' : return $this->model ; break;
        }
    }

    /**
     * After Crud PUT
     * @param  object entry
     * @return  void
     */
    public function afterPut( $inputs, Customer $customer )
    {
        CustomerAfterUpdatedEvent::dispatch( $customer );
        
        return $inputs;
    }
    
    /**
     * Protect an access to a specific crud UI
     * @param  array { namespace, id, type }
     * @return  array | throw AccessDeniedException
    **/
    public function canAccess( $fields )
    {
        $users      =   app()->make( Users::class );
        
        if ( $users->is([ 'admin' ]) ) {
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The access is granted.' )
            ];
        }

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id, Customer $customer ) {
        if ( $namespace == 'ns.customers' ) {
            $this->allowedTo( 'delete' );

            CustomerBeforeDeletedEvent::dispatch( $customer );
        }
    }

    /**
     * before creating
     * @return  void
     */
    public function beforePost( $inputs ) {
        $this->allowedTo( 'create' );
    }

    /**
     * before updating
     * @return  void
     */
    public function beforePut( $inputs, $customer ) {
        $this->allowedTo( 'update' );
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'name'  =>  [
                'label'  =>  __( 'Name' )
            ],
            'surname'  =>  [
                'label'  =>  __( 'Surname' )
            ],
            'phone'  =>  [
                'label'  =>  __( 'Phone' )
            ],
            'nexopos_customers_groups_name'  =>  [
                'label'  =>  __( 'Group' )
            ],
            'email'  =>  [
                'label'  =>  __( 'Email' )
            ],
            'account_amount'  =>  [
                'label'  =>  __( 'Account Credit' )
            ],
            'owed_amount'  =>  [
                'label'  =>  __( 'Owed Amount' )
            ],
            'purchases_amount'  =>  [
                'label'  =>  __( 'Purchase Amount' )
            ],
            'gender'  =>  [
                'label'  =>  __( 'Gender' )
            ],
            'nexopos_users_username'  =>  [
                'label'  =>  __( 'Author' )
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created At' )
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        $entry->owed_amount         =   ( string ) ns()->currency->define( $entry->owed_amount );
        $entry->account_amount      =   ( string ) ns()->currency->define( $entry->account_amount );
        $entry->purchases_amount    =   ( string ) ns()->currency->define( $entry->purchases_amount );
        $entry->phone               =   empty( $entry->phone ) ? __( 'Not Defined' ) : $entry->phone;
        
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit_customers_group',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( 'dashboard/customers/edit/' . $entry->id )
            ], [
                'label'         =>      __( 'Orders' ),
                'namespace'     =>      'customers_orders',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( 'dashboard/customers/' . $entry->id . '/orders' )
            ], [
                'label'         =>      __( 'Rewards' ),
                'namespace'     =>      'customers_rewards',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( 'dashboard/customers/' . $entry->id . '/rewards' )
            ], [
                'label'         =>      __( 'Coupons' ),
                'namespace'     =>      'customers_rewards',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( 'dashboard/customers/' . $entry->id . '/coupons' )
            ], [
                'label'         =>      __( 'Account History' ),
                'namespace'     =>      'customers_rewards',
                'type'          =>      'GOTO',
                'url'           =>      ns()->url( 'dashboard/customers/' . $entry->id . '/account-history' )
            ], [
                'label'     =>      __( 'Delete' ),
                'namespace' =>      'delete',
                'type'      =>      'DELETE',
                'url'       =>      ns()->url( '/api/nexopos/v4/crud/ns.customers/' . $entry->id ),
                'confirm'   =>  [
                    'message'   =>  __( 'Would you like to delete this ?' ),
                    'title'     =>  __( 'Delete a customers' )
                ]
            ]
        ];

        $entry->{ '$checked' }          =   false;
        $entry->{ '$toggled' }          =   false;
        $entry->{ '$id' }               =   $entry->id;
        $entry->surname                 =   $entry->surname ?? __( 'Not Defined' );
        $entry->pobox                   =   $entry->pobox ?? __( 'Not Defined' );
        $entry->reward_system_id        =   $entry->reward_system_id ?? __( 'Not Defined' );
        $entry->email                   =   $entry->email ?: __( 'Not Defined' );
        
        switch( $entry->gender ) {
            case 'male': $entry->gender = __( 'Male' );break;
            case 'female': $entry->gender = __( 'Female' );break;
            default: $entry->gender = __( 'Not Defined' );break;
        }

        return $entry;
    }

    
    /**
     * Bulk Delete Action
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction( Request $request ) 
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user   =   app()->make( Users::class );
        if ( ! $user->is([ 'admin', 'supervisor' ]) ) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  __( 'You\'re not allowed to do this operation' )
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries_id' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Customer ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }
            return $status;
        }
        return false;
    }

    /**
     * get Links
     * @return  array of links
     */
    public function getLinks()
    {
        return  [
            'list'      =>  ns()->url( '/dashboard/customers' ),
            'create'    =>  ns()->url( '/dashboard/customers/create' ),
            'edit'      =>  ns()->url( '/dashboard/customers/edit/{id}' ),
            'post'      =>  ns()->url( '/api/nexopos/v4/crud/ns.customers' ),
            'put'       =>  ns()->url( '/api/nexopos/v4/crud/ns.customers/{id}' ),
        ];
    }

    /**
     * Get Bulk actions
     * @return  array of actions
    **/
    public function getBulkActions()
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label'         =>  __( 'Delete Selected Customers' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace
                ])
            ]
        ]);
    }

    /**
     * get exports
     * @return  array of export formats
    **/
    public function getExports()
    {
        return [];
    }
}