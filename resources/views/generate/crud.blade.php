@inject( 'Schema', 'Illuminate\Support\Facades\Schema' )
@inject( 'Str', 'Illuminate\Support\Str' )
<?php
$model          =   explode( '\\', $model_name );
$lastClassName  =   $model[ count( $model ) - 1 ];
?>
<{{ '?php' }}
@if( isset( $module ) )
namespace Modules\{{ $module[ 'namespace' ] }}\Crud;
@else
namespace App\Crud;
@endif

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use {{ trim( $model_name ) }};

class {{ ucwords( $Str::camel( $resource_name ) ) }}Crud extends CrudService
{
    /**
     * define the base table
     * @param string
     */
    protected $table      =   '{{ strtolower( trim( $table_name ) ) }}';

    /**
     * default slug
     * @param string
     */
    protected $slug   =   '{{ strtolower( trim( $route_name ) ) }}';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace  =   '{{ strtolower( trim( $namespace ) ) }}';

    /**
     * Model Used
     * @param string
     */
    protected $model      =   {{ trim( $lastClassName ) }}::class;

    /**
     * Define permissions
     * @param array
     */
    protected $permissions  =   [
        'create'    =>  true,
        'read'      =>  true,
        'update'    =>  true,
        'delete'    =>  true,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     * @param array
     */
    public $relations   =  [
        @if( isset( $relations ) && count( $relations ) > 0 )@foreach( $relations as $relation )[ '{{ strtolower( trim( $relation[0] ) ) }}', '{{ strtolower( trim( $relation[2] ) ) }}', '=', '{{ strtolower( trim( $relation[1] ) ) }}' ],
        @endforeach
        @endif
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations    =   [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    /**
     * Pick
     * Restrict columns you retreive from relation.
     * Should be an array of associative keys, where 
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick        =   [];

    /**
     * Define where statement
     * @var array
    **/
    protected $listWhere    =   [];

    /**
     * Define where in statement
     * @var array
     */
    protected $whereIn      =   [];

    /**
     * Fields which will be filled during post/put
     */
    @php
    $fields         =   explode( ',', $fillable );
    foreach( $fields as &$field ) {
        $field      =   trim( $field );
    }
    @endphp

    /**
     * If few fields should only be filled
     * those should be listed here.
     */
    public $fillable    =   {!! json_encode( $fillable ?: [] ) !!};

    /**
     * If fields should be ignored during saving
     * those fields should be listed here
     */
    public $skippable   =   [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    private $prependOptions     =   false;

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
     * @return array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( '{{ ucwords( $Str::plural( trim( $resource_name ) ) ) }} List' ),
            'list_description'      =>  __( 'Display all {{ strtolower( $Str::plural( trim( $resource_name ) ) ) }}.' ),
            'no_entry'              =>  __( 'No {{ strtolower( $Str::plural( trim( $resource_name ) ) ) }} has been registered' ),
            'create_new'            =>  __( 'Add a new {{ strtolower( $Str::singular( trim( $resource_name ) ) ) }}' ),
            'create_title'          =>  __( 'Create a new {{ strtolower( $Str::singular( trim( $resource_name ) ) ) }}' ),
            'create_description'    =>  __( 'Register a new {{ strtolower( $Str::singular( trim( $resource_name ) ) ) }} and save it.' ),
            'edit_title'            =>  __( 'Edit {{ strtolower( $Str::singular( trim( $resource_name ) ) ) }}' ),
            'edit_description'      =>  __( 'Modify  {{ ucwords( strtolower( $Str::singular( trim( $resource_name ) ) ) ) }}.' ),
            'back_to_list'          =>  __( 'Return to {{ ucwords( $Str::plural( trim( $resource_name ) ) ) }}' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     * @return boolean
    **/
    public function isEnabled( $feature )
    {
        return false; // by default
    }

    /**
     * Fields
     * @param object/null
     * @return array of field
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
                        @foreach( $Schema::getColumnListing( $table_name ) as $column )[
                            'type'  =>  'text',
                            'name'  =>  '{{ $column }}',
                            'label' =>  __( '{{ ucwords( $column ) }}' ),
                            'value' =>  $entry->{{ $column }} ?? '',
                        ], @endforeach
                    ]
                ]
            ]
        ];
    }

    /**
     * Filter POST input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, {{ trim( $lastClassName ) }} $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     * @param Request $request
     * @return void
     */
    public function beforePost( $request )
    {
        if ( $this->permissions[ 'create' ] !== false ) {
            ns()->restrict( $this->permissions[ 'create' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After saving a record
     * @param Request $request
     * @param {{ trim( $lastClassName ) }} $entry
     * @return void
     */
    public function afterPost( $request, {{ trim( $lastClassName ) }} $entry )
    {
        return $request;
    }

    
    /**
     * get
     * @param string
     * @return mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model' : return $this->model ; break;
        }
    }

    /**
     * Before updating a record
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function beforePut( $request, $entry )
    {
        if ( $this->permissions[ 'update' ] !== false ) {
            ns()->restrict( $this->permissions[ 'update' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After updating a record
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }

    /**
     * Before Delete
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model ) {
        if ( $namespace == '{{ strtolower( trim( $namespace ) ) }}' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
            **/
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }
        }
    }

    /**
     * Define Columns
     * @return array of columns configuration
     */
    public function getColumns() {
        return [
            @foreach( $Schema::getColumnListing( $table_name ) as $column )
'{{ $column }}'  =>  [
                'label'  =>  __( '{{ ucwords( $column ) }}' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            @endforeach
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
                'url'           =>      ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id )
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       =>  ns()->url( '/api/nexopos/v4/crud/{{ strtolower( trim( $namespace ) ) }}/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                ]
            ]
        ];

        return $entry;
    }

    
    /**
     * Bulk Delete Action
     * @param  object Request with object
     * @return  false/array
     */
    public function bulkAction( Request $request ) 
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

            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof {{ trim( $lastClassName ) }} ) {
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
     * @return array of links
     */
    public function getLinks()
    {
        return  [
            'list'      =>  ns()->url( 'dashboard/' . '{{ strtolower( trim( $route_name ) ) }}' ),
            'create'    =>  ns()->url( 'dashboard/' . '{{ strtolower( trim( $route_name ) ) }}/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . '{{ strtolower( trim( $route_name ) ) }}/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . '{{ strtolower( trim( $namespace ) ) }}' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . '{{ strtolower( trim( $namespace ) ) }}/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
     * @return array of actions
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
     * @return array of export formats
    **/
    public function getExports()
    {
        return [];
    }
}