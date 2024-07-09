<?php

namespace App\Crud;

use App\Casts\NotDefinedCast;
use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Exceptions\NotAllowedException;
use App\Models\Coupon;
use App\Models\CouponCategory;
use App\Models\CouponCustomer;
use App\Models\CouponCustomerGroup;
use App\Models\CouponProduct;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\CustomerService;
use App\Services\Helper;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use TorMorten\Eventy\Facades\Events as Hook;

class CouponCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.coupons';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_coupons';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'customers/coupons';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.coupons';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = Coupon::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => 'nexopos.create.coupons',
        'read' => 'nexopos.read.coupons',
        'update' => 'nexopos.update.coupons',
        'delete' => 'nexopos.delete.coupons',
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_users', 'nexopos_coupons.author', '=', 'nexopos_users.id' ],
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations = [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    protected $tabs = [
        'valid_until' => NotDefinedCast::class,
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
    public $pick = [];

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

    public $skippable = [ 'products', 'categories', 'groups', 'customers' ];

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
            'list_title' => __( 'Coupons List' ),
            'list_description' => __( 'Display all coupons.' ),
            'no_entry' => __( 'No coupons has been registered' ),
            'create_new' => __( 'Add a new coupon' ),
            'create_title' => __( 'Create a new coupon' ),
            'create_description' => __( 'Register a new coupon and save it.' ),
            'edit_title' => __( 'Edit coupon' ),
            'edit_description' => __( 'Modify  Coupon.' ),
            'back_to_list' => __( 'Return to Coupons' ),
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
                validation: 'required',
                description: __( 'Provide a name to the resource.' ),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: CrudForm::fields(
                        FormInput::text(
                            label: __( 'Coupon Code' ),
                            name: 'code',
                            validation: [
                                'required',
                                Rule::unique( 'nexopos_coupons', 'code' )->ignore( $entry !== null ? $entry->id : 0 ),
                            ],
                            description: __( 'Might be used while printing the coupon.' ),
                            value: $entry->code ?? '',
                        ),
                        FormInput::select(
                            name: 'type',
                            validation: 'required',
                            options: Helper::kvToJsOptions( [
                                'percentage_discount' => __( 'Percentage Discount' ),
                                'flat_discount' => __( 'Flat Discount' ),
                            ] ),
                            label: __( 'Type' ),
                            value: $entry->type ?? '',
                            description: __( 'Define which type of discount apply to the current coupon.' ),
                        ),
                        FormInput::text(
                            label: __( 'Discount Value' ),
                            name: 'discount_value',
                            description: __( 'Define the percentage or flat value.' ),
                            value: $entry->discount_value ?? '',
                        ),
                        FormInput::datetime(
                            label: __( 'Valid Until' ),
                            name: 'valid_until',
                            description: __( 'Determine Until When the coupon is valid.' ),
                            value: $entry->valid_until ?? '',
                        ),
                        FormInput::number(
                            label: __( 'Minimum Cart Value' ),
                            name: 'minimum_cart_value',
                            description: __( 'What is the minimum value of the cart to make this coupon eligible.' ),
                            value: $entry->minimum_cart_value ?? '',
                        ),
                        FormInput::text(
                            label: __( 'Maximum Cart Value' ),
                            name: 'maximum_cart_value',
                            description: __( 'The value above which the current coupon can\'t apply.' ),
                            value: $entry->maximum_cart_value ?? '',
                        ),
                        FormInput::datetime(
                            label: __( 'Valid Hours Start' ),
                            name: 'valid_hours_start',
                            description: __( 'Define form which hour during the day the coupons is valid.' ),
                            value: $entry->valid_hours_start ?? '',
                        ),
                        FormInput::datetime(
                            label: __( 'Valid Hours End' ),
                            name: 'valid_hours_end',
                            description: __( 'Define to which hour during the day the coupons end stop valid.' ),
                            value: $entry->valid_hours_end ?? '',
                        ),
                        FormInput::number(
                            label: __( 'Limit Usage' ),
                            name: 'limit_usage',
                            description: __( 'Define how many time a coupons can be redeemed.' ),
                            value: $entry->limit_usage ?? '',
                        ),
                    )
                ),
                CrudForm::tab(
                    identifier: 'selected_products',
                    label: __( 'Products' ),
                    fields: CrudForm::fields(
                        FormInput::multiselect(
                            name: 'products',
                            options: Helper::toJsOptions( Product::get(), [ 'id', 'name' ] ),
                            label: __( 'Select Products' ),
                            value: $entry instanceof Coupon ? $entry->products->map( fn( $product ) => $product->product_id )->toArray() : [],
                            description: __( 'The following products will be required to be present on the cart, in order for this coupon to be valid.' ),
                        ),
                    )
                ),
                CrudForm::tab(
                    identifier: 'selected_categories',
                    label: __( 'Categories' ),
                    fields: CrudForm::fields(
                        FormInput::multiselect(
                            name: 'categories',
                            options: Helper::toJsOptions( ProductCategory::get(), [ 'id', 'name' ] ),
                            label: __( 'Select Categories' ),
                            value: $entry instanceof Coupon ? $entry->categories->map( fn( $category ) => $category->category_id )->toArray() : [],
                            description: __( 'The products assigned to one of these categories should be on the cart, in order for this coupon to be valid.' ),
                        ),
                    )
                ),
                CrudForm::tab(
                    identifier: 'selected_groups',
                    label: __( 'Customer Groups' ),
                    fields: CrudForm::fields(
                        FormInput::multiselect(
                            name: 'groups',
                            options: CustomerGroup::get( [ 'name', 'id' ] )->map( fn( $group ) => [
                                'label' => $group->name,
                                'value' => $group->id,
                            ] ),
                            label: __( 'Assigned To Customer Group' ),
                            description: __( 'Only the customers who belongs to the selected groups will be able to use the coupon.' ),
                            value: $entry instanceof Coupon ? $entry->groups->map( fn( $group ) => $group->group_id )->toArray() : [],
                        ),
                    )
                ),
            )
        );
    }

    /**
     * Filter POST input fields
     */
    public function filterPostInputs( array $inputs ): array
    {
        $inputs = collect( $inputs )->map( function ( $field, $key ) {
            if ( ( in_array( $key, [
                'minimum_cart_value',
                'maximum_cart_value',
                'assigned',
                'limit_usage',
            ] ) && empty( $field ) ) || is_array( $field ) ) {
                return ! is_array( $field ) ? ( $field ?: 0 ) : $field;
            }

            return $field;
        } )->toArray();

        $inputs = collect( $inputs )->filter( function ( $field, $key ) {
            if ( ( in_array( $key, [
                'minimum_cart_value',
                'maximum_cart_value',
                'assigned',
                'limit_usage',
            ] ) && empty( $field ) && $field === null ) || is_array( $field ) ) {
                return false;
            }

            return true;
        } )->toArray();

        if ( ! empty( $inputs[ 'valid_hours_end' ] ) ) {
            $inputs[ 'valid_hours_end' ] = Carbon::parse( $inputs[ 'valid_hours_end' ] )->toDateTimeString();
        }

        if ( ! empty( $inputs[ 'valid_hours_start' ] ) ) {
            $inputs[ 'valid_hours_start' ] = Carbon::parse( $inputs[ 'valid_hours_start' ] )->toDateTimeString();
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     */
    public function filterPutInputs( array $inputs, Coupon $entry ): array
    {
        $inputs = collect( $inputs )->map( function ( $field, $key ) {
            if ( ( in_array( $key, [
                'minimum_cart_value',
                'maximum_cart_value',
                'assigned',
                'limit_usage',
            ] ) && empty( $field ) ) || is_array( $field ) ) {
                return ! is_array( $field ) ? ( $field ?: 0 ) : $field;
            }

            return $field;
        } )->toArray();

        $inputs = collect( $inputs )->filter( function ( $field, $key ) {
            if ( ( in_array( $key, [
                'minimum_cart_value',
                'maximum_cart_value',
                'assigned',
                'limit_usage',
            ] ) && empty( $field ) && $field === null ) || is_array( $field ) ) {
                return false;
            }

            return true;
        } )->toArray();

        if ( ! empty( $inputs[ 'valid_hours_end' ] ) ) {
            $inputs[ 'valid_hours_end' ] = Carbon::parse( $inputs[ 'valid_hours_end' ] )->toDateTimeString();
        }

        if ( ! empty( $inputs[ 'valid_hours_start' ] ) ) {
            $inputs[ 'valid_hours_start' ] = Carbon::parse( $inputs[ 'valid_hours_start' ] )->toDateTimeString();
        }

        return $inputs;
    }

    /**
     * Before saving a record
     */
    public function beforePost( array $inputs ): array
    {
        $this->allowedTo( 'create' );

        if ( $this->permissions[ 'create' ] !== false ) {
            if ( isset( $inputs[ 'products' ] ) && ! empty( $inputs[ 'products' ] ) ) {
                foreach ( $inputs[ 'products' ] as $product_id ) {
                    $product = Product::find( $product_id );
                    if ( ! $product instanceof Product ) {
                        throw new Exception( __( 'Unable to save the coupon product as this product doens\'t exists.' ) );
                    }
                }
            }

            if ( isset( $inputs[ 'categories' ] ) && ! empty( $inputs[ 'categories' ] ) ) {
                foreach ( $inputs[ 'categories' ] as $category_id ) {
                    $category = ProductCategory::find( $category_id );
                    if ( ! $category instanceof ProductCategory ) {
                        throw new Exception( __( 'Unable to save the coupon category as this category doens\'t exists.' ) );
                    }
                }
            }

            if ( isset( $inputs[ 'customers' ] ) && ! empty( $inputs[ 'customers' ] ) ) {
                foreach ( $inputs[ 'customers' ] as $customer_id ) {
                    $category = Customer::find( $customer_id );
                    if ( ! $category instanceof Customer ) {
                        throw new Exception( __( 'Unable to save the coupon as one of the selected customer no longer exists.' ) );
                    }
                }
            }

            if ( isset( $inputs[ 'groups' ] ) && ! empty( $inputs[ 'groups' ] ) ) {
                foreach ( $inputs[ 'groups' ] as $group_id ) {
                    $category = CustomerGroup::find( $group_id );
                    if ( ! $category instanceof CustomerGroup ) {
                        throw new Exception( __( 'Unable to save the coupon as one of the selected customer group no longer exists.' ) );
                    }
                }
            }
        } else {
            throw new NotAllowedException;
        }

        return $inputs;
    }

    /**
     * After saving a record
     */
    public function afterPost( array $inputs, Coupon $coupon ): array
    {
        if ( isset( $inputs[ 'products' ] ) && ! empty( $inputs[ 'products' ] ) ) {
            foreach ( $inputs[ 'products' ] as $product_id ) {
                $productRelation = new CouponProduct;
                $productRelation->coupon_id = $coupon->id;
                $productRelation->product_id = $product_id;
                $productRelation->save();
            }
        }

        if ( isset( $inputs[ 'categories' ] ) && ! empty( $inputs[ 'categories' ] ) ) {
            foreach ( $inputs[ 'categories' ] as $category_id ) {
                $categoryRelation = new CouponCategory;
                $categoryRelation->coupon_id = $coupon->id;
                $categoryRelation->category_id = $category_id;
                $categoryRelation->save();
            }
        }

        if ( isset( $inputs[ 'customers' ] ) && ! empty( $inputs[ 'customers' ] ) ) {
            foreach ( $inputs[ 'customers' ] as $customer_id ) {
                $categoryRelation = new CouponCustomer;
                $categoryRelation->coupon_id = $coupon->id;
                $categoryRelation->customer_id = $customer_id;
                $categoryRelation->save();
            }
        }

        if ( isset( $inputs[ 'groups' ] ) && ! empty( $inputs[ 'groups' ] ) ) {
            foreach ( $inputs[ 'groups' ] as $group_id ) {
                $categoryRelation = new CouponCustomerGroup;
                $categoryRelation->coupon_id = $coupon->id;
                $categoryRelation->group_id = $group_id;
                $categoryRelation->save();
            }
        }

        return $inputs;
    }

    /**
     * get model.
     */
    public function get( string $param ): mixed
    {
        switch ( $param ) {
            case 'model': return $this->model;
                break;
        }
    }

    /**
     * Before updating a record
     */
    public function beforePut( array $inputs, $entry ): array
    {
        if ( $this->permissions[ 'update' ] !== false ) {
            ns()->restrict( $this->permissions[ 'update' ] );

            foreach ( $inputs[ 'products' ] ?? [] as $product_id ) {
                $product = Product::find( $product_id );
                if ( ! $product instanceof Product ) {
                    throw new Exception( __( 'Unable to save the coupon product as this product doens\'t exists.' ) );
                }
            }

            foreach ( $inputs[ 'categories' ] ?? [] as $category_id ) {
                $category = ProductCategory::find( $category_id );
                if ( ! $category instanceof ProductCategory ) {
                    throw new Exception( __( 'Unable to save the coupon as this category doens\'t exists.' ) );
                }
            }

            foreach ( $inputs[ 'customers' ] ?? [] as $customer_id ) {
                $customer = Customer::find( $customer_id );
                if ( ! $customer instanceof Customer ) {
                    throw new Exception( __( 'Unable to save the coupon as one of the customers provided no longer exists.' ) );
                }
            }

            foreach ( $inputs[ 'groups' ] ?? [] as $groups ) {
                $customerGroup = CustomerGroup::find( $groups );
                if ( ! $customerGroup instanceof CustomerGroup ) {
                    throw new Exception( __( 'Unable to save the coupon as one of the provided customer group no longer exists.' ) );
                }
            }
        } else {
            throw new NotAllowedException;
        }

        return $inputs;
    }

    /**
     * After updating a record
     */
    public function afterPut( array $inputs, Coupon $coupon ): array
    {
        collect( [
            'products' => [
                'property' => 'product_id',
                'class' => CouponProduct::class,
            ],
            'categories' => [
                'property' => 'category_id',
                'class' => CouponCategory::class,
            ],
            'groups' => [
                'property' => 'group_id',
                'class' => CouponCustomerGroup::class,
            ],
            'customers' => [
                'property' => 'customer_id',
                'class' => CouponCustomer::class,
            ],
        ] )->each( function ( $data, $key ) use ( $inputs, $coupon ) {
            $coupon->{$key}->each( function ( $element ) use ( $inputs, $data, $key ) {
                if ( isset( $inputs[ $key ] ) && ! in_array( $element->{$data[ 'property' ]}, $inputs[ $key ] ) ) {
                    $element->delete();
                }
            } );

            if ( isset( $inputs[ $key ] ) ) {
                foreach ( $inputs[ $key ] as $argument ) {
                    $productRelation = $data[ 'class' ]::where( 'coupon_id', $coupon->id )
                        ->where( $data[ 'property' ], $argument )
                        ->first();

                    if ( ! $productRelation instanceof $data[ 'class' ] ) {
                        $productRelation = new $data[ 'class' ];
                    }

                    $productRelation->coupon_id = $coupon->id;
                    $productRelation->{$data[ 'property' ]} = $argument;
                    $productRelation->save();
                }
            }
        } );

        return $inputs;
    }

    /**
     * Before Delete
     */
    public function beforeDelete( $namespace, $id, $coupon ): void
    {
        ns()->restrict( $this->permissions[ 'delete' ] );

        if ( $namespace == 'ns.coupons' ) {
            /**
             * @var CustomerService
             */
            $customerService = app()->make( CustomerService::class );
            $customerService->deleteRelatedCustomerCoupon( $coupon );

            $coupon->categories()->delete();
            $coupon->products()->delete();
            $coupon->customers()->delete();
            $coupon->groups()->delete();
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
            'type' => [
                'label' => __( 'Type' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'discount_value' => [
                'label' => __( 'Discount Value' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'valid_hours_start' => [
                'label' => __( 'Valid From' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'valid_hours_end' => [
                'label' => __( 'Valid Till' ),
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
        switch ( $entry->type ) {
            case 'percentage_discount':
                $entry->type = __( 'Percentage Discount' );
                $entry->discount_value = $entry->discount_value . '%';
                break;
            case 'flat_discount':
                $entry->type = __( 'Flat Discount' );
                $entry->discount_value = (string) ns()->currency->define( $entry->discount_value );
                break;
            default:
                $entry->type = __( 'N/A' );
                break;
        }

        $entry->valid_until = $entry->valid_until ?? __( 'Undefined' );

        // you can make changes here
        $entry->action(
            identifier: 'edit-coupon',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/customers/coupons/edit/' . $entry->id ),
        );

        $entry->action(
            identifier: 'coupon-history',
            label: __( 'History' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/customers/coupons/history/' . $entry->id ),
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.coupons/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
                'title' => __( 'Delete a licence' ),
            ],
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     */
    public function bulkAction( Request $request ): bool|array
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
                if ( $entity instanceof Coupon ) {
                    $this->beforeDelete( $this->namespace, null, $entity );
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
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url( 'dashboard/' . 'customers/coupons' ),
            'create' => ns()->url( 'dashboard/' . 'customers/coupons/create' ),
            'edit' => ns()->url( 'dashboard/' . 'customers/coupons/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.coupons' ),
            'put' => ns()->url( 'api/crud/' . 'ns.coupons/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
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

    public function hook( $query ): void
    {
        $query->orderBy( 'created_at', 'desc' );
    }

    /**
     * get exports
     **/
    public function getExports(): array
    {
        return [];
    }
}
