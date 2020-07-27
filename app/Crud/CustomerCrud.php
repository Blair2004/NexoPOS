<?php
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Helper;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Hook;

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
        public $fillable    =   "";

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( 'crud.entry', [ $this, 'setActions' ], 10, 2 );
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

    /**
     * Fields
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null ) 
    {
        return [
            'main'  =>  [
                'label' =>  __( 'Customer Name' ),
                'name'  =>  'name',
                'description'   =>  __( 'Provide a unique name for the customer.' )
            ], 
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'          =>  'select',
                            'label'         =>  __( 'Group' ),
                            'validation'    =>  'required',
                            'options'       =>  Helper::toJsOptions( CustomerGroup::all(), [ 'id', 'name' ]),
                            'description'   =>  __( 'Assign the customer to a group' )
                        ], [
                            'type'          =>  'text',
                            'label'         =>  __( 'Surname' ),
                            'validation'    =>  'required',
                            'description'   =>  __( 'Provide the customer surname' )
                        ], [
                            'type'          =>  'email',
                            'label'         =>  __( 'Email' ),
                            'validation'    =>  'required|email',
                            'description'   =>  __( 'Provide the customer email' )
                        ], [
                            'type'          =>  'text',
                            'label'         =>  __( 'Phone Number' ),
                            'validation'    =>  'required',
                            'description'   =>  __( 'Provide the customer phone number' )
                        ]
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
    public function filterPutInputs( $inputs, \App\Models\Customer $entry )
    {
        return $inputs;
    }

    /**
     * After Crud POST
     * @param  object entry
     * @return  void
     */
    public function afterPost( $inputs )
    {
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
    public function afterPut( $inputs )
    {
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

        throw new AccessDeniedException( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id ) {
        if ( $namespace == 'ns.customers' ) {
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
                'label'  =>  __( 'Name' )
            ],
            'surname'  =>  [
                'label'  =>  __( 'Surname' )
            ],
            'group_id'  =>  [
                'label'  =>  __( 'Group' )
            ],
            'email'  =>  [
                'label'  =>  __( 'Email' )
            ],
            'gender'  =>  [
                'label'  =>  __( 'Gender' )
            ],
            'phone'  =>  [
                'label'  =>  __( 'Phone' )
            ],
            'pobox'  =>  [
                'label'  =>  __( 'Pobox' )
            ],
            'author'  =>  [
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
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit.licence',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>      '/dashboard/crud/ns.customers/edit/#'
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'index'     =>  'id',
                'url'       =>  'tendoo/crud/ns.customers' . '/#',
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                    'title'     =>  __( 'Delete a licence' )
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
    public function bulkDelete( Request $request ) 
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user   =   app()->make( 'Tendoo\Core\Services\Users' );
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
                if ( $entity instanceof App\Models\Customer ) {
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
            'list'  =>  'ns.customers.index',
            'create'    =>  'ns.customers.index/create',
            'edit'      =>  'ns.customers.index/edit/#'
        ];
    }

    /**
     * Get Bulk actions
     * @return  array of actions
    **/
    public function getBulkActions()
    {
        return [];
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