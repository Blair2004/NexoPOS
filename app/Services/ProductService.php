<?php

namespace App\Services;

use App\Events\ProductAfterCreatedEvent;
use App\Events\ProductAfterDeleteEvent;
use App\Events\ProductAfterStockAdjustmentEvent;
use App\Events\ProductAfterUpdatedEvent;
use App\Events\ProductBeforeDeleteEvent;
use App\Events\ProductResetEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Models\OrderProduct;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGallery;
use App\Models\ProductHistory;
use App\Models\ProductSubItem;
use App\Models\ProductUnitQuantity;
use App\Models\Unit;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected ProductCategoryService $categoryService,
        protected TaxService $taxService,
        protected CurrencyService $currency,
        protected UnitService $unitService,
        protected BarcodeService $barcodeService
    ) {
        // ...
    }

    /**
     * Get produt using
     * the provided id
     *
     * @param int product id
     * @return Product
     */
    public function get( $id )
    {
        $product = Product::find( $id );

        if ( ! $product instanceof Product ) {
            throw new Exception( __( 'Unable to find the product using the provided id.' ) );
        }

        return $product;
    }

    /**
     * Get Product using barcode
     *
     * @param string barcode
     * @return Product|false
     */
    public function getProductUsingBarcode( $barcode )
    {
        /**
         * checks if a similar product already
         * exists and throw an error if it's the case
         */
        $product = Product::findUsingBarcode( $barcode )
            ->first();

        if ( $product instanceof Product ) {
            return $product;
        }

        return false;
    }

    /**
     * Get Product using barcode
     *
     * @param string barcode
     * @return Product|false
     */
    public function getProductUsingBarcodeOrFail( $barcode )
    {
        /**
         * checks if a similar product already
         * exists and throw an error if it's the case
         */
        $product = Product::findUsingBarcode( $barcode )
            ->first();

        if ( $product instanceof Product ) {
            return $product;
        }

        return false;
    }

    /**
     * Get Product using sku
     *
     * @param string sku
     * @return Product|false
     */
    public function getProductUsingSKU( $sku )
    {
        /**
         * checks if a similar product already
         * exists and throw an error if it's the case
         */
        $product = Product::findUsingSKU( $sku )
            ->first();

        if ( $product instanceof Product ) {
            return $product;
        }

        return false;
    }

    /**
     * retrive a product using a SKU or fail
     * if the product is not found
     *
     * @param string sku
     * @return Product
     */
    public function getProductUsingSKUOrFail( $sku )
    {
        $product = $this->getProductUsingSKU( $sku );

        if ( ! $product instanceof Product ) {
            throw new Exception( __( 'Unable to find the requested product using the provided SKU.' ) );
        }

        return $product;
    }

    /**
     * Create a product either it's a "product"
     * or a "variable" product
     *
     * @param array data to handle
     * @return array response
     */
    public function create( $data )
    {
        /**
         * check if the provided category
         * exists or throw an error
         */
        if ( ! $this->categoryService->get( $data[ 'category_id' ] ) ) {
            throw new Exception( __( 'The category to which the product is attached doesn\'t exists or has been deleted' ) );
        }

        /**
         * We need to check the product
         * before proceed and avoiding adding grouped
         * product within grouped product.
         */
        if ( $data[ 'type' ] === Product::TYPE_GROUPED ) {
            $this->checkGroupProduct( $data[ 'groups' ] );
        }

        /**
         * check if it's a simple product or not
         */
        if ( $data[ 'product_type' ] === 'product' ) {
            return $this->createSimpleProduct( $data );
        } elseif ( $data[ 'product_type' ] === 'variable' ) {
            return $this->createVariableProduct( $data );
        } else {
            throw new NotAllowedException( sprintf( __( 'Unable to create a product with an unknow type : %s' ), $data[ 'product_type' ] ) );
        }
    }

    /**
     * create a variable product
     *
     * @param array data to handle
     * @return array response
     */
    public function createVariableProduct( $data )
    {
        /**
         * let's try to check if the product required
         * fields are valid. We should do that before saving anything to
         * the database
         */
        collect( $data[ 'variations' ] )->each( function( $variation ) {
            if ( $this->getProductUsingBarcode( $variation[ 'barcode' ] ) ) {
                throw new Exception( sprintf( __( 'A variation within the product has a barcode which is already in use : %s.' ), $variation[ 'barcode' ] ) );
            }

            /**
             * search a product using the provided SKU
             * and throw an error if it's the case
             */
            if ( $this->getProductUsingSKU( $variation[ 'sku' ] ) ) {
                throw new Exception( sprintf( __( 'A variation within the product has a SKU which is already in use : %s' ), $variation[ 'sku' ] ) );
            }
        });

        /**
         * save the simple product
         * as a variable product
         */
        $result = $this->createSimpleProduct( $data );
        $parent = $result[ 'data' ][ 'product' ];
        $parent->product_type = 'variable';
        $parent->save();

        /**
         * loop variations to
         * see if they aren't using already in use SKU, Barcode
         */
        foreach ( $data[ 'variations' ] as $variation ) {
            $this->createProductVariation( $parent, $variation );
        }

        return [
            'status' => 'success',
            'message' => __( 'The variable product has been created.' ),
            'data' => compact( 'parent' ),
        ];
    }

    /**
     * Create a simple product
     *
     * @param array data to handle
     * @return array response
     */
    public function createSimpleProduct( $data )
    {
        if ( empty( $data[ 'barcode' ] ) ) {
            $data[ 'barcode' ] = $this->barcodeService->generateRandomBarcode( $data[ 'barcode_type' ] );
        }

        if ( $this->getProductUsingBarcode( $data[ 'barcode' ] ) ) {
            throw new Exception( sprintf(
                __( 'The provided barcode "%s" is already in use.' ),
                $data[ 'barcode' ]
            ) );
        }

        if ( empty( $data[ 'sku' ] ) ) {
            $category = ProductCategory::find( $data[ 'category_id' ] );
            $data[ 'sku' ] = Str::slug( $category->name ) . '--' . Str::slug( $data[ 'name' ] ) . '--' . Str::random(5);
        }

        /**
         * search a product using the provided SKU
         * and throw an error if it's the case
         */
        if ( $this->getProductUsingSKU( $data[ 'sku' ] ) ) {
            throw new Exception( sprintf(
                __( 'The provided SKU "%s" is already in use.' ),
                $data[ 'sku' ]
            ) );
        }

        $product = new Product;
        $mode = 'create';

        foreach ( $data as $field => $value ) {
            if ( ! in_array( $field, [ 'variations' ] ) ) {
                $fields = $data;
                $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
            }
        }

        $product->author = $fields[ 'author' ] ?? Auth::id();
        $product->save();

        /**
         * this will calculate the unit quantities
         * for the created product. This also comute taxes
         */
        $this->__computeUnitQuantities( $fields, $product );

        /**
         * We'll reload the unit quantity
         * that is helpful to test if the tax is well computed
         */
        $product->load( 'unit_quantities' );

        /**
         * save product images
         */
        $this->saveGallery( $product, $fields[ 'images' ] ?? [] );

        /**
         * We'll now save all attached sub items
         */
        if (  $product->type === Product::TYPE_GROUPED ) {
            $this->saveSubItems( $product, $fields[ 'groups' ] ?? [] );
        }

        event( new ProductAfterCreatedEvent( $product, $data ) );

        $editUrl = ns()->route( 'ns.products-edit', [ 'product' => $product->id ]);

        return [
            'status' => 'success',
            'message' => __( 'The product has been saved.' ),
            'data' => compact( 'product', 'editUrl' ),
        ];
    }

    /**
     * Update a product either is a "product" or a "variable"
     *
     * @param int product id
     * @param array fields
     * @return array response
     */
    public function update( Product $product, $data )
    {
        /**
         * check if the provided category
         * exists or throw an error
         */
        if ( ! $this->categoryService->get( $data[ 'category_id' ] ) ) {
            throw new Exception( __( 'The category to which the product is attached doesn\'t exists or has been deleted' ) );
        }

        /**
         * We need to check the product
         * before proceed and avoiding adding grouped
         * product within grouped product.
         */
        if ( $data[ 'type' ] === Product::TYPE_GROUPED ) {
            $this->checkGroupProduct( $data[ 'groups' ] );
        }

        switch ( $data[ 'product_type' ] ) {
            case 'product':
                return $this->updateSimpleProduct( $product, $data );
                break;
            case 'variable':
                return $this->updateVariableProduct( $product, $data );
                break;
            default:
                throw new Exception( sprintf( __( 'Unable to edit a product with an unknown type : %s' ), $data[ 'product_type' ] ) );
                break;
        }
    }

    /**
     * Will release the product taxes
     * before a new modification is made to it
     *
     * @param Product
     * @return void
     */
    public function releaseProductTaxes( $product )
    {
        $product->product_taxes()->delete();
    }

    /**
     * Performs a verification to see if the subitems only
     * consist of valid items (not gruoped items).
     *
     * @param array $fields
     * @return void
     */
    public function checkGroupProduct( $fields ): void
    {
        if ( ! isset( $fields[ 'product_subitems' ] )  ) {
            throw new NotAllowedException( __( 'A grouped product cannot be saved without any sub items.' ) );
        }

        foreach ( $fields[ 'product_subitems' ] as $item ) {
            $product = Product::find( $item[ 'product_id' ] );

            if ( $product->type === Product::TYPE_GROUPED ) {
                throw new NotAllowedException( __( 'A grouped product cannot contain grouped product.' ) );
            }
        }
    }

    /**
     * Update a simple product. This doesn't delete
     * the variable within a product, if this latest has
     * the type "product" before
     *
     * @param int product id
     * @param array fields
     * @return array response
     */
    public function updateSimpleProduct( $id, $fields )
    {
        /**
         * will get a product if
         * the provided value is an integer
         * and not an instance of Product
         */
        $product = $this->getProductUsingArgument( 'id', $id );
        $mode = 'update';

        $this->releaseProductTaxes( $product );

        if ( empty( $fields[ 'barcode' ] ) ) {
            $fields[ 'barcode' ] = $this->barcodeService->generateRandomBarcode( $fields[ 'barcode_type' ] );
        }

        if ( $existingProduct = $this->getProductUsingBarcode( $fields[ 'barcode' ] ) ) {
            if ( $existingProduct->id !== $product->id ) {
                throw new Exception( __( 'The provided barcode is already in use.' ) );
            }
        }

        if ( empty( $fields[ 'sku' ] ) ) {
            $category = ProductCategory::find( $fields[ 'category_id' ] );
            $fields[ 'sku' ] = Str::slug( $category->name ) . '--' . Str::slug( $fields[ 'name' ] ) . '--' . strtolower( Str::random(5) );
        }

        /**
         * search a product using the provided SKU
         * and throw an error if it's the case
         */
        if ( $existingProduct = $this->getProductUsingSKU( $fields[ 'sku' ] ) ) {
            if ( $existingProduct->id !== $product->id ) {
                throw new Exception( __( 'The provided SKU is already in use.' ) );
            }
        }

        foreach ( $fields as $field => $value ) {
            $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
        }

        $product->author = $fields[ 'author' ] ?? Auth::id();
        $product->save();

        /**
         * this will calculate the unit quantities
         * for the creaed product.
         */
        $this->__computeUnitQuantities( $fields, $product );

        /**
         * save product images
         */
        $this->saveGallery( $product, $fields[ 'images' ] ?? [] );

        /**
         * We'll now save all attached sub items. That is only applicable
         * if the product is set to be a grouped product.
         */
        if (  $product->type === Product::TYPE_GROUPED ) {
            $this->saveSubItems( $product, $fields[ 'groups' ] ?? [] );
        }

        event( new ProductAfterUpdatedEvent( $product, $fields ) );

        $editUrl = ns()->route( 'ns.products-edit', [ 'product' => $product->id ]);

        return [
            'status' => 'success',
            'message' => __( 'The product has been updated' ),
            'data' => compact( 'product', 'editUrl' ),
        ];
    }

    /**
     * Saves the sub items by binding that to a product
     *
     * @param Product $product
     * @param array $subItems
     * @return array response
     */
    public function saveSubItems( Product $product, $subItems )
    {
        $savedItems = collect([]);

        foreach ( $subItems[ 'product_subitems' ] as $item ) {
            if ( ! isset( $item[ 'id' ] ) ) {
                $subitem = new ProductSubItem;
                $subitem->parent_id = $product->id;
                $subitem->product_id = $item[ 'product_id' ];
                $subitem->unit_id = $item[ 'unit_id' ];
                $subitem->unit_quantity_id = $item[ 'unit_quantity_id' ];
                $subitem->sale_price = $item[ 'sale_price' ];
                $subitem->quantity = $item[ 'quantity' ];
                $subitem->total_price = $item[ 'total_price' ] ?? (float) $item[ 'sale_price' ] * (float) $item[ 'quantity' ];
                $subitem->author = Auth::id();
                $subitem->save();
            } else {
                $subitem = ProductSubItem::find( $item[ 'id' ] );

                if ( ! $subitem instanceof ProductSubItem ) {
                    throw new NotFoundException( __( 'The requested sub item doesn\'t exists.' ) );
                }

                $subitem->parent_id = $product->id;
                $subitem->product_id = $item[ 'product_id' ];
                $subitem->unit_id = $item[ 'unit_id' ];
                $subitem->unit_quantity_id = $item[ 'unit_quantity_id' ];
                $subitem->sale_price = $item[ 'sale_price' ];
                $subitem->quantity = $item[ 'quantity' ];
                $subitem->total_price = $item[ 'total_price' ] ?? (float) $item[ 'sale_price' ] * (float) $item[ 'quantity' ];
                $subitem->author = Auth::id();
                $subitem->save();
            }

            $savedItems->push( $subitem->id );
        }

        /**
         * We'll delete all products
         * that aren't submitted
         */
        ProductSubItem::where( 'parent_id', $product->id )
            ->whereNotIn( 'id', $savedItems->toArray() )
            ->delete();

        return [
            'status' => 'success',
            'message' => __( 'The subitem has been saved.' ),
        ];
    }

    public function saveGallery( Product $product, $groups )
    {
        $product->galleries()
            ->get()
            ->each( function( $image ) {
                $image->delete();
            });

        /**
         * if there are many featured images
         * let's choose one for the user.
         *
         * @todo should be tested
         */
        $manyPrimary = collect( $groups )->map( function( $fields ) {
            return isset( $fields[ 'featured' ] ) && (int) $fields[ 'featured' ] === 1;
        })
            ->filter( fn( $result ) => $result === true )
            ->count() > 1;

        if ( $manyPrimary ) {
            $groups = collect( $groups )->map( function( $fields, $index ) {
                return collect( $fields )->map( function( $field, $fieldName ) use ( $index ) {
                    if ( $fieldName === 'featured' ) {
                        if ( $index === 0 ) {
                            $field = 1;
                        } else {
                            $field = 0;
                        }
                    }

                    return $field;
                });
            });
        }

        foreach ( $groups as $group ) {
            $image = new ProductGallery;
            $image->featured = $group[ 'featured' ] ?? 0;
            $image->url = $group[ 'url' ];
            $image->author = $product->author;
            $image->product_id = $product->id;
            $image->save();
        }
    }

    /**
     * Update a variable product
     *
     * @param Product $product
     * @param array fields to save
     * @return array response of the process
     */
    public function updateVariableProduct( Product $product, $data )
    {
        /**
         * let's try to check if the product variations
         * doesn't use any barcode already in use excluding
         * their id
         *
         * @var Illuminate\Support\Collection
         */
        $valid = collect( $data[ 'variations' ] )->filter( function( $product ) {
            return ! empty( $product[ 'id' ] );
        });

        /**
         * if the product variation doesn\'t include
         * any identifier
         */
        if ( $valid->empty() ) {
            throw new Exception(
                __( 'One of the provided product variation doesn\'t include an identifier.' )
            );
        }

        $valid->each( function( $variation ) {
            if ( $foundProduct = $this->getProductUsingBarcode( $variation[ 'barcode' ] ) ) {
                if ( $foundProduct->id !== $variation[ 'id' ] ) {
                    throw new Exception( sprintf( __( 'A variation within the product has a barcode which is already in use : %s.' ), $variation[ 'barcode' ] ) );
                }
            }

            /**
             * search a product using the provided SKU
             * and throw an error if it's the case
             */
            if ( $foundProduct = $this->getProductUsingSKU( $variation[ 'sku' ] ) ) {
                if ( $foundProduct->id !== $variation[ 'id' ] ) {
                    throw new Exception( sprintf( __( 'A variation within the product has a SKU which is already in use : %s' ), $variation[ 'sku' ] ) );
                }
            }
        });

        /**
         * let's update the product and recover the
         * parent product, which id will be reused.
         *
         * @var array [
         *      'status': string,
         *      'message': string,
         *      'product': Product
         * ]
         */
        $result = $this->updateSimpleProduct( $product, $data );
        $parent = $result[ 'data' ][ 'product' ];
        $parent->product_type = 'variable';
        $parent->save();

        /**
         * loop variations to see if they aren't
         * using already used SKU or Barcode
         */
        foreach ( $data[ 'variations' ] as $variation ) {
            $this->updateProductVariation( $parent, $variation[ 'id' ], $variation );
        }

        return [
            'status' => 'success',
            'message' => __( 'The variable product has been updated.' ),
            'data' => compact( 'parent' ),
        ];
    }

    /**
     * Compute the tax and update the
     * product according to the tax assigned
     * to that product
     *
     * @param Product instance of the product to update
     * @param array of the data to handle
     * @return array response of the process
     */
    private function __fillProductFields( Product $product, array $data )
    {
        /**
         * @param string $field
         * @param mixed $value
         * @param string $mode
         * @param array fields
         */
        extract( $data );

        if ( ! in_array( $field, [ 'units', 'images', 'groups' ]) && ! is_array( $value ) ) {
            $product->$field = $value;
        } elseif ( $field === 'units' ) {
            $product->unit_group = $fields[ 'units' ][ 'unit_group' ];
            $product->accurate_tracking = $fields[ 'units' ][ 'accurate_tracking' ] ?? false;
        }
    }

    private function __computeUnitQuantities( $fields, $product )
    {
        if ( $fields[ 'units' ] ) {
            foreach ( $fields[ 'units' ][ 'selling_group' ] as $group ) {
                $unitQuantity = $this->getUnitQuantity(
                    $product->id,
                    $group[ 'unit_id' ]
                );

                if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
                    $unitQuantity = new ProductUnitQuantity;
                    $unitQuantity->unit_id = $group[ 'unit_id' ];
                    $unitQuantity->product_id = $product->id;
                    $unitQuantity->quantity = 0;
                }

                /**
                 * We don't need tos ave all the informations
                 * available on the group variable, that's why we define
                 * explicitly how everything is saved here.
                 */
                $unitQuantity->sale_price = $this->currency->define( $group[ 'sale_price_edit' ] )->getRaw();
                $unitQuantity->sale_price_edit = $this->currency->define( $group[ 'sale_price_edit' ] )->getRaw();
                $unitQuantity->wholesale_price_edit = $this->currency->define( $group[ 'wholesale_price_edit' ] )->getRaw();
                $unitQuantity->preview_url = $group[ 'preview_url' ] ?? '';
                $unitQuantity->low_quantity = $group[ 'low_quantity' ] ?? 0;
                $unitQuantity->stock_alert_enabled = $group[ 'stock_alert_enabled' ] ?? false;

                /**
                 * Let's compute the tax only
                 * when the tax group is provided.
                 */
                $this->taxService->computeTax(
                    $unitQuantity,
                    $fields[ 'tax_group_id' ] ?? null,
                    $fields[ 'tax_type' ] ?? null
                );

                /**
                 * save custom barcode for the created unit quantity
                 */
                $unitQuantity->barcode = $product->barcode . '-' . $unitQuantity->id;
                $unitQuantity->save();
            }
        }
    }

    /**
     * refresh the price for a specific product
     *
     * @param ProductUnitQuantity instance of the product
     * @return array response of the operation
     *
     * @deprecated
     */
    public function refreshPrices( ProductUnitQuantity $product )
    {
        return $this->taxService->computeTax( $product, $product->tax_group_id ?? null );
    }

    /**
     * get product quantity according
     * to a specific unit id
     *
     * @param int product id
     * @param int unit id
     */
    public function getQuantity( $product_id, $unit_id )
    {
        $unitQuantities = $this->get( $product_id )->unit_quantities;
        $filtredQuantities = $unitQuantities->filter( function( $quantity ) use ( $unit_id ) {
            return (int) $quantity->unit_id === (int) $unit_id;
        });

        /**
         * if there is not an entry, we'll return 0
         * if there is an entry, we'll get the first quantity
         * if it's no set, we'll return 0
         */
        return $filtredQuantities->count() > 0 ? $this->currency->define(
            $filtredQuantities->first()->quantity
        )->get() : 0;
    }

    /**
     * save product history
     *
     * @param string operation type
     * @param array history to save
     * @return array
     */
    public function saveHistory( $operationType, array $data )
    {
        switch ( $operationType ) {
            case ProductHistory::ACTION_STOCKED:
                $this->__saveProcurementHistory( $data );
                break;
        }
    }

    /**
     * Record a procurement history for
     * a specific set of product informations
     *
     * @param array product informations to handle
     * @return array response of the process.
     * @return void
     */
    private function __saveProcurementHistory( $data )
    {
        /**
         * @var int unit_id
         * @var int product_id
         * @var float unit_price
         * @var float total_price
         * @var int procurement_product_id
         * @var int procurement_id
         * @var float quantity
         */
        extract( $data );

        $currentQuantity = $this->getQuantity( $product_id, $unit_id );
        $newQuantity = $this->currency
            ->define( $currentQuantity )
            ->additionateBy( $quantity )
            ->get();

        $history = new ProductHistory;
        $history->product_id = $product_id;
        $history->procurement_id = $procurement_id;
        $history->procurement_product_id = $procurement_product_id;
        $history->unit_id = $unit_id;
        $history->operation_type = ProductHistory::ACTION_STOCKED;
        $history->unit_price = $unit_price;
        $history->total_price = $total_price;
        $history->before_quantity = $currentQuantity;
        $history->quantity = $quantity;
        $history->after_quantity = $newQuantity;
        $history->author = Auth::id() ?: Procurement::find( $procurement_id )->author;
        $history->save();
    }

    /**
     * set quantity
     * this will update the quantity of
     * a product using a unit as a reference
     *
     * @param int product id
     * @param int unit id
     * @param float quantity
     * @return arrray response
     */
    public function setQuantity( $product_id, $unit_id, $quantity )
    {
        $query = ProductUnitQuantity::where( 'product_id', $product_id )
            ->where( 'unit_id', $unit_id );

        $unitQuantity = $query->first();

        if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
            $unitQuantity = new ProductUnitQuantity;
        }

        $unitQuantity->product_id = $product_id;
        $unitQuantity->unit_id = $unit_id;
        $unitQuantity->quantity = $quantity;
        $unitQuantity->save();

        return [
            'status' => 'success',
            'message' => __( 'The product\'s unit quantity has been updated.' ),
            'data' => compact( 'unitQuantity' ),
        ];
    }

    /**
     * Reset a product quantity
     * this will delete all quantity
     *
     * @param int|Product product id
     * @return array response
     */
    public function resetProduct( $product_id )
    {
        /**
         * to avoid multiple call to the DB
         */
        if ( $product_id instanceof Product ) {
            $product = $product_id;
            $product_id = $product->id;
        } else {
            $product = $this->get( $product_id );
        }

        /**
         * let's check if the product is a variable
         * product
         */
        if ( $product->product_type === 'variable' ) {
            $result = $product->variations->map( function( Product $product ) {
                return $this->__resetProductRelatives( $product );
            })->toArray();

            if ( count( $result ) === 0 ) {
                return [
                    'status' => 'info',
                    'message' => sprintf( __( 'Unable to reset this variable product "%s", since it doens\'t seems to have any variations' ), $product->name ),
                ];
            }

            return [
                'status' => 'success',
                'message' => __( 'The product variations has been reset' ),
                'data' => compact( 'result' ),
            ];
        } else {
            return $this->__resetProductRelatives( $product );
        }
    }

    private function __resetProductRelatives( Product $product )
    {
        ProductHistory::where( 'product_id', $product->id )->delete();
        ProductUnitQuantity::where( 'product_id', $product->id )->delete();

        /**
         * dispatch an event to let everyone knows
         * a product has been reset
         */
        event( new ProductResetEvent( $product ) );

        return [
            'status' => 'success',
            'message' => __( 'The product has been reset.' ),
            'data' => compact( 'product' ),
        ];
    }

    /**
     * delete a product using the
     * provided identifier
     *
     * @param int product id
     * @return array operation status
     */
    public function deleteUsingID( $product_id )
    {
        $product = $this->get( $product_id );

        return $this->deleteProduct( $product );
    }

    /**
     * delete an instance of a product
     *
     * @param Product instance to delete
     * @return array operation status
     */
    public function deleteProduct( Product $product )
    {
        $name = $product->name;

        event( new ProductBeforeDeleteEvent( $product ) );

        $product->delete();

        event( new ProductAfterDeleteEvent( $product ) );

        return [
            'status' => 'success',
            'message' => sprintf( __( 'The product "%s" has been successfully deleted' ), $name ),
        ];
    }

    /**
     * get product variation
     *
     * @param int|Product
     * @return Collection<Product> variation
     */
    public function getProductVariations( $product = null )
    {
        if ( $product !== null ) {
            if ( is_numeric( $product ) ) {
                $product = $this->get( $product );
            }

            return $product->variations;
        } else {
            return Product::onlyVariations()->get();
        }
    }

    /**
     * get variations
     *
     * @param int id to find
     * @return Product
     */
    public function getVariations()
    {
        return Product::onlyVariations()->get();
    }

    /**
     * get speciifc variation
     *
     * @param int variation id
     * @return Product
     */
    public function getVariation( $id )
    {
        $variation = Product::where( 'product_type', 'variation' )
            ->where( 'id', $id )
            ->first();

        if ( ! $variation instanceof Product ) {
            throw new Exception( __( 'Unable to find the requested variation using the provided ID.' ) );
        }

        return $variation;
    }

    /**
     * get unit quantity for a specific product
     *
     * @param int product id
     * @return Collection<ProductUnitQuantity>
     */
    public function getUnitQuantities( $product_id )
    {
        return ProductUnitQuantity::withProduct( $product_id )
            ->get()
            ->map( function( $productQuantity ) {
                $productQuantity->unit;

                return $productQuantity;
            });
    }

    public function getUnitQuantity( $product_id, $unit_id )
    {
        return ProductUnitQuantity::withProduct( $product_id )
            ->withUnit( $unit_id )
            ->first();
    }

    /**
     * get specific product quantity using the provided id
     *
     * @param int id
     * @return Collection<ProductHistory>
     */
    public function getProductHistory( $product_id )
    {
        return ProductHistory::withProduct( $product_id )->orderBy( 'id' )->get()->map( function( $product ) {
            $product->unit;

            return $product;
        });
    }

    /**
     * @param ProcurementProduct updating a procurement
     * @param array fields [ quantity, unit_id, purchase_price ]
     * @return void
     */
    public function procurementStockOuting( ProcurementProduct $oldProduct, $fields )
    {
        $history = $this->stockAdjustment( ProductHistory::ACTION_REMOVED, [
            'unit_id' => $oldProduct->unit_id,
            'product_id' => $oldProduct->product_id,
            'unit_price' => $oldProduct->purchase_price,
            'total_price' => $oldProduct->total_price,
            'procurement_id' => $oldProduct->procurement_id,
            'procurementProduct' => $oldProduct,
            'procurement_product_id' => $oldProduct->id,
            'quantity' => $fields[ 'quantity' ],
        ]);

        return [
            'status' => 'success',
            'message' => __( 'The product stock has been updated.' ),
            'compac' => compact( 'history' ),
        ];
    }

    /**
     * make an unit adjustment for
     * a specific product
     *
     * @param string operation : deducted, sold, procured, deleted, adjusted, damaged
     * @param mixed[]<$unit_id,$product_id,$unit_price,?$total_price,?$procurement_id,?$procurement_product_id,?$sale_id,?$quantity> $data to manage
     * @return ProductHistory|EloquentCollection|bool
     */
    public function stockAdjustment( $action, $data ): ProductHistory|EloquentCollection|bool
    {
        extract( $data, EXTR_REFS );
        /**
         * @param int $product_id
         * @param float $unit_price
         * @param id $unit_id
         * @param float $total_price
         * @param int $procurement_product_id
         * @param OrderProduct $orderProduct
         * @param ProcurementProduct $procurementProduct
         * @param string $description
         * @param float $quantity
         * @param string $sku
         * @param string $unit_identifier
         */
        $product = isset( $product_id ) ? Product::findOrFail( $product_id ) : Product::usingSKU( $sku )->first();
        $product_id = $product->id;
        $unit_id = isset( $unit_id ) ? $unit_id : $unit->id;
        $unit = Unit::findOrFail( $unit_id );

        /**
         * let's check the different
         * actions which are allowed on the current request
         */
        if ( ! in_array( $action, [
            ProductHistory::ACTION_DEFECTIVE,
            ProductHistory::ACTION_DELETED,
            ProductHistory::ACTION_STOCKED,
            ProductHistory::ACTION_REMOVED,
            ProductHistory::ACTION_ADDED,
            ProductHistory::ACTION_RETURNED,
            ProductHistory::ACTION_SOLD,
            ProductHistory::ACTION_TRANSFER_IN,
            ProductHistory::ACTION_TRANSFER_REJECTED,
            ProductHistory::ACTION_TRANSFER_CANCELED,
            ProductHistory::ACTION_TRANSFER_OUT,
            ProductHistory::ACTION_LOST,
            ProductHistory::ACTION_VOID_RETURN,
            ProductHistory::ACTION_ADJUSTMENT_RETURN,
            ProductHistory::ACTION_ADJUSTMENT_SALE,
        ]) ) {
            throw new NotAllowedException( __( 'The action is not an allowed operation.' ) );
        }

        /**
         * if the total_price is not provided
         * then we'll compute it
         */
        $total_price = empty( $data[ 'total_price' ] ) ? $this->currency
            ->define( $data[ 'unit_price' ] )
            ->multipliedBy( $data[ 'quantity' ] )
            ->get() : $data[ 'total_price' ];

        /**
         * the change on the stock is only performed
         * if the Product has the stock management enabled.
         */
        if ( $product->stock_management === Product::STOCK_MANAGEMENT_ENABLED ) {
            if ( $product->type === Product::TYPE_GROUPED ) {
                return $this->handleStockAdjustmentsForGroupedProducts(
                    action: $action,
                    orderProductQuantity: $quantity,
                    product: $product,
                    orderProduct: isset( $orderProduct ) ? $orderProduct : null,
                    parentUnit: $unit
                );
            } else {
                return $this->handleStockAdjustmentRegularProducts(
                    action: $action,
                    quantity: $quantity,
                    product_id: $product_id,
                    unit_id: $unit_id,
                    total_price: $total_price,
                    unit_price: $unit_price,
                    orderProduct: isset( $orderProduct ) ? $orderProduct : null,
                    procurementProduct: isset( $procurementProduct ) ? $procurementProduct : null
                );
            }
        }

        return false;
    }

    /**
     * Handle stock transaction for the grouped products
     *
     * @param string $action
     * @param float $quantity
     * @param Product $product
     * @param Unit $unit
     * @return EloquentCollection
     */
    private function handleStockAdjustmentsForGroupedProducts( 
        $action, 
        $orderProductQuantity, 
        Product $product, 
        Unit $parentUnit, 
        OrderProduct $orderProduct = null  ): EloquentCollection
    {
        $product->load( 'sub_items' );

        $products   =   $product->sub_items->map( function( ProductSubItem $subItem ) use ( $action, $orderProductQuantity, $parentUnit, $orderProduct ) {            
            $finalQuantity = $this->computeSubItemQuantity(
                subItemQuantity: $subItem->quantity,
                parentUnit: $parentUnit,
                parentQuantity: $orderProductQuantity
            );
            
            /**
             * Let's retrieve the old item quantity.
             */
            $oldQuantity = $this->getQuantity( $subItem->product_id, $subItem->unit_id );

            if ( in_array( $action, ProductHistory::STOCK_REDUCE ) ) {
                $this->preventNegativity(
                    oldQuantity: $oldQuantity,
                    quantity: $finalQuantity
                );

                /**
                 * @var string status
                 * @var string message
                 * @var array [ 'oldQuantity', 'newQuantity' ]
                 */
                $result = $this->reduceUnitQuantities(
                    product_id: $subItem->product_id,
                    unit_id: $subItem->unit_id,
                    quantity: $finalQuantity,
                    oldQuantity: $oldQuantity
                );
            } else {
                /**
                 * @var string status
                 * @var string message
                 * @var array [ 'oldQuantity', 'newQuantity' ]
                 */
                $result = $this->increaseUnitQuantities(
                    product_id: $subItem->product_id,
                    unit_id: $subItem->unit_id,
                    quantity: $finalQuantity,
                    oldQuantity: $oldQuantity
                );
            }

            /**
             * We would like to record for every sub product
             * included an history of the stock transaction.
             */
            return $this->recordStockHistory(
                product_id: $subItem->product_id,
                action: $action,
                unit_id: $subItem->unit_id,
                unit_price: $subItem->sale_price,
                quantity: $finalQuantity,
                order_id: $orderProduct->order_id,
                order_product_id: $orderProduct->id,
                total_price: $finalQuantity * $subItem->sale_price,
                old_quantity: $result[ 'data' ][ 'oldQuantity' ],
                new_quantity: $result[ 'data' ][ 'newQuantity' ]
            );
        });

        /**
         * This should record the transaction for
         * the grouped product
         */
        $this->recordStockHistory(
            product_id: $orderProduct->id,
            action: $action,
            unit_id: $orderProduct->unit_id,
            unit_price: $orderProduct->unit_price,
            quantity: $orderProductQuantity,
            order_id: $orderProduct->order_id,
            order_product_id: $orderProduct->id,
            total_price: $orderProductQuantity * $orderProduct->unit_price,
            old_quantity: 0,
            new_quantity: 0
        );

        return $products;
    }

    /**
     * Will prevent negativity to occurs
     *
     * @param float $oldQuantity
     * @param float $quantity
     * @return void
     */
    private function preventNegativity( $oldQuantity, $quantity )
    {
        $diffQuantity = $this->currency
            ->define( $oldQuantity )
            ->subtractBy( $quantity )
            ->get();

        /**
         * this should prevent negative
         * stock on the current item
         */
        if ( $diffQuantity < 0 ) {
            throw new NotAllowedException( sprintf( __( 'Unable to proceed, this action will cause negative stock (%s). Old Quantity : (%s),  Quantity : (%s).' ), $diffQuantity, $oldQuantity, $quantity ) );
        }
    }

    /**
     * We'll handle here stock adjustment
     * for all regular products
     *
     * @param string $action
     * @param float $oldQuantity
     * @param float $quantity
     * @param int $product_id
     * @param int $order_id
     * @param int $order_product_id
     * @param int $unit_id
     * @param ProcurementProduct $procurementProduct
     * @return ProductHistory
     */
    private function handleStockAdjustmentRegularProducts( $action, $quantity, $product_id, $unit_id, $orderProduct = null, $unit_price = 0, $total_price = 0, $procurementProduct = null )
    {
        /**
         * we would like to verify if
         * by editing a procurement product
         * the remaining quantity will be greather than 0
         */
        $oldQuantity = $this->getQuantity( $product_id, $unit_id );

        if ( in_array( $action, ProductHistory::STOCK_REDUCE ) ) {
            $this->preventNegativity(
                oldQuantity: $oldQuantity,
                quantity: $quantity
            );

            /**
             * @var string status
             * @var string message
             * @var array [ 'oldQuantity', 'newQuantity' ]
             */
            $result = $this->reduceUnitQuantities( $product_id, $unit_id, abs( $quantity ), $oldQuantity );

            /**
             * We should reduce the quantity if
             * we're dealing with a product that has
             * accurate stock tracking
             */
            if ( $procurementProduct instanceof ProcurementProduct ) {
                $this->updateProcurementProductQuantity( $procurementProduct, $quantity, ProcurementProduct::STOCK_REDUCE );
            }
        } else {
            /**
             * @var string status
             * @var string message
             * @var array [ 'oldQuantity', 'newQuantity' ]
             */
            $result = $this->increaseUnitQuantities( $product_id, $unit_id, abs( $quantity ), $oldQuantity );

            /**
             * We should reduce the quantity if
             * we're dealing with a product that has
             * accurate stock tracking
             */
            if ( $procurementProduct instanceof ProcurementProduct ) {
                $this->updateProcurementProductQuantity( $procurementProduct, $quantity, ProcurementProduct::STOCK_INCREASE );
            }
        }

        return $this->recordStockHistory(
            product_id: $product_id,
            action: $action,
            unit_id: $unit_id,
            unit_price: $unit_price,
            quantity: $quantity,
            total_price: $total_price,
            order_id: isset( $orderProduct ) ? $orderProduct->order_id : null,
            order_product_id: isset( $orderProduct ) ? $orderProduct->id : null,
            old_quantity: $result[ 'data' ][ 'oldQuantity' ],
            new_quantity: $result[ 'data' ][ 'newQuantity' ]
        );
    }

    /**
     * Records stock transaction for the provided product.
     *
     * @param int $product_id
     * @param string $action
     * @param int $unit_id
     * @param float $unit_price
     * @param float $quantity
     * @param float $total_price
     * @param float $old_quantity
     * @param float $new_quantity
     */
    public function recordStockHistory( 
        $product_id, 
        $action, 
        $unit_id, 
        $unit_price, 
        $quantity, 
        $total_price, 
        $order_id = null, 
        $order_product_id = null,
        $old_quantity = 0, 
        $new_quantity = 0 )
    {
        $history = new ProductHistory;
        $history->product_id = $product_id;
        $history->procurement_id = $procurement_id ?? null;
        $history->procurement_product_id = $procurement_product_id ?? null;
        $history->unit_id = $unit_id;
        $history->order_id = $order_id ?? null;
        $history->order_product_id = $order_product_id ?? null;
        $history->operation_type = $action;
        $history->unit_price = $unit_price;
        $history->total_price = $total_price;
        $history->description = $description ?? ''; // a description might be provided to describe the operation
        $history->before_quantity = $old_quantity; // if the stock management is 0, it shouldn't change
        $history->quantity = abs( $quantity );
        $history->after_quantity = $new_quantity; // if the stock management is 0, it shouldn't change
        $history->author = Auth::id();
        $history->save();

        event( new ProductAfterStockAdjustmentEvent( $history ) );

        return $history;
    }

    /**
     * Return a base unit from a unit.
     */
    public function getBaseUnit( Unit $unit )
    {
        if ( $unit->base_unit ) {
            return $unit;
        }

        $unit->load( 'group.units' );
        return $unit->group->units->filter( fn( $unit ) => $unit->base_unit )->first();
    }

    public function computeSubItemQuantity( 
        float $subItemQuantity,
        Unit $parentUnit,
        float $parentQuantity )
    {
        return ( ( $subItemQuantity * $parentUnit->value ) * $parentQuantity );
    }

    /**
     * Update procurement product quantity
     *
     * @param ProcurementProduct $procurementProduct
     * @param int $quantity
     * @param string $action
     */
    public function updateProcurementProductQuantity( $procurementProduct, $quantity, $action )
    {
        if ( $action === ProcurementProduct::STOCK_INCREASE ) {
            $procurementProduct->available_quantity += $quantity;
        } elseif ( $action === ProcurementProduct::STOCK_REDUCE ) {
            $procurementProduct->available_quantity -= $quantity;
        }

        $procurementProduct->save();
    }

    /**
     * reduce Product unit quantities and update
     * the available quantity for the unit provided
     *
     * @param int product_id
     * @param int unit_id
     * @param float quantity
     * @return void
     */
    public function reduceUnitQuantities( $product_id, $unit_id, $quantity, $oldQuantity )
    {
        /**
         * we would like to verify if
         * by editing a procurement product
         * the remaining quantity will be greather than 0
         */
        $newQuantity = $this->currency
            ->define( $oldQuantity )
            ->subtractBy( $quantity )
            ->get();

        /**
         * update the remaining quantity
         * by removing the quantity that has been
         * deducted
         */
        $this->setQuantity(
            $product_id,
            $unit_id,
            $newQuantity
        );

        return [
            'status' => 'success',
            'message' => __( 'The product quantity has been updated.' ),
            'data' => compact( 'newQuantity', 'oldQuantity', 'quantity' ),
        ];
    }

    /**
     * Increase Product unit quantities and update
     * the available quantity for the unit provided
     *
     * @param int product_id
     * @param int unit_id
     * @param float quantity
     * @return void
     */
    public function increaseUnitQuantities( $product_id, $unit_id, $quantity, $oldQuantity )
    {
        /**
         * we would like to verify if
         * by editing a procurement product
         * the remaining quantity will be greather than 0
         */
        $newQuantity = $this->currency
            ->define( $oldQuantity )
            ->additionateBy( $quantity )
            ->get();

        /**
         * update the remaining quantity
         * by removing the quantity that has been
         * deducted
         */
        $this->setQuantity(
            $product_id,
            $unit_id,
            $newQuantity
        );

        return [
            'status' => 'success',
            'message' => __( 'The product quantity has been updated.' ),
            'data' => compact( 'newQuantity', 'oldQuantity', 'quantity' ),
        ];
    }

    /**
     * add a stock entry to a product
     * history using the provided informations
     *
     * @param ProcurementProduct $product
     * @param array<$quantity,$unit_id,$purchase_price,$product_id>
     */
    public function procurementStockEntry( ProcurementProduct $product, $fields )
    {
        $history = $this->stockAdjustment( ProductHistory::ACTION_ADDED, [
            'unit_id' => $product->unit_id,
            'product_id' => $product->product_id,
            'unit_price' => $product->purchase_price,
            'total_price' => $product->total_price,
            'procurement_id' => $product->procurement_id,
            'procurementProduct' => $product,
            'procurement_product_id' => $product->id,
            'quantity' => $fields[ 'quantity' ],
        ]);

        return [
            'status' => 'success',
            'message' => __( 'The product stock has been updated.' ),
            'data' => compact( 'history' ),
        ];
    }

    /**
     * returns only variable & product
     *
     * @return Collection
     */
    public function getProducts()
    {
        return Product::excludeVariations()->get();
    }

    /**
     * Before delete a specific variations
     *
     * @return array operation result
     */
    public function deleteVariations( $id = null )
    {
        $variations = $this->getVariations( $id );
        $count = $variations->count();

        $variations->map( function( $variation ) {
            event( new ProductBeforeDeleteEvent( $variation ) );

            $variation->delete();

            event( new ProductAfterDeleteEvent( $variation ) );
        });

        if ( $count === 0 ) {
            return [
                'status' => 'info',
                'message' => __( 'There is no variations to delete.' ),
            ];
        }

        return [
            'status' => 'success',
            'message' => sprintf( __( '%s product(s) has been deleted.' ), $count ),
        ];
    }

    /**
     * Delete all the available products
     *
     * @return array result of the operation
     */
    public function deleteAllProducts()
    {
        $result = $this->getProducts()->map( function( $product ) {
            return $this->deleteProduct( $product );
        })->toArray();

        if ( ! $result ) {
            return [
                'status' => 'info',
                'message' => __( 'There is no products to delete.' ),
            ];
        }

        return [
            'status' => 'success',
            'message' => sprintf( __( '%s products(s) has been deleted.' ), count( $result ) ),
            'data' => compact( 'result' ),
        ];
    }

    /**
     * Will return the last purchase price
     * defined for the provided product
     *
     * @param Product $product
     * @return float
     */
    public function getLastPurchasePrice( $product )
    {
        if ( $product instanceof Product ) {
            $procurementProduct = ProcurementProduct::where( 'product_id', $product->id )
                ->orderBy( 'id', 'desc' )
                ->first();

            if ( $procurementProduct instanceof ProcurementProduct ) {
                return $procurementProduct->purchase_price;
            }
        }

        return 0;
    }

    /**
     * Get a specific product using the
     * provided argument & identifier
     *
     * @param string argument
     * @param string|int identifier
     * @return Product
     */
    public function getProductUsingArgument( $argument = 'id', $identifier = null )
    {
        if ( $identifier instanceof Product ) {
            return $identifier;
        }

        try {
            switch ( $argument ) {
                case 'id':
                    return $this->get( $identifier );
                case 'sku':
                    return $this->getProductUsingSKUOrFail( $identifier );
                case 'barcode':
                    return $this->getProductUsingBarcodeOrFail( $identifier );
            }
        } catch ( Exception $exception ) {
            throw new Exception( sprintf( __( 'Unable to find the product, as the argument "%s" which value is "%s", doesn\'t have any match.' ), $argument, $identifier ) );
        }
    }

    /**
     * Create a variation for a
     * specified parent product
     *
     * @param Product parent
     * @param array fields
     * @return array
     */
    public function createProductVariation( Product $parent, $fields )
    {
        $product = new Product;
        $mode = 'create';

        foreach ( $fields as $field => $value ) {
            $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
        }

        $product->author = Auth::id();
        $product->parent_id = $parent->id;
        $product->type = $parent->type;
        $product->category_id = $parent->category_id;
        $product->product_type = 'variation';
        $product->save();

        /**
         * compute product tax
         */
        $this->taxService->computeTax( $product, $fields[ 'tax_group_id' ] ?? null );

        return [
            'status' => 'success',
            'message' => __( 'The product variation has been successfully created.' ),
            'data' => compact( 'product' ),
        ];
    }

    /**
     * Update product variation
     *
     * @param Product $parent
     * @param int $id
     * @param array $fields
     * @return array
     */
    public function updateProductVariation( $parent, $id, $fields )
    {
        $product = Product::find( $id );
        $mode = 'update';

        foreach ( $fields as $field => $value ) {
            /**
             * we'll update the data
             * since the variation don't need to
             * access the parent data informations.
             */
            $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
        }

        $product->author = Auth::id();
        $product->parent_id = $parent->id;
        $product->type = $parent->type;
        $product->product_type = 'variation';
        $product->save();

        /**
         * compute product tax
         * for the meantime we assume the tax applies on the
         * main product
         */
        $this->taxService->computeTax( $product, $fields[ 'tax_group_id' ] ?? null );

        return [
            'status' => 'success',
            'message' => __( 'The product variation has been updated.' ),
            'data' => compact( 'product' ),
        ];
    }

    /**
     * Will return the Product Unit Quantities
     * for the provided product
     *
     * @param Product $product
     * @return array
     */
    public function getProductUnitQuantities( Product $product )
    {
        $product->unit_quantities->each( fn( $quantity ) => $quantity->load( 'unit' ) );

        return $product->unit_quantities;
    }

    /**
     * Generate product barcode using product
     * configurations.
     *
     * @param Product $product
     * @return void
     */
    public function generateProductBarcode( Product $product )
    {
        $this->barcodeService->generateBarcode(
            $product->barcode,
            $product->barcode_type
        );

        $product->unit_quantities->each( function( $unitQuantity ) use ( $product ) {
            $this->barcodeService->generateBarcode(
                $unitQuantity->barcode,
                $product->barcode_type
            );
        });
    }

    public function searchProduct( $search, $limit = 5, $arguments = [] )
    {
        /**
         * @var Builder $query
         */
        $query = Product::query()
            ->searchable()
            ->where( function( $query ) use ( $search ) {
                $query
                ->orWhere( 'name', 'LIKE', "%{$search}%" )
                ->orWhere( 'sku', 'LIKE', "%{$search}%" )
                ->orWhere( 'barcode', 'LIKE', "%{$search}%" );
            })
            ->with( 'unit_quantities.unit' )
            ->limit( $limit );

        /**
         * if custom arguments are provided
         * we'll parse it and convert it into
         * eloquent arguments
         */
        if ( ! empty( $arguments ) ) {
            $eloquenize = new EloquenizeArrayService;
            $eloquenize->parse( $query, $arguments );
        }

        return  $query->get()
            ->map( function( $product ) {
                $units = json_decode( $product->purchase_unit_ids );

                if ( $units ) {
                    $product->purchase_units = collect();
                    collect( $units )->each( function( $unitID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $unitID ) );
                    });
                }

                return $product;
            });
    }
}
