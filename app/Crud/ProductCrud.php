<?php

namespace App\Crud;

use App\Events\ProductBeforeDeleteEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\TaxService;
use App\Services\Users;
use Exception;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProductCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table = 'nexopos_products';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.products';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.products';

    /**
     * Model Used
     */
    protected $model = Product::class;

    /**
     * Will prepend the system options.
     */
    protected $prependOptions = true;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users as user', 'nexopos_products.author', '=', 'user.id' ],
        'leftJoin' => [
            [ 'nexopos_products_categories as category', 'nexopos_products.category_id', '=', 'category.id' ],
            [ 'nexopos_products as parent', 'nexopos_products.parent_id', '=', 'parent.id' ],
            [ 'nexopos_taxes_groups as taxes_groups', 'nexopos_products.tax_group_id', '=', 'taxes_groups.id' ],
        ],
    ];

    protected $pick = [
        'parent' => [ 'name' ],
        'user' => [ 'username' ],
        'category' => [ 'name' ],
    ];

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => 'nexopos.create.products',
        'read' => 'nexopos.read.products',
        'update' => 'nexopos.update.products',
        'delete' => 'nexopos.delete.products',
    ];

    /**
     * Define where statement
     *
     * @var  array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var  array
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

    /**
     * protected tax service
     *
     * @param TaxService
     */
    protected $taxService;

    /**
     * Define Constructor
     *
     * @param
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );

        $this->taxService = app()->make( TaxService::class );
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return  array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Products List' ),
            'list_description' => __( 'Display all products.' ),
            'no_entry' => __( 'No products has been registered' ),
            'create_new' => __( 'Add a new product' ),
            'create_title' => __( 'Create a new product' ),
            'create_description' => __( 'Register a new product and save it.' ),
            'edit_title' => __( 'Edit product' ),
            'edit_description' => __( 'Modify  Product.' ),
            'back_to_list' => __( 'Return to Products' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     *
     * @return  bool
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Fields
     *
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null )
    {
        $groups = UnitGroup::get();

        if ( $entry instanceof Product ) {
            $unitGroup = UnitGroup::where( 'id', $entry->unit_group )->with( 'units' )->first() ?? [];
            $units = UnitGroup::find( $entry->unit_group )->units;
        } else {
            $unitGroup = $groups->first();
            $units = collect([]);

            if ( $unitGroup instanceof UnitGroup ) {
                $units = UnitGroup::find( $unitGroup->id )->units;
            }
        }

        $fields = [
            [
                'type' => 'select',
                'errors' => [],
                'name' => 'unit_id',
                'options' => Helper::toJsOptions( $units, [ 'id', 'name' ] ),
                'label' => __( 'Assigned Unit' ),
                'description' => __( 'The assigned unit for sale' ),
                'validation' => 'required',
                'value' => ! $units->isEmpty() ? $units->first()->id : '',
            ], [
                'type' => 'number',
                'errors' => [],
                'name' => 'sale_price_edit',
                'label' => __( 'Sale Price' ),
                'description' => __( 'Define the regular selling price.' ),
                'validation' => 'required',
            ], [
                'type' => 'number',
                'errors' => [],
                'name' => 'wholesale_price_edit',
                'label' => __( 'Wholesale Price' ),
                'description' => __( 'Define the wholesale price.' ),
                'validation' => 'required',
            ], [
                'type' => 'switch',
                'errors' => [],
                'name' => 'stock_alert_enabled',
                'label' => __( 'Stock Alert' ),
                'options' => Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                'description' => __( 'Define whether the stock alert should be enabled for this unit.' ),
            ], [
                'type' => 'number',
                'errors' => [],
                'name' => 'low_quantity',
                'label' => __( 'Low Quantity' ),
                'description' => __( 'Which quantity should be assumed low.' ),
            ], [
                'type' => 'media',
                'errors' => [],
                'name' => 'preview_url',
                'label' => __( 'Preview Url' ),
                'description' => __( 'Provide the preview of the current unit.' ),
            ], [
                'type' => 'hidden',
                'errors' => [],
                'name' => 'id',
            ], [
                'type' => 'hidden',
                'errors' => [],
                'name' => 'quantity',
            ],
        ];

        return Hook::filter( 'ns-products-crud-form', [
            'main' => [
                'label' => __( 'Name' ),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'validation' => 'required',
                'description' => __( 'Provide a name to the resource.' ),
            ],
            'variations' => [
                [
                    'id' => $entry->id ?? '',
                    'tabs' => [
                        'identification' => [
                            'label' => __( 'Identification' ),
                            'fields' => [
                                [
                                    'type' => 'text',
                                    'name' => 'name',
                                    'description' => __( 'Product unique name. If it\' variation, it should be relevant for that variation' ),
                                    'label' => __( 'Name' ),
                                    'validation' => 'required',
                                    'value' => $entry->name ?? '',
                                ], [
                                    'type' => 'select',
                                    'description' => __( 'Select to which category the item is assigned.' ),
                                    'options' => Helper::toJsOptions( ProductCategory::get(), [ 'id', 'name' ]),
                                    'name' => 'category_id',
                                    'label' => __( 'Category' ),
                                    'validation' => 'required',
                                    'value' => $entry->category_id ?? '',
                                ], [
                                    'type' => 'text',
                                    'name' => 'barcode',
                                    'description' => __( 'Define the barcode value. Focus the cursor here before scanning the product.' ),
                                    'label' => __( 'Barcode' ),
                                    'validation' => '',
                                    'value' => $entry->barcode ?? '',
                                ], [
                                    'type' => 'text',
                                    'name' => 'sku',
                                    'description' => __( 'Define a unique SKU value for the product.' ),
                                    'label' => __( 'SKU' ),
                                    'validation' => '',
                                    'value' => $entry->sku ?? '',
                                ], [
                                    'type' => 'select',
                                    'description' => __( 'Define the barcode type scanned.' ),
                                    'options' => Helper::kvToJsOptions([
                                        'ean8' => __( 'EAN 8' ),
                                        'ean13' => __( 'EAN 13' ),
                                        'codabar' => __( 'Codabar' ),
                                        'code128' => __( 'Code 128' ),
                                        'code39' => __( 'Code 39' ),
                                        'code11' => __( 'Code 11' ),
                                        'upca' => __( 'UPC A' ),
                                        'upce' => __( 'UPC E' ),
                                    ]),
                                    'name' => 'barcode_type',
                                    'label' => __( 'Barcode Type' ),
                                    'validation' => 'required',
                                    'value' => $entry->barcode_type ?? 'code128',
                                ], [
                                    'type' => 'switch',
                                    'description' => __( 'Determine if the product can be searched on the POS.' ),
                                    'options' => Helper::kvToJsOptions([
                                        1 => __( 'Yes' ),
                                        0 => __( 'No' ),
                                    ]),
                                    'name' => 'searchable',
                                    'label' => __( 'Searchable' ),
                                    'value' => $entry->searchable ?? 1,
                                ], [
                                    'type' => 'select',
                                    'options' => Helper::kvToJsOptions( Hook::filter( 'ns-products-type', [
                                        'materialized' => __( 'Materialized Product' ),
                                        'dematerialized' => __( 'Dematerialized Product' ),
                                        'grouped' => __( 'Grouped Product' ),
                                    ] ) ),
                                    'description' => __( 'Define the product type. Applies to all variations.' ),
                                    'name' => 'type',
                                    'validation' => 'required',
                                    'label' => __( 'Product Type' ),
                                    'value' => $entry->type ?? 'materialized',
                                ], [
                                    'type' => 'select',
                                    'options' => Helper::kvToJsOptions([
                                        'available' => __( 'On Sale' ),
                                        'unavailable' => __( 'Hidden' ),
                                    ]),
                                    'description' => __( 'Define whether the product is available for sale.' ),
                                    'name' => 'status',
                                    'validation' => 'required',
                                    'label' => __( 'Status' ),
                                    'value' => $entry->status ?? 'available',
                                ], [
                                    'type' => 'switch',
                                    'options' => Helper::kvToJsOptions([
                                        'enabled' => __( 'Yes' ),
                                        'disabled' => __( 'No' ),
                                    ]),
                                    'description' => __( 'Enable the stock management on the product. Will not work for service or uncountable products.' ),
                                    'name' => 'stock_management',
                                    'label' => __( 'Stock Management Enabled' ),
                                    'validation' => 'required',
                                    'value' => $entry->stock_management ?? 'enabled',
                                ], [
                                    'type' => 'textarea',
                                    'name' => 'description',
                                    'label' => __( 'Description' ),
                                    'value' => $entry->description ?? '',
                                ],
                            ],
                        ],
                        'groups' => [
                            'label' => __( 'Groups' ),
                            'fields' => [
                                [
                                    'type' => 'hidden',
                                    'name' => 'product_subitems',
                                    'value' => $entry !== null ? $entry->sub_items()->get()->map( function( $subItem ) {
                                        $subItem->load( 'product.unit_quantities.unit' );

                                        return [
                                            '_quantity_toggled' => false,
                                            '_price_toggled' => false,
                                            '_unit_toggled' => false,
                                            'id' => $subItem->id,
                                            'name' => $subItem->product->name,
                                            'unit_quantity_id' => $subItem->unit_quantity_id,
                                            'unit_quantity' => $subItem->unit_quantity,
                                            'product_id' => $subItem->product_id,
                                            'parent_id' => $subItem->parent_id,
                                            'unit_id' => $subItem->unit_id,
                                            'unit' => $subItem->unit,
                                            'quantity' => $subItem->quantity,
                                            'unit_quantities' => $subItem->product->unit_quantities,
                                            'sale_price' => $subItem->sale_price,
                                        ];
                                    }) : [],
                                ],
                            ],
                            'component' => 'nsProductGroup',
                        ],
                        'units' => [
                            'label' => __( 'Units' ),
                            'fields' => [
                                [
                                    'type' => 'switch',
                                    'description' => __( 'The product won\'t be visible on the grid and fetched only using the barcode reader or associated barcode.' ),
                                    'options' => Helper::kvToJsOptions([
                                        1 => __( 'Yes' ),
                                        0 => __( 'No' ),
                                    ]),
                                    'name' => 'accurate_tracking',
                                    'label' => __( 'Accurate Tracking' ),
                                    'value' => $entry->accurate_tracking ?? 0,
                                ], [
                                    'type' => 'select',
                                    'options' => Helper::toJsOptions( $groups, [ 'id', 'name' ] ),
                                    'name' => 'unit_group',
                                    'description' => __( 'What unit group applies to the actual item. This group will apply during the procurement.' ),
                                    'label' => __( 'Unit Group' ),
                                    'validation' => 'required',
                                    'value' => $entry->unit_group ?? ( ! $groups->isEmpty() ? $groups->first()->id : '' ),
                                ], [
                                    'type' => 'group',
                                    'name' => 'selling_group',
                                    'description' => __( 'Determine the unit for sale.' ),
                                    'label' => __( 'Selling Unit' ),
                                    'fields' => $fields,

                                    /**
                                     * We make sure to popular the unit quantity
                                     * with the entry values using the fields array.
                                     */
                                    'groups' => ( $entry instanceof Product ? ProductUnitQuantity::withProduct( $entry->id )
                                        ->get()
                                        ->map( function( $productUnitQuantity ) use ( $fields ) {
                                            return collect( $fields )->map( function( $field ) use ( $productUnitQuantity ) {
                                                $field[ 'value' ] = $productUnitQuantity->{ $field[ 'name' ] };

                                                return $field;
                                            });
                                        }) : [] ),
                                    'options' => $entry instanceof Product ? UnitGroup::find( $entry->unit_group )->units : [],
                                ],
                            ],
                        ],
                        'expiracy' => [
                            'label' => __( 'Expiry' ),
                            'fields' => [
                                [
                                    'type' => 'switch',
                                    'name' => 'expires',
                                    'validation' => 'required',
                                    'label' => __( 'Product Expires' ),
                                    'options' => Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                                    'description' => __( 'Set to "No" expiration time will be ignored.' ),
                                    'value' => ( $entry !== null && $entry->expires ? 1 : 0 ),
                                ], [
                                    'type' => 'select',
                                    'options' => Helper::kvToJsOptions([
                                        'prevent_sales' => __( 'Prevent Sales' ),
                                        'allow_sales' => __( 'Allow Sales' ),
                                    ]),
                                    'description' => __( 'Determine the action taken while a product has expired.' ),
                                    'name' => 'on_expiration',
                                    'label' => __( 'On Expiration' ),
                                    'value' => $entry->on_expiration ?? 'prevent-sales',
                                ],
                            ],
                        ],
                        'taxes' => [
                            'label' => __( 'Taxes' ),
                            'fields' => [
                                [
                                    'type' => 'select',
                                    'options' => Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ], [
                                        null => __( 'Choose Group' ),
                                    ]),
                                    'description' => __( 'Select the tax group that applies to the product/variation.' ),
                                    'name' => 'tax_group_id',
                                    'label' => __( 'Tax Group' ),
                                    'value' => $entry->tax_group_id ?? '',
                                ], [
                                    'type' => 'select',
                                    'options' => Helper::kvToJsOptions([
                                        'inclusive' => __( 'Inclusive' ),
                                        'exclusive' => __( 'Exclusive' ),
                                    ]),
                                    'description' => __( 'Define what is the type of the tax.' ),
                                    'name' => 'tax_type',
                                    'label' => __( 'Tax Type' ),
                                    'value' => $entry->tax_type ?? 'inclusive',
                                ],
                            ],
                        ],
                        'images' => [
                            'label' => __( 'Images' ),
                            'fields' => [
                                [
                                    'type' => 'media',
                                    'name' => 'url',
                                    'label' => __( 'Image' ),
                                    'description' => __( 'Choose an image to add on the product gallery' ),
                                ], [
                                    'type' => 'switch',
                                    'name' => 'featured',
                                    'options' => Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                                    'label' => __( 'Is Primary' ),
                                    'description' => __( 'Define whether the image should be primary. If there are more than one primary image, one will be chosen for you.' ),
                                ],
                            ],
                            'groups' => $entry ? $entry->galleries->map( function( $gallery ) {
                                return [
                                    [
                                        'type' => 'media',
                                        'name' => 'url',
                                        'label' => __( 'Image' ),
                                        'value' => $gallery->url,
                                        'description' => __( 'Choose an image to add on the product gallery' ),
                                    ], [
                                        'type' => 'switch',
                                        'name' => 'featured',
                                        'options' => Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                                        'label' => __( 'Is Primary' ),
                                        'value' => (int) $gallery->featured,
                                        'description' => __( 'Define whether the image should be primary. If there are more than one primary image, one will be chosen for you.' ),
                                    ],
                                ];
                            }) : [],
                        ],
                    ],
                ],
            ],
        ], $entry );
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, Product $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request $request
     * @return  void
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
     * @param  Product $entry
     * @return  void
     */
    public function afterPost( $request, Product $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param  string
     * @return  mixed
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
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function beforePut( $request, $entry )
    {
        $this->allowedTo( 'update' );

        return $request;
    }

    /**
     * After updating a record
     *
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function afterPut( $request, Product $product )
    {
        /**
         * delete all assigned taxes as it
         * be newly assigned
         */
        if ( $product instanceof Product ) {
            $product->taxes()->delete();
        }

        return $request;
    }

    /**
     * Protect an access to a specific crud UI
     *
     * @param  array { namespace, id, type }
     * @return  array | throw Exception
     **/
    public function canAccess( $fields )
    {
        $users = app()->make( Users::class );

        if ( $users->is([ 'admin' ]) ) {
            return [
                'status' => 'success',
                'message' => __( 'The access is granted.' ),
            ];
        }

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.products' ) {
            $this->allowedTo( 'delete' );
        }

        ProductBeforeDeleteEvent::dispatch( $model );

        $this->deleteProductAttachedRelation( $model );
    }

    public function deleteProductAttachedRelation( $model )
    {
        $model->sub_items()->delete();
        $model->galleries()->delete();
        $model->variations()->delete();
        $model->product_taxes()->delete();
        $model->unit_quantities()->delete();
    }

    /**
     * Define Columns
     *
     * @return  array of columns configuration
     */
    public function getColumns()
    {
        return [
            'type' => [
                'label' => __( 'Type' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'name' => [
                'label' => __( 'Name' ),
                '$direction' => '',
                'width' => '150px',
                '$sort' => false,
            ],
            'sku' => [
                'label' => __( 'Sku' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'category_name' => [
                'label' => __( 'Category' ),
                'width' => '150px',
                '$direction' => '',
                '$sort' => false,
            ],
            'status' => [
                'label' => __( 'Status' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __( 'Author' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'Date' ),
                'width' => '150px',
                '$direction' => '',
                '$sort' => false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry, $namespace )
    {
        $class = match ( $entry->type ) {
            'grouped' => 'text-success-tertiary',
            default => 'text-info-tertiary'
        };

        $entry->type = match ( $entry->type ) {
            'materialized' => __( 'Materialized' ),
            'dematerialized' => __( 'Dematerialized' ),
            'grouped' => __( 'Grouped' ),
            default => sprintf( __( 'Unknown Type: %s' ), $entry->type ),
        };

        $entry->type = '<strong class="' . $class . ' ">' . $entry->type . '</strong>';

        $entry->stock_management = $entry->stock_management === 'enabled' ? __( 'Enabled' ) : __( 'Disabled' );
        $entry->status = $entry->status === 'available' ? __( 'Available' ) : __( 'Hidden' );
        $entry->category_name = $entry->category_name ?: __( 'Unassigned' );
        // you can make changes here
        $entry->addAction( 'edit', [
            'label' => '<i class="mr-2 las la-edit"></i> ' . __( 'Edit' ),
            'namespace' => 'edit',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'products' . '/edit/' . $entry->id ),
        ]);

        $entry->addAction( 'ns.quantities', [
            'label' => '<i class="mr-2 las la-eye"></i> ' . __( 'Preview' ),
            'namespace' => 'ns.quantities',
            'type' => 'POPUP',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'products' . '/edit/' . $entry->id ),
        ]);

        $entry->addAction( 'units', [
            'label' => '<i class="mr-2 las la-balance-scale-left"></i> ' . __( 'See Quantities' ),
            'namespace' => 'units',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'products/' . $entry->id . '/units' ),
        ]);

        $entry->addAction( 'history', [
            'label' => '<i class="mr-2 las la-history"></i> ' . __( 'See History' ),
            'namespace' => 'history',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'products/' . $entry->id . '/history' ),
        ]);

        $entry->addAction( 'delete', [
            'label' => '<i class="mr-2 las la-trash"></i> ' . __( 'Delete' ),
            'namespace' => 'delete',
            'type' => 'DELETE',
            'url' => ns()->url( '/api/nexopos/v4/crud/ns.products/' . $entry->id ),
            'confirm' => [
                'message' => __( 'Would you like to delete this ?' ),
            ],
        ]);

        return $entry;
    }

    public function hook( $query ): void
    {
        $query->orderBy( 'updated_at', 'desc' );
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction( Request $request )
    {
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
                'failed' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof Product ) {
                    $this->deleteProductAttachedRelation( $entity );
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }

            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     *
     * @return  array of links
     */
    public function getLinks(): array
    {
        return  [
            'list' => ns()->url( 'dashboard/' . 'products' ),
            'create' => ns()->url( 'dashboard/' . 'products/create' ),
            'edit' => ns()->url( 'dashboard/' . 'products/edit/' ),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return  array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label' => __( 'Delete Selected Groups' ),
                'identifier' => 'delete_selected',
                'confirm' => __( 'Would you like to delete selected entries ?' ),
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ]);
    }

    /**
     * get exports
     *
     * @return  array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
