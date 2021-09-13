<?php
namespace App\Crud;

use App\Events\ProductCategoryAfterCreatedEvent;
use App\Events\ProductCategoryAfterUpdatedEvent;
use App\Events\ProductCategoryBeforeDeletedEvent;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\ProductCategory;
use App\Services\Helper;

class ProductCategoryCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_products_categories';

    /**
     * base route name
     */
    protected $mainRoute      =   'ns.products-categories';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.products-categories';

    /**
     * Model Used
     */
    protected $model      =   ProductCategory::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_users as user', 'nexopos_products_categories.author', '=', 'user.id' ],
        'leftJoin'  =>  [
            [ 'nexopos_products_categories as parent', 'nexopos_products_categories.parent_id', '=', 'parent.id' ]
        ],
    ];

    protected $pick     =   [
        'user'      =>  [ 'username' ],
        'parent'    =>  [ 'name' ],
    ];

    protected $permissions  =   [
        'create'    =>  'nexopos.create.categories',
        'read'    =>  'nexopos.read.categories',
        'update'    =>  'nexopos.update.categories',
        'delete'    =>  'nexopos.delete.categories',
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
            'list_title'            =>  __( 'Category Products List' ),
            'list_description'      =>  __( 'Display all category products.' ),
            'no_entry'              =>  __( 'No category products has been registered' ),
            'create_new'            =>  __( 'Add a new category product' ),
            'create_title'          =>  __( 'Create a new category product' ),
            'create_description'    =>  __( 'Register a new category product and save it.' ),
            'edit_title'            =>  __( 'Edit category product' ),
            'edit_description'      =>  __( 'Modify  Category Product.' ),
            'back_to_list'          =>  __( 'Return to Category Products' ),
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
        $parents    =   ProductCategory::where( 'id', '<>', $entry->id ?? 0 )->get();
        $parents->prepend( ( object ) [
            'id'    =>    0,
            'name'  =>  __( 'No Parent' ),
        ]);

        return [
            'main' =>  [
                'label'         =>  __( 'Name' ),
                'name'          =>  'name',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'          =>  'media',
                            'label'         =>  __( 'Preview' ),
                            'name'          =>  'preview_url',
                            'description'   =>  __( 'Provide a preview url to the category.' ),
                            'value'         =>  $entry->preview_url ?? '',
                        ], [
                            'type'          =>  'switch',
                            'label'         =>  __( 'Displays On POS' ),
                            'name'          =>  'displays_on_pos',
                            'description'   =>  __( 'If clicked to no, all products assigned to this category or all sub categories, won\'t appear at the POS.' ),
                            'options'       =>  Helper::kvToJsOptions([ __( 'No' ), __( 'Yes' ) ]),
                            'validation'    =>  'required',
                            'value'         =>  $entry->displays_on_pos ?? 1, // ( $entry !== null && $entry->displays_on_pos ? ( int ) $entry->displays_on_pos : 1 ),
                        ], [
                            'type'          =>  'select',
                            'options'       =>  Helper::toJsOptions( $parents, [ 'id', 'name' ]),
                            'name'          =>  'parent_id',
                            'label'         =>  __( 'Parent' ),
                            'description'   =>  __( 'If this category should be a child category of an existing category' ),
                            'value'         =>  $entry->parent_id ?? '',
                        ], [
                            'type'  =>  'ckeditor',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
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
    public function filterPutInputs( $inputs, ProductCategory $entry )
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
        $this->allowedTo( 'create' );

        return $request;
    }

    /**
     * After saving a record
     * @param  Request $request
     * @param  ProductCategory $entry
     * @return  void
     */
    public function afterPost( $request, ProductCategory $entry )
    {
        ProductCategoryAfterCreatedEvent::dispatch( $entry );

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
        $this->allowedTo( 'delete' );
        return $request;
    }

    /**
     * After updating a record
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function afterPut( $request, ProductCategory $entry )
    {
        /**
         * If the category is not visible on the POS
         * the products aren't searchable.
         */
        if ( ! ( bool ) $entry->displays_on_pos ) {
            Product::where( 'category_id', $entry->id )->update([
                'searchable'    =>  false
            ]);
        } else {
            Product::where( 'category_id', $entry->id )->update([
                'searchable'    =>  true
            ]);
        }

        ProductCategoryAfterUpdatedEvent::dispatch( $entry );

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
        if ( $namespace == 'ns.products-categories' ) {
            $this->allowedTo( 'delete' );

            ProductCategoryBeforeDeletedEvent::dispatch( $model );
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
            'parent_name'  =>  [
                'label'  =>  __( 'Parent' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'total_items'  =>  [
                'label'  =>  __( 'Total Products' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'displays_on_pos'  =>  [
                'label'         =>  __( 'Displays On POS' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'user_username'  =>  [
                'label'  =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created At' ),
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

        $entry->parent_name     =   $entry->parent_name === null ? __( 'No Parent' ) : $entry->parent_name;
        $entry->displays_on_pos =   ( int ) $entry->displays_on_pos === 1 ? __( 'Yes' ) : __( 'No' );
        
        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'products/categories' . '/edit/' . $entry->id )
            ], [
                'label'         =>      __( 'Compute Products' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'products/categories' . '/compute-products/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.products-categories/' . $entry->id ),
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
                if ( $entity instanceof ProductCategory ) {
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
            'list'      =>  ns()->url( 'dashboard/' . 'products/categories' ),
            'create'    =>  ns()->url( 'dashboard/' . 'products/categories/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'products/categories/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.products-categories' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'ns.products-categories/{id}' . '' ),
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