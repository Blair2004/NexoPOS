<?php

namespace App\Services;

use App\Exceptions\NotAllowedException;
use App\Models\NsModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View as ContractView;
use Illuminate\Database\Eloquent\Model;
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
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = false;

    /**
     * Determine if actions should be displayed
     */
    protected $showOptions = true;

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
     * @param string $namespace
     * @param array $inputs
     * @param mixed $id
     * @return array as a crud response
     */
    public function submitPreparedRequest( $namespace, $inputs, $id = null ): array
    {
        $crudInstance = $this->getCrudInstance( $namespace );
        $model = $id !== null ? $crudInstance->getModel()::find( $id ) : null;
        $data = $this->getFlatForm( $crudInstance, $inputs, $model );

        return $this->submitRequest( $namespace, $data, $id );
    }

    /**
     * Will submit a request to the current
     * crud instance using the input provided
     *
     * @param array $inputs
     * @param int $id
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
     * @param string $namespace
     * @param array $inputs
     * @param int|null $id
     * @return array $response
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
                    }

                    /**
                     * sanitizing input to remove
                     * all script tags
                     */
                    if ( ! empty( $entry->$name ) && ! [ $entry->$name ] ) {
                        $entry->$name = strip_tags( $entry->$name );
                    }
                }
            }

            /**
             * If fillable is empty or if "author" it's explicitly
             * mentionned on the fillable array.
             */
            if ( empty( $fillable ) || in_array( 'author', $fillable ) ) {
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
                $foreighKey = $relationParams[2];

                if ( ! empty( $fields ) ) {
                    $model = $class::where( $localKey, $entry->$foreighKey )->first();

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

                    $model->$localKey = $entry->$foreighKey;
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

        /**
         * @todo adding a link to edit the new entry
         */
        return [
            'status' => 'success',
            'entry' => $entry, // deprecated
            'data' => [
                'entry' => $entry,
                'editUrl' => str_contains( $resource->getLinks()[ 'edit' ], '{id}' ) ? Str::replace( '{id}', $entry->id, $resource->getLinks()[ 'edit' ] ) : false,
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
    public function isEnabled( $feature ): bool|null
    {
        return @$this->features[ $feature ];
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
     * @param array $definition
     * @param array $searchKeyValue
     */
    public function handleDefinitionOperator( $query, $definition, $searchKeyValue ): void
    {
        extract( $searchKeyValue );
        /**
         * @param string $key
         * @param mixed $value
         */
        if ( isset( $definition[ 'operator' ] ) ) {
            $query->where( $key, $definition[ 'operator' ], $value );
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
            Cache::put( 'table-columns-' . $table, $columns, Carbon::now()->addDays(1) );
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
                        });
                    }
                }
            });

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
                     * If the CRUD instance has osme entries
                     * that are picked, we'll allow extensibility
                     * using the filter "getPicked".
                     */
                    $pick = Hook::filter( self::method( 'getPicked' ), $this->getPicked() );

                    $hasAlias = explode( ' as ', $relation[0] ); // if there is an alias, let's just pick the table name
                    $hasAlias[0] = $this->hookTableName( $hasAlias[0] ); // make the table name hookable
                    $aliasName = $hasAlias[1] ?? false; // for aliased relation. The pick use the alias as a reference.
                    $columns = collect( Schema::getColumnListing( count( $hasAlias ) === 2 ? trim( $hasAlias[0] ) : $relation[0] ) )
                        ->filter( function ( $column ) use ( $pick, $table, $aliasName ) {
                            $picked = $pick[ $aliasName ? trim( $aliasName ) : $table ] ?? [];
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

                foreach ( $columns as $index => $column ) {
                    $hasAlias = explode( ' as ', $relation[0]);
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
            $query = call_user_func_array([ $query, 'select' ], $select );

            foreach ( $this->getRelations() as $junction => $relation ) {
                /**
                 * if no junction statement is provided
                 * then let's make it inner by default
                 */
                $junction = is_numeric( $junction ) ? 'join' : $junction;

                if ( in_array( $junction, [ 'join', 'leftJoin', 'rightJoin', 'crossJoin' ] ) ) {
                    if ( $junction !== 'join' ) {
                        foreach ( $relation as $junction_relation ) {
                            $hasAlias = explode( ' as ', $junction_relation[0]);
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
                                $query->$junction( trim($hasAlias[0]) . ' as ' . trim($hasAlias[1]), $junction_relation[1], $junction_relation[2], $junction_relation[3] );
                            } else {
                                $query->$junction( $junction_relation[0], $junction_relation[1], $junction_relation[2], $junction_relation[3] );
                            }
                        }
                    } else {
                        $hasAlias = explode( ' as ', $relation[0]);
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

                    $definition = collect( $this->queryFilters )->filter( fn( $filter ) => $filter[ 'name' ] === $key )->first();

                    if ( ! empty( $definition ) ) {
                        switch ( $definition[ 'type' ] ) {
                            case 'daterangepicker':
                                if ( $value[ 'startDate' ] !== null && $value[ 'endDate' ] !== null ) {
                                    $query->where( $key, '>=', Carbon::parse( $value[ 'startDate' ] )->toDateTimeString() );
                                    $query->where( $key, '<=', Carbon::parse( $value[ 'endDate' ] )->toDateTimeString() );
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
        $perPage = $config[ 'per_page' ] ?? 20;
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
            });
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
         * if some enties ID are provided. These
         * reference will only be part of the result.
         */
        if ( isset( $config[ 'pick' ] ) ) {
            $query->whereIn( $this->hookTableName( $this->table ) . '.id', $config[ 'pick' ] );
        }

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
        $entries[ 'data' ] = collect( $entries[ 'data' ] )->map( function ( $entry ) {
            $entry = new CrudEntry( (array) $entry );

            /**
             * apply casting to crud resources
             * as it's defined by the class casting
             *
             * @todo add support for default casting.
             */
            $casts = ( new $this->model )->casts;

            if ( ! empty( $casts ) ) {
                foreach ( $casts as $column => $cast ) {
                    if ( class_exists( $cast ) ) {
                        $castObject = new $cast;
                        $entry->$column = $castObject->get( $entry, $column, $entry->$column, []);
                    }
                }
            }

            /**
             * We'll allow any resource to mutate the
             * entries but make sure to keep the originals.
             */
            $entry = Hook::filter( $this->namespace . '-crud-actions', $entry );
            $entry = Hook::filter( get_class( $this )::method( 'setActions' ), $entry );

            return $entry;
        });

        return $entries;
    }

    protected function hookTableName( $tableName ): string
    {
        return Hook::filter( 'ns-model-table', $tableName );
    }

    public function hook( $query ): void
    {
        //
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
            throw new Exception( __( 'Unhandled crud resource' ) );
        }

        return new $crudClass;
    }

    /**
     * Extracts Crud validation from a crud resource
     *
     * @param Crud $resource
     * @return arra
     */
    public function extractCrudValidation( $crud, $model = null ): array
    {
        $form = Hook::filter( 'ns.crud.form', $crud->getForm( $model ), $crud->getNamespace(), compact( 'model' ) );

        if ( is_subclass_of( $crud, CrudService::class ) ) {
            $form = Hook::filter( get_class( $crud )::method( 'getForm' ), $crud->getForm( $model ), compact( 'model' ) );
        }

        $rules = [];

        if ( isset( $form[ 'main' ][ 'validation' ] ) ) {
            $rules[ $form[ 'main' ][ 'name' ] ] = $form[ 'main' ][ 'validation' ];
        }

        foreach ( $form[ 'tabs' ] as $tabKey => $tab ) {
            if ( ! empty( $tab[ 'fields' ] ) ) {
                foreach ( $tab[ 'fields' ] as $field ) {
                    if ( isset( $field[ 'validation' ] ) ) {
                        $rules[ $tabKey ][ $field[ 'name' ] ] = $field[ 'validation' ];
                    }
                }
            }
        }

        return $rules;
    }

    public function getForm()
    {
        return [];
    }

    /**
     * Will extract form with the entry
     * as a reference for the values.
     *
     * @param object $entry
     * @return array $final
     */
    public function getExtractedForm( $entry = null, $multiEntry = false )
    {
        $form = $this->getForm( $entry );

        $final = [];

        if ( isset( $form[ 'main' ][ 'validation' ] ) ) {
            $final[ $form[ 'main' ][ 'name' ] ] = $form[ 'main' ][ 'value' ];
        }

        /**
         * this is specific to products
         */
        if ( isset( $form[ 'variations' ] ) ) {
            foreach ( $form[ 'variations' ] as $variation ) {
                if ( $multiEntry ) {
                    $final[ 'variations' ][] = $this->extractTabs( $variation );
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

        foreach ( $form[ 'tabs' ] as $tabKey => $tab ) {
            if ( ! empty( $tab[ 'fields' ] ) ) {
                foreach ( $tab[ 'fields' ] as $field ) {
                    if ( isset( $field[ 'value' ] ) ) {
                        $final[ $tabKey ][ $field[ 'name' ] ] = $field[ 'value' ];
                    }
                }
            }
        }

        return $final;
    }

    /**
     * Return flat fields for the crud form provided
     *
     * @param CrudService
     * @param array $fields
     * @param Model|null $model
     */
    public function getFlatForm( $crud, $fields, $model = null ): array
    {
        $form = Hook::filter( 'ns.crud.form', $crud->getForm( $model ), $crud->getNamespace(), compact( 'model' ) );

        if ( is_subclass_of( $crud, CrudService::class ) ) {
            $form = Hook::filter( get_class( $crud )::method( 'getForm' ), $crud->getForm( $model ), compact( 'model' ) );
        }

        $data = [];

        if ( isset( $form[ 'main' ][ 'name' ] ) ) {
            $data[ $form[ 'main' ][ 'name' ] ] = $fields[ $form[ 'main' ][ 'name' ] ];
        }

        foreach ( $form[ 'tabs' ] as $tabKey => $tab ) {
            /**
             * if the object bein used is not an instance
             * of a Crud and include the method, let's skip
             * this.
             */
            $keys = [];
            if ( method_exists( $crud, 'getTabsRelations' ) ) {
                $keys = array_keys( $crud->getTabsRelations() );
            }

            /**
             * We're ignoring the tabs
             * that are linked to a model.
             */
            if ( ! in_array( $tabKey, $keys ) && ! empty( $tab[ 'fields' ] ) ) {
                foreach ( $tab[ 'fields' ] as $field ) {
                    $value = data_get( $fields, $tabKey . '.' . $field[ 'name' ] );

                    /**
                     * if the field doesn't have any value
                     * we'll omit it. To avoid filling wrong value
                     */
                    if ( ! empty( $value ) || (int) $value === 0 ) {
                        $data[ $field[ 'name' ] ] = $value;
                    }
                }
            }
        }

        /**
         * We'll add custom fields
         * that might be added by modules
         */
        $fieldsToIgnore = array_keys( collect( $form[ 'tabs' ] )->toArray() );

        foreach ( $fields as $field => $value ) {
            if ( ! in_array( $field, $fieldsToIgnore ) ) {
                $data[ $field ] = $value;
            }
        }

        return $data;
    }

    /**
     * Return plain data that can be used
     * for inserting. The data is parsed from the defined
     * form on the Request
     *
     * @param Crud $resource
     * @return array
     */
    public function getPlainData( $crud, Request $request, $model = null )
    {
        $fields = $request->post();

        return $this->getFlatForm( $crud, $fields, $model );
    }

    /**
     * To pull out the tabs relations
     */
    public function getTabsRelations(): array
    {
        return $this->tabsRelations;
    }

    /**
     * Isolate Rules that use the Rule class
     *
     * @param array
     */
    public function isolateArrayRules( $arrayRules, $parentKey = '' ): array
    {
        $rules = [];

        foreach ( $arrayRules as $key => $value ) {
            if ( is_array( $value ) && collect( array_keys( $value ) )->filter( function ( $key ) {
                return is_string( $key );
            })->count() > 0 ) {
                $rules = array_merge( $rules, $this->isolateArrayRules( $value, $key ) );
            } else {
                $rules[] = [ ( ! empty( $parentKey ) ? $parentKey . '.' : '' ) . $key, $value ];
            }
        }

        return $rules;
    }

    public static function table( $config = [] ): ContractView
    {
        $className = get_called_class();
        $instance = new $className;

        /**
         * "manage.profile" is the default permission
         * granted to every user. If a permission check return "false"
         * that means performing that action is disabled.
         */
        if ( $instance->getPermission( 'read' ) !== false ) {
            ns()->restrict([ $instance->getPermission( 'read' ) ]);
        } else {
            throw new NotAllowedException;
        }

        return View::make( 'pages.dashboard.crud.table', array_merge([
            /**
             * that displays the title on the page.
             * It fetches the value from the labels
             */
            'title' => Hook::filter( $instance::method( 'getLabels' ), $instance->getLabels() )[ 'list_title' ],

            /**
             * That displays the page description. This allow pull the value
             * from the labels.
             */
            'description' => Hook::filter( $instance::method( 'getLabels' ), $instance->getLabels() )[ 'list_description' ],

            /**
             * This create the src URL using the "namespace".
             */
            'src' => ns()->url( '/api/nexopos/v4/crud/' . $instance->namespace ),

            /**
             * This pull the creation link. That link should takes the user
             * to the creation form.
             */
            'createUrl' => Hook::filter( $instance::method( 'getLinks' ), $instance->getLinks() )[ 'create' ] ?? '#',

            /**
             * Provided to render the side menu.
             */
            'menus' => app()->make( MenuService::class ),

            /**
             * to provide custom query params
             * to every outgoing request on the table
             */
            'queryParams' => [],
        ], $config ) );
    }

    /**
     * Will render a form UI
     *
     * @param Model|null reference passed
     * @param array custom configuration
     */
    public static function form( $entry = null, $config = [] )
    {
        $className = get_called_class();
        $instance = new $className;
        $permissionType = $entry === null ? 'create' : 'update';

        /**
         * if a permission for creating or updating is
         * not disabled let's make a validation.
         */
        if ( $instance->getPermission( $permissionType ) !== false ) {
            ns()->restrict([ $instance->getPermission( $permissionType ) ]);
        } else {
            throw new NotAllowedException( __( 'You\'re not allowed to see this page.' ) );
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
            'title' => $config[ 'title' ] ?? ( $entry === null ? $instance->getLabels()[ 'create_title' ] : $instance->getLabels()[ 'edit_title' ] ),

            /**
             * this pull the description either the form is made to
             * create or edit a resource.
             */
            'description' => $config[ 'description' ] ?? ( $entry === null ? $instance->getLabels()[ 'create_description' ] : $instance->getLabels()[ 'edit_description' ] ),

            /**
             * this automatically build a source URL based on the identifier
             * provided. But can be overwritten with the config.
             */
            'src' => $config[ 'src' ] ?? ( ns()->url( '/api/nexopos/v4/crud/' . $instance->namespace . '/' . ( ! empty( $entry ) ? 'form-config/' . $entry->id : 'form-config' ) ) ),

            /**
             * this use the built in links to create a return URL.
             * It can also be overwritten by the configuration.
             */
            'returnUrl' => $config[ 'returnUrl' ] ?? ( $instance->getLinks()[ 'list' ] ?? '#' ),

            /**
             * This will pull the submitURL that might be different whether the $entry is
             * provided or not. can be overwritten on the configuration ($config).
             */
            'submitUrl' => $config[ 'submitUrl' ] ?? ( $entry === null ? $instance->getLinks()[ 'post' ] : str_replace( '{id}', $entry->id, $instance->getLinks()[ 'put' ] ) ),

            /**
             * By default the method used is "post" but might change to "put" according to
             * whether the entry is provided (Model). Can be changed from the $config.
             */
            'submitMethod' => $config[ 'submitMethod' ] ?? ( $entry === null ? 'post' : 'put' ),

            /**
             * This will pass an instance of the MenuService.
             */
            'menus' => app()->make( MenuService::class ),

            /**
             * provide the current crud namespace
             */
            'namespace' => $instance->getNamespace(),
        ]);
    }

    /**
     * perform a quick check over
     * the permissions array provided on the instance
     */
    public function allowedTo( $permission )
    {
        if ( isset( $this->permissions ) && $this->permissions[ $permission ] !== false ) {
            ns()->restrict( $this->permissions[ $permission ] );
        } else {
            throw new NotAllowedException;
        }
    }

    /**
     * retrieve one of the declared permissions
     * the name must either be "create", "read", "update", "delete".
     *
     * @param string $name
     * @return string $permission
     */
    public function getPermission( $name )
    {
        return $this->permissions[ $name ] ?? false;
    }

    /**
     * Provide a callback notation for
     * a specific method
     *
     * @param string $methodName
     * @return string
     */
    public static function method( $methodName )
    {
        return get_called_class() . '@' . $methodName;
    }

    /**
     * Shortcut for filtering CRUD methods
     *
     * @param string $methodName
     * @param callable $callback
     * @return mixed
     */
    public static function filterMethod( $methodName, $callback )
    {
        return Hook::filter( self::method( $methodName ), $callback );
    }

    /**
     * Return if the table show display raw actions.
     *
     * @return bool
     */
    public function getShowOptions()
    {
        return $this->showOptions;
    }

    /**
     * Will check if the provided model
     * has dependencies declared and existing
     * to prevent any deletion.
     *
     * @param NsModel
     */
    public function handleDependencyForDeletion( $model )
    {
        if ( method_exists( $model, 'getDeclaredDependencies' ) ) {
            /**
             * Let's verify if the current model
             * is a dependency for other models.
             */
            $declaredDependencies = $model->getDeclaredDependencies();

            foreach ( $declaredDependencies as $class => $indexes ) {
                $localIndex = $indexes[ 'local_index' ] ?? 'id';
                $request = $class::where( $indexes[ 'foreign_index' ], $model->$localIndex );
                $dependencyFound = $request->first();
                $countDependency = $request->count() - 1;

                if ( $dependencyFound instanceof $class ) {
                    if ( isset( $model->{ $indexes[ 'local_name' ] } ) && ! empty( $indexes[ 'foreign_name' ] ) ) {
                        /**
                         * if the foreign name is an array
                         * we'll pull the first model set as linked
                         * to the item being deleted.
                         */
                        if ( is_array( $indexes[ 'foreign_name' ] ) ) {
                            $relatedSubModel = $indexes[ 'foreign_name' ][0]; // model name
                            $localIndex = $indexes[ 'foreign_name' ][1]; // local index on the dependency table $dependencyFound
                            $foreignIndex = $indexes[ 'foreign_name' ][2] ?? 'id'; // foreign index on the related table $model
                            $labelColumn = $indexes[ 'foreign_name' ][3] ?? 'name'; // foreign index on the related table $model

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
                            $foreignName = $dependencyFound->{ $indexes[ 'foreign_name' ] } ?? __( 'Unidentified Item' );
                        }

                        /**
                         * The local name will always pull from
                         * the related model table.
                         */
                        $localName = $model->{ $indexes[ 'local_name' ] };

                        throw new NotAllowedException( sprintf(
                            __( 'Unable to delete "%s" as it\'s a dependency for "%s"%s' ),
                            $localName,
                            $foreignName,
                            $countDependency >= 1 ? ' ' . trans_choice( '{1} and :count more item.|[2,*] and :count more items.', $countDependency, [ 'count' => $countDependency ] ) : '.'
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
}
