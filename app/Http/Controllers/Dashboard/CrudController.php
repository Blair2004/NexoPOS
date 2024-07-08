<?php
/**
 * @todo review for api-server
 */

namespace App\Http\Controllers\Dashboard;

use App\Events\CrudAfterDeleteEvent;
use App\Events\CrudBeforeExportEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use App\Http\Requests\CrudPostRequest;
use App\Http\Requests\CrudPutRequest;
use App\Services\CrudService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudController extends DashboardController
{
    public function __construct()
    {
        $this->middleware( function ( $request, $next ) {
            return $next( $request );
        } );
    }

    /**
     * CRUD delete we expect this request to be
     * provided by an Ajax Request
     *
     * @param void
     * @return view
     */
    public function crudDelete( $namespace, $id )
    {
        /**
         * Catch event before deleting user
         */
        $service = new CrudService;
        $resource = $service->getCrudInstance( $namespace );
        $modelClass = $resource->getModel();
        $model = $modelClass::find( $id );

        if ( ! $model instanceof $modelClass ) {
            throw new NotFoundException( __( 'Unable to delete an entry that no longer exists.' ) );
        }

        /**
         * Run the filter before deleting
         */
        if ( method_exists( $resource, 'beforeDelete' ) ) {
            /**
             * the callback should return an empty value to proceed.
             */
            if ( ! empty( $response = $resource->beforeDelete( $namespace, $id, $model ) ) ) {
                return $response;
            }
        }

        $resource->handleDependencyForDeletion( $model );

        $model->delete();

        /**
         * That will trigger everytime an instance is deleted.
         */
        event( new CrudAfterDeleteEvent( $resource, (object) $model->toArray() ) );

        return [
            'status' => 'success',
            'message' => __( 'The entry has been successfully deleted.' ),
        ];
    }

    /**
     * Dashboard Crud POST
     * receive and treat POST request for CRUD Resource
     *
     * @return void
     */
    public function crudPost( string $namespace, CrudPostRequest $request )
    {
        $service = new CrudService;
        $inputs = $request->getPlainData( $namespace );

        return $service->submitRequest( $namespace, $inputs );
    }

    /**
     * Dashboard CRUD PUT
     * receive and treat a PUT request for CRUD resource
     *
     * @param  string $namespace
     * @param  int    $id        primary key
     * @return void
     */
    public function crudPut( $namespace, $id, CrudPutRequest $request )
    {
        $service = new CrudService;
        $inputs = $request->getPlainData( $namespace );

        return $service->submitRequest( $namespace, $inputs, $id );
    }

    /**
     * Crud List
     *
     * @return array of results
     */
    public function crudList( string $namespace )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( sprintf( __( 'Unable to load the CRUD resource : %s.' ), $crudClass ) );
        }

        /**
         * @var CrudService
         */
        $resource = new $crudClass;
        $resource->allowedTo( 'read' );

        return $resource->getEntries();
    }

    /**
     * CRUD Bulk Action
     *
     * @param string namespace
     * @return void
     */
    public function crudBulkActions( string $namespace, Request $request )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( __( 'Unhandled crud resource' ) );
        }

        $resource = new $crudClass;

        /**
         * Check if an entry is selected,
         * else throw an error
         */
        if ( $request->input( 'entries' ) == null ) {
            throw new Exception( __( 'You need to select at least one item to delete' ) );
        }

        if ( $request->input( 'action' ) == null ) {
            throw new Exception( __( 'You need to define which action to perform' ) );
        }

        /**
         * assuming we're bulk performing an action
         * we're expecting an array response with successful
         * operations and failed operations.
         */
        $response = Hook::filter( get_class( $resource ) . '@bulkAction', $resource->bulkAction( $request ), $request );

        return [
            'status' => 'success',
            'message' => sprintf(
                $response[ 'message' ] ?? __( '%s has been processed, %s has not been processed.' ),
                $response[ 'success' ] ?? 0,
                $response[ 'error' ] ?? 0
            ),
            'data' => $response,
        ];
    }

    /**
     * Crud GET
     *
     * @param string resource namespace
     * @return CRUD Response
     */
    public function crudGet( string $namespace, Request $request )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * Let's check it the resource has a method to retrieve an item
         *
         * @var CrudService
         */
        $resource = new $crudClass;
        $resource->allowedTo( 'read' );

        if ( method_exists( $resource, 'getEntries' ) ) {
            return $resource->getEntries( $request );
        } else {
            throw new Exception( __( 'Unable to retrieve items. The current CRUD resource doesn\'t implement "getEntries" methods' ) );
        }
    }

    /**
     * get column for a specific namespace
     *
     * @param string CRUD resource namespace
     * @return TableConfig
     */
    public function getColumns( string $namespace )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );
        $resource = new $crudClass;
        $resource->allowedTo( 'read' );

        if ( method_exists( $resource, 'getEntries' ) ) {
            return Hook::filter(
                get_class( $resource ) . '@getColumns',
                $resource->getColumns()
            );
        }

        return response()->json( [
            'status' => 'error',
            'message' => __( 'Unable to proceed. No matching CRUD resource has been found.' ),
        ], 403 );
    }

    /**
     * return an entre crud configuration
     *
     * @return array
     */
    public function getConfig( string $namespace )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );

        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( sprintf(
                __( 'The class "%s" is not defined. Does that crud class exists ? Make sure you\'ve registered the instance if it\'s the case.' ),
                $crudClass
            ) );
        }

        $resource = new $crudClass;
        $resource->allowedTo( 'read' );

        if ( method_exists( $resource, 'getEntries' ) ) {
            return [
                'columns' => Hook::filter(
                    get_class( $resource ) . '@getColumns',
                    $resource->getColumns()
                ),
                'queryFilters' => Hook::filter( get_class( $resource ) . '@getQueryFilters', $resource->getQueryFilters() ),
                'labels' => Hook::filter( get_class( $resource ) . '@getLabels', $resource->getLabels() ),
                'links' => Hook::filter( get_class( $resource ) . '@getFilteredLinks', $resource->getFilteredLinks() ?? [] ),
                'bulkActions' => Hook::filter( get_class( $resource ) . '@getBulkActions', $resource->getBulkActions() ),
                'prependOptions' => Hook::filter( get_class( $resource ) . '@getPrependOptions', $resource->getPrependOptions() ),
                'showOptions' => Hook::filter( get_class( $resource ) . '@getShowOptions', $resource->getShowOptions() ),
                'showCheckboxes' => Hook::filter( get_class( $resource ) . '@getShowCheckboxes', $resource->getShowCheckboxes() ),
                'headerButtons' => Hook::filter( get_class( $resource ) . '@getHeaderButtons', $resource->getHeaderButtons() ),
                'namespace' => $namespace,
            ];
        }

        return response()->json( [
            'status' => 'error',
            'message' => __( 'Unable to proceed. No matching CRUD resource has been found.' ),
        ], 403 );
    }

    /**
     * get create form config
     *
     * @param namespace
     * @return array | AsyncResponse
     */
    public function getFormConfig( string $namespace, $id = null )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );
        $resource = new $crudClass( compact( 'namespace', 'id' ) );
        $resource->allowedTo( 'read' );

        if ( method_exists( $resource, 'getEntries' ) ) {
            $model = $resource->get( 'model' );
            $model = $model::find( $id );
            $form = $resource->getForm( $model );

            /**
             * @since 4.4.3
             *
             * @todo it's no use providing compact( 'model' ) as a second parameter, if it's
             * for providing only one parameter. We should directly pass the model.
             */
            $form = Hook::filter( get_class( $resource )::method( 'getForm' ), $form, compact( 'model' ) );
            $config = [
                'form' => $form,
                'labels' => Hook::filter( get_class( $resource ) . '@getLabels', $resource->getLabels() ),
                'links' => Hook::filter( get_class( $resource ) . '@getLinks', $resource->getLinks() ),
                'namespace' => $namespace,
            ];

            return $config;
        }

        return response()->json( [
            'status' => 'error',
            'message' => __( 'Unable to proceed. No matching CRUD resource has been found.' ),
        ], 403 );
    }

    /**
     * Export the entries as a CSV file
     *
     * @param  string $namespace
     * @return array  $response
     */
    public function exportCrud( $namespace, Request $request )
    {
        $crudClass = Hook::filter( 'ns-crud-resource', $namespace );

        $resource = new $crudClass;
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        /**
         * only users having read capability
         * can download a CSV file.
         */
        $resource->allowedTo( 'read' );

        $columns = Hook::filter(
            get_class( $resource ) . '@getColumns',
            $resource->getExportColumns() ?: $resource->getColumns()
        );

        /**
         * We'll make sure th provide enough columns to ensure
         * long tables are exported successfully.
         */
        $sheetCol1 = [ '', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
        $sheetCol2 = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' ];
        $sheetColumns = [];

        foreach ( $sheetCol1 as $col ) {
            foreach ( $sheetCol2 as $col2 ) {
                $sheetColumns[] = $col . $col2;
            }
        }

        if ( count( array_values( $columns ) ) > count( $sheetColumns ) ) {
            throw new Exception( __( 'The crud columns exceed the maximum column that can be exported (27)' ) );
        }

        /**
         * in case custom custom export columns are provided
         * we'll make sure to use them instead of the
         * default columns.
         */
        foreach ( array_values( $columns ) as $index => $column ) {
            $sheet->setCellValue( $sheetColumns[ $index ] . '1', $column[ 'label' ] );
        }

        /**
         * We'll disable the perPage argument to make
         * sure to pull all the data from the database.
         */
        $config = [ 'perPage' => false ];

        /**
         * let's check if the request include
         * specific entries to export
         */
        if ( $request->input( 'entries' ) ) {
            $config[ 'pick' ] = $request->input( 'entries' );
        }

        $entries = $resource->getEntries( $config );
        $totalColumns = 0;

        /**
         * We can't export if there is
         * nothing to export, so we'll skip that.
         */
        if ( count( $entries[ 'data' ] ) === 0 ) {
            throw new NotAllowedException( __( 'Unable to export if there is nothing to export.' ) );
        }

        foreach ( $entries[ 'data' ] as $rowIndex => $entry ) {
            foreach ( $columns as $columnName => $column ) {
                $sheet->setCellValue( $sheetColumns[ $totalColumns ] . ( $rowIndex + 2 ), strip_tags( $entry->$columnName ) );
                $totalColumns++;
            }
            $totalColumns = 0;
        }

        /**
         * We'll emit an event to allow any procees
         * to edit the current file.
         */
        CrudBeforeExportEvent::dispatch( $sheet, $totalColumns, $rowIndex, $sheetColumns, $entries );

        /**
         * let's define what will be the output name
         * of the exported file.
         */
        if ( ! is_dir( storage_path( 'app/public/exports' ) ) ) {
            mkdir( storage_path( 'app/public/exports' ) );
        }

        $dateFormat = Str::slug( ns()->date->toDateTimeString() );
        $relativePath = 'exports/' . Str::slug( $resource->getLabels()[ 'list_title' ] ) . '-' . $dateFormat . '.csv';
        $fileName = storage_path( 'app/public/' . $relativePath );

        /**
         * We'll prepare the writer
         * and output the file.
         */
        $writer = new Csv( $spreadsheet );
        $writer->save( $fileName );

        /**
         * We'll hide the asset URL behind random lettes
         */
        $hash = Str::random( 20 );

        Cache::put( $hash, $relativePath, now()->addMinutes( 5 ) );

        return [
            'url' => route( 'ns.dashboard.crud-download', compact( 'hash' ) ),
        ];
    }

    public function downloadSavedFile( $hash )
    {
        $relativePath = Cache::pull( $hash );

        if ( $relativePath === null ) {
            throw new NotAllowedException( __( 'This link has expired.' ) );
        }

        if ( Storage::disk( 'public' )->exists( $relativePath ) ) {
            return Storage::disk( 'public' )->download( $relativePath );
        }

        throw new NotFoundException( __( 'The requested file cannot be downloaded or has already been downloaded.' ) );
    }
}
