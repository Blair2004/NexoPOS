<?php
namespace App\Services;

use App\Exceptions\NotAllowedException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\View;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudService 
{
    /**
     * Toggle feature array
     * @param boolean
     */
    protected $features       =   [
        'bulk-actions'      =>  true, // enable bulk action
        'single-action'     =>  true, // enable single action
        'checkboxes'        =>  true // enable checkboxes
    ];

    /**
     * Actions Array
     */
    protected $actions  =   [];

    /**
     * Protected columns
     */
    protected   $columns    =   [];

    /**
     * Link
     * @return array
     */
    protected   $links       =   [
        'list'      =>  [],
        'edit'      =>  [],
        'create'    =>  []
    ];

    /**
     * Define a where for getEntries
     * @var array
     */
    protected $listWhere    =   [];

    /**
     * Bulk Options
     * @param array
     */
    protected $bulkActions  =   [];

    /**
     * define where in statement
     */
    protected $whereIn      =   [];

    /**
     * define tabs relations
     */
    protected $tabsRelations    =   [];

    /**
     * Construct Parent
     */
    public function __construct()
    {
        /**
         * @todo provide more build in actions
         */
        $this->bulkActions  =   [
            'delete_selected'   =>  __( 'Delete Selected entries' )
        ];

        /**
         * Bulk action messages
         */
        $this->bulkDeleteSuccessMessage     =   __( '%s entries has been deleted' );
        $this->bulkDeleteDangerMessage      =   __( '%s entries has not been deleted' );
    }

    /**
     * Is enabled
     * Return whether a feature is enabled (true) or not (false)
     * @param string feature name
     * @return boolean/null
     */
    public function isEnabled( $feature )
    {
        return @$this->features[ $feature ];
    }

    /**
     * Get namespace
     * @return string current namespace
     */
    public function getNamespace()
    {
        return $this->namespace;
    }


    /**
     * Get Bulk Actions
     * @return array of bulk actions
     */
    public function getBulkActions()
    {
        return $this->bulkActions;
    }

    /**
     * get Entries
     * @param crud config
     * @return entries
     */
    public function getEntries()
    {
        $request            =   app()->make( Request::class );
        $query              =   DB::table( $this->table );
        $columnsLongName    =   [];
        /**
         * Let's loop relation if they exists
         */
        if ( $this->relations ) {
            /**
             * First loop to retreive the columns and rename it
             */
            $select         =   [];

            /**
             * Building Select field for primary table
             * We're caching the table columns, since we would like to 
             * avoid many DB Calls
             */
            if( ! empty( Cache::get( 'table-columns-' . $this->table ) ) && true === false ) {
                $columns        =   Cache::get( 'table-columns-' . $this->table );
            } else {
                $columns        =   Schema::getColumnListing( $this->table );
                Cache::put( 'table-columns-' . $this->table, $columns, Carbon::now()->addDays(1) );
            }

            foreach( $columns as $index => $column ) {
                $__name             =   $this->table . '.' . $column;
                $columnsLongName[]  =   $__name;
                $select[]           =  $__name . ' as ' . $column;
            }

            /**
             * we're extracting the joined table
             * to make sure building the alias works
             */
            $relations  =   [];
            
            collect( $this->relations )->each( function( $relation ) use ( &$relations ){
                if ( isset( $relation[0] ) ) {
                    if ( ! is_array( $relation[0] ) ) {
                        $relations[]    =   $relation;
                    } else {
                        collect( $relation )->each( function( $_relation ) use ( &$relations ) {
                            $relations[]    =   $_relation;
                        });
                    }
                }
            });
            
            /**
             * Build Select for joined table
             */
            foreach( $relations as $relation ) {
                /**
                 * We're caching the columns to avoid once again many DB request
                 */
                if( ! empty( Cache::get( 'table-columns-' . $relation[0] ) ) && true == false ) {
                    $columns        =   Cache::get( 'table-columns-' . $relation[0] );
                } else {
                    /**
                     * Will ensure to only pick
                     * some columns from the related tables
                     */
                    $table          =   $relation[0];
                    $pick           =   $this->pick ?? [];
                    $hasAlias       =   explode( 'as', $relation[0] ); // if there is an alias, let's just pick the table name
                    $aliasName      =   $hasAlias[1] ?? false; // for aliased relation. The pick use the alias as a reference.
                    $columns        =   collect( Schema::getColumnListing( count( $hasAlias ) === 2 ? trim( $hasAlias[0] ) : $relation[0] ) )
                        ->filter( function( $column ) use ( $pick, $table, $aliasName ) {
                            $picked     =   $pick[ $aliasName ? trim( $aliasName ) : $table ] ?? [];
                            if ( ! empty( $picked ) ) {
                                if ( in_array( $column, $picked ) ) {
                                    return true;
                                } else {
                                    return false;
                                }
                            }
                            return true;
                    })->toArray();

                    Cache::put( 'table-columns-' . $relation[0], $columns, Carbon::now()->addDays(1) );
                }

                foreach( $columns as $index => $column ) {
                    $hasAlias           =   explode( 'as', $relation[0]);

                    /**
                     * If the relation has an alias, we'll 
                     * use the provided alias to compose
                     * the juncture.
                     */
                    if ( count( $hasAlias ) === 2 ) {
                        $__name             =   trim( $hasAlias[1] ) . '.' . $column;
                        $columnsLongName[]  =   $__name;
                        $select[]           =   $__name . ' as ' . trim( $hasAlias[1] ) . '_' . $column;
                    } else {
                        $__name             =   $relation[0] . '.' . $column;
                        $columnsLongName[]  =   $__name;
                        $select[]           =   $__name . ' as ' . $relation[0] . '_' . $column;
                    }
                }
            }

            $query          =   call_user_func_array([ $query, 'select' ], $select );

            foreach( $this->relations as $junction => $relation ) {
                /**
                 * if no junction statement is provided
                 * then let's make it inner by default
                 */
                $junction   =   is_numeric( $junction ) ? 'join' : $junction;

                if ( in_array( $junction, [ 'join', 'leftJoin', 'rightJoin', 'crossJoin' ] ) ) {
                    if ( $junction !== 'join' ) {
                        foreach( $relation as $junction_relation ) {
                            $hasAlias           =   explode( 'as', $junction_relation[0]);
                            if ( count( $hasAlias ) === 2 ) {
                                $query->$junction( trim($hasAlias[0]) . ' as ' . trim($hasAlias[1]), $junction_relation[1], $junction_relation[2], $junction_relation[3] );
                            } else {
                                $query->$junction( $junction_relation[0], $junction_relation[1], $junction_relation[2], $junction_relation[3] );
                            }
                        }
                    } else {
                        $hasAlias           =   explode( 'as', $relation[0]);
                        if ( count( $hasAlias ) === 2 ) {
                            $query->$junction( trim($hasAlias[0]) . ' as ' . trim($hasAlias[1]), $relation[1], $relation[2], $relation[3] );
                        } else {
                            $query->$junction( $relation[0], $relation[1], $relation[2], $relation[3] );
                        }
                    }

                }
            }
        }

        /**
         * check if the query has a where statement
         */
        if ( $this->listWhere ) {
            foreach( $this->listWhere as $key => $value ) {
                if ( count( $this->listWhere ) > 1 ) {
                    $query->orWhere( $key, $value );
                } else {
                    $query->where( $key, $value );
                }
            }
        }

        /**
         * if hook method is defined
         */
        if ( method_exists( $this, 'hook' ) ) {
            $this->hook( $query );
        }

        /**
         * try to run the where in statement
         */
        if ( $this->whereIn ) {
            foreach( $this->whereIn as $key => $values ) {
                $query->whereIn( $key, $values );
            }
        }

        /**
         * Order the current result, according to the mentionned columns
         * means the user has clicked on "reorder"
         */
        if ( $request->query( 'direction' ) && $request->query( 'active' ) ) {
            $query->orderBy( 
                $request->query( 'active' ),
                $request->query( 'direction' )
            );
        }

        /**
         * let's make the "perPage" value adjustable
         */
        $perPage    =   20;
        if ( $request->query( 'per_page' ) ) {
            $perPage    =   $request->query( 'per_page' );
        }

        /**
         * searching
         */
        if ( $request->query( 'search' ) ) {
            $query->where( function( $query ) use ( $request, $columnsLongName ) {
                foreach( $columnsLongName as $index => $column ) {
                    if ( $index == 0 ) {
                        $query->where( $column, 'like', "%{$request->query( 'search' )}%" );
                    } else {
                        $query->orWhere( $column, 'like', "%{$request->query( 'search' )}%" );
                    }
                }
            });
        }

        $entries    =   $query->paginate( $perPage )->toArray();

        /**
         * looping entries to provide inline 
         * options
         */
        foreach( $entries[ 'data' ] as &$entry ) {
            /**
             * @hook crud.entry
             */
            $entry  =   Hook::filter( $this->namespace . '-crud-actions', $entry );
        }

        return $entries;
    }

    /**
     * Get action
     * @return array of actions
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Get link
     * @return array of link
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Get route
     * @return string 
     */
    public function getMainRoute()
    {
        return $this->mainRoute;
    }

    /**
     * Get Model
     * @return current model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get Fillable fields
     * @return array of string as field name
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Get crud instance
     * @param string namespace
     * @return Crud
     */
    public function getCrudInstance( $namespace )
    {
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( __( 'Unhandled crud resource' ) );
        }

        return new $crudClass;
    }

    /**
     * Extracts Crud validation from a crud resource
     * @param Crud $resource
     * @return Array
     */
    public function extractCrudValidation( $crud, $entry = null )
    {
        $form   =   $crud->getForm( $entry );

        $rules  =   [];

        if ( isset( $form[ 'main' ][ 'validation' ] ) ) {
            $rules[ $form[ 'main' ][ 'name' ] ]     =   $form[ 'main' ][ 'validation' ];
        }

        foreach( $form[ 'tabs' ] as $tabKey => $tab ) {
            foreach( $tab[ 'fields' ] as $field ) {
                if ( isset( $field[ 'validation' ] ) ) {
                    $rules[ $tabKey ][ $field[ 'name' ] ]   =   $field[ 'validation' ]; 
                }
            }
        }
        
        return $rules;
    }

    /**
     * Return plain data that can be used 
     * for inserting. The data is parsed form the defined
     * form on the Request
     * @param Crud $resource
     * @param Request $request
     * @return array
     */
    public function getPlainData( $resource, Request $request, $entry = null )
    {
        $form   =   $resource->getForm( $entry );
        $data   =   [];

        if ( isset( $form[ 'main' ] ) ) {
            $data[ $form[ 'main' ][ 'name' ] ]  =   $request->input( $form[ 'main' ][ 'name' ] );
        }

        foreach( $form[ 'tabs' ] as $tabKey => $tab ) {
            $keys   =   array_keys( $resource->getTabsRelations() );

            /**
             * We're ignoring the tabs
             * that are linked to a model.
             */
            if ( ! in_array( $tabKey, $keys ) ) {
                foreach( $tab[ 'fields' ] as $field ) {
                    $data[ $field[ 'name' ] ]   =   $request->input( $tabKey . '.' . $field[ 'name' ] ); 
                }
            }
        }

        return $data;
    }

    /**
     * To pull out the tabs relations
     * @return array
     */
    public function getTabsRelations(): array
    {
        return $this->tabsRelations;
    }

    /**
     * Isolate Rules that use the Rule class
     * @param array
     * @return array
     */
    public function isolateArrayRules( $arrayRules, $parentKey = '' )
    {
        $rules      =   [];

        foreach( $arrayRules as $key => $value ) {
            if ( is_array( $value ) && collect( array_keys( $value ) )->filter( function( $key ) {
                return is_string( $key );
            })->count() > 0 ) {
                $rules  =   array_merge( $rules, $this->isolateArrayRules( $value, $key ) );
            } else {
                $rules[]  =   [ ( ! empty( $parentKey ) ? $parentKey . '.' : '' ) . $key, $value ];
            }
        }

        return $rules;
    }

    public static function table( $config = [] )
    {
        $className  =   get_called_class();
        $instance   =   new $className;

        /**
         * "manage.profile" is the default permission
         * granted to every user. If a permission check return "false"
         * that means performing that action is disabled.
         */
        if ( $instance->getPermission( 'read' ) !== false ) {
            ns()->restrict([ $instance->getPermission( 'read' ) ]);
        } else {
            throw new NotAllowedException();
        }
        
        return View::make( 'pages.dashboard.crud.table', [
            /**
             * that displays the title on the page.
             * It fetches the value from the labels
             */
            'title'         =>  $instance->getLabels()[ 'list_title' ],

            /**
             * That displays the page description. This allow pull the value
             * from the labels.
             */
            'description'   =>  $instance->getLabels()[ 'list_description' ],

            /**
             * This create the src URL using the "namespace".
             */
            'src'           =>  url( '/api/nexopos/v4/crud/' . $instance->namespace ),

            /**
             * This pull the creation link. That link should takes the user
             * to the creation form.
             */
            'createUrl'     =>  $instance->getLinks()[ 'create' ] ?? '#',

            /**
             * Provided to render the side menu.
             */
            'menus'         =>  app()->make( MenuService::class )
        ]);
    }

    /**
     * Will render a form UI
     * @param Model|null reference passed
     * @param array custom configuration
     */
    public static function form( $entry = null, $config = [] )
    {
        $className          =   get_called_class();
        $instance           =   new $className;
        $permissionType     =   $entry === null ? 'create' : 'update';

        /**
         * if a permission for creating or updating is 
         * not disabled let's make a validation.
         */
        if ( $instance->getPermission( $permissionType ) !== false ) {
            ns()->restrict([ $instance->getPermission( $permissionType ) ]);
        }
        
        /**
         * use crud form to render
         * a valid form.
         */
        return View::make( 'pages.dashboard.crud.form', [
            /**
             * this pull the title either
             * the form is made to create or edit a resource.
             */
            'title'         =>  $config[ 'title' ] ?? ( $entry === null ? $instance->getLabels()[ 'create_title' ] : $instance->getLabels()[ 'edit_title' ] ),

            /**
             * this pull the description either the form is made to
             * create or edit a resource.
             */
            'description'   =>  $config[ 'description' ] ?? ( $entry === null ? $instance->getLabels()[ 'create_description' ] : $instance->getLabels()[ 'edit_description' ] ),

            /**
             * this automatically build a source URL based on the identifier
             * provided. But can be overwritten with the config.
             */
            'src'           =>  $config[ 'src' ] ?? ( url( '/api/nexopos/v4/crud/' . $instance->namespace . '/' . ( $entry ? $entry->id . '/form-config' : 'form-config' ) ) ),

            /**
             * this use the built in links to create a return URL.
             * It can also be overwritten by the configuration.
             */
            'returnUrl'     =>  $config[ 'returnUrl' ] ?? ( $instance->getLinks()[ 'list' ] ?? '#' ),

            /**
             * This will pull the submitURL that might be different wether the $entry is
             * provided or not. can be overwritten on the configuration ($config).
             */
            'submitUrl'     =>  $config[ 'submitUrl' ] ?? ( $entry === null ? $instance->getLinks()[ 'post' ] : $instance->getLinks()[ 'put' ] ),
            
            /**
             * By default the method used is "post" but might change to "put" according to 
             * wether the entry is provided (Model). Can be changed from the $config.
             */
            'submitMethod'  =>  $config[ 'submitMethod' ] ?? ( $entry === null ? 'post' : 'put' ),

            /**
             * This will pass an instance of the MenuService.
             */
            'menus'         =>  app()->make( MenuService::class )
        ]);
    }

    /**
     * retrieve one of the declared permissions
     * the name must either be "create", "read", "update", "delete".
     * @param string $name
     * @return string $permission
     */
    public function getPermission( $name ) 
    {
        return $this->permissions[ $name ] ?? false;
    }
}