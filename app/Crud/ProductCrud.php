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
        [ 'nexopos_users', 'nexopos_products.author', '=', 'nexopos_users.id' ],
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
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'author',
                            'label' =>  __( 'Author' ),
                            'value' =>  $entry->author ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'barcode',
                            'label' =>  __( 'Barcode' ),
                            'value' =>  $entry->barcode ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'barcode_type',
                            'label' =>  __( 'Barcode_type' ),
                            'value' =>  $entry->barcode_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'category_id',
                            'label' =>  __( 'Category_id' ),
                            'value' =>  $entry->category_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'created_at',
                            'label' =>  __( 'Created_at' ),
                            'value' =>  $entry->created_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'expiration',
                            'label' =>  __( 'Expiration' ),
                            'value' =>  $entry->expiration ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'gross_sale_price',
                            'label' =>  __( 'Gross_sale_price' ),
                            'value' =>  $entry->gross_sale_price ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'id',
                            'label' =>  __( 'Id' ),
                            'value' =>  $entry->id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'label' =>  __( 'Name' ),
                            'value' =>  $entry->name ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'net_sale_price',
                            'label' =>  __( 'Net_sale_price' ),
                            'value' =>  $entry->net_sale_price ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'on_expiration',
                            'label' =>  __( 'On_expiration' ),
                            'value' =>  $entry->on_expiration ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'parent_id',
                            'label' =>  __( 'Parent_id' ),
                            'value' =>  $entry->parent_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'product_type',
                            'label' =>  __( 'Product_type' ),
                            'value' =>  $entry->product_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'purchase_unit_id',
                            'label' =>  __( 'Purchase_unit_id' ),
                            'value' =>  $entry->purchase_unit_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'purchase_unit_type',
                            'label' =>  __( 'Purchase_unit_type' ),
                            'value' =>  $entry->purchase_unit_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'sale_price',
                            'label' =>  __( 'Sale_price' ),
                            'value' =>  $entry->sale_price ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'sale_price_edit',
                            'label' =>  __( 'Sale_price_edit' ),
                            'value' =>  $entry->sale_price_edit ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'selling_unit_id',
                            'label' =>  __( 'Selling_unit_id' ),
                            'value' =>  $entry->selling_unit_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'selling_unit_type',
                            'label' =>  __( 'Selling_unit_type' ),
                            'value' =>  $entry->selling_unit_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'sku',
                            'label' =>  __( 'Sku' ),
                            'value' =>  $entry->sku ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'status',
                            'label' =>  __( 'Status' ),
                            'value' =>  $entry->status ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'stock_management',
                            'label' =>  __( 'Stock_management' ),
                            'value' =>  $entry->stock_management ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'tax_id',
                            'label' =>  __( 'Tax_id' ),
                            'value' =>  $entry->tax_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'tax_type',
                            'label' =>  __( 'Tax_type' ),
                            'value' =>  $entry->tax_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'tax_value',
                            'label' =>  __( 'Tax_value' ),
                            'value' =>  $entry->tax_value ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'thumbnail_id',
                            'label' =>  __( 'Thumbnail_id' ),
                            'value' =>  $entry->thumbnail_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'transfer_unit_id',
                            'label' =>  __( 'Transfer_unit_id' ),
                            'value' =>  $entry->transfer_unit_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'transfer_unit_type',
                            'label' =>  __( 'Transfer_unit_type' ),
                            'value' =>  $entry->transfer_unit_type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'type',
                            'label' =>  __( 'Type' ),
                            'value' =>  $entry->type ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'updated_at',
                            'label' =>  __( 'Updated_at' ),
                            'value' =>  $entry->updated_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'uuid',
                            'label' =>  __( 'Uuid' ),
                            'value' =>  $entry->uuid ?? '',
                        ],                     ]
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
            'author'  =>  [
                'label'  =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'barcode'  =>  [
                'label'  =>  __( 'Barcode' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'barcode_type'  =>  [
                'label'  =>  __( 'Barcode_type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'category_id'  =>  [
                'label'  =>  __( 'Category_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created_at' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'description'  =>  [
                'label'  =>  __( 'Description' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'expiration'  =>  [
                'label'  =>  __( 'Expiration' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'gross_sale_price'  =>  [
                'label'  =>  __( 'Gross_sale_price' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'id'  =>  [
                'label'  =>  __( 'Id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'net_sale_price'  =>  [
                'label'  =>  __( 'Net_sale_price' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'on_expiration'  =>  [
                'label'  =>  __( 'On_expiration' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'parent_id'  =>  [
                'label'  =>  __( 'Parent_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'product_type'  =>  [
                'label'  =>  __( 'Product_type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'purchase_unit_id'  =>  [
                'label'  =>  __( 'Purchase_unit_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'purchase_unit_type'  =>  [
                'label'  =>  __( 'Purchase_unit_type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'sale_price'  =>  [
                'label'  =>  __( 'Sale_price' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'sale_price_edit'  =>  [
                'label'  =>  __( 'Sale_price_edit' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'selling_unit_id'  =>  [
                'label'  =>  __( 'Selling_unit_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'selling_unit_type'  =>  [
                'label'  =>  __( 'Selling_unit_type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'sku'  =>  [
                'label'  =>  __( 'Sku' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'status'  =>  [
                'label'  =>  __( 'Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'stock_management'  =>  [
                'label'  =>  __( 'Stock_management' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'tax_id'  =>  [
                'label'  =>  __( 'Tax_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'tax_type'  =>  [
                'label'  =>  __( 'Tax_type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'tax_value'  =>  [
                'label'  =>  __( 'Tax_value' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'thumbnail_id'  =>  [
                'label'  =>  __( 'Thumbnail_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'transfer_unit_id'  =>  [
                'label'  =>  __( 'Transfer_unit_id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'transfer_unit_type'  =>  [
                'label'  =>  __( 'Transfer_unit_type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'type'  =>  [
                'label'  =>  __( 'Type' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'updated_at'  =>  [
                'label'  =>  __( 'Updated_at' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'uuid'  =>  [
                'label'  =>  __( 'Uuid' ),
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