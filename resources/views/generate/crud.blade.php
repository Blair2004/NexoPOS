@inject( 'Schema', 'Illuminate\Support\Facades\Schema' )
@inject( 'Str', 'Illuminate\Support\Str' )
<{{ '?php' }}
namespace App\Crud;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\Crud;
use App\Models\User;
use Hook;
use {{ trim( $model_name ) }};

class {{ ucwords( $Str::camel( $resource_name ) ) }}Crud extends Crud
{
    /**
     * define the base table
     */
    protected $table      =   '{{ strtolower( trim( $table_name ) ) }}';

    /**
     * base route name
     */
    protected $mainRoute      =   '{{ strtolower( trim( $route_name ) ) }}';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace  =   '{{ strtolower( trim( $namespace ) ) }}';

    /**
     * Model Used
     */
    protected $model      =   \{{ trim( $model_name ) }}::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        @if( isset( $relations ) && count( $relations ) > 0 )@foreach( $relations as $relation )[ '{{ strtolower( trim( $relation[0] ) ) }}', '{{ strtolower( trim( $relation[2] ) ) }}', '=', '{{ strtolower( trim( $relation[1] ) ) }}' ],
        @endforeach
        @endif
    ];

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
    public $fillable    =   {!! json_encode( $fillable ) !!};

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
    public function getFields( $entry = null ) 
    {
        return [
            // your field here
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
    public function filterPutInputs( $inputs, \{{ trim( $model_name ) }} $entry )
    {
        return $inputs;
    }

    /**
     * After Crud POST
     * @param object entry
     * @return void
     */
    public function afterPost( $inputs )
    {
        return $inputs;
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
     * After Crud PUT
     * @param object entry
     * @return void
     */
    public function afterPut( $inputs )
    {
        return $inputs;
    }
    
    /**
     * Protect an access to a specific crud UI
     * @param array { namespace, id, type }
     * @return array | throw AccessDeniedException
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
     * @return void
     */
    public function beforeDelete( $namespace, $id ) {
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
                'label'  =>  __( '{{ ucwords( $column ) }}' )
            ],
            @endforeach
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
                'url'           =>      '/dashboard/crud/{{ strtolower( trim( $namespace ) ) }}/edit/#'
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'index'     =>  'id',
                'url'       =>  'tendoo/crud/{{ strtolower( trim( $namespace ) ) }}' . '/#',
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
     * @param  object Request with object
     * @return  false/array
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
                if ( $entity instanceof {{ trim( $model_name ) }} ) {
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
     * @return array of links
     */
    public function getLinks()
    {
        return  [
            'list'  =>  '{{ strtolower( trim( $route_name ) ) }}',
            'create'    =>  '{{ strtolower( trim( $route_name ) ) }}/create',
            'edit'      =>  '{{ strtolower( trim( $route_name ) ) }}/edit/#'
        ];
    }

    /**
     * Get Bulk actions
     * @return array of actions
    **/
    public function getBulkActions()
    {
        return [];
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