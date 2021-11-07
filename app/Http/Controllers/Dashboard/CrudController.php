<?php
/**
 * @todo review for api-server
 */
namespace App\Http\Controllers\Dashboard;

use App\Exceptions\NotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Exception;
use App\Http\Controllers\DashboardController;
use App\Http\Requests\CrudPostRequest;
use App\Http\Requests\CrudPutRequest;
use App\Services\CrudService;
use TorMorten\Eventy\Facades\Events as Hook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class CrudController extends DashboardController
{
    public function __construct()
    {
        $this->middleware( function( $request, $next ) {
            return $next( $request );
        });
    }

    /**
     * CRUD delete we expect this request to be 
     * provided by an Ajax Request
     * @param void
     * @return view
     */
    public function crudDelete( $namespace, $id )
    {
        /**
         * Catch event before deleting user
         */
        $service    =   new CrudService;
        $resource   =   $service->getCrudInstance( $namespace );
        $modelClass =   $resource->getModel();
        $model      =   $modelClass::find( $id );

        /**
         * Run the filter before deleting
         */
        if ( method_exists( $resource, 'beforeDelete' ) ) {

            /**
             * the callback should return an empty value to proceed.
             */
            if( ! empty( $response = $resource->beforeDelete( $namespace, $id, $model ) ) ) {
                return $response;
            }
        }

        $model->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The entry has been successfully deleted.' )
        ];
    }

    /**
     * Dashboard Crud POST
     * receive and treat POST request for CRUD Resource
     * @param void
     * @return void
     */
    public function crudPost( String $namespace, CrudPostRequest $request )
    {
        $service    =   new CrudService;
        $resource   =   $service->getCrudInstance( $namespace );
        $model      =   $resource->getModel();
        $entry      =   new $model;

        /**
         * Filter POST input
         * check if on the CRUD resource the filter exists
         */
        $inputs         =   $request->getPlainData( $namespace );

        if ( method_exists( $resource, 'filterPostInputs' ) ) {
            $inputs     =   $resource->filterPostInputs( $inputs, $entry );
        }

        /**
         * this trigger a global filter
         * on the actual crud instance
         */
        $inputs     =   Hook::filter( 
            get_class( $resource ) . '@filterPostInputs', 
            $inputs
        );

        if ( method_exists( $resource, 'beforePost' ) ) {
            $resource->beforePost( $request );
        }

        /**
         * If we would like to handle the PUT request
         * by any other handler than the CrudService
         */
        if ( ! $resource->disablePut ) {

            $fillable   =   Hook::filter( 
                get_class( $resource ) . '@getFillable', 
                $resource->getFillable()
            );

            foreach ( $inputs as $name => $value ) {

                /**
                 * If submitted field are part of fillable fields
                 */
                if ( in_array( $name, $fillable ) || count( $fillable ) === 0 ) {

                    /**
                     * We might give the capacity to filter fields 
                     * before storing. This can be used to apply specific formating to the field.
                     */
                    if ( method_exists( $resource, 'filterPost' ) ) {
                        $entry->$name   =   $resource->filterPost( $value, $name );
                    } else {
                        $entry->$name   =   $value;
                    }
                }

                /**
                 * sanitizing input to remove
                 * all script tags
                 */
                if ( ! empty( $entry->$name ) ) {
                    $entry->$name       =   strip_tags( $entry->$name );
                }
            }
            
            /**
             * If fillable is empty or if "author" it's explicitely
             * mentionned on the fillable array.
             */
            if ( empty( $fillable ) || in_array( 'author', $fillable ) ) {
                $entry->author      =   Auth::id();
            }

            $entry->save();

            /**
             * loop the tabs relations
             * and store it
             */
            foreach( $resource->getTabsRelations() as $tab => $relationParams ) {
                $fields         =   $request->input( $tab );
                $class          =   $relationParams[0];
                $localKey       =   $relationParams[1];
                $foreighKey     =   $relationParams[2];
                
                if ( ! empty( $fields ) ) {
                    $model  =   $class::where( $localKey, $entry->$foreighKey )->first();

                    /**
                     * no relation has been found
                     * so we'll store that.
                     */
                    if ( ! $model instanceof $class ) {
                        $model  =   new $relationParams[0]; // should be the class;
                    }

                    /**
                     * We're saving here all the fields for 
                     * the related model
                     */
                    foreach( $fields as $name => $value ) {
                        $model->$name   =   $value;
                    }

                    $model->$localKey   =   $entry->$foreighKey;
                    $model->author      =   Auth::id();
                    $model->save();
                }
            }
        }

        /**
         * Create an event after crud POST
         */
        if ( method_exists( $resource, 'afterPost' ) ) {
            $resource->afterPost( $request, $entry );
        }

        /**
         * @todo adding a link to edit the new entry
         */
        return [
            'status'    =>  'success',
            'entry'     =>  $entry,
            'message'   =>  __( 'A new entry has been successfully created.' )
        ];
    }

    /**
     * Dashboard CRUD PUT
     * receive and treat a PUT request for CRUD resource
     * @param string resource namespace
     * @param int primary key
     * @param object request : CrudPutRequest
     * @return void
     */
    public function crudPut( String $namespace, $id, CrudPutRequest $request  ) 
    {
        $service    =   new CrudService;
        $resource   =   $service->getCrudInstance( $namespace );
        $model      =   $resource->getModel();
        $entry      =   $model::find( $id );

        /**
         * Filter POST input
         * check if on the CRUD resource the filter exists
         */
        $inputs         =   $request->getPlainData( $namespace, $entry );

        if ( method_exists( $resource, 'filterPutInputs' ) ) {
            $inputs     =   $resource->filterPutInputs( $inputs, $entry );
        }

        $inputs     =   Hook::filter( 
            get_class( $resource ) . '@filterPutInputs', 
            $inputs
        );

        if ( method_exists( $resource, 'beforePut' ) ) {
            $resource->beforePut( $request, $entry );
        }

        /**
         * If we would like to handle the PUT request
         * by any other handler than the CrudService
         */
        if ( ! $resource->disablePut ) {

            $fillable   =   Hook::filter( 
                get_class( $resource ) . '@getFillable', 
                $resource->getFillable()
            );

            foreach ( $inputs as $name => $value ) {
    
                /**
                 * If submitted field are part of fillable fields
                 */
                if ( in_array( $name, $fillable ) || count( $fillable ) === 0 ) {
    
                    /**
                     * We might give the capacity to filter fields 
                     * before storing. This can be used to apply specific formating to the field.
                     */
                    if ( method_exists( $resource, 'filterPut' ) ) {
                        $entry->$name   =   $resource->filterPut( $value, $name );
                    } else {
                        $entry->$name   =   $value;
                    }
                }
    
                /**
                 * sanitizing input to remove
                 * all script tags
                 */
                if ( ! empty( $entry->$name ) ) {
                    $entry->$name       =   strip_tags( $entry->$name );
                }
            }
            
            /**
             * If fillable is empty or if "author" it's explicitely
             * mentionned on the fillable array.
             */
            if ( empty( $fillable ) || in_array( 'author', $fillable ) ) {
                $entry->author      =   Auth::id();
            }
            
            $entry->save();
    
            /**
             * loop the tabs relations
             * and store it
             */
            foreach( $resource->getTabsRelations() as $tab => $relationParams ) {
                $fields         =   $request->input( $tab );
                $class          =   $relationParams[0];
                $localKey       =   $relationParams[1];
                $foreighKey     =   $relationParams[2];
                
                if ( ! empty( $fields ) ) {
                    $model  =   $class::where( $localKey, $entry->$foreighKey )->first();
    
                    /**
                     * no relation has been found
                     * so we'll store that.
                     */
                    if ( ! $model instanceof $class ) {
                        $model  =   new $relationParams[0]; // should be the class;
                    }
    
                    /**
                     * We're saving here all the fields for 
                     * the related model
                     */
                    foreach( $fields as $name => $value ) {
                        $model->$name   =   $value;
                    }
    
                    $model->$localKey   =   $entry->$foreighKey;
                    $model->author      =   Auth::id();
                    $model->save();
                }
            }
    
        }
        
        /**
         * Create an event after crud POST
         */
        if ( method_exists( $resource, 'afterPut' ) ) {
            $resource->afterPut( $request, $entry );
        }

        /**
         * @todo adding a link to edit the new entry
         */
        return [
            'status'    =>  'success',
            'entry'     =>  $entry,
            'message'   =>  __( 'A new entry has been successfully created.' )
        ];
    }

    /**
     * Crud List
     * @return array of results
     */
    public function crudList( string $namespace )
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( sprintf( __( 'Unable to load the CRUD resource : %s.' ), $crudClass ) );
        }

        $resource   =   new $crudClass;

        return $resource->getEntries();
    }

    /**
     * CRUD Bulk Action
     * @param string namespace
     * @return void
     */
    public function crudBulkActions( String $namespace, Request $request )
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( __( 'Unhandled crud resource' ) );
        }
        
        $resource   =   new $crudClass;
        
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
         * assuming we're bulk deleting
         * but the action might be different later
         */
        $response           =   Hook::filter( get_class( $resource ) . '@bulkAction', $resource->bulkAction( $request ), $request );

        return [
            'status'    =>  'success',
            'message'   =>  sprintf( 
                $response[ 'message' ] ?? __( '%s has been processed, %s has not been processed.' ), 
                $response[ 'success' ], 
                $response[ 'failed' ]
            ),
            'data'      =>  $response
        ];
    }

    /**
     * Crud GET
     * @param string resource namespace
     * @return CRUD Response
     */
    public function crudGet( string $namespace, Request $request )
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );

        /**
         * Let's check it the resource has a method to retreive an item
         * @var CrudService
         */
        $resource  =   new $crudClass;

        if ( method_exists( $resource, 'getEntries' ) ) {
            return $resource->getEntries( $request );
        } else {
            throw new Exception( __( 'Unable to retreive items. The current CRUD resource doesn\'t implement "getEntries" methods' ) );
        }
    }

    /**
     * get column for a specific namespace
     * @param string CRUD resource namespace
     * @return TableConfig
     */
    public function getColumns( string $namespace )
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );
        $resource           =   new $crudClass;

        if ( method_exists( $resource, 'getEntries' ) ) {
            return Hook::filter( 
                get_class( $resource ) . '@getColumns', 
                $resource->getColumns()
            );
        } 

        return response()->json([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to proceed. No matching CRUD resource has been found.' )
        ], 403 );
    }

    /**
     * return an entre crud configuration
     * @return array
     */
    public function getConfig( string $namespace ) 
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );

        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( sprintf( 
                __( 'The class "%s" is not defined. Does that crud class exists ? Make sure you\'ve registered the instance if it\'s the case.' ),
                $crudClass
            ) );
        }

        $resource           =   new $crudClass;

        if ( method_exists( $resource, 'getEntries' ) ) {
            return [
                'columns'               =>  Hook::filter( 
                    get_class( $resource ) . '@getColumns', 
                    $resource->getColumns()
                ),
                'queryFilters'          =>  Hook::filter( get_class( $resource ) . '@getQueryFilters', $resource->getQueryFilters() ),
                'labels'                =>  Hook::filter( get_class( $resource ) . '@getLabels', $resource->getLabels() ),
                'links'                 =>  Hook::filter( get_class( $resource ) . '@getLinks', $resource->getLinks() ?? [] ),
                'bulkActions'           =>  Hook::filter( get_class( $resource ) . '@getBulkActions', $resource->getBulkActions() ),
                'namespace'             =>  $namespace,
            ];
        } 

        return response()->json([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to proceed. No matching CRUD resource has been found.' )
        ], 403 );
    }

    /**
     * get create form config
     * @param namespace
     * @return array | AsyncResponse
     */
    public function getFormConfig( string $namespace, $id = null )
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );
        $resource           =   new $crudClass( compact( 'namespace', 'id' ) );

        if ( method_exists( $resource, 'getEntries' ) ) {
            $model          =   $resource->get( 'model' );
            $model          =   $model::find( $id );
            $form           =   $resource->getForm( $model );
            
            /**
             * @deprecated
             */
            $form           =   Hook::filter( 'ns.crud.form', $form, $namespace, compact( 'model', 'namespace', 'id' ) );

            /**
             * @since 4.4.3
             */
            $form           =   Hook::filter( get_class( $resource )::method( 'getForm' ), $form, compact( 'model' ) );
            $config         =   [
                'form'                  =>  $form,
                'labels'                =>  Hook::filter( get_class( $resource ) . '@getLabels', $resource->getLabels() ),
                'links'                 =>  Hook::filter( get_class( $resource ) . '@getLinks', $resource->getLinks() ),
                'namespace'             =>  $namespace,
            ];

            return $config;
        } 

        return response()->json([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to proceed. No matching CRUD resource has been found.' )
        ], 403 );
    }

    public function exportCrud( $namespace, Request $request )
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );
        $resource           =   new $crudClass;
        $spreadsheet        =   new Spreadsheet();
        $sheet              =   $spreadsheet->getActiveSheet();

        $columns            =   Hook::filter( 
            get_class( $resource ) . '@getColumns', 
            $resource->getColumns()
        );

        $sheetColumns       =   [ 'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z' ];

        if ( count( array_values( $columns ) ) > count( $sheetColumns ) ) {
            throw new Exception( __( 'The crud columns exceed the maximum column that can be exported (27)' ) );
        }

        foreach( array_values( $columns ) as $index => $column ) {
            $sheet->setCellValue( $sheetColumns[ $index ] . '1', $column[ 'label' ] );
        } 

        $config     =   [ 'perPage' => false ];

        /**
         * let's check if the request include
         * specific entries to export
         */        
        if ( $request->input( 'entries' ) ) {
            $config[ 'pick' ]   = $request->input( 'entries' );
        }

        $entries         =   $resource->getEntries( $config );

        foreach( $entries[ 'data' ] as $rowIndex => $entry ) {
            $sheetIndex     =   0;
            foreach( $columns as $columnName => $column ) {
                $sheet->setCellValue( $sheetColumns[ $sheetIndex ] . ( $rowIndex + 2 ), strip_tags( $entry->$columnName ) );
                $sheetIndex++;
            }
        }

        /**
         * let's define what will be the output name
         * of the exported file.
         */
        if ( ! is_dir( storage_path( 'app/public/exports' ) ) ) {
            mkdir( storage_path( 'app/public/exports' ) );
        }

        $dateFormat         =   Str::slug( ns()->date->toDateTimeString() );
        $relativePath       =   'exports/' . Str::slug( $resource->getLabels()[ 'list_title' ] ) . '-' . $dateFormat . '.csv';
        $fileName           =   storage_path( 'app/public/' . $relativePath );

        /**
         * We'll prepare the writer
         * and output the file.
         */
        $writer             = new Csv($spreadsheet);
        $writer->save( $fileName );

        /**
         * We'll hide the asset URL behind random lettes
         */
        $hash   =   Str::random(20);

        Cache::put( $hash, $relativePath, now()->addMinutes(5) );

        return [
            'url'   =>  route( 'ns.dashboard.crud-download', compact( 'hash' ) )
        ];
    }

    /**
     * Can Access
     * Check wether the logged user has
     * the right to access to the requested resource
     * @return AsyncResponse
     */
    public function canAccess( $namespace, Request $request ) 
    {
        $crudClass          =   Hook::filter( 'ns-crud-resource', $namespace );
        $resource           =   new $crudClass;

        if ( method_exists( $resource, 'canAccess' ) ) {
            if ( $resource->canAccess([
                'type'          =>  $request->input( 'type' ),
                'namespace'     =>  $request->input( 'namespace' ),
                'id'            =>  $request->input( 'id' ),
            ]) ) {
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  __( 'You\'re allowed to access to that page' )
                ]);
            }

            return response()->json([
                'status'    =>  'failed',
                'message'   =>  __( 'You don\'t have the right to access to the requested page.' )
            ], 403 );
        } 

        return response()->json([
            'status'    =>  'success',
            'message'   =>  __( 'This resource is not protected. The access is granted.' )
        ]);
    }

    public function downloadSavedFile( $hash )
    {
        $relativePath   =   Cache::pull( $hash );

        if ( Storage::disk( 'public' )->exists( $relativePath ) ) {
            return Storage::disk( 'public' )->download( $relativePath );
        }

        throw new NotFoundException( __( 'The requested file cannot be downloaded or has already been downloaded.' ) );
    }
}
