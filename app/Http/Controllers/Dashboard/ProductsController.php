<?php
/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;



use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\ProductService;
use App\Services\Options;
use Exception;
use Illuminate\Support\Arr;

class ProductsController extends DashboardController
{
    /** @var ProductService */
    protected $productService;

    public function __construct( 
        ProductService $productService
    )
    {
        parent::__construct();

        $this->productService   =   $productService;
    }

    public function saveProduct( Request $request )
    {
        $primary    =   collect( $request->input( 'variations' ) )
            ->filter( fn( $variation ) => isset( $variation[ '$primary' ] ) )
            ->first();

        $units                                  =   $primary[ 'units' ];

        /**
         * this is made to ensure the array 
         * provided aren't flatten
         */
        unset( $primary[ 'units' ][ 'purchase_unit_ids' ] );
        unset( $primary[ 'units' ][ 'selling_unit_ids' ] );
        unset( $primary[ 'units' ][ 'transfer_unit_ids' ] );

        $primary[ 'identification' ][ 'name' ]          =   $request->input( 'name' );
        $primary                                        =    Helper::flatArrayWithKeys( $primary );
        $primary[ 'product_type' ]                      =   'product';

        /**
         * let's restore the fields before
         * storing that.
         */
        $primary[ 'units' ][ 'purchase_unit_ids' ]      =   $units[ 'purchase_unit_ids' ];
        $primary[ 'units' ][ 'selling_unit_ids' ]       =   $units[ 'selling_unit_ids' ];
        $primary[ 'units' ][ 'transfer_unit_ids' ]      =   $units[ 'transfer_unit_ids' ];
        
        unset( $primary[ '$primary' ] );

        /**
         * the method "create" is capable of 
         * creating either a product or a variable product
         */
        return $this->productService->create( $primary );
    }

    /**
     * returns a list of available 
     * product
     * @return array
     */
    public function getProduts()
    {
        return $this->productService->getProducts();
    }

    /**
     * Update a product using
     * a provided id
     * @param Request
     * @param int product id
     * @return array
     */
    public function updateProduct( Request $request, Product $product )
    {
        $primary    =   collect( $request->input( 'variations' ) )
            ->filter( fn( $variation ) => isset( $variation[ '$primary' ] ) )
            ->first();

        $units                                  =   $primary[ 'units' ];
        
        /**
         * this is made to ensure the array 
         * provided aren't flatten
         */
        unset( $primary[ 'units' ][ 'purchase_unit_ids' ] );
        unset( $primary[ 'units' ][ 'selling_unit_ids' ] );
        unset( $primary[ 'units' ][ 'transfer_unit_ids' ] );

        $primary[ 'identification' ][ 'name' ]          =   $request->input( 'name' );
        $primary                                        =    Helper::flatArrayWithKeys( $primary );
        $primary[ 'product_type' ]                      =   'product';

        /**
         * let's restore the fields before
         * storing that.
         */
        $primary[ 'purchase_unit_ids' ]      =   $units[ 'purchase_unit_ids' ];
        $primary[ 'selling_unit_ids' ]       =   $units[ 'selling_unit_ids' ];
        $primary[ 'transfer_unit_ids' ]      =   $units[ 'transfer_unit_ids' ];

        unset( $primary[ '$primary' ] );

        /**
         * the method "create" is capable of 
         * creating either a product or a variable product
         */
        return $this->productService->update( $product, $primary );
    }

    public function searchProduct( Request $request )
    {
        return Product::query()->orWhere( 'name', 'LIKE', "%{$request->input( 'search' )}%" )
            ->orWhere( 'sku', 'LIKE', "%{$request->input( 'search' )}%" )
            ->orWhere( 'barcode', 'LIKE', "%{$request->input( 'search' )}%" )
            ->limit(5)
            ->get()
            ->map( function( $product ) {
                $units  =   json_decode( $product->purchase_unit_ids );
                
                if ( $units ) {
                    $product->purchase_units     =   collect();
                    collect( $units )->each( function( $taxID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $taxID ) );
                    });
                }

                return $product;
            });
    }

    public function refreshPrices( $id )
    {
        $product    =   $this->productService->get( $id );
        $this->productService->refreshPrices( $product );
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product price has been refreshed.' ),
            'data'      =>  compact( 'product' )
        ];
    }

    public function reset( $identifier )
    {
        $product        =   $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );
        
        return $this->productService->resetProduct( $product );
    }

    /**
     * return the full history of a product
     * @param int product id
     * @return array
     */
    public function history( $identifier )
    {
        $product        =   $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $this->productService->getProductHistory( 
            $product->id
        );
    }

    public function units( $identifier )
    {
        $product        =   $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );
        
        return $this->productService->getUnitQuantities( 
            $product->id
        );
    }

    /**
     * delete a product
     * @param int product_id
     * @return array reponse
     */
    public function deleteProduct( $identifier )
    {
        $product        =   $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $this->productService->deleteProduct( $product );
    }

    /**
     * Return a single product ig that exists
     * with his variations
     * @param string|int filter
     * @return array found product
     */
    public function singleProduct( $identifier )
    {
        return $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );
    }

    /**
     * return all available variations
     * @return array
     */
    public function getAllVariations()
    {
        return $this->productService->getProductVariations();
    }

    /**
     * delete all available product variations
     */
    public function deleteAllVariations()
    {
        return $this->productService->deleteVariations();
    }

    public function deleteAllProducts()
    {
        return $this->productService->deleteAllProducts();        
    }

    public function getProductVariations( $identifier )
    {
        $product    =   $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $product->variations;
    }

    /**
     * delete a single variation product
     * @param int product id
     * @param int variation id
     * @return array status of the operation
     */
    public function deleteSingleVariation( $product_id, int $variation_id )
    {
        /**
         * @todo consider registering an event for 
         * catching when a single is about to be delete
         */

        /** @var Product */
        $product    =   $this->singleProduct( $product_id );

        $results    =   $product->variations->map( function( $variation ) use ( $variation_id ) {
            if ( $variation->id === $variation_id ) {
                $variation->delete();
                return 1;
            }
            return 0;
        });

        $opResult   =   $results->reduce( function( $before, $after ) {
            return $before + $after;
        });

        return floatval( $opResult ) > 0 ? [
            'status'        =>      'success',
            'message'       =>      __( 'The single variation has been deleted.' )
        ] : [
            'status'        =>      'failed',
            'message'       =>      sprintf( __( 'The the variation hasn\'t been deleted because it might not exist or is not assigned to the parent product "%s".' ), $product->name )
        ];
    }

    /**
     * Create a single product
     * variation
     * @param int product id (parent)
     * @param Request data
     * @return array
     */
    public function createSingleVariation( $product_id, Request $request )
    {
        $product    =   $this->productService->get( $product_id );
        return $this->productService->createProductVariation( $product, $request->all() );
    }

    public function editSingleVariation( $parent_id, $variation_id, Request $request )
    {
        $parent     =   $this->productService->get( $parent_id );
        return $this->productService->updateProductVariation( $parent, $variation_id, $request->all() );
    }

    public function listProducts()
    {
        ns()->restrict([ 'nexopos.read.products' ]);

        return $this->view( 'pages.dashboard.crud.table', [
            'title'         =>      __( 'Products List' ),
            'createUrl'     =>  url( '/dashboard/products/create' ),
            'desccription'  =>  __( 'List all products available on the system' ),
            'src'           =>  url( '/api/nexopos/v4/crud/ns.products' ),
        ]);
    }

    public function editProduct( Product $product )
    {
        ns()->restrict([ 'nexopos.update.products' ]);

        return $this->view( 'pages.dashboard.products.create', [
            'title'         =>  __( 'Edit a product' ),
            'description'   =>  __( 'Makes modifications to a product' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/products/' . $product->id ),
            'returnUrl'     =>  url( '/dashboard/products' ),
            'unitsUrl'      =>  url( '/api/nexopos/v4/units-groups/{id}/units' ),
            'submitMethod'  =>  'PUT',
            'src'           =>  url( '/api/nexopos/v4/crud/ns.products/form-config/' . $product->id ),
        ]);
    }

    public function createProduct()
    {
        ns()->restrict([ 'nexopos.create.products' ]);

        return $this->view( 'pages.dashboard.products.create', [
            'title'         =>  __( 'Create a product' ),
            'description'   =>  __( 'Add a new product on the system' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/products' ),
            'returnUrl'    =>  url( '/dashboard/products' ),
            'unitsUrl'      =>  url( '/api/nexopos/v4/units-groups/{id}/units' ),
            'src'           =>  url( '/api/nexopos/v4/crud/ns.products/form-config' ),
        ]);
    }
}

