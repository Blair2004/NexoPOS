<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\GlobalProductHistoryCrud;
use App\Crud\ProductCategoryCrud;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\DateService;
use App\Services\ProductCategoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends DashboardController
{
    public function __construct(
        protected ProductCategoryService $categoryService,
        protected DateService $dateService
    ) {
        // ...
    }

    public function get( $id = null )
    {
        if ( ! empty( $id ) ) {
            $category = ProductCategory::find( $id );
            if ( ! $category instanceof ProductCategory ) {
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
     *
     * @param number id
     * @return json
     */
    public function delete( $id )
    {
        $category = ProductCategory::find( $id );

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
                'status' => 'success',
                'message' => __( 'The category has been deleted.' ),
            ];
        }

        throw new NotFoundException( __( 'Unable to find the category using the provided identifier.' ) );
    }

    /**
     * create a category using the provided
     * form data
     *
     * @param request
     * @return json
     */
    public function post( Request $request ) // must be a specific form request with a validation
    {
        /**
         * let's retrieve if the parent exists
         */
        $parentCategory = ProductCategory::find( $request->input( 'parent_id' ) );
        if ( ! $parentCategory instanceof ProductCategory && intval( $request->input( 'parent_id' ) ) !== 0 ) {
            throw new NotFoundException( __( 'Unable to find the attached category parent' ) );
        }

        $fields = $request->only( [ 'name', 'parent_id', 'description', 'media_id' ] );
        if ( empty( $fields ) ) {
            throw new NotFoundException( __( 'Unable to proceed. The request doesn\'t provide enough data which could be handled' ) );
        }

        $category = new ProductCategory;

        foreach ( $fields as $name => $field ) {
            $category->$name = $field;
        }

        $category->author = Auth::id();
        $category->save();

        return [
            'status' => 'success',
            'message' => __( 'The category has been correctly saved' ),
            'data' => compact( 'category' ),
        ];
    }

    /**
     * edit existing category using
     * the provided ID
     *
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
        $category = ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory && $request->input( 'parent_id' ) !== 0 ) {
            throw new NotFoundException( __( 'Unable to find the category using the provided identifier' ) );
        }

        $fields = $request->only( [ 'name', 'parent_id', 'description', 'media_id' ] );
        if ( empty( $fields ) ) {
            throw new NotFoundException( __( 'Unable to proceed. The request doesn\'t provide enough data which could be handled' ) );
        }

        foreach ( $fields as $name => $field ) {
            $category->$name = $field;
        }

        $category->author = Auth::id();
        $category->save();

        return [
            'status' => 'success',
            'message' => __( 'The category has been updated' ),
            'data' => compact( 'category' ),
        ];
    }

    /**
     * Reorder categories
     *
     * @return array
     */
    public function reorderCategories( Request $request )
    {
        $items = $request->input( 'items', [] );

        foreach ( $items as $index => $item ) {
            $category = ProductCategory::find( $item['id'] );
            if ( $category instanceof ProductCategory ) {
                $category->position = $index;
                $category->save();
            }
        }

        return [
            'status' => 'success',
            'message' => __( 'Categories have been successfully reordered.' ),
        ];
    }

    /**
     * get a specific category product
     *
     * @param number category id
     * @return json
     */
    public function getCategoriesProducts( $id )
    {
        $category = ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory ) {
            throw new NotFoundException( __( 'Unable to find the category using the provided identifier' ) );
        }

        return $category->products()
            ->whereIn( 'product_type', [ 'product', 'variation' ] )
            ->onSale()
            ->get();
    }

    /**
     * get a specific category variations
     *
     * @param number category id
     * @return json
     */
    public function getCategoriesVariations( $id )
    {
        $category = ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory ) {
            throw new NotFoundException( __( 'Unable to find the category using the provided identifier' ) );
        }

        return $category->products->products()
            ->whereIn( 'product_type', [ 'variation' ] )
            ->onSale()
            ->get();
    }

    /**
     * Get Model Schema
     * which describe the field expected on post/put
     * requests
     *
     * @return json
     */
    public function schema()
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'media_id' => 'number',
            'parent_id' => 'number',
        ];
    }

    public function listCategories()
    {
        return ProductCategoryCrud::table();
    }

    public function createCategory()
    {
        return ProductCategoryCrud::form();
    }

    /**
     * Edit an existing category
     */
    public function editCategory( ProductCategory $category )
    {
        return ProductCategoryCrud::form( $category );
    }

    public function computeCategoryProducts( ProductCategory $category )
    {
        $this->categoryService->computeProducts( $category );

        return redirect( url()->previous() )
            ->with( 'message', __( 'The category products has been refreshed' ) );
    }

    public function getCategories( $id = '0' )
    {
        $enableReordering = ns()->option->get( 'ns_pos_enable_reordering' ) === 'yes';

        if ( $id !== '0' ) {
            $category = ProductCategory::where( 'id', $id )
                ->displayOnPOS()
                ->where( function ( $query ) {
                    $this->applyHideCategories( $query );
                } )
                ->with( 'subCategories' )
                ->first();

            $productsQuery = $category->products()
                ->with( 'galleries', 'tax_group.taxes' )
                ->onSale()
                ->where( function ( $query ) {
                    $this->applyHideProducts( $query );
                } )
                ->trackingDisabled();

            // Apply ordering based on feature toggle
            if ( $enableReordering ) {
                $productsQuery->orderBy( 'position', 'asc' );
            } else {
                $productsQuery->orderBy( 'created_at', 'asc' );
            }

            $categoriesQuery = $category
                ->subCategories()
                ->displayOnPOS();

            // Apply ordering based on feature toggle
            if ( $enableReordering ) {
                $categoriesQuery->orderBy( 'position', 'asc' );
            } else {
                $categoriesQuery->orderBy( 'created_at', 'asc' );
            }

            return [
                'products' => $productsQuery
                    ->get()
                    ->map( function ( $product ) {
                        if ( $product->unit_quantities()->count() === 1 ) {
                            $product->load( 'unit_quantities.unit' );
                        }

                        return $product;
                    } ),
                'categories' => $categoriesQuery->get(),
                'previousCategory' => ProductCategory::find( $category->parent_id ) ?? null,
                'currentCategory' => $category,
                'pinnedProducts' => $this->getPinnedProducts(),
            ];
        }

        $categoriesQuery = ProductCategory::where( function ( $query ) {
            $query->where( 'parent_id', null )
                ->orWhere( 'parent_id', 0 );
        } )
            ->where( function ( $query ) {
                $this->applyHideCategories( $query );
            } )
            ->displayOnPOS();

        // Apply ordering based on feature toggle
        if ( $enableReordering ) {
            $categoriesQuery->orderBy( 'position', 'asc' );
        } else {
            $categoriesQuery->orderBy( 'created_at', 'asc' );
        }

        return [
            'products' => [],
            'previousCategory' => false,
            'currentCategory' => false,
            'categories' => $categoriesQuery->get(),
            'pinnedProducts' => $this->getPinnedProducts(),
        ];
    }

    /**
     * Get pinned products for POS display
     *
     * @return Collection
     */
    private function getPinnedProducts()
    {
        return Product::where( 'pinned', true )
            ->with( 'galleries', 'tax_group.taxes' )
            ->onSale()
            ->where( function ( $query ) {
                $this->applyHideProducts( $query );
            } )
            ->trackingDisabled()
            ->get()
            ->map( function ( $product ) {
                if ( $product->unit_quantities()->count() === 1 ) {
                    $product->load( 'unit_quantities.unit' );
                }

                return $product;
            } );
    }

    private function applyHideProducts( $query )
    {
        $exhaustedHidden = ns()->option->get( 'ns_pos_hide_exhausted_products' );

        if ( $exhaustedHidden === 'yes' ) {
            $query->where( 'stock_management', Product::STOCK_MANAGEMENT_DISABLED );

            $query->orWhere( function ( $query ) {
                $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED );

                $query->whereHas( 'unit_quantities', function ( $query ) {
                    $query->where( 'quantity', '>', 0 );
                } );
            } );
        }
    }

    private function applyHideCategories( $query )
    {
        $exhaustedHidden = ns()->option->get( 'ns_pos_hide_empty_categories' );

        if ( $exhaustedHidden === 'yes' ) {
            $query->whereHas( 'products', function ( $query ) {
                $query->where( 'stock_management', Product::STOCK_MANAGEMENT_DISABLED );
            } );

            $query->orWhereHas( 'products', function ( $query ) {
                $query->where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED );

                $query->whereHas( 'unit_quantities', function ( $query ) {
                    $query->where( 'quantity', '>', 0 );
                } );
            } );
        }
    }

    /**
     * Will display the actual stock flow history
     *
     * @return array crud table.
     */
    public function showStockFlowCrud()
    {
        return GlobalProductHistoryCrud::table();
    }
}
