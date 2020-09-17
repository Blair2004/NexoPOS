<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTax;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\Helper;

class ProductCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_products';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.products';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.products';

    /**
     * Model Used
     */
    protected $model      =   Product::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'nexopos_products.author', '=', 'user.id' ],
        [ 'nexopos_products_categories as category', 'nexopos_products.category_id', '=', 'category.id' ],
        'leftJoin'  =>  [
            [ 'nexopos_products as parent', 'nexopos_products.parent_id', '=', 'parent.id' ],
            [ 'nexopos_taxes_groups as taxes_groups', 'nexopos_products.tax_group_id', '=', 'taxes_groups.id' ],
        ],
    ];

    protected $pick     =   [
        'parent'    =>  [ 'name' ],
        'user'      =>  [ 'username' ],
        'category'  =>  [ 'name' ],
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

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

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
            'list_title'            =>  __( 'Products List' ),
            'list_description'      =>  __( 'Display all products.' ),
            'no_entry'              =>  __( 'No products has been registered' ),
            'create_new'            =>  __( 'Add a new product' ),
            'create_title'          =>  __( 'Create a new product' ),
            'create_description'    =>  __( 'Register a new product and save it.' ),
            'edit_title'            =>  __( 'Edit product' ),
            'edit_description'      =>  __( 'Modify  Product.' ),
            'back_to_list'          =>  __( 'Return to Products' ),
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

    /**
     * Fields
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null ) 
    {
        if ( $entry instanceof Product ) {
            $unitGroup              =   UnitGroup::where( 'id', $entry->unit_group )->with( 'units' )->first() ?? [];
        } else {
            $unitGroup      =   null;
        }

        $groups             =   UnitGroup::get();

        return [
            'main' =>  [
                'label'         =>  __( 'Name' ),
                'name'          =>  'name',
                'value'         =>  $entry->name ?? '',
                'validation'    =>  'required',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'variations'    =>  [
                [
                    'id'    =>  $entry->id ?? '',
                    'tabs'  =>  [
                        'identification'   =>  [
                            'label'     =>  __( 'Identification' ),
                            'fields'    =>  [
                                [
                                    'type'  =>  'text',
                                    'name'  =>  'name',
                                    'description'   =>  __( 'Product unique name. If it\' variation, it should be relevant for that variation' ),
                                    'label' =>  __( 'Name' ),
                                    'validation'    =>  'required',
                                    'value' =>  $entry->name ?? '',
                                ], [
                                    'type'  =>  'text',
                                    'name'  =>  'barcode',
                                    'description'   =>  __( 'Define the barcode value. Focus the cursor here before scanning the product.' ),
                                    'label' =>  __( 'Barcode' ),
                                    'validation'    =>  'required',
                                    'value' =>  $entry->barcode ?? '',
                                ], [
                                    'type'  =>  'select',
                                    'description'   =>  __( 'Define the barcode type scanned.' ),
                                    'options'   =>  Helper::kvToJsOptions([
                                        'ean8'      =>  __( 'EAN 8' ),
                                        'ean13'     =>  __( 'EAN 13' ),
                                        'codabar'   =>  __( 'Codeabar' ),
                                    ]),
                                    'name'  =>  'barcode_type',
                                    'label' =>  __( 'Barcode Type' ),
                                    'validation'    =>  'required',
                                    'value' =>  $entry->barcode_type ?? 'ean8',
                                ], [
                                    'type'      =>  'select',
                                    'description'   =>  __( 'Select to which category the item is assigned.' ),
                                    'options'   =>  Helper::toJsOptions( ProductCategory::get(), [ 'id', 'name' ]),
                                    'name'      =>  'category_id',
                                    'label'     =>  __( 'Category' ),
                                    'value'     =>  $entry->category_id ?? '',
                                ], [
                                    'type'          =>  'select',
                                    'options'       =>  Helper::kvToJsOptions([
                                        'materialized'      =>  __( 'Materialized Product' ),
                                        'dematerialized'    =>  __( 'Dematerialized Product' ),
                                    ]),
                                    'description'   =>  __( 'Define the product type. Applies to all variations.' ),
                                    'name'          =>  'product_type',
                                    'validation'    =>  'required',
                                    'label'         =>  __( 'Product Type' ),
                                    'value'         =>  $entry->product_type ?? 'materialized',
                                ], [
                                    'type'  =>  'text',
                                    'name'  =>  'sku',
                                    'description'   =>  __( 'Define a unique SKU value for the product.' ),
                                    'label' =>  __( 'SKU' ),
                                    'validation'    =>  'required',
                                    'value' =>  $entry->sku ?? '',
                                ], [
                                    'type'  =>  'select',
                                    'options'   =>  Helper::kvToJsOptions([
                                        'available'     =>  __( 'On Sale' ),
                                        'unavailable'   =>  __( 'Hidden' ),
                                    ]),
                                    'description'   =>  __( 'Define wether the product is available for sale.' ),
                                    'name'  =>  'status',
                                    'validation'    =>  'required',
                                    'label' =>  __( 'Status' ),
                                    'value' =>  $entry->status ?? 'available',
                                ], [
                                    'type'      =>  'switch',
                                    'options'   =>  Helper::kvToJsOptions([
                                        'enabled'   =>  __( 'Yes' ),
                                        'disabled'  =>  __( 'No' ),
                                    ]),
                                    'description'   =>  __( 'Enable the stock management on the product. Will not work for service or uncountable products.' ),
                                    'name'  =>  'stock_management',
                                    'label' =>  __( 'Stock Management Enabled' ),
                                    'validation'    =>  'required',
                                    'value' =>  $entry->stock_management ?? 'enabled',
                                ], [
                                    'type'  =>  'textarea',
                                    'name'  =>  'description',
                                    'label' =>  __( 'Description' ),
                                    'value' =>  $entry->description ?? '',
                                ],                 
                            ]
                        ],
                        'units'     =>  [
                            'label' =>  __( 'Units' ),
                            'fields'    =>  [
                                [
                                    'type'          =>  'select',
                                    'options'       =>  Helper::toJsOptions( $groups, [ 'id', 'name' ] ),
                                    'name'          =>  'unit_group',
                                    'description'   =>  __( 'What unit group applies to the actual item' ),
                                    'label'         =>  __( 'Unit Group' ),
                                    'validation'    =>  'required',
                                    'value'         =>  $entry->unit_group ?? '',
                                ], [
                                    'type'  =>  'multiselect',
                                    'options'   =>  ! $unitGroup instanceof UnitGroup ? [] : $unitGroup->units->map( function( $unit ) use ( $entry ) {
                                        return [
                                            'label'     =>  $unit->name,
                                            'value'     =>  $unit->id,
                                            'selected'  =>  ! empty( $entry->purchase_unit_ids ) ? in_array( $unit->id, json_decode( $entry->purchase_unit_ids, true ) ) : false,
                                        ];
                                    }),
                                    'name'  =>  'purchase_unit_ids',
                                    'label' =>  __( 'Purchase Unit' ),
                                    'description'    =>  __( 'Define the unit or units used while purchasing' ),
                                    'value' =>  ! empty( $entry->purchase_unit_ids ) ? json_decode( $entry->purchase_unit_ids, true ) : '',
                                ], [
                                    'type'  =>  'multiselect',
                                    'options'   =>  ! $unitGroup instanceof UnitGroup ? [] : $unitGroup->units->map( function( $unit ) use ( $entry ) {
                                        return [
                                            'label'     =>  $unit->name,
                                            'value'     =>  $unit->id,
                                            'selected'  =>  ! empty( $entry->selling_unit_ids ) ? in_array( $unit->id, json_decode( $entry->selling_unit_ids, true ) ) : false,
                                        ];
                                    }),
                                    'name'  =>  'selling_unit_ids',
                                    'label' =>  __( 'Selling Unit' ),
                                    'description'   =>  __( 'Define the unit or units used for sale' ),
                                    'value' =>  ! empty( $entry->selling_unit_ids ) ? json_decode( $entry->selling_unit_ids, true ) : '',
                                ], [
                                    'type'  =>  'multiselect',
                                    'options'   =>  ! $unitGroup instanceof UnitGroup ? [] : $unitGroup->units->map( function( $unit ) use ( $entry ) {
                                        return [
                                            'label'     =>  $unit->name,
                                            'value'     =>  $unit->id,
                                            'selected'  =>  ! empty( $entry->transfer_unit_ids ) ? in_array( $unit->id, json_decode( $entry->transfer_unit_ids, true ) ) : false,
                                        ];
                                    }),
                                    'name'  =>  'transfer_unit_ids',
                                    'label' =>  __( 'Transfer Unit' ),
                                    'description'   =>  __( 'Define the unit or units used for transfer' ),
                                    'value' =>  ! empty( $entry->transfer_unit_ids ) ? json_decode( $entry->transfer_unit_ids, true ) : '',
                                ], 
                            ]
                        ],
                        'expiracy'      =>  [
                            'label'     =>  __( 'Expiracy' ),
                            'fields'    =>  [
                                [
                                    'type'          =>  'switch',
                                    'name'          =>  'expires',
                                    'validation'    =>  'required',
                                    'label'         =>  __( 'Product Expires' ),
                                    'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                                    'description'   =>  __( 'Set to "No" expiration time will be ignored.' ),
                                    'value'         =>  ( $entry !== null && $entry->expires ? 1 : 0 ),
                                ], [
                                    'type'          =>  'date',
                                    'name'          =>  'expiration',
                                    'label'         =>  __( 'Expiration' ),
                                    'description'   =>  __( 'Define when the product expires' ),
                                    'value'         =>  $entry->expiration ?? '',
                                ], [
                                    'type'              =>  'select',
                                    'options'           =>  Helper::kvToJsOptions([
                                        'prevent_sales' =>  __( 'Prevent Sales' ),
                                        'allow_sales'   =>  __( 'Allow Sales' ),
                                    ]),
                                    'description'       =>  __( 'Determine the action taken while a product has expired.' ),
                                    'name'              =>  'on_expiration',
                                    'label'             =>  __( 'On Expiration' ),
                                    'value'             =>  $entry->on_expiration ?? 'prevent-sales',
                                ]
                            ]
                        ],
                        'prices'    =>  [
                            'label' =>  __( 'Price & Taxes' ),
                            'fields'    =>  [
                                [
                                    'type'  =>  'text',
                                    'name'  =>  'sale_price_edit',
                                    'label' =>  __( 'Sale Price' ),
                                    'validation'    =>  'required',
                                    'description'   =>  __( 'Define the sale price.' ),
                                    'value' =>  $entry->sale_price_edit ?? '',
                                    'extra' =>  $entry->sale_price ?? 0
                                ], [
                                    'type'  =>  'text',
                                    'name'  =>  'wholesale_price_edit',
                                    'label' =>  __( 'WholeSale Price' ),
                                    'validation'    =>  'required',
                                    'description'   =>  __( 'Define the wholesale price.' ),
                                    'value' =>  $entry->wholesale_price_edit ?? '',
                                    'extra' =>  $entry->wholesale_price ?? 0
                                ], [
                                    'type'  =>  'select',
                                    'options'   =>  Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ]),
                                    'description'   =>  __( 'Select the tax group that applies to the product/variation.' ),
                                    'name'  =>  'tax_group_id',
                                    'label' =>  __( 'Tax Group' ),
                                    'value' =>  $entry->tax_group_id ?? '',
                                ], [
                                    'type'  =>  'select',
                                    'options'   =>  Helper::kvToJsOptions([
                                        'inclusive' =>  __( 'Inclusive' ),
                                        'exclusive' =>  __( 'Exclusive' ),
                                    ]),
                                    'description'   =>  __( 'Define what is the type of the tax.' ),
                                    'name'  =>  'tax_type',
                                    'label' =>  __( 'Tax Type' ),
                                    'value' =>  $entry->tax_type ?? 'inclusive',
                                ], 
                            ]
                        ],
                    ]
                ]
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
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, Product $entry )
    {
        return $inputs;
    }

    /**
     * Will only calculate taxes
     * @param array $fields
     * @return array $fields
     */
    private function calculateTaxes( $inputs, Product $product = null )
    {
        $taxGroup                       =   TaxGroup::find( $inputs[ 'tax_id' ] );
        $inputs[ 'net_sale_price' ]     =   $inputs[ 'net_sale_price_edit' ];

        /**
         * calculate the taxes wether they are all
         * inclusive or exclusive
         */
        if ( $taxGroup instanceof TaxGroup ) {
            $taxValue       =   $taxGroup->taxes
                ->map( function( $tax ) use ( $inputs, $product ) {
                    $taxValue           =   ( floatval( $tax[ 'rate' ] ) * $inputs[ 'net_sale_price_edit' ] ) / 100;

                    ProductTax::create([
                        'product_id'    =>  $product->id,
                        'tax_id'        =>  $tax->id,
                        'rate'          =>  $tax->rate,
                        'name'          =>  $tax->name,
                        'author'        =>  Auth::id(),
                        'value'         =>  $taxValue
                    ]);

                    return $taxValue;
                })
                ->sum();

            if ( $inputs[ 'tax_type' ] === 'inclusive' ) {
                $inputs[ 'gross_sale_price' ]       =   floatval( $inputs[ 'net_sale_price_edit' ] ) - $taxValue;
            } else {
                $inputs[ 'gross_sale_price' ]       =   floatval( $inputs[ 'net_sale_price_edit' ] );
                $inputs[ 'net_sale_price' ]         =   floatval( $inputs[ 'net_sale_price_edit' ] ) + $taxValue;
            }
        }

        return $inputs;
    }

    /**
     * Before saving a record
     * @param  Request $request
     * @return  void
     */
    public function beforePost( $request )
    {
        return $request;
    }

    /**
     * After saving a record
     * @param  Request $request
     * @param  Product $entry
     * @return  void
     */
    public function afterPost( $request, Product $entry )
    {
        $this->calculateTaxes( $request->all(), $entry );

        return $request;
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
     * Before updating a record
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function beforePut( $request, $entry )
    {
        return $request;
    }

    /**
     * After updating a record
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

        $this->calculateTaxes( $request->all(), $product );

        return $request;
    }
    
    /**
     * Protect an access to a specific crud UI
     * @param  array { namespace, id, type }
     * @return  array | throw Exception
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
    public function beforeDelete( $namespace, $id, $model ) {
        if ( $namespace == 'ns.products' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
            **/
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                'width'         =>  '150px',
                '$sort'         =>  false
            ],
            'sku'               =>  [
                'label'         =>  __( 'Sku' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'category_name'  =>  [
                'label'  =>  __( 'Category' ),
                'width'         =>  '150px',
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'product_type'  =>  [
                'label'         =>  __( 'Type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'wholesale_price'  =>  [
                'label'         =>  __( 'WholeSale Price' ),
                'width'         =>  '100px',
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'net_sale_price'  =>  [
                'label'         =>  __( 'Net Sale Price' ),
                'width'         =>  '100px',
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'status'  =>  [
                'label'         =>  __( 'Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
                'label'         =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'         =>  __( 'Date' ),
                'width'         =>  '150px',
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        // Don't overwrite
        $entry->{ '$checked' }  =   false;
        $entry->{ '$toggled' }  =   false;
        $entry->{ '$id' }       =   $entry->id;

        $entry->product_type        =   $entry->product_type === 'materialized' ? __( 'Materialized' ) : __( 'Dematerialized' );
        $entry->stock_management    =   $entry->stock_management === 'enabled' ? __( 'Enabled' ) : __( 'Disabled' );
        $entry->status              =   $entry->status === 'available' ? __( 'Available' ) : __( 'Hidden' );
        $entry->sale_price          =   ( string ) ns()->currency->value( $entry->sale_price );
        $entry->wholesale_price     =   ( string ) ns()->currency->value( $entry->wholesale_price );
        $entry->net_sale_price      =   ( string ) ns()->currency->value( $entry->net_sale_price );
        $entry->gross_sale_price    =   ( string ) ns()->currency->value( $entry->gross_sale_price );
        $entry->tax_value           =   ( string ) ns()->currency->value( $entry->tax_value );
        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      url( '/dashboard/' . 'products' . '/edit/' . $entry->id )
            ], [
                'label'         =>      __( 'See Quantities' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      url( '/dashboard/' . 'products/' . $entry->id . '/units' )
            ], [
                'label'         =>      __( 'See History' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      url( '/dashboard/' . 'products/' . $entry->id . '/history' )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       =>  url( '/api/nexopos/v4/crud/ns.products/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                ]
            ]
        ];

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

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Product ) {
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
     * @return  array of links
     */
    public function getLinks()
    {
        return  [
            'list'      =>  'ns.products',
            'create'    =>  'ns.products/create',
            'edit'      =>  'ns.products/edit/#'
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
                'label'         =>  __( 'Delete Selected Groups' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  route( 'crud.bulk-actions', [
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