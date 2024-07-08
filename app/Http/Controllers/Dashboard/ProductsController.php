<?php
/**
 * NexoPOS Controller
 *
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Classes\Hook;
use App\Classes\Output;
use App\Crud\ProductCrud;
use App\Crud\ProductHistoryCrud;
use App\Crud\ProductUnitQuantitiesCrud;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use App\Http\Requests\ProductRequest;
use App\Models\ProcurementProduct;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Unit;
use App\Services\DateService;
use App\Services\Helper;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class ProductsController extends DashboardController
{
    public function __construct(
        protected ProductService $productService,
        protected DateService $dateService
    ) {
        // ...
    }

    public function saveProduct( Request $request )
    {
        $primary = collect( $request->input( 'variations' ) )
            ->filter( fn( $variation ) => isset( $variation[ '$primary' ] ) )
            ->first();

        $source = $primary;
        $units = $primary[ 'units' ];

        /**
         * this is made to ensure the array
         * provided aren't flatten
         */
        unset( $primary[ 'units' ] );
        unset( $primary[ 'images' ] );
        unset( $primary[ 'groups' ] );

        $primary[ 'identification' ][ 'name' ] = $request->input( 'name' );
        $primary = Helper::flatArrayWithKeys( $primary )->toArray();
        $primary[ 'product_type' ] = 'product';

        /**
         * let's restore the fields before
         * storing that.
         */
        $primary[ 'images' ] = $source[ 'images' ];
        $primary[ 'units' ] = $source[ 'units' ];
        $primary[ 'groups' ] = $source[ 'groups' ] ?? [];

        unset( $primary[ '$primary' ] );

        /**
         * As foreign fields aren't handled with
         * they are complex (array), this methods allow
         * external script to reinject those complex fields.
         */
        $primary = Hook::filter( 'ns-create-products-inputs', $primary, $source );

        /**
         * the method "create" is capable of
         * creating either a product or a variable product
         */
        return $this->productService->create( $primary );
    }

    public function convertUnits( Request $request, Product $product )
    {
        ns()->restrict( [ 'nexopos.convert.products-units' ] );

        return $this->productService->convertUnitQuantities(
            product: $product,
            from: Unit::findOrFail( $request->input( 'from' ) ),
            to: Unit::findOrFail( $request->input( 'to' ) ),
            quantity: $request->input( 'quantity' )
        );
    }

    /**
     * returns a list of available
     * product
     *
     * @return array
     */
    public function getProduts()
    {
        return $this->productService->getProducts();
    }

    /**
     * Update a product using
     * a provided id
     *
     * @param Request
     * @param int product id
     * @return array
     */
    public function updateProduct( ProductRequest $request, Product $product )
    {
        $productCrud = new ProductCrud;
        $form = $productCrud->getFlatForm( $request->post(), $product );

        /**
         * the method "create" is capable of
         * creating either a product or a variable product
         */
        return $this->productService->update( $product, $form );
    }

    public function searchProduct( Request $request )
    {
        return $this->productService->searchProduct(
            search: $request->input( 'search' ),
            arguments: (array) $request->input( 'arguments' )
        );
    }

    public function refreshPrices( $id )
    {
        $product = $this->productService->get( $id );
        $this->productService->refreshPrices( $product );

        return [
            'status' => 'success',
            'message' => __( 'The product price has been refreshed.' ),
            'data' => compact( 'product' ),
        ];
    }

    public function reset( $identifier )
    {
        $product = $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $this->productService->resetProduct( $product );
    }

    /**
     * return the full history of a product
     *
     * @param int product id
     * @return array
     */
    public function history( $identifier )
    {
        $product = $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $this->productService->getProductHistory(
            $product->id
        );
    }

    public function units( $identifier )
    {
        $product = $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $this->productService->getUnitQuantities(
            $product->id
        );
    }

    public function getUnitQuantities( Product $product )
    {
        return $this->productService->getProductUnitQuantities( $product );
    }

    /**
     * delete a product
     *
     * @param int product_id
     * @return array reponse
     */
    public function deleteProduct( $identifier )
    {
        $product = $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $this->productService->deleteProduct( $product );
    }

    /**
     * Return a single product ig that exists
     * with his variations
     *
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
     *
     * @return array
     */
    public function getAllVariations()
    {
        return $this->productService->getProductVariations();
    }

    public function deleteAllProducts()
    {
        return $this->productService->deleteAllProducts();
    }

    public function getProductVariations( $identifier )
    {
        $product = $this->productService->getProductUsingArgument(
            request()->query( 'as' ) ?? 'id',
            $identifier
        );

        return $product->variations;
    }

    /**
     * delete a single variation product
     *
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
        $product = $this->singleProduct( $product_id );

        $results = $product->variations->map( function ( $variation ) use ( $variation_id ) {
            if ( $variation->id === $variation_id ) {
                $variation->delete();

                return 1;
            }

            return 0;
        } );

        $opResult = $results->reduce( function ( $before, $after ) {
            return $before + $after;
        } );

        return floatval( $opResult ) > 0 ? [
            'status' => 'success',
            'message' => __( 'The single variation has been deleted.' ),
        ] : [
            'status' => 'error',
            'message' => sprintf( __( 'The the variation hasn\'t been deleted because it might not exist or is not assigned to the parent product "%s".' ), $product->name ),
        ];
    }

    /**
     * Create a single product
     * variation
     *
     * @param int product id (parent)
     * @param Request data
     * @return array
     */
    public function createSingleVariation( $product_id, Request $request )
    {
        $product = $this->productService->get( $product_id );

        return $this->productService->createProductVariation(
            $product,
            $request->all()
        );
    }

    public function editSingleVariation( $parent_id, $variation_id, Request $request )
    {
        $parent = $this->productService->get( $parent_id );

        return $this->productService->updateProductVariation( $parent, $variation_id, $request->all() );
    }

    public function listProducts()
    {
        ns()->restrict( [ 'nexopos.read.products' ] );

        Hook::addAction( 'ns-crud-footer', function ( Output $output ) {
            $output->addView( 'pages.dashboard.products.quantity-popup' );

            return $output;
        } );

        return ProductCrud::table();
    }

    public function editProduct( Product $product )
    {
        ns()->restrict( [ 'nexopos.update.products' ] );

        return view::make( 'pages.dashboard.products.create', [
            'title' => __( 'Edit a product' ),
            'description' => __( 'Makes modifications to a product' ),
            'submitUrl' => ns()->url( '/api/products/' . $product->id ),
            'returnUrl' => ns()->url( '/dashboard/products' ),
            'unitsUrl' => ns()->url( '/api/units-groups/{id}/units' ),
            'submitMethod' => 'PUT',
            'src' => ns()->url( '/api/crud/ns.products/form-config/' . $product->id ),
        ] );
    }

    public function createProduct()
    {
        ns()->restrict( [ 'nexopos.create.products' ] );

        return view::make( 'pages.dashboard.products.create', [
            'title' => __( 'Create a product' ),
            'description' => __( 'Add a new product on the system' ),
            'submitUrl' => ns()->url( '/api/products' ),
            'returnUrl' => ns()->url( '/dashboard/products' ),
            'unitsUrl' => ns()->url( '/api/units-groups/{id}/units' ),
            'src' => ns()->url( '/api/crud/ns.products/form-config' ),
        ] );
    }

    /**
     * Renders the crud table for the product
     * units
     *
     * @return View
     */
    public function productUnits( Product $product )
    {
        return ProductUnitQuantitiesCrud::table( [
            'queryParams' => [
                'product_id' => $product->id,
            ],
        ] );
    }

    /**
     * render the crud table for the product
     * history
     *
     * @return View
     */
    public function productHistory( $identifier )
    {
        Hook::addAction( 'ns-crud-footer', function ( Output $output, $identifier ) {
            $output->addView( 'pages.dashboard.products.history' );

            return $output;
        }, 10, 2 );

        $product = Product::find( $identifier );

        return ProductHistoryCrud::table( [
            'title' => sprintf( __( 'Stock History For %s' ), $product->name ),
            'queryParams' => [
                'product_id' => $identifier,
            ],
        ] );
    }

    public function showStockAdjustment()
    {
        return View::make( 'pages.dashboard.products.stock-adjustment', [
            'title' => __( 'Stock Adjustment' ),
            'description' => __( 'Adjust stock of existing products.' ),
            'actions' => Helper::kvToJsOptions( [
                ProductHistory::ACTION_ADDED => __( 'Add' ),
                ProductHistory::ACTION_DELETED => __( 'Delete' ),
                ProductHistory::ACTION_DEFECTIVE => __( 'Defective' ),
                ProductHistory::ACTION_LOST => __( 'Lost' ),
                ProductHistory::ACTION_SET => __( 'Set' ),
            ] ),
        ] );
    }

    public function getUnitQuantity( Product $product, Unit $unit )
    {
        $quantity = $this->productService->getUnitQuantity( $product->id, $unit->id );

        if ( $quantity instanceof ProductUnitQuantity ) {
            return $quantity;
        }

        throw new Exception( __( 'No stock is provided for the requested product.' ) );
    }

    public function deleteUnitQuantity( ProductUnitQuantity $unitQuantity )
    {
        ns()->restrict( [ 'nexopos.delete.products-units', 'nexopos.make.products-adjustments' ] );

        if ( $unitQuantity->quantity > 0 ) {
            $this->productService->stockAdjustment( ProductHistory::ACTION_DELETED, [
                'unit_price' => $unitQuantity->sale_price,
                'unit_id' => $unitQuantity->unit_id,
                'product_id' => $unitQuantity->product_id,
                'quantity' => $unitQuantity->quantity,
            ] );
        }

        $unitQuantity->delete();

        return [
            'status' => 'success',
            'message' => __( 'The product unit quantity has been deleted.' ),
        ];
    }

    public function createAdjustment( Request $request )
    {
        ns()->restrict( [ 'nexopos.make.products-adjustments' ] );

        $validator = Validator::make( $request->all(), [
            'products' => 'required',
        ] );

        if ( $validator->fails() ) {
            throw new Exception( __( 'Unable to proceed as the request is not valid.' ) );
        }

        $results = [];

        /**
         * We need to make sure the action
         * made are actually supported.
         */
        foreach ( $request->input( 'products' ) as $unit ) {
            /**
             * if the action is set, then we need to make sure
             * the quantity is set
             */
            if ( ! isset( $unit[ 'adjust_unit' ][ 'unit_id' ] ) ) {
                throw new Exception( sprintf( __( 'The unit is not set for the product "%s".' ), $unit[ 'name' ] ) );
            }

            /**
             * let's check if the action is supported
             */
            if (
                ! in_array( $unit[ 'adjust_action' ], ProductHistory::STOCK_INCREASE ) &&
                ! in_array( $unit[ 'adjust_action' ], ProductHistory::STOCK_REDUCE ) &&
                ! in_array( $unit[ 'adjust_action' ], [
                    ProductHistory::ACTION_SET,
                ] )
            ) {
                throw new Exception( sprintf( __( 'Unsupported action for the product %s.' ), $unit[ 'name' ] ) );
            }

            /**
             * let's check for every operation if there is enough inventory
             */
            $productUnitQuantity = ProductUnitQuantity::where( 'product_id', $unit[ 'id' ] )
                ->where( 'unit_id', $unit[ 'adjust_unit' ][ 'unit_id' ] )
                ->first();

            if ( $productUnitQuantity instanceof ProductUnitQuantity && in_array( $unit[ 'adjust_action' ], ProductHistory::STOCK_REDUCE ) ) {
                $remaining = $productUnitQuantity->quantity - (float) $unit[ 'adjust_quantity' ];

                if ( $remaining < 0 ) {
                    throw new NotAllowedException(
                        sprintf(
                            __( 'The operation will cause a negative stock for the product "%s" (%s).' ),
                            $productUnitQuantity->product->name,
                            $remaining
                        )
                    );
                }
            }

            if ( $unit[ 'adjust_quantity' ] < 0 ) {
                throw new NotAllowedException(
                    sprintf(
                        __( 'The adjustment quantity can\'t be negative for the product "%s" (%s)' ),
                        $unit[ 'name' ],
                        $unit[ 'adjust_quantity' ]
                    )
                );
            }
        }

        /**
         * now we can adjust the stock of the items
         */
        foreach ( $request->input( 'products' ) as $product ) {
            $results[] = $this->productService->stockAdjustment( $product[ 'adjust_action' ], [
                'unit_price' => $product[ 'adjust_unit' ][ 'sale_price' ],
                'unit_id' => $product[ 'adjust_unit' ][ 'unit_id' ],
                'procurement_product_id' => $product[ 'procurement_product_id' ] ?? null,
                'product_id' => $product[ 'id' ],
                'quantity' => $product[ 'adjust_quantity' ],
                'description' => $product[ 'adjust_reason' ] ?? '',
            ] );
        }

        return [
            'status' => 'success',
            'message' => __( 'The stock has been adjustment successfully.' ),
            'data' => $results,
        ];
    }

    public function searchUsingArgument( $reference )
    {
        $procurementProduct = ProcurementProduct::barcode( $reference )->first();
        $productUnitQuantity = ProductUnitQuantity::barcode( $reference )->with( 'unit' )->first();
        $product = Product::barcode( $reference )
            ->onSale()
            ->first();

        if ( $procurementProduct instanceof ProcurementProduct ) {
            $product = $procurementProduct->product;
            $product->load( 'tax_group.taxes' );

            /**
             * check if the product has expired
             * and the sales are disallowed.
             */
            if (
                $this->dateService->copy()->greaterThan( $procurementProduct->expiration_date ) &&
                $product->expires &&
                $product->on_expiration === Product::EXPIRES_PREVENT_SALES ) {
                throw new NotAllowedException( __( 'Unable to add the product to the cart as it has expired.' ) );
            }

            /**
             * We need to add  a reference of the procurement product
             * in order to deplete the available quantity accordingly.
             * Will also be helpful to track how products are sold.
             */
            $product->procurement_product_id = $procurementProduct->id;
        } elseif ( $productUnitQuantity instanceof ProductUnitQuantity ) {
            /**
             * if a product unit quantity is loaded. Then we make sure to return the parent
             * product with the selected unit quantity.
             */
            $productUnitQuantity->load( 'unit' );

            $product = Product::find( $productUnitQuantity->product_id );
            $product->load( 'unit_quantities.unit' );
            $product->load( 'tax_group.taxes' );
            $product->selectedUnitQuantity = $productUnitQuantity;
        } elseif ( $product instanceof Product ) {
            $product->load( 'unit_quantities.unit' );
            $product->load( 'tax_group.taxes' );

            if ( $product->accurate_tracking ) {
                throw new NotAllowedException( __( 'Unable to add a product that has accurate tracking enabled, using an ordinary barcode.' ) );
            }
        }

        if ( $product instanceof Product ) {
            return [
                'type' => 'product',
                'product' => $product,
            ];
        }

        throw new NotFoundException( __( 'There is no products matching the current request.' ) );
    }

    public function printLabels()
    {
        return view::make( 'pages.dashboard.products.print-labels', [
            'title' => __( 'Print Labels' ),
            'description' => __( 'Customize and print products labels.' ),
        ] );
    }

    public function getProcuredProducts( Product $product )
    {
        return $product->procurementHistory->map( function ( $procurementProduct ) {
            $procurementProduct->procurement = $procurementProduct->procurement()->select( 'name' )->first();

            return $procurementProduct;
        } );
    }
}
