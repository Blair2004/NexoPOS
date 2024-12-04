<?php

namespace App\Services;

use App\Events\ProcurementAfterCreateEvent;
use App\Events\ProcurementAfterDeleteProductEvent;
use App\Events\ProcurementAfterHandledEvent;
use App\Events\ProcurementAfterSaveProductEvent;
use App\Events\ProcurementAfterUpdateEvent;
use App\Events\ProcurementBeforeCreateEvent;
use App\Events\ProcurementBeforeDeleteProductEvent;
use App\Events\ProcurementBeforeHandledEvent;
use App\Events\ProcurementBeforeUpdateEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Unit;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProcurementService
{
    protected $providerService;

    protected $unitService;

    protected $productService;

    protected $currency;

    protected $dateService;

    /**
     * @param BarcodeService $barcodeservice
     **/
    protected $barcodeService;

    public function __construct(
        ProviderService $providerService,
        UnitService $unitService,
        ProductService $productService,
        CurrencyService $currency,
        DateService $dateService,
        BarcodeService $barcodeService
    ) {
        $this->providerService = $providerService;
        $this->unitService = $unitService;
        $this->productService = $productService;
        $this->dateService = $dateService;
        $this->currency = $currency;
        $this->barcodeService = $barcodeService;
    }

    /**
     * get a single procurement
     * or retrieve a list of procurement
     *
     * @param int procurement id
     * @return Collection|Procurement
     */
    public function get( $id = null )
    {
        if ( $id !== null ) {
            $provider = Procurement::find( $id );

            if ( ! $provider instanceof Procurement ) {
                throw new Exception( __( 'Unable to find the requested procurement using the provided identifier.' ) );
            }

            return $provider;
        }

        return Procurement::get();
    }

    public function procurementName()
    {
        $lastProcurement = Procurement::orderBy( 'id', 'desc' )->first();

        if ( $lastProcurement instanceof Procurement ) {
            $number = str_pad( $lastProcurement->id + 1, 5, '0', STR_PAD_LEFT );
        } else {
            $number = str_pad( 1, 5, '0', STR_PAD_LEFT );
        }

        return sprintf( __( '%s' ), $number );
    }

    /**
     * create a procurement
     * using the provided informations
     *
     * @param array procurement data
     * @return array|Exception
     */
    public function create( $data )
    {
        extract( $data );

        /**
         * try to find the provider
         * or return an error
         */
        $provider = $this->providerService->get( $data[ 'general' ][ 'provider_id' ] );

        if ( ! $provider instanceof Provider ) {
            throw new Exception( __( 'Unable to find the assigned provider.' ) );
        }

        /**
         * We'll create a new instance
         * of the procurement
         *
         * @param Procurement
         */
        $procurement = new Procurement;

        /**
         * we'll make sure to trigger some event before
         * performing some change on the procurement
         */
        event( new ProcurementBeforeCreateEvent( $procurement, $data ) );

        /**
         * We don't want the event ProcurementBeforeCreateEvent
         * and ProcurementAfterCreateEvent to trigger while saving
         */
        Procurement::withoutEvents( function () use ( $procurement, $data ) {
            $procurement->name = $data[ 'name' ] ?: $this->procurementName();

            foreach ( $data[ 'general' ] as $field => $value ) {
                $procurement->$field = $value;
            }

            if ( ! empty( $procurement->created_at ) || ! empty( $procurement->updated_at ) ) {
                $procurement->timestamps = false;
            }

            $procurement->author = Auth::id();
            $procurement->cost = 0;
            $procurement->save();
        } );

        /**
         * Let's save the product that are procured
         * This doesn't affect the stock but only store the product
         */
        if ( $data[ 'products' ] ) {
            $this->saveProducts( $procurement, collect( $data[ 'products' ] ) );
        }

        /**
         * We can now safely trigger the event here
         * that will ensure correct computing
         */
        event( new ProcurementAfterCreateEvent( $procurement ) );

        return [
            'status' => 'success',
            'message' => __( 'The procurement has been created.' ),
            'data' => [
                'products' => $procurement->products,
                'procurement' => $procurement,
            ],
        ];
    }

    /**
     * Editing a specific procurement using the provided informations
     *
     * @param int procurement id
     * @param array data to update
     * @return array
     */
    public function edit( $id, $data )
    {
        /**
         * @param array  $general
         * @param string $name
         * @param array  $products
         */
        extract( $data );

        /**
         * try to find the provider
         * or return an error
         */
        $provider = $this->providerService->get( $data[ 'general' ][ 'provider_id' ] );

        if ( ! $provider instanceof Provider ) {
            throw new Exception( __( 'Unable to find the assigned provider.' ) );
        }

        $procurement = Procurement::findOrFail( $id );

        /**
         * we'll make sure to trigger some event before
         * performing some change on the procurement
         */
        event( new ProcurementBeforeUpdateEvent( $procurement ) );

        /**
         * We won't dispatch the even while savin the procurement
         * however we'll do that once the product has been stored.
         */
        Procurement::withoutEvents( function () use ( $data, $procurement ) {
            if ( $procurement->delivery_status === 'stocked' ) {
                throw new Exception( __( 'Unable to edit a procurement that has already been stocked. Please consider performing and stock adjustment.' ) );
            }

            $procurement->name = $data[ 'name' ];

            foreach ( $data[ 'general' ] as $field => $value ) {
                $procurement->$field = $value;
            }

            if ( ! empty( $procurement->created_at ) || ! empty( $procurement->updated_at ) ) {
                $procurement->timestamps = false;
            }

            $procurement->author = Auth::id();
            $procurement->cost = 0;
            $procurement->save();
        } );

        /**
         * We can now safely save
         * the procurement products
         */
        if ( $data[ 'products' ] ) {
            $this->saveProducts( $procurement, collect( $data[ 'products' ] ) );
        }

        /**
         * we want to dispatch the event
         * only when the product has been created
         */
        event( new ProcurementAfterUpdateEvent( $procurement ) );

        return [
            'status' => 'success',
            'message' => __( 'The provider has been edited.' ),
            'data' => compact( 'procurement' ),
        ];
    }

    /**
     * delete a specific procurement
     * using the provided id
     *
     * @param int procurement id
     * @return void
     */
    public function delete( $id )
    {
        $procurement = Procurement::find( $id );

        if ( ! $procurement instanceof Procurement ) {
            throw new Exception( 'Unable to find the requested procurement using the provided id.' );
        }

        $procurement->delete();

        return [
            'status' => 'success',
            'message' => __( 'The procurement has been deleted.' ),
        ];
    }

    /**
     * Attempt a product stock removal
     * if the procurement has been stocked
     *
     * @throws NotAllowedException
     */
    public function attemptProductsStockRemoval( Procurement $procurement ): void
    {
        if ( $procurement->delivery_status === 'stocked' ) {
            $procurement->products->each( function ( ProcurementProduct $procurementProduct ) {
                /**
                 * We'll handle products that was converted a bit
                 * differently to ensure converted product inventory is taken in account.
                 */
                if ( empty( $procurementProduct->convert_unit_id ) ) {
                    $unitQuantity = ProductUnitQuantity::withProduct( $procurementProduct->product_id )
                        ->withUnit( $procurementProduct->unit_id )
                        ->first();

                    $quantity = $procurementProduct->quantity;
                    $unitName = $procurementProduct->unit->name;
                } else {
                    $fromUnit = $procurementProduct->unit;
                    $toUnit = Unit::find( $procurementProduct->convert_unit_id );

                    $quantity = $this->unitService->getConvertedQuantity(
                        from: $fromUnit,
                        to: $toUnit,
                        quantity: $procurementProduct->quantity
                    );

                    $unitName = $toUnit->name;
                    $unitQuantity = ProductUnitQuantity::withProduct( $procurementProduct->product_id )
                        ->withUnit( $toUnit->id )
                        ->first();
                }

                if ( $unitQuantity instanceof ProductUnitQuantity ) {
                    if ( floatval( $unitQuantity->quantity ) - floatval( $quantity ) < 0 ) {
                        throw new NotAllowedException(
                            sprintf(
                                __( 'Unable to delete the procurement as there is not enough stock remaining for "%s" on unit "%s". This likely means the stock count has changed either with a sale, adjustment after the procurement has been stocked.' ),
                                $procurementProduct->product->name,
                                $unitName
                            )
                        );
                    }
                }
            } );
        }
    }

    /**
     * This will delete product available on a procurement
     * and dispatch some events before and after that occurs.
     */
    public function deleteProcurementProducts( Procurement $procurement ): void
    {
        $procurement->products->each( function ( ProcurementProduct $product ) use ( $procurement ) {
            $this->deleteProduct( $product, $procurement );
        } );
    }

    /**
     * This helps to compute the unit value and the total cost
     * of a procurement product. It return various value as an array of
     * the product updated along with an array of errors.
     */
    private function __computeProcurementProductValues( array $data )
    {
        /**
         * @var ProcurementProduct $procurementProduct
         * @var $storeUnitReference
         * @var Procurement $procurement
         * @var $itemsToSave
         * @var $item
         */
        extract( $data, EXTR_REFS );

        if ( $item->purchase_unit_type === 'unit' ) {
            extract( $this->__procureForSingleUnit( compact( 'procurementProduct', 'storedUnitReference', 'itemsToSave', 'item' ) ) );
        } elseif ( $item->purchase_unit_type === 'unit-group' ) {
            if ( ! isset( $procurementProduct->unit_id ) ) {
                /**
                 * this is made to ensure
                 * we have a self explanatory error,
                 * that describe why a product couldn't be processed
                 */
                $keys = array_keys( (array) $procurementProduct );

                foreach ( $keys as $key ) {
                    if ( in_array( $key, [ 'id', 'sku', 'barcode' ] ) ) {
                        $argument = $key;
                        $identifier = $procurementProduct->$key;
                        break;
                    }
                }

                $errors[] = [
                    'status' => 'error',
                    'message' => sprintf( __( 'Unable to have a unit group id for the product using the reference "%s" as "%s"' ), $identifier, $argument ),
                ];
            }

            try {
                extract( $this->__procureForUnitGroup( compact( 'procurementProduct', 'storedunitReference', 'itemsToSave', 'item' ) ) );
            } catch ( Exception $exception ) {
                $errors[] = [
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'data' => [
                        'product' => collect( $item )->only( [ 'id', 'name', 'sku', 'barcode' ] ),
                    ],
                ];
            }
        }

        return $data;
    }

    /**
     * This only save the product
     * but doesn't affect the stock.
     */
    public function saveProducts( Procurement $procurement, Collection $products )
    {
        /**
         * We'll just make sure to have a reference
         * of all the product that has been procured.
         */
        $procuredProducts = $products->map( function ( $procuredProduct ) use ( $procurement ) {
            $product = Product::find( $procuredProduct[ 'product_id' ] );

            if ( ! $product instanceof Product ) {
                throw new Exception( sprintf( __( 'Unable to find the product using the provided id "%s"' ), $procuredProduct[ 'product_id' ] ) );
            }

            if ( $product->stock_management === 'disabled' ) {
                throw new Exception( sprintf( __( 'Unable to procure the product "%s" as the stock management is disabled.' ), $product->name ) );
            }

            if ( $product->product_type === 'grouped' ) {
                throw new Exception( sprintf( __( 'Unable to procure the product "%s" as it is a grouped product.' ), $product->name ) );
            }

            /**
             * as the id might not always be provided
             * We'll find some record having an id set to 0
             * as not result will pop, that will create a new instance.
             */
            $procurementProduct = ProcurementProduct::find( $procuredProduct[ 'id' ] ?? 0 );

            if ( ! $procurementProduct instanceof ProcurementProduct ) {
                $procurementProduct = new ProcurementProduct;
            }

            /**
             * @todo these value might also
             * be calculated automatically.
             */
            $procurementProduct->name = $product->name;
            $procurementProduct->gross_purchase_price = $procuredProduct[ 'gross_purchase_price' ];
            $procurementProduct->net_purchase_price = $procuredProduct[ 'net_purchase_price' ];
            $procurementProduct->procurement_id = $procurement->id;
            $procurementProduct->product_id = $procuredProduct[ 'product_id' ];
            $procurementProduct->purchase_price = $procuredProduct[ 'purchase_price' ];
            $procurementProduct->quantity = $procuredProduct[ 'quantity' ];
            $procurementProduct->available_quantity = $procuredProduct[ 'quantity' ];
            $procurementProduct->tax_group_id = $procuredProduct[ 'tax_group_id' ] ?? 0;
            $procurementProduct->tax_type = $procuredProduct[ 'tax_type' ];
            $procurementProduct->tax_value = $procuredProduct[ 'tax_value' ];
            $procurementProduct->expiration_date = $procuredProduct[ 'expiration_date' ] ?? null;
            $procurementProduct->total_purchase_price = $procuredProduct[ 'total_purchase_price' ];
            $procurementProduct->convert_unit_id = $procuredProduct[ 'convert_unit_id' ] ?? null;
            $procurementProduct->unit_id = $procuredProduct[ 'unit_id' ];
            $procurementProduct->author = Auth::id();
            $procurementProduct->save();
            $procurementProduct->barcode = str_pad( $product->barcode, 5, '0', STR_PAD_LEFT ) . '-' . str_pad( $procurementProduct->unit_id, 3, '0', STR_PAD_LEFT ) . '-' . str_pad( $procurementProduct->id, 3, '0', STR_PAD_LEFT );
            $procurementProduct->save();

            event( new ProcurementAfterSaveProductEvent( $procurement, $procurementProduct, $procuredProduct ) );

            return $procurementProduct;
        } );

        return $procuredProducts;
    }

    /**
     * prepare the procurement entry.
     */
    private function __procureForUnitGroup( array $data )
    {
        /**
         * @var $storeUnitReference
         * @var ProcurementProduct $procurementProduct
         * @var $storedBase
         * @var $item
         */
        extract( $data );

        if ( empty( $stored = @$storedUnitReference[ $procurementProduct->unit_id ] ) ) {
            $unit = $this->unitService->get( $procurementProduct->unit_id );
            $group = $this->unitService->getGroups( $item->purchase_unit_id ); // which should retrieve the group
            $base = $unit->base_unit ? $unit : $this->unitService->getBaseUnit( $group );
            $base_quantity = $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
            $storedBase[ $procurementProduct->unit_id ] = compact( 'base', 'unit', 'group' );
        } else {
            extract( $stored );
            $base_quantity = $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
        }

        /**
         * let's check if the unit assigned
         * during the purchase is a sub unit of the
         * unit assigned to the item.
         */
        if ( $group->id !== $item->purchase_unit_id ) {
            throw new Exception( sprintf( __( 'The unit used for the product %s doesn\'t belongs to the Unit Group assigned to the item' ), $item->name ) );
        }

        $itemData = [
            'product_id' => $item->id,
            'unit_id' => $procurementProduct->unit_id,
            'base_quantity' => $base_quantity,
            'quantity' => $procurementProduct->quantity,
            'purchase_price' => $this->currency->value( $procurementProduct->purchase_price )->get(),
            'total_purchase_price' => $this->currency->value( $procurementProduct->purchase_price )->multiplyBy( $procurementProduct->quantity )->get(),
            'author' => Auth::id(),
            'name' => $item->name,
        ];

        $itemsToSave[] = $itemData;

        return compact( 'itemsToSave', 'storedUnitReference' );
    }

    private function __procureForSingleUnit( $data )
    {
        extract( $data );

        /**
         * if the purchase unit id hasn't already been
         * recorded, then let's save it
         */
        if ( empty( $stored = @$storedUnitReference[ $item->purchase_unit_id ] ) ) {
            $unit = $this->unitService->get( $item->purchase_unit_id );
            $group = $unit->group;
            $base = $unit->base_unit ? $unit : $this->unitService->getBaseUnit( $group );
            $base_quantity = $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
            $storedUnitReference[ $item->purchase_unit_id ] = compact( 'base', 'unit' );
        } else {
            extract( $stored );
            $base_quantity = $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
        }

        $itemData = [
            'product_id' => $item->id,
            'unit_id' => $item->purchase_unit_id,
            'base_quantity' => $base_quantity,
            'quantity' => $procurementProduct->quantity,
            'purchase_price' => $this->currency->value( $procurementProduct->purchase_price )->get(),
            'total_price' => $this->currency->value( $procurementProduct->purchase_price )->multiplyBy( $procurementProduct->quantity )->get(),
            'author' => Auth::id(),
            'name' => $item->name,
        ];

        $itemsToSave[] = $itemData;

        return compact( 'itemsToSave', 'storedUnitReference' );
    }

    /**
     * save a defined procurement products
     *
     * @param int procurement id
     * @param array items
     * @return array;
     */
    public function saveProcurementProducts( $procurement_id, $items )
    {
        $procuredItems = [];

        foreach ( $items as $item ) {
            $product = new ProcurementProduct;

            foreach ( $item as $field => $value ) {
                $product->$field = $value;
            }

            $product->author = Auth::id();
            $product->procurement_id = $procurement_id;
            $product->save();

            $procuredItems[] = $product->toArray();
        }

        return [
            'status' => 'success',
            'message' => __( 'The operation has completed.' ),
            'data' => [
                'success' => $procuredItems,
            ],
        ];
    }

    /**
     * refresh a procurement
     * by counting the total items & value
     *
     * @param  Procurement $provided procurement
     * @return array
     */
    public function refresh( Procurement $procurement )
    {
        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        Procurement::withoutEvents( function () use ( $procurement, $productService ) {
            /**
             * Let's loop all procured produt
             * and get unit quantity if that exists
             * otherwise we'll create a new one.
             */
            $purchases = $procurement
                ->products()
                ->get()
                ->map( function ( $procurementProduct ) use ( $productService ) {
                    $unitPrice = 0;
                    $unit = $productService->getUnitQuantity( $procurementProduct->product_id, $procurementProduct->unit_id );

                    if ( $unit instanceof ProductUnitQuantity ) {
                        $unitPrice = $unit->sale_price * $procurementProduct->quantity;
                    }

                    /**
                     * We'll return the total purchase
                     * price to update the procurement total fees.
                     */
                    return [
                        'total_purchase_price' => $procurementProduct->total_purchase_price,
                        'tax_value' => $procurementProduct->tax_value,
                        'total_price' => $unitPrice,
                    ];
                } );

            $procurement->cost = $purchases->sum( 'total_purchase_price' );
            $procurement->tax_value = $purchases->sum( 'tax_value' );
            $procurement->value = $purchases->sum( 'total_price' );
            $procurement->total_items = count( $purchases );
            $procurement->save();
        } );

        return [
            'status' => 'success',
            'message' => __( 'The procurement has been refreshed.' ),
            'data' => compact( 'procurement' ),
        ];
    }

    /**
     * delete procurement
     * products
     *
     * @param Procurement
     * @return array
     */
    public function deleteProducts( Procurement $procurement )
    {
        $procurement->products->each( function ( $product ) {
            $product->delete();
        } );

        return [
            'status' => 'success',
            'message' => __( 'The procurement products has been deleted.' ),
        ];
    }

    /**
     * helps to determine if a procurement
     * includes a specific product using their id.
     * The ID of the product should be the one of the products of the procurements
     *
     * @param int procurement id
     * @param int product id
     */
    public function hasProduct( int $procurement_id, int $product_id )
    {
        $procurement = $this->get( $procurement_id );

        return $procurement->products->filter( function ( $product ) use ( $product_id ) {
            return (int) $product->id === (int) $product_id;
        } )->count() > 0;
    }

    /**
     * @deprecated
     */
    public function updateProcurementProduct( $product_id, $fields )
    {
        $procurementProduct = $this->getProcurementProduct( $product_id );
        $item = $this->productService->get( $procurementProduct->product_id );
        $storedUnitReference = [];
        $itemsToSave = [];

        /**
         * the idea here it to update the procurement
         * quantity, unit_id and purchase price, since that information
         * is used on __computeProcurementProductValues
         */
        foreach ( $fields as $field => $value ) {
            $procurementProduct->$field = $value;
        }

        /**
         * @var array $itemsToSave
         * @var array errors
         */
        extract( $this->__computeProcurementProductValues( compact( 'item', 'procurementProduct', 'storeUnitReference', 'itemsToSave', 'errors' ) ) );

        /**
         * typically since the items to save should be
         * only a single entry, we'll harcode it to be "0"
         */
        foreach ( $itemsToSave[0] as $field => $value ) {
            $procurementProduct->$field = $value;
        }

        $procurementProduct->author = Auth::id();
        $procurementProduct->save();

        return [
            'status' => 'success',
            'message' => __( 'The procurement product has been updated.' ),
            'data' => [
                'product' => $procurementProduct,
            ],
        ];
    }

    public function getProcurementProduct( $product_id )
    {
        $product = ProcurementProduct::find( $product_id );

        if ( ! $product instanceof ProcurementProduct ) {
            throw new Exception( __( 'Unable to find the procurement product using the provided id.' ) );
        }

        return $product;
    }

    /**
     * Delete a procurement product
     *
     * @param int procurement product id
     * @return array response
     */
    public function deleteProduct( ProcurementProduct $procurementProduct, Procurement $procurement )
    {
        /**
         * this could be useful to prevent deletion for
         * product which might be in use by another resource
         */
        event( new ProcurementBeforeDeleteProductEvent( $procurementProduct ) );

        /**
         * we'll reduce the stock only if the
         * procurement has been stocked.
         */
        if ( $procurement->delivery_status === 'stocked' ) {
            /**
             * if the product was'nt convered into a different unit
             * then we'll directly perform a stock adjustment on that product.
             */
            if ( ! empty( $procurementProduct->convert_unit_id ) ) {
                $from = Unit::find( $procurementProduct->unit_id );
                $to = Unit::find( $procurementProduct->convert_unit_id );
                $convertedQuantityToRemove = $this->unitService->getConvertedQuantity(
                    from: $from,
                    to: $to,
                    quantity: $procurementProduct->quantity
                );

                $purchasePrice = $this->unitService->getPurchasePriceFromUnit(
                    purchasePrice: $procurementProduct->purchase_price,
                    from: $from,
                    to: $to
                );

                $this->productService->stockAdjustment( ProductHistory::ACTION_DELETED, [
                    'total_price' => ns()->currency->define( $purchasePrice )->multipliedBy( $convertedQuantityToRemove )->toFloat(),
                    'unit_price' => $purchasePrice,
                    'unit_id' => $procurementProduct->convert_unit_id,
                    'product_id' => $procurementProduct->product_id,
                    'quantity' => $convertedQuantityToRemove,
                    'procurementProduct' => $procurementProduct,
                ] );
            } else {
                /**
                 * Record the deletion on the product
                 * history
                 */
                $this->productService->stockAdjustment( ProductHistory::ACTION_DELETED, [
                    'total_price' => $procurementProduct->total_purchase_price,
                    'unit_price' => $procurementProduct->purchase_price,
                    'unit_id' => $procurementProduct->unit_id,
                    'product_id' => $procurementProduct->product_id,
                    'quantity' => $procurementProduct->quantity,
                    'procurementProduct' => $procurementProduct,
                ] );
            }
        }

        $procurementProduct->delete();

        /**
         * the product has been deleted, so we couldn't pass
         * the Model Object anymore
         */
        event( new ProcurementAfterDeleteProductEvent( $procurementProduct->id, $procurement ) );

        return [
            'status' => 'sucecss',
            'message' => sprintf(
                __( 'The product %s has been deleted from the procurement %s' ),
                $procurementProduct->name,
                $procurement->name,
            ),
        ];
    }

    public function getProcurementProducts( $procurement_id )
    {
        return ProcurementProduct::getByProcurement( $procurement_id )
            ->get();
    }

    /**
     * Update a procurement products
     * using the provided product collection
     *
     * @param int procurement id
     * @param array array
     * @return array status
     *
     * @deprecated
     */
    public function bulkUpdateProducts( $procurement_id, $products )
    {
        $productsId = $this->getProcurementProducts( $procurement_id )
            ->pluck( 'id' );

        $result = collect( $products )
            ->map( function ( $product ) use ( $productsId ) {
                if ( ! in_array( $product[ 'id' ], $productsId ) ) {
                    throw new Exception( sprintf( __( 'The product with the following ID "%s" is not initially included on the procurement' ), $product[ 'id' ] ) );
                }

                return $product;
            } )
            ->map( function ( $product ) {
                return $this->updateProcurementProduct( $product[ 'id' ], $product );
            } );

        return [
            'status' => 'success',
            'message' => __( 'The procurement products has been updated.' ),
            'data' => compact( 'result' ),
        ];
    }

    /**
     * Get the procurements product
     *
     * @param int procurement id
     */
    public function getProducts( $procurement_id ): EloquentCollection
    {
        $procurement = $this->get( $procurement_id );

        return $procurement->products;
    }

    public function setDeliveryStatus( Procurement $procurement, string $status )
    {
        Procurement::withoutEvents( function () use ( $procurement, $status ) {
            $procurement->delivery_status = $status;
            $procurement->save();
        } );
    }

    /**
     * When a procurement is being made
     * this will actually save the history and update
     * the product stock
     *
     * @return void
     */
    public function handleProcurement( Procurement $procurement )
    {
        event( new ProcurementBeforeHandledEvent( $procurement ) );

        if ( $procurement->delivery_status === Procurement::DELIVERED ) {
            $procurement->products->map( function ( ProcurementProduct $product ) {
                /**
                 * We'll keep an history of what has just happened.
                 * in order to monitor how the stock evolve.
                 */
                $this->productService->saveHistory( ProductHistory::ACTION_STOCKED, [
                    'procurement_id' => $product->procurement_id,
                    'product_id' => $product->product_id,
                    'procurement_product_id' => $product->id,
                    'operation_type' => ProductHistory::ACTION_STOCKED,
                    'quantity' => $product->quantity,
                    'unit_price' => $product->purchase_price,
                    'total_price' => $product->total_purchase_price,
                    'unit_id' => $product->unit_id,
                ] );

                $currentQuantity = $this->productService->getQuantity(
                    $product->product_id,
                    $product->unit_id,
                    $product->id
                );

                $newQuantity = $this->currency
                    ->define( $currentQuantity )
                    ->additionateBy( $product->quantity )
                    ->get();

                $this->productService->setQuantity( $product->product_id, $product->unit_id, $newQuantity, $product->id );

                /**
                 * will generate a unique barcode for the procured product
                 */
                $this->generateBarcode( $product );

                /**
                 * We'll now check if the product is about to be
                 * converted in another unit
                 */
                if ( ! empty( $product->convert_unit_id ) ) {
                    $this->productService->convertUnitQuantities(
                        product: $product->product,
                        quantity: $product->quantity,
                        from: $product->unit,
                        procurementProduct: $product,
                        to: Unit::find( $product->convert_unit_id )
                    );
                }
            } );

            $this->setDeliveryStatus( $procurement, Procurement::STOCKED );
        }

        event( new ProcurementAfterHandledEvent( $procurement ) );
    }

    public function generateBarcode( ProcurementProduct $procurementProduct )
    {
        $this->barcodeService->generateBarcode(
            $procurementProduct->barcode,
            BarcodeService::TYPE_CODE128
        );
    }

    /**
     * Make sure to procure procurement that
     * are awaiting auto-submittion
     *
     * @return void
     */
    public function stockAwaitingProcurements()
    {
        $startOfDay = $this->dateService->copy();
        $procurements = Procurement::where( 'delivery_time', '<=', $startOfDay )
            ->pending()
            ->autoApproval()
            ->get();

        $procurements->each( function ( Procurement $procurement ) {
            $this->setDeliveryStatus( $procurement, Procurement::DELIVERED );
            $this->handleProcurement( $procurement );
        } );

        if ( $procurements->count() ) {
            ns()->notification->create( [
                'title' => __( 'Procurement Automatically Stocked' ),
                'identifier' => 'ns-warn-auto-procurement',
                'url' => url( '/dashboard/procurements' ),
                'description' => sprintf( __( '%s procurement(s) has recently been automatically procured.' ), $procurements->count() ),
            ] )->dispatchForGroup( [
                Role::namespace( 'admin' ),
                Role::namespace( 'nexopos.store.administrator' ),
            ] );
        }
    }

    public function getDeliveryLabel( $label )
    {
        switch ( $label ) {
            case Procurement::DELIVERED:
                return __( 'Delivered' );
            case Procurement::DRAFT:
                return __( 'Draft' );
            case Procurement::PENDING:
                return __( 'Pending' );
            case Procurement::STOCKED:
                return __( 'Stocked' );
            default:
                return $label;
        }
    }

    public function getPaymentLabel( $label )
    {
        switch ( $label ) {
            case Procurement::PAYMENT_PAID:
                return __( 'Paid' );
            case Procurement::PAYMENT_UNPAID:
                return __( 'Unpaid' );
            default:
                return $label;
        }
    }

    public function searchProduct( $argument, $limit = 10 )
    {
        return Product::query()
            ->whereIn( 'type', [
                Product::TYPE_DEMATERIALIZED,
                Product::TYPE_MATERIALIZED,
            ] )
            ->notGrouped()
            ->where( function ( $query ) use ( $argument ) {
                $query->orWhere( 'name', 'LIKE', "%{$argument}%" )
                    ->orWhere( 'sku', 'LIKE', "%{$argument}%" )
                    ->orWhere( 'barcode', 'LIKE', "%{$argument}%" );
            } )
            ->withStockEnabled()
            ->with( 'unit_quantities.unit' )
            ->limit( $limit )
            ->get()
            ->map( function ( $product ) {
                $units = json_decode( $product->purchase_unit_ids );

                if ( $units ) {
                    $product->purchase_units = collect();
                    collect( $units )->each( function ( $unitID ) use ( &$product ) {
                        $product->purchase_units->push( Unit::find( $unitID ) );
                    } );
                }

                /**
                 * We'll pull the last purchase
                 * price for the item retreived
                 */
                $product->unit_quantities->each( function ( $unitQuantity ) {
                    $lastPurchase = ProcurementProduct::where( 'product_id', $unitQuantity->product_id )
                        ->where( 'unit_id', $unitQuantity->unit_id )
                        ->orderBy( 'updated_at', 'desc' )
                        ->first();

                    /**
                     * just in case it's not a valid instance
                     * we'll provide a default value "0"
                     */
                    $unitQuantity->last_purchase_price = 0;

                    if ( $lastPurchase instanceof ProcurementProduct ) {
                        $unitQuantity->last_purchase_price = $lastPurchase->purchase_price;
                    }
                } );

                return $product;
            } );
    }

    public function searchProcurementProduct( $argument )
    {
        $procurementProduct = ProcurementProduct::where( 'barcode', $argument )
            ->with( [ 'unit', 'procurement' ] )
            ->first();

        if ( $procurementProduct instanceof ProcurementProduct ) {
            $procurementProduct->unit_quantity = $this->productService->getUnitQuantity(
                $procurementProduct->product_id,
                $procurementProduct->unit_id
            );
        }

        return $procurementProduct;
    }

    public function handlePaymentStatusChanging( Procurement $procurement, string $previous, string $new )
    {
        // if ( $previous === Procurement::PAYMENT_UNPAID && $new === Procurement::PAYMENT_PAID ) {
        //     $this->transn
        // }
    }
}
