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
use App\Models\TaxGroup;
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
        'leftJoin'  =>  [ 'nexopos_products as parent', 'nexopos_products.parent_id', '=', 'parent.id' ],
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
                    'id'    =>  '',
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
                                    'type'  =>  'select',
                                    'options'   =>  Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ] ),
                                    'name'  =>  'purchase_unit_group',
                                    'description'    =>  __( 'Define the unit group used for purchasing' ),
                                    'label' =>  __( 'Purchase Group' ),
                                    'validation'    =>  'required',
                                    'value' =>  $entry->purchase_unit_type ?? '',
                                ], [
                                    'type'  =>  'multiselect',
                                    'options'   =>  [],
                                    'name'  =>  'purchase_unit_ids',
                                    'label' =>  __( 'Purchase Unit' ),
                                    'description'    =>  __( 'Define the unit or units used while purchasing' ),
                                    'value' =>  $entry->purchase_unit_ids ?? '',
                                ], [
                                    'type'  =>  'select',
                                    'options'   =>  Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ] ),
                                    'name'  =>  'selling_unit_group',
                                    'label' =>  __( 'Selling Group' ),
                                    'validation'    =>  'required',
                                    'description'   =>  __( 'Define the unit group used for sale' ),
                                    'value' =>  $entry->selling_unit_type ?? '',
                                ], [
                                    'type'  =>  'multiselect',
                                    'options'   =>  [],
                                    'name'  =>  'selling_unit_ids',
                                    'label' =>  __( 'Selling Unit' ),
                                    'description'   =>  __( 'Define the unit or units used for sale' ),
                                    'value' =>  $entry->selling_unit_ids ?? '',
                                ], [
                                    'type'  =>  'select',
                                    'options'   =>  Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ] ),
                                    'name'  =>  'transfer_unit_group',
                                    'label' =>  __( 'Transfer Group' ),
                                    'validation'    =>  'required',
                                    'description'   =>  __( 'Define the unit group used for transfer' ),
                                    'value' =>  $entry->transfer_unit_type ?? '',
                                ], [
                                    'type'  =>  'multiselect',
                                    'options'   =>  [],
                                    'name'  =>  'transfer_unit_ids',
                                    'label' =>  __( 'Transfer Unit' ),
                                    'description'   =>  __( 'Define the unit or units used for transfer' ),
                                    'value' =>  $entry->transfer_unit_ids ?? '',
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
                                    'description'   =>  __( 'Define the sale price excluding taxes.' ),
                                    'value' =>  $entry->sale_price_edit ?? '',
                                    'extra' =>  $entry->sale_price ?? 0
                                ], [
                                    'type'  =>  'select',
                                    'options'   =>  Helper::toJsOptions( TaxGroup::get(), [ 'id', 'name' ]),
                                    'description'   =>  __( 'Select the tax group that applies to the product/variation.' ),
                                    'name'  =>  'tax_id',
                                    'label' =>  __( 'Tax' ),
                                    'value' =>  $entry->tax_id ?? '',
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
    public function afterPut( $request, $entry )
    {
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
                '$sort'         =>  false
            ],
            'sku'               =>  [
                'label'         =>  __( 'Sku' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'category_name'  =>  [
                'label'  =>  __( 'Category' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'parent_id'  =>  [
                'label'         =>  __( 'Parent' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'product_type'  =>  [
                'label'         =>  __( 'Product Type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'sale_price'  =>  [
                'label'         =>  __( 'Sale Price' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'net_sale_price'  =>  [
                'label'         =>  __( 'Net Sale Price' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'stock_management'  =>  [
                'label'         =>  __( 'Stock Mngmt' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'type'  =>  [
                'label'         =>  __( 'Type' ),
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
                'label'         =>  __( 'Created At' ),
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
        $entry->net_sale_price      =   ( string ) ns()->currency->value( $entry->net_sale_price );
        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      url( '/dashboard/' . '' . '/edit/' . $entry->id )
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