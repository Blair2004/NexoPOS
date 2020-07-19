<?php
/**
 * @todo review for api-server
 */
namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Exception;
use App\Http\Controllers\DashboardController;
use Hook;

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
        $crudClass      =   Hook::filter( 'ns.crud-resource', $namespace, $id );
        $resource       =   new $crudClass;

        if ( empty( $resource ) ) {
            return response([
                'status'    =>  'danger',
                'message'   =>  __( 'The crud resource is either not handled by the system or by any installed module.' )
            ], 403 );
        }

        /**
         * Run the filter before deleting
         */
        if ( method_exists( $resource, 'beforeDelete' ) ) {

            /**
             * the callback should return an empty value to proceed.
             */
            if( ! empty( $response = $resource->beforeDelete( $namespace, $id ) ) ) {
                return $response;
            }
        }

        /**
         * We'll retreive the model and delete it
         */
        $model          =   $resource->get( 'model' );
        $model::find( $id )->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The entry has been successfully delete.' )
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
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( __( 'Unhandled crud resource' ) );
        }

        $resource   =   new $crudClass;
        $model      =   $resource->getModel();
        $entry      =   new $model;

        /**
         * Filter POST input
         * check if on the CRUD resource the filter exists
         */
        $inputs         =   $request->all();

        if ( method_exists( $resource, 'filterPostInputs' ) ) {
            $inputs     =   $resource->filterPostInputs( $request->all() );
        }


        foreach ( $inputs as $name => $value ) {

            /**
             * If submitted field are part of fillable fields
             */
            if ( in_array( $name, $resource->getFillable() ) ) {

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
        }

        $entry->save();

        /**
         * Create an event after crud POST
         */
        if ( method_exists( $resource, 'afterPost' ) ) {
            $resource->afterPost( $entry );
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
    public function crudPut( String $namespace, $entry, CrudPutRequest $request  ) 
    {
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );

        /**
         * In case nothing handle this crud
         */
        if ( ! class_exists( $crudClass ) ) {
            throw new Exception( __( 'Unhandled CRUD Resource' ) );
        }
        
        $resource   =   new $crudClass;
        $model      =   $resource->getModel();
        $entry      =   $model::find( $entry );

        /**
         * Filter PUT input
         * check if on the CRUD resource the filter exists
         */
        $inputs         =   $request->all();
        if ( method_exists( $resource, 'filterPutInputs' ) ) {
            $inputs     =   $resource->filterPutInputs( $request->all(), $entry );

            /**
             * if a redirect response is returned
             * the execution should stop immediately
             */
            if ( $inputs instanceof RedirectResponse ) {
                return $inputs;
            }
        }

        foreach ( $inputs as $name => $value ) {

            /**
             * If submitted field are part of fillable fields
             * The field should not be null
             */
            if ( in_array( $name, $resource->getFillable() ) && $value !== null ) {

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
        }

        $entry->save();

        /**
         * Create an event after crud put
         */
        if ( method_exists( $resource, 'afterPut' ) ) {
            $resource->afterPut( $entry );
        }

        /**
         * @todo adding a link to edit the new entry
         */
        return [
            'status'    =>   'success',
            'message'   =>  __( 'the entry has been updated' ),
            'data'      =>  compact( 'entry' )
        ];
    }

    /**
     * Crud List
     * @return array of results
     */
    public function crudList( string $namespace )
    {
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );

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
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );

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
        if ( $request->input( 'entries_id' ) == null ) {
            throw new Exception( __( 'You need to select at least one item to delete' ) );
        }

        if ( $request->input( 'action' ) == null ) {
            throw new Exception( __( 'You need to define which action to perform' ) );
        }

        /**
         * assuming we're bulk deleting
         * but the action might be different later
         */
        $response           =   $resource->bulkDelete( $request );

        return [
            'status'    =>  'success',
            'message'   =>  sprintf( __( '%s has been deleted, %s has not been deleted.' ), $response[ 'success' ], $response[ 'failed' ]),
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
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );

        /**
         * Let's check it the resource has a method to retreive an item
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
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );
        $resource           =   new $crudClass;

        if ( method_exists( $resource, 'getEntries' ) ) {
            return $resource->getColumns();
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
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );
        $resource           =   new $crudClass;

        if ( method_exists( $resource, 'getEntries' ) ) {
            return [
                'columns'               =>  $resource->getColumns(),
                'labels'                 => $resource->getLabels(),
                'links'                 =>  @$resource->getLinks(),
                'results'               =>  $resource->getEntries(),
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
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );
        $resource           =   new $crudClass( compact( 'namespace', 'id' ) );

        if ( method_exists( $resource, 'getEntries' ) ) {
            $model          =   $resource->get( 'model' );
            $model          =   $model::find( $id );
            $fields         =   Hook::filter( 'dashboard.crud.fields', [], $namespace, compact( 'model', 'namespace', 'id' ) );
            $config         =   [
                'fields'                =>  $fields,
                'labels'                =>  $resource->getLabels(),
                'links'                 =>  @$resource->getLinks(),
                'namespace'             =>  $namespace,
            ];

            return $config;
        } 

        return response()->json([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to proceed. No matching CRUD resource has been found.' )
        ], 403 );
    }

    /**
     * Can Access
     * Check wether the logged user has
     * the right to access to the requested resource
     * @return AsyncResponse
     */
    public function canAccess( $namespace, Request $request ) 
    {
        $crudClass          =   Hook::filter( 'ns.crud-resource', $namespace );
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
}
