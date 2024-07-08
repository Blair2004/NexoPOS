<?php

namespace App\Services;

use App\Casts\DateCast;
use App\Classes\Output;
use App\Events\CrudHookEvent;
use App\Exceptions\NotAllowedException;
use App\Traits\NsForms;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View as ContractView;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudService
{
    use NsForms;

    /**
     * Toggle feature array
     *
     * @param bool
     */
    protected $features = [
        'bulk-actions' => true, // enable bulk action
        'single-action' => true, // enable single action
        'checkboxes' => true, // enable checkboxes
    ];

    /**
     * Actions Array
     */
    protected $actions = [];

    /**
     * Protected columns
     */
    protected $columns = [];

    /**
     * Query filters
     *
     * @param array
     */
    protected $queryFilters = [];

    /**
     * Link
     *
     * @return array
     */
    protected $links = [
        'list' => [],
        'edit' => [],
        'create' => [],
    ];

    /**
     * Define a where for getEntries
     *
     * @var array
     */
    protected $listWhere = [];

    /**
     * Bulk Options
     *
     * @param array
     */
    protected $bulkActions = [];

    /**
     * define where in statement
     */
    protected $whereIn = [];

    /**
     * define tabs relations
     */
    protected $tabsRelations = [];

    /**
     * Will ensure every POST request
     * aren't persistent while events
     * for this request are triggered.
     */
    public $disablePost = false;

    /**
     * Will ensure every PUT requests aren't persisten
     * while the events for that request are triggered.
     */
    public $disablePut = false;

    /**
     * define all fiels that shouldn't be used for saving
     *
     * @param array
     */
    public $skippable = [];

    /**
     * Define the files that are allowed
     * do be used while saving or updating an entry.
     */
    public $fillable = [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = false;

    /**
     * Determine if actions should be displayed
     */
    protected $showOptions = true;

    /**
     * Determine if checkboxes should be displayed
     */
    protected $showCheckboxes = true;

    /**
     * Will enforce slug to be defined as
     * a protected property.
     *
     * @param string
     */
    protected $slug;

    /**
     * Define the columns that should
     * be included on the exportation.
     */
    protected $exportColumns = [];

    /**
     * This defines the casts that applies
     * to every entry on a crud table.
     */
    protected $casts = [
        'created_at' => DateCast::class,
        'updated_at' => DateCast::class,
    ];

    protected $model;

    /**
     * Define permissions for using
     * the current resource
     */
    protected $permissions = [];

    /**
     * Store the bulk messages for either successful
     * and unsuccessful operations.
     */
    protected $bulkDeleteSuccessMessage;

    protected $bulkDeleteDangerMessage;

    /**
     * Store the values that should be
     * picked from relations
     */
    protected $pick;

    /**
     * Store relations that should be joined to every
     * request made to the database using the model provided
     */
    protected $relations = [];

    /**
     * Keeps the table name for the provided model
     */
    protected $table;

    /**
     * Define the main route identifier.
     */
    protected $mainRoute;

    /**
     * Define the attributes to use while using
     * the crud component on a search-select field.
     */
    protected $optionAttributes = [
        'label' => 'name',
        'value' => 'id',
    ];

    /**
     * Construct Parent
     */
    public function __construct()
    {
        /**
         * @todo provide more build in actions
         */
        $this->bulkActions = [
            'delete_selected' => __( 'Delete Selected entries' ),
        ];

        /**
         * Bulk action messages
         */
        $this->bulkDeleteSuccessMessage = __( '%s entries has been deleted' );
        $this->bulkDeleteDangerMessage = __( '%s entries has not been deleted' );
    }

    /**
     * Shorthand for preparing and submitting crud request
     *
     * @param  string $namespace
     * @param  array  $inputs
     * @param  mixed  $id
     * @return array  as a crud response
     */
    public function submitPreparedRequest( $inputs, $id = null ): array
    {
        $model = $id !== null ? $this->getModel()::find( $id ) : null;
        $data = $this->getFlatForm( $inputs, $model );

        return $this->submitRequest( $this->getNamespace(), $data, $id );
    }

    /**
     * Will submit a request to the current
     * crud instance using the input provided
     *
     * @param  array $inputs
     * @param  int   $id
     * @return array $response
     */
    public function submit( $inputs, $id = null )
    {
        return $this->submitRequest(
            namespace: $this->getNamespace(),
            inputs: $inputs,
            id: $id
        );
    }

    /**
     * Submit a prepared request to a crud instance
     *
     * @param  string   $namespace
     * @param  array    $inputs
     * @param  int|null $id
     * @return array    $response
     */
    public function submitRequest( $namespace, $inputs, $id = null ): array
    {
        $resource = $this->getCrudInstance( $namespace );
        $model = $resource->getModel();
        $isEditing = $id !== null;
        $entry = ! $isEditing ? new $model : $model::find( $id );

        /**
         * let's keep old form inputs
         */
        $unfiltredInputs = $inputs;

        if ( method_exists( $resource, 'filterPostInputs' ) && ! $isEditing ) {
            $inputs = $resource->filterPostInputs( $inputs, null );
        }

        if ( method_exists( $resource, 'filterPutInputs' ) && $isEditing ) {
            $inputs = $resource->filterPutInputs( $inputs, $entry );
        }

        /**
         * this trigger a global filter
         * on the actual crud instance
         */
        $inputs = Hook::filter(
            get_class( $resource ) . ( $isEditing ? '@filterPutInputs' : '@filterPostInputs' ),
            $inputs,
            $isEditing ? $entry : null
        );

        if ( method_exists( $resource, 'beforePost' ) && ! $isEditing ) {
            $resource->beforePost( $unfiltredInputs, null, $inputs );
        }

        if ( method_exists( $resource, 'beforePut' ) && $isEditing ) {
            $resource->beforePut( $unfiltredInputs, $entry, $inputs );
        }

        /**
         * If we would like to handle the PUT request
         * by any other handler than the CrudService
         */
        if (
            ( ! $isEditing && ! $resource->disablePost ) ||
            ( $isEditing && ! $resource->disablePut )
        ) {
            $fillable = Hook::filter(
                get_class( $resource ) . '@getFillable',
                $resource->getFillable()
            );

            foreach ( $inputs as $name => $value ) {
                /**
                 * If the fields where explicitly added
                 * on field that must be ignored we should skip that.
                 */
                if ( ! in_array( $name, $resource->skippable ) ) {
                    /**
                     * If submitted field are part of fillable fields
                     */
                    if ( in_array( $name, $fillable ) || count( $fillable ) === 0 ) {
                        /**
                         * We might give the capacity to filter fields
                         * before storing. This can be used to apply specific formating to the field.
                         */
                        if ( method_exists( $resource, 'filterPostInput' ) || method_exists( $resource, 'filterPutInput' ) ) {
                            $entry->$name = $isEditing ? $resource->filterPutInput( $value, $name ) : $resource->filterPostInput( $value, $name );
                        } else {
                            $entry->$name = $value;
                        }

                        /**
                         * sanitizing input to remove
                         * all script tags
                         */
                        if ( ! empty( $entry->$name ) ) {
                            $entry->$name = strip_tags( $entry->$name );
                        }
                    }
                }
            }

            /**
             * If fillable is empty or if "author" it's explicitly
             * mentionned on the fillable array.
             */
            $columns = array_keys( $this->getColumns() );

            if ( empty( $fillable ) || (
                in_array( 'author', $fillable )
            ) ) {
                $entry->author = Auth::id();
            }

            /**
             * if timestamp are provided we'll disable the timestamp feature.
             * In case a field is not provided, the default value is used.
             */
            if ( ! empty( $entry->created_at ) || ! empty( $entry->updated_at ) ) {
                $entry->timestamps = false;
                $entry->created_at = $entry->created_at ?: ns()->date->toDateTimeString();
                $entry->updated_at = $entry->updated_at ?: ns()->date->toDateTimeString();
            }

            $entry->save();

            /**
             * loop the tabs relations
             * and store it
             */
            foreach ( $resource->getTabsRelations() as $tab => $relationParams ) {
                $fields = request()->input( $tab );
                $class = $relationParams[0];
                $localKey = $relationParams[1];
                $foreignKey = $relationParams[2];

                if ( ! empty( $fields ) ) {
                    $model = $class::where( $localKey, $entry->$foreignKey )->first();

                    /**
                     * no relation has been found
                     * so we'll store that.
                     */
                    if ( ! $model instanceof $class ) {
                        $model = new $relationParams[0]; // should be the class;
                    }

                    /**
                     * We're saving here all the fields for
                     * the related model
                     */
                    foreach ( $fields as $name => $value ) {
                        $model->$name = $value;
                    }

                    $model->$localKey = $entry->$foreignKey;
                    $model->author = Auth::id();
                    $model->save();
                }
            }
        }

        /**
         * Create an event after crud POST
         */
        if ( ! $isEditing && method_exists( $resource, 'afterPost' ) ) {
            $resource->afterPost( $unfiltredInputs, $entry, $inputs );
        }

        /**
         * Create an event after crud POST
         */
        if ( $isEditing && method_exists( $resource, 'afterPut' ) ) {
            $resource->afterPut( $unfiltredInputs, $entry, $inputs );
        }

        return [
            'status' => 'success',
            'data' => [
                'entry' => $entry,
                'editUrl' => str_contains( $resource->getLinks()['edit'], '{id}' ) ? Str::replace( '{id}', $entry->id, $resource->getLinks()['edit'] ) : false,
            ],
            'message' => $id === null ? __( 'A new entry has been successfully created.' ) : __( 'The entry has been successfully updated.' ),
        ];
    }

    /**
     * Is enabled
     * Return whether a feature is enabled (true) or not (false)
     *
     * @param string feature name
     * @return boolean/null
     */
    public function isEnabled( $feature ): ?bool
    {
        return $this->features[$feature] ?? false;
    }

    /**
     * Get namespace
     *
     * @return string current namespace
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Will return the columns that should
     * be included on the exportation.
     */
    public function getExportColumns()
    {
        return $this->exportColumns;
    }

    /**
     * Get Bulk Actions
     *
     * @return array of bulk actions
     */
    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    /**
     * Will return picked array
     */
    public function getPicked(): array
    {
        return $this->pick ?? [];
    }

    /**
     * Will handle the definition operator
     *
     * @param Builder $query
     * @param array   $definition
     * @param array   $searchKeyValue
     */
    public function handleDefinitionOperator( $query, $definition, $searchKeyValue ): void
    {
        extract( $searchKeyValue );
        /**
         * @param string $key
         * @param mixed  $value
         */
        if ( isset( $definition['operator'] ) ) {
            $query->where( $key, $definition['operator'], $value );
        } else {
            $query->where( $key, $value );
        }
    }

    /**
     * Returns the available query filters
     */
    public function getQueryFilters(): array
    {
        return $this->queryFilters;
    }

    public function __extractTable( $relation ): string
    {
        $parts = explode( ' as ', $relation[0] );
        if ( count( $parts ) === 2 ) {
            return trim( $parts[0] );
        } else {
            return $relation[0];
        }
    }

    /**
     * Will return mutated relation
     * for the Crud instance
     *
     * @return array $relations
     */
    public function getRelations(): array
    {
        return Hook::filter( self::method( 'getRelations' ), $this->relations );
    }

    /**
     * Will returns the CRUD component slug
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Returns a boolean that determine if the options should be displayed
     * before the crud columns or after the crud columns. This method is defined
     * for allowing other module to override this behavior.
     */
    public function getPrependOptions(): bool
    {
        return $this->prependOptions;
    }

    /**
     * Get crud instance entries.
     *
     * @param array config
     * @return array entries
     */
    public function getEntries( $config = [] ): array
    {
        $table = $this->hookTableName( $this->table );
        $request = app()->make( Request::class );
        $query = DB::table( $table );
        $columnsLongName = [];

        /**
         * First loop to retrieve the columns and rename it
         */
        $select = [];

        /**
         * Building Select field for primary table
         * We're caching the table columns, since we would like to
         * avoid many DB Calls
         */
        if ( ! empty( Cache::get( 'table-columns-' . $table ) ) && true === false ) {
            $columns = Cache::get( 'table-columns-' . $table );
        } else {
            $columns = Schema::getColumnListing( $table );
            Cache::put( 'table-columns-' . $table, $columns, Carbon::now()->addDays( 1 ) );
        }

        foreach ( $columns as $index => $column ) {
            $__name = $table . '.' . $column;
            $columnsLongName[] = $__name;
            $select[] = $__name . ' as ' . $column;
        }

        /**
         * Let's loop relation if they exists
         */
        if ( $this->getRelations() ) {
            /**
             * we're extracting the joined table
             * to make sure building the alias works
             */
            $relations = [];
            $relatedTables = [];

            collect( $this->getRelations() )->each( function ( $relation ) use ( &$relations, &$relatedTables ) {
                if ( isset( $relation[0] ) ) {
                    if ( ! is_array( $relation[0] ) ) {
                        $relations[] = $relation;

                        /**
                         * We do extract the table name
                         * defined on the relation array
                         */
                        $relatedTables[] = $this->__extractTable( $relation );
                    } else {
                        collect( $relation )->each( function ( $_relation ) use ( &$relations, &$relatedTables ) {
                            $relations[] = $_relation;

                            /**
                             * We do extract the table name
                             * defined on the relation array
                             */
                            $relatedTables[] = $this->__extractTable( $_relation );
                        } );
                    }
                }
            } );

            $relatedTables = collect( $relatedTables )
                ->unique()
                ->push( $this->table ) // the crud table must be considered as a related table as well.
                ->toArray();

            /**
             * Build Select for joined table
             */
            foreach ( $relations as $relation ) {
                /**
                 * We're caching the columns to avoid once again many DB request
                 */
                if ( ! empty( Cache::get( 'table-columns-' . $relation[0] ) ) && true == false ) {
                    $columns = Cache::get( 'table-columns-' . $relation[0] );
                } else {
                    /**
                     * Will ensure to only pick
                     * some columns from the related tables
                     */
                    $table = $relation[0];

                    /**
                     * If the CRUD instance has some entries
                     * that are picked, we'll allow extensibility
                     * using the filter "getPicked".
                     */
                    $pick = Hook::filter( self::method( 'getPicked' ), $this->getPicked() );

                    $hasAlias = explode( ' as ', $relation[0] ); // if there is an alias, let's just pick the table name
                    $hasAlias[0] = $this->hookTableName( $hasAlias[0] ); // make the table name hookable
                    $aliasName = $hasAlias[1] ?? false; // for aliased relation. The pick use the alias as a reference.
                    $columns = collect( Schema::getColumnListing( count( $hasAlias ) === 2 ? trim( $hasAlias[0] ) : $relation[0] ) )
                        ->filter( function ( $column ) use ( $pick, $table, $aliasName ) {
                            $picked = $pick[$aliasName ? trim( $aliasName ) : $table] ?? [];
                            if ( ! empty( $picked ) ) {
                                if ( in_array( $column, $picked ) ) {
                                    return true;
                                } else {
                                    return false;
                                }
                            }

                            return true;
                        } )->toArray();

                    Cache::put( 'table-columns-' . $relation[0], $columns, Carbon::now()->addDays( 1 ) );
                }

                foreach ( $columns as $index => $column ) {
                    $hasAlias = explode( ' as ', $relation[0] );
                    $hasAlias[0] = $this->hookTableName( $hasAlias[0] );

                    /**
                     * If the relation has an alias, we'll
                     * use the provided alias to compose
                     * the juncture.
                     */
                    if ( count( $hasAlias ) === 2 ) {
                        $__name = trim( $hasAlias[1] ) . '.' . $column;
                        $columnsLongName[] = $__name;
                        $select[] = $__name . ' as ' . trim( $hasAlias[1] ) . '_' . $column;
                    } else {
                        $__name = $this->hookTableName( $relation[0] ) . '.' . $column;
                        $columnsLongName[] = $__name;
                        $select[] = $__name . ' as ' . $relation[0] . '_' . $column;
                    }
                }
            }

            /**
             * @var Builder
             */
            $query = call_user_func_array( [$query, 'select'], $select );

            foreach ( $this->getRelations() as $junction => $relation ) {
                /**
                 * if no junction statement is provided
                 * then let's make it inner by default
                 */
                $junction = is_numeric( $junction ) ? 'join' : $junction;

                if ( in_array( $junction, ['join', 'leftJoin', 'rightJoin', 'crossJoin'] ) ) {
                    if ( $junction !== 'join' ) {
                        foreach ( $relation as $junction_relation ) {
                            $hasAlias = explode( ' as ', $junction_relation[0] );
                            $hasAlias[0] = $this->hookTableName( $hasAlias[0] );

                            /**
                             * makes sure first table can be filtered. We should also check
                             * if the column are actual column and not aliases
                             */
                            $relatedTableParts = explode( '.', $junction_relation[1] );

                            if ( count( $relatedTableParts ) === 2 && in_array( $relatedTableParts[0], $relatedTables ) ) {
                                $junction_relation[1] = $this->hookTableName( $relatedTableParts[0] ) . '.' . $relatedTableParts[1];
                            }

                            /**
                             * makes sure the second table can be filtered. We should also check
                             * if the column are actual column and not aliases
                             */
                            $relatedTableParts = explode( '.', $junction_relation[3] );
                            if ( count( $relatedTableParts ) === 2 && in_array( $relatedTableParts[0], $relatedTables ) ) {
                                $junction_relation[3] = $this->hookTableName( $relatedTableParts[0] ) . '.' . $relatedTableParts[1];
                            }

                            if ( count( $hasAlias ) === 2 ) {
                                $query->$junction( trim( $hasAlias[0] ) . ' as ' . trim( $hasAlias[1] ), $junction_relation[1], $junction_relation[2], $junction_relation[3] );
                            } else {
                                $query->$junction( $junction_relation[0], $junction_relation[1], $junction_relation[2], $junction_relation[3] );
                            }
                        }
                    } else {
                        $hasAlias = explode( ' as ', $relation[0] );
                        $hasAlias[0] = $this->hookTableName( $hasAlias[0] );

                        /**
                         * makes sure the first table can be filtered. We should also check
                         * if the column are actual column and not aliases
                         */
                        $relation[0] = $this->hookTableName( $relation[0] );

                        /**
                         * makes sure the first table can be filtered. We should also check
                         * if the column are actual column and not aliases
                         */
                        $relatedTableParts = explode( '.', $relation[1] );
                        if ( count( $relatedTableParts ) === 2 && in_array( $relatedTableParts[0], $relatedTables ) ) {
                            $relation[1] = $this->hookTableName( $relatedTableParts[0] ) . '.' . $relatedTableParts[1];
                        }

                        /**
                         * makes sure the second table can be filtered. We should also check
                         * if the column are actual column and not aliases
                         */
                        $relatedTableParts = explode( '.', $relation[3] );
                        if ( count( $relatedTableParts ) === 2 && in_array( $relatedTableParts[0], $relatedTables ) ) {
                            $relation[3] = $this->hookTableName( $relatedTableParts[0] ) . '.' . $relatedTableParts[1];
                        }

                        if ( count( $hasAlias ) === 2 ) {
                            $query->$junction( trim( $hasAlias[0] ) . ' as ' . trim( $hasAlias[1] ), $relation[1], $relation[2], $relation[3] );
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
            foreach ( $this->listWhere as $key => $value ) {
                if ( count( $this->listWhere ) > 1 ) {
                    $query->orWhere( $key, $value );
                } else {
                    $query->where( $key, $value );
                }
            }
        }

        /**
         * if hook method is defined and only when conditional argument
         * that affect result sorting is not active.
         */
        if ( method_exists( $this, 'hook' ) && request()->query( 'active' ) === null ) {
            $this->hook( $query );
        }

        /**
         * try to run the where in statement
         */
        if ( $this->whereIn ) {
            foreach ( $this->whereIn as $key => $values ) {
                $query->whereIn( $key, $values );
            }
        }

        /**
         * @since 4.5.5
         * Will filter request using provided
         * query filters
         */
        if ( $request->query( 'queryFilters' ) ) {
            $filters = json_decode( urldecode( $request->query( 'queryFilters' ) ), true );

            /**
             * @todo we might need to get the filter from the resource
             * so that we can parse correctly the provider query filters.
             */
            if ( ! empty( $filters ) ) {
                foreach ( $filters as $key => $value ) {
                    /**
                     * we won't handle empty value
                     */
                    if ( empty( $value ) ) {
                        continue;
                    }

                    $definition = collect( $this->queryFilters )->filter( fn ( $filter ) => $filter['name'] === $key )->first();

                    if ( ! empty( $definition ) ) {
                        switch ( $definition['type'] ) {
                            case 'daterangepicker':
                                if ( $value['startDate'] !== null && $value['endDate'] !== null ) {
                                    $query->where( $key, '>=', Carbon::parse( $value['startDate'] )->toDateTimeString() );
                                    $query->where( $key, '<=', Carbon::parse( $value['endDate'] )->toDateTimeString() );
                                }
                                break;
                            default:
                                /**
                                 * We would like to apply a specific operator
                                 * if it's provided to each requests
                                 */
                                $this->handleDefinitionOperator(
                                    $query,
                                    $definition,
                                    compact( 'key', 'value' )
                                );
                                break;
                        }
                    } else {
                        $query->where( $key, $value );
                    }
                }
            }
        }

        /**
         * let's make the "perPage" value adjustable
         */
        $perPage = $config['per_page'] ?? 20;
        if ( $request->query( 'per_page' ) ) {
            $perPage = $request->query( 'per_page' );
        }

        /**
         * searching
         */
        if ( $request->query( 'search' ) ) {
            $query->where( function ( $query ) use ( $request, $columnsLongName ) {
                foreach ( $columnsLongName as $index => $column ) {
                    if ( $index == 0 ) {
                        $query->where( $column, 'like', "%{$request->query( 'search' )}%" );
                    } else {
                        $query->orWhere( $column, 'like', "%{$request->query( 'search' )}%" );
                    }
                }
            } );
        }

        /**
         * Order the current result, according to the mentionned columns
         * means the user has clicked on "reorder"
         */
        if ( $request->query( 'direction' ) && $request->query( 'active' ) ) {
            $columns = $this->getColumns();

            $cannotSort =
                array_key_exists( $request->query( 'active' ), array_keys( $columns ) ) &&
                $columns[$request->query( 'active' )]['$sort'] === false;

            /**
             * If for some reason, we're trying to sort
             * custom columns that doesn't have any reference on the database.
             */
            if ( $cannotSort ) {
                throw new NotAllowedException( sprintf(
                    __( 'Sorting is explicitely disabled for the column "%s".' ),
                    $columns[$request->query( 'active' )]['label']
                ) );
            }

            $query->orderBy(
                $request->query( 'active' ),
                $request->query( 'direction' )
            );
        }

        /**
         * if some enties ID are provided. These
         * reference will only be part of the result.
         */
        if ( isset( $config['pick'] ) ) {
            $query->whereIn( $this->hookTableName( $this->table ) . '.id', $config['pick'] );
        }

        /**
         * This will allow any module to interact with
         * the way the query is built.
         */
        CrudHookEvent::dispatch( $this, $query );

        /**
         * if $perPage is not defined
         * probably we're trying to return all the entries.
         */
        if ( $perPage ) {
            $entries = $query->paginate( $perPage )->toArray();
        } else {
            $entries = $query->get()->toArray();
        }

        /**
         * looping entries to provide inline
         * options
         */
        $entries['data'] = collect( $entries['data'] )->map( function ( $entry ) {
            $entry = new CrudEntry( (array) $entry );

            /**
             * apply casting to crud resources
             * as it's defined by the casting property
             */
            $casts = $this->getCasts();

            /**
             * We'll define a raw property
             * that will have default uncasted values.
             */
            if ( ! isset( $entry->__raw ) ) {
                $entry->__raw = new \stdClass;
            }

            if ( ! empty( $casts ) ) {
                foreach ( $casts as $column => $cast ) {
                    if ( class_exists( $cast ) ) {
                        $castObject = new $cast;

                        // We'll keep a reference of the raw
                        // uncasted property.
                        $entry->__raw->$column = $entry->$column;

                        // We'll now cast the property.
                        $entry->$column = $castObject->get( $entry, $column, $entry->$column, [] );
                    }
                }
            }

            /**
             * We'll allow any resource to mutate the
             * entries but make sure to keep the originals.
             */
            if ( method_exists( $this, 'setActions' ) ) {
                Hook::action( get_class( $this )::method( 'setActions' ), $this->setActions( $entry ) );
            }

            return $entry;
        } );

        return $entries;
    }

    protected function setActions( CrudEntry $entry ): CrudEntry
    {
        return $entry;
    }

    protected function hookTableName( $tableName ): string
    {
        return Hook::filter( 'ns-model-table', $tableName );
    }

    public function hook( $query ): void
    {
        //
    }

    public function getColumns(): array
    {
        return [];
    }

    /**
     * Get action
     *
     * @return array of actions
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get link
     *
     * @return array of link
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Get route
     */
    public function getMainRoute(): string
    {
        return $this->mainRoute;
    }

    /**
     * Get Model
     *
     * @return current model
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get Fillable fields
     *
     * @return array of string as field name
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Get crud instance
     *
     * @param string namespace
     * @return CrudService
     */
    public function getCrudInstance( $namespace )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( sprintf( __( 'Unhandled crud resource "%s"' ), $crudClass ) );
        }

        return new $crudClass;
    }

    public function getForm()
    {
        return [];
    }

    /**
     * Will extract form with the entry
     * as a reference for the values.
     *
     * @unused
     */
    public function getExtractedForm( $entry = null, $multiEntry = false )
    {
        $form = $this->getForm( $entry );

        $final = [];

        if ( isset( $form['main']['validation'] ) ) {
            $final[$form['main']['name']] = $form['main']['value'];
        }

        /**
         * this is specific to products
         */
        if ( isset( $form['variations'] ) ) {
            foreach ( $form['variations'] as $variation ) {
                if ( $multiEntry ) {
                    $final['variations'][] = $this->extractTabs( $variation );
                } else {
                    $final = array_merge( $final, $this->extractTabs( $variation ) );
                }
            }
        } else {
            $final = $this->extractTabs( $form );
        }

        return $final;
    }

    private function extractTabs( $form )
    {
        $final = [];

        foreach ( $form['tabs'] as $tabKey => $tab ) {
            if ( ! empty( $tab['fields'] ) ) {
                foreach ( $tab['fields'] as $field ) {
                    if ( isset( $field['value'] ) ) {
                        $final[$tabKey][$field['name']] = $field['value'];
                    }
                }
            }
        }

        return $final;
    }

    /**
     * Will returns the defined tabs relations.
     */
    public function getTabsRelations(): array
    {
        return $this->tabsRelations;
    }

    public static function table( array $config = [], ?string $title = null, ?string $description = null, ?string $src = null, ?string $createUrl = null, ?array $queryParams = null ): ContractView
    {
        $className = get_called_class();
        $instance = new $className;

        /**
         * in case the default way of proceeding is not defined
         * we'll proceed by using the named arguments.
         */
        if ( empty( $config ) ) {
            $config = collect( compact( 'title', 'description', 'src', 'createUrl', 'queryParams' ) )
                ->filter()
                ->toArray();
        }

        /**
         * If a permission check return "false"
         * that means performing that action is disabled.
         */
        $instance->allowedTo( 'read' );

        $labels = Hook::filter( $instance::method( 'getLabels' ), $instance->getLabels() );

        return View::make( 'pages.dashboard.crud.table', array_merge( [
            /**
             * that displays the title on the page.
             * It fetches the value from the labels
             */
            'title' => $labels['list_title'],

            /**
             * That displays the page description. This allow pull the value
             * from the labels.
             */
            'description' => $labels['list_description'],

            /**
             * This create the src URL using the "namespace".
             */
            'src' => ns()->url( '/api/crud/' . $instance->namespace ),

            /**
             * This pull the creation link. That link should takes the user
             * to the creation form.
             */
            'createUrl' => Hook::filter( $instance::method( 'getFilteredLinks' ), $instance->getFilteredLinks() )['create'] ?? false,

            /**
             * to provide custom query params
             * to every outgoing request on the table
             */
            'queryParams' => [],

            /**
             * An instance of the current called crud component.
             */
            'instance' => $instance,
        ], $config ) );
    }

    /**
     * Will render a crud form using
     * the provided settings.
     */
    public static function form( $entry = null, array $config = [], string $title = '', string $description = '', string $src = '', string $returnUrl = '', string $submitUrl = '', string $submitMethod = '', array $queryParams = [] ): ContractView
    {
        /**
         * in case the default way of proceeding is not defined
         * we'll proceed by using the named arguments.
         */
        if ( empty( $config ) ) {
            $config = collect( compact( 'title', 'description', 'src', 'submitUrl', 'queryParams', 'returnUrl', 'submitMethod' ) )
                ->filter()
                ->toArray();
        }

        /**
         * use crud form to render a valid form.
         * "view" on the $config might be used to use a custom view file.
         */
        return View::make( $config[ 'view' ] ?? 'pages.dashboard.crud.form', self::getFormConfig(
            config: $config,
            entry: $entry
        ) );
    }

    public static function getFormConfig( $config = [], $entry = null )
    {
        $className = get_called_class();
        $instance = new $className;
        $permissionType = $entry === null ? 'create' : 'update';

        /**
         * if a permission for creating or updating is
         * not disabled let's make a validation.
         */
        $instance->allowedTo( $permissionType );

        return array_merge( [
            /**
             * this pull the title either
             * the form is made to create or edit a resource.
             */
            'title' => $config['title'] ?? ( $entry === null ? $instance->getLabels()['create_title'] : $instance->getLabels()['edit_title'] ),

            /**
             * this pull the description either the form is made to
             * create or edit a resource.
             */
            'description' => $config['description'] ?? ( $entry === null ? $instance->getLabels()['create_description'] : $instance->getLabels()['edit_description'] ),

            /**
             * this automatically build a source URL based on the identifier
             * provided. But can be overwritten with the config.
             */
            'src' => $config['src'] ?? ( ns()->url( '/api/crud/' . $instance->namespace . '/' . ( ! empty( $entry ) ? 'form-config/' . $entry->id : 'form-config' ) ) ),

            /**
             * this use the built in links to create a return URL.
             * It can also be overwritten by the configuration.
             */
            'returnUrl' => $config['returnUrl'] ?? ( $instance->getLinks()['list'] ?? '#' ),

            /**
             * This will pull the submitURL that might be different whether the $entry is
             * provided or not. can be overwritten on the configuration ($config).
             */
            'submitUrl' => $config['submitUrl'] ?? ( $entry === null ? $instance->getLinks()['post'] : str_replace( '{id}', $entry->id, $instance->getLinks()['put'] ) ),

            /**
             * By default the method used is "post" but might change to "put" according to
             * whether the entry is provided (Model). Can be changed from the $config.
             */
            'submitMethod' => $config['submitMethod'] ?? ( $entry === null ? 'post' : 'put' ),

            /**
             * provide the current crud namespace
             */
            'namespace' => $instance->getNamespace(),

            /**
             * We'll return here the select attribute that will
             * be used to automatically popuplate "options" entry of select and search-select field
             */
            'optionAttributes' => $instance->getOptionAttributes(),

            /**
             * to provide custom query params
             * to every outgoing request on the table
             */
            'queryParams' => [],
        ], $config );
    }

    public function getOptionAttributes()
    {
        return $this->optionAttributes;
    }

    /**
     * perform a quick check over
     * the permissions array provided on the instance
     */
    public function allowedTo( string $permission ): void
    {
        if ( isset( $this->permissions ) && $this->permissions[$permission] !== false ) {
            ns()->restrict( $this->permissions[$permission] );
        } else {
            throw new NotAllowedException;
        }
    }

    /**
     * retrieve one of the declared permissions
     * the name must either be "create", "read", "update", "delete".
     */
    public function getPermission( ?string $name ): bool|string
    {
        return $this->permissions[$name] ?? false;
    }

    /**
     * Shortcut for filtering CRUD methods
     */
    public static function filterMethod( string $methodName, callable|array $callback, $priority = 10, $arguments = 1 ): mixed
    {
        return Hook::addFilter( self::method( $methodName ), $callback, $priority, $arguments );
    }

    /**
     * Return if the table show display raw actions.
     */
    public function getShowOptions(): bool
    {
        return $this->showOptions;
    }

    /**
     * Return if the table show display raw checkboxes.
     */
    public function getShowCheckboxes(): bool
    {
        return $this->showCheckboxes;
    }

    /**
     * Will check if the provided model
     * has dependencies declared and existing
     * to prevent any deletion.
     */
    public function handleDependencyForDeletion( mixed $model ): void
    {
        if ( method_exists( $model, 'getDeclaredDependencies' ) ) {
            /**
             * Let's verify if the current model
             * is a dependency for other models.
             */
            $declaredDependencies = $model->getDeclaredDependencies();

            foreach ( $declaredDependencies as $class => $indexes ) {
                $localIndex = $indexes['local_index'] ?? 'id';
                $request = $class::where( $indexes['foreign_index'], $model->$localIndex );
                $dependencyFound = $request->first();
                $countDependency = $request->count() - 1;

                if ( $dependencyFound instanceof $class ) {
                    if ( isset( $model->{$indexes['local_name']} ) && ! empty( $indexes['foreign_name'] ) ) {
                        /**
                         * if the foreign name is an array
                         * we'll pull the first model set as linked
                         * to the item being deleted.
                         */
                        if ( is_array( $indexes['foreign_name'] ) ) {
                            $relatedSubModel = $indexes['foreign_name'][0]; // model name
                            $localIndex = $indexes['foreign_name'][1]; // local index on the dependency table $dependencyFound
                            $foreignIndex = $indexes['foreign_name'][2] ?? 'id'; // foreign index on the related table $model
                            $labelColumn = $indexes['foreign_name'][3] ?? 'name'; // foreign index on the related table $model

                            /**
                             * we'll find if we find the model
                             * for the provided details.
                             */
                            $result = $relatedSubModel::where( $foreignIndex, $dependencyFound->$localIndex )->first();

                            /**
                             * the model might exists. If that doesn't exists
                             * then probably it's not existing. There might be a misconfiguration
                             * on the relation.
                             */
                            if ( $result instanceof $relatedSubModel ) {
                                $foreignName = $result->$labelColumn ?? __( 'Unidentified Item' );
                            } else {
                                $foreignName = $result->$labelColumn ?? __( 'Non-existent Item' );
                            }
                        } else {
                            $foreignName = $dependencyFound->{$indexes['foreign_name']} ?? __( 'Unidentified Item' );
                        }

                        /**
                         * The local name will always pull from
                         * the related model table.
                         */
                        $localName = $model->{$indexes['local_name']};

                        throw new NotAllowedException( sprintf(
                            __( 'Unable to delete "%s" as it\'s a dependency for "%s"%s' ),
                            $localName,
                            $foreignName,
                            $countDependency >= 1 ? ' ' . trans_choice( '{1} and :count more item.|[2,*] and :count more items.', $countDependency, ['count' => $countDependency] ) : '.'
                        ) );
                    } else {
                        throw new NotAllowedException( sprintf(
                            $countDependency === 1 ?
                                __( 'Unable to delete this resource as it has %s dependency with %s item.' ) :
                                __( 'Unable to delete this resource as it has %s dependency with %s items.' ),
                            $class
                        ) );
                    }
                }
            }
        }
    }

    /**
     * Returns the defined casts
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * We want to restrict links if matching
     * permissons is explicitely disabled by the user
     */
    public function getFilteredLinks(): array
    {
        $links = $this->getLinks();

        $mapping = [
            'create' => ['post', 'create'],
            'read' => ['list'],
            'update' => ['put', 'update'],
            'delete' => ['delete'],
        ];

        return collect( $links )
            ->filter( function ( $value, $key ) use ( $mapping ) {
                $rightVerb = collect( $mapping )->map( fn ( $value, $mapKey ) => (
                    in_array( $key, $value ) ? $mapKey : false
                ) )->filter();

                return $this->getPermission( $rightVerb->first() ?: null );
            } )
            ->toArray();
    }

    /**
     * This will loads the header buttons that can be used
     * to render custom vue component on the crud table.
     */
    public function getHeaderButtons(): array
    {
        return [];
    }

    public function getTableFooter( Output $output ): Output
    {
        return $output;
    }
}
