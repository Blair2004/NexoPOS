<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Exception;
use Illuminate\Support\Facades\Auth;

class CategoryController extends DashboardController
{
    public function get( $id = null )
    {
        if ( ! empty( $id ) ) {
            $category   =   ProductCategory::find( $id );
            if( ! $category instanceof ProductCategory ) {
                throw new Exception( __( 'Unable to find the category using the provided identifier' ) );
            }
            return $category;
        }

        if ( request()->query( 'parent' ) === 'true' ) {
            return ProductCategory::where( 'parent_id', null )->get();
        }

        return ProductCategory::get();
    }

    /**
     * try to delete a category using the provided
     * id
     * @param number id
     * @return json
     */
    public function delete( $id )
    {
        $category   =   ProductCategory::find( $id );

        if ( $category instanceof ProductCategory ) {

            /**
             * prevent deleting a category
             * which might have some categories
             * linked to it.
             */
            if ( $category->subCategories->count() > 0 ) {
                throw new NotFoundException( __( 'Can\'t delete a category having sub categories linked to it.' ) );
            }
            
            $category->delete();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The category has been deleted.' )
            ];
        }

        throw new NotFoundException( __( 'Unable to find the category using the provided identifier.' ) );
    }

    /**
     * create a category using the provided
     * form data
     * @param request
     * @return json
     */
    public function post( Request $request ) // must be a specific form request with a validation
    {
        /**
         * let's retrieve if the parent exists
         */
        $parentCategory   =   ProductCategory::find( $request->input( 'parent_id' ) );
        if ( ! $parentCategory instanceof ProductCategory && intval( $request->input( 'parent_id' ) ) !== 0 ) {
            throw new NotFoundException( __( 'Unable to find the attached category parent' ) );
        }

        $fields         =   $request->only([ 'name', 'parent_id', 'description', 'media_id' ]);
        if ( empty( $fields ) ) {
            throw new NotFoundException( __( 'Unable to proceed. The request doesn\'t provide enough data which could be handled' ) );
        }

        $category    =   new ProductCategory;

        foreach( $fields as $name => $field ) {
            $category->$name     =   $field;
        }
        
        $category->author    =   Auth::id();
        $category->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The category has been correctly saved' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * edit existing category using
     * the provided ID
     * @param int category id
     * @return json
     */
    public function put( $id, Request $request ) // must use a specific request which include a validation
    {
        /**
         * @todo we should make sure the parent id
         * is not similar to the current category
         * id. We could also check circular categories
         */
        $category   =   ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory && $request->input( 'parent_id' ) !== 0 ) {
            throw new NotFoundException( __( 'Unable to find the category using the provided identifier' ) );
        }

        $fields         =   $request->only([ 'name', 'parent_id', 'description', 'media_id' ]);
        if ( empty( $fields ) ) {
            throw new NotFoundException( __( 'Unable to proceed. The request doesn\'t provide enough data which could be handled' ) );
        }

        foreach( $fields as $name => $field ) {
            $category->$name     =   $field;
        }

        $category->author   =   Auth::id();
        $category->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The category has been updated' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * get a specific category product
     * @param number category id
     * @return json
     */
    public function getCategoriesProducts( $id )
    {
        $category   =   ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory ) {
            throw new NotFoundException( __( 'Unable to find the category using the provided identifier' ) );
        }

        return $category->products->filter( function( $product ) {
            return in_array( $product->product_type, [ 'product', 'variable' ]);
        })->values();
    }

    /**
     * get a specific category variations
     * @param number category id
     * @return json
     */
    public function getCategoriesVariations( $id )
    {
        $category   =   ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory ) {
            throw new NotFoundException( __( 'Unable to find the category using the provided identifier' ) );
        }

        return $category->products->filter( function( $product ) {
            return $product->product_type === 'variation';
        })->values();
    }

    /**
     * Get Model Schema
     * which describe the field expected on post/put
     * requests
     * @return json
     */
    public function schema()
    {
        return [
            'name'          =>  'string',
            'description'   =>  'string',
            'media_id'      =>  'number',
            'parent_id'     =>  'number'
        ];
    }

    public function listCategories()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'title'         =>      __( 'Product Categories' ),
            'createUrl'     =>  ns()->url( '/dashboard/products/categories/create' ),
            'description'   =>  __( 'List all categories available.' ),
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.products-categories' ),
        ]);
    }

    public function createCategory()
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'title'         =>  __( 'Create New Product Category' ),
            'returnUrl'     =>  ns()->url( '/dashboard/products/categories' ),
            'submitUrl'     =>  ns()->url( '/api/nexopos/v4/crud/ns.products-categories' ),
            'description'   =>  __( 'Allow you to create a new product category.' ),
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.products-categories/form-config' )
        ]);
    }

    /**
     * Edit an existing category
     */
    public function editCategory( ProductCategory $category )
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'title'         =>  __( 'Edit Product Category' ),
            'returnUrl'     =>  ns()->url( '/dashboard/products/categories' ),
            'submitMethod'  =>  'PUT',
            'submitUrl'     =>  ns()->url( '/api/nexopos/v4/crud/ns.products-categories/' . $category->id ),
            'description'   =>  __( 'Allow you to edit an existing product category.' ),
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.products-categories/form-config/' . $category->id )
        ]);
    }

    public function getCategories( $id = '0' )
    {
        if ( $id !== '0' ) {
            $category       =   ProductCategory::where( 'id', $id )
                ->displayOnPOS()
                ->with( 'subCategories' )
                ->first();

            return [
                'products'          =>  $category->products()
                    ->with( 'galleries' )
                    ->trackingDisabled()
                    ->get(),
                'categories'        =>  $category
                    ->subCategories()
                    ->displayOnPOS()
                    ->get(),
                'previousCategory'  =>  ProductCategory::find( $category->parent_id ) ?? null, // means should return to the root
                'currentCategory'   =>  $category, // means should return to the root
            ];
        }

        return [
            'products'          =>  [],
            'previousCategory'  =>  false,
            'currentCategory'   =>  false,
            'categories'        =>  ProductCategory::where(function( $query ) {
                    $query->where( 'parent_id', null )
                        ->orWhere( 'parent_id', 0 );
                })
                ->displayOnPOS()
                ->get(),
        ];
    }
}

