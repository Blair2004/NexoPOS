<?php
namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Provider;
use App\Models\Procurement;
use App\Services\ProductService;
use App\Exceptions\NotFoundException;
use App\Models\ProcurementProduct;
use App\Exceptions\NotAllowedException;
use App\Events\ProcurementAfterDelete;
use App\Events\ProcurementBeforeDelete;
use App\Events\ProcurementDeliveryEvent;
use App\Events\ProcurementDeliveredEvent;
use App\Events\ProcurementCancelationEvent;
use App\Events\ProcurementProductSavedEvent;
use App\Events\ProcurementAfterDeleteProduct;
use App\Events\ProcurementAfterUpdateProduct;
use App\Events\ProcurementBeforeDeleteProduct;
use App\Events\ProcurementBeforeUpdateProduct;
use App\Events\ProcurementRefreshedEvent;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\Unit;
use Exception;

class ProcurementService
{
    protected $providerService;
    protected $unitService;
    protected $productService;
    protected $currency;

    public function __construct( 
        ProviderService $providerService,
        UnitService $unitService,
        ProductService $productService,
        CurrencyService $currency
    )
    {
        $this->providerService      =   $providerService;
        $this->unitService          =   $unitService;
        $this->productService       =   $productService;
        $this->currency             =   $currency;
    }

    /**
     * get a single procurement
     * or retreive a list of procurement
     * @param int procurement id
     * @return Collection|Procurement
     */
    public function get( $id = null )
    {
        if ( $id !== null ) {
            $provider   =   Procurement::find( $id ); 
            if ( ! $provider instanceof Procurement ) {
                throw new NotFoundException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'Unable to find the requested procurement using the provided identifier.' )
                ]);
            }
            return $provider;
        }

        return Procurement::get();
    }

    /**
     * create a procurement 
     * using the provided informations
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
        $provider           =   $this->providerService->get( $data[ 'general' ][ 'provider_id' ] );

        if ( ! $provider instanceof Provider ) {
            throw new Exception( __( 'Unable to find the assigned provider.' ) );
        }

        /**
         * @todo check if all products exists
         */

        $procurement                    =   new Procurement;
        $procurement->name              =   $data[ 'name' ];

        foreach( $data[ 'general' ] as $field => $value ) {
            $procurement->$field        =   $value;
        }

        $procurement->author            =   Auth::id();
        $procurement->value             =   0;
        $procurement->save();

        if ( $data[ 'products' ] ) {
            $result     =   $this->procure( $procurement, collect( $data[ 'products' ] ) );
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The provider has been created.' ),
            'data'      =>  compact( 'procurement', 'result' )
        ];
    }

    /**
     * Editing a specific procurement using the provided informations
     * @param int procurement id
     * @param array data to update
     * @return array
     */
    public function edit( $id, $data )
    {
        extract( $data );
        /**
         * -> provider_id
         */

        $procurement        =   Procurement::findOrFail( $id );
        $provider           =   $this->providerService->get( $provider_id );

        foreach( $data as $field => $value ) {
            $procurement->$field        =   $value;
        }

        $procurement->author            =   Auth::id();
        $procurement->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The provider has been edited.' ),
            'data'      =>  compact( 'procurement' )
        ];
    }

    /**
     * delete a specific procurement
     * using the provided id
     * @param int procurement id
     * @return void
     */
    public function delete( $id )
    {
        $procurement    =   Procurement::find( $id );

        if ( ! $procurement instanceof Procurement ) {
            throw new Exception( 'Unable to find the requested procurement using the provided id.' );
        }

        event( new ProcurementBeforeDelete( $procurement ) );

        $totalProducts      =   $procurement->products->count();
        $procurement->products->each( function( ProcurementProduct $product ) {
            $this->deleteProduct( $product );
        });

        $procurement->delete();

        event( new ProcurementAfterDelete( $procurement ) );

        $procurement->delete();

        return [
            'status'    =>  'success',
            'message'   =>  sprintf( __( 'The procurement has been deleted. %s included stock record(s) has been deleted as well.' ), $totalProducts )
        ];
    }

    /**
     * @todo needs review
     */
    public function computeValue( $id )
    {
        $procurement    =   Procurement::findOrFail( $id );
        $value          =   $procurement->products->map( function( $product ) {
            return $product->price * $product->quantity;
        });
    }

    /**
     * This helps to compute the unit value and the total cost 
     * of a procurement product. It return various value as an array of
     * the product updated as long as an array of errors
     * @param array [ procurementProduct, storedUnitReference, procurement, itemsToSave, item ]
     * @return array<$itemsToSave,$errors>
     */
    private function __computeProcurementProductValues( $data )
    {
        extract( $data, EXTR_REFS );

        if ( $item->purchase_unit_type === 'unit' ) {
            extract( $this->__procureForSingleUnit( compact( 'procurementProduct', 'storedUnitReference', 'itemsToSave', 'item' ) ) );
        } else if ( $item->purchase_unit_type === 'unit-group' ) {

            if ( ! isset( $procurementProduct->unit_id ) ) {

                /**
                 * this is made to ensure
                 * we have a self explanatory error, 
                 * that describe why a product couldn't be processed
                 */
                $keys   =   array_keys( ( array ) $procurementProduct );

                foreach( $keys as $key ) {
                    if ( in_array( $key, [ 'id', 'sku', 'barcode' ] ) ) {
                        $argument       =   $key;
                        $identifier     =   $procurementProduct->$key;
                        break;
                    }
                }

                $errors[]       =   [
                    'status'        =>  'failed',
                    'message'       =>  sprintf( __( 'Unable to have a unit group id for the product using the reference "%s" as "%s"' ), $identifier, $argument )
                ];
            }

            try {
                extract( $this->__procureForUnitGroup( compact( 'procurementProduct', 'storedunitReference', 'itemsToSave', 'item' ) ) );
            } catch( Exception $exception ) {
                $errors[]   =   [
                    'status'    =>  'failed',
                    'message'   =>  $exception->getMessage(),
                    'data'      =>  [
                        'product'       =>  collect( $item )->only([ 'id', 'name', 'sku', 'barcode' ])
                    ]
                ];
            }
        }

        return $data;
    }

    public function procure( Procurement $procurement, Collection $products )
    {  
        /**
         * We'll just make sure to have a reference
         * of all the product that has been procured.
         */
        $procuredProducts                                   =   $products->map( function( $procuredProduct ) use ( $procurement ) {
            $product                                        =   Product::find( $procuredProduct[ 'product_id' ] );
            $procurementProduct                             =   new ProcurementProduct;
            $procurementProduct->name                       =   $product->name;
            $procurementProduct->gross_purchase_price       =   $procuredProduct[ 'gross_purchase_price' ];
            $procurementProduct->net_purchase_price         =   $procuredProduct[ 'net_purchase_price' ];
            $procurementProduct->procurement_id             =   $procurement->id;
            $procurementProduct->product_id                 =   $procuredProduct[ 'product_id' ];
            $procurementProduct->purchase_price             =   $procuredProduct[ 'purchase_price' ];
            $procurementProduct->quantity                   =   $procuredProduct[ 'quantity' ];
            $procurementProduct->tax_group_id               =   $procuredProduct[ 'tax_group_id' ];
            $procurementProduct->tax_type                   =   $procuredProduct[ 'tax_type' ];
            $procurementProduct->tax_value                  =   $procuredProduct[ 'tax_value' ];
            $procurementProduct->total_purchase_price       =   $procuredProduct[ 'total_purchase_price' ];
            $procurementProduct->unit_id                    =   $procuredProduct[ 'unit_id' ];
            $procurementProduct->author                     =   Auth::id();
            $procurementProduct->save();

            return $procurementProduct;
        });

        /**
         * trigger a specific event
         * to let other perform some action
         */
        event( new ProcurementDeliveryEvent( $procurement ) );

        return $procuredProducts;
    }

    /**
     * prepare the procurement entry
     * @param array entry to record
     * @return array
     */
    private function __procureForUnitGroup( $data )
    {
        extract( $data );
        
        if ( empty( $stored = @$storedUnitReference[ $procurementProduct->unit_id ] ) ) {
            $unit           =   $this->unitService->get( $procurementProduct->unit_id );
            $group          =   $this->unitService->getGroups( $item->purchase_unit_id ); // which should retreive the group
            $base           =   $unit->base_unit ? $unit : $this->unitService->getBaseUnit( $group );
            $base_quantity  =   $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
            $storedBase[ $procurementProduct->unit_id ]   =   compact( 'base', 'unit', 'group' );
        } else {
            extract( $stored );
            $base_quantity  =   $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
        }

        /**
         * let's check if the unit assigned
         * during the purchase is a sub unit of the
         * unit assigned to the item.
         */
        if ( $group->id !== $item->purchase_unit_id ) {
            throw new Exception( sprintf( __( 'The unit used for the product %s doesn\'t belongs to the Unit Group assigned to the item' ), $item->name ) );
        }

        $itemData       =   [
            'product_id'                =>  $item->id,
            'unit_id'                   =>  $procurementProduct->unit_id,
            'base_quantity'             =>  $base_quantity,
            'quantity'                  =>  $procurementProduct->quantity,
            'purchase_price'            =>  $this->currency->value( $procurementProduct->purchase_price )->get(),
            'total_purchase_price'      =>  $this->currency->value( $procurementProduct->purchase_price )->multiplyBy( $procurementProduct->quantity )->get(),
            'author'                    =>  Auth::id(),
            'name'                      =>  $item->name
        ];

        $itemsToSave[]  =   $itemData;

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
            $unit           =   $this->unitService->get( $item->purchase_unit_id );
            $group          =   $unit->group;
            $base           =   $unit->base_unit ? $unit : $this->unitService->getBaseUnit( $group );
            $base_quantity  =   $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
            $storedUnitReference[ $item->purchase_unit_id ]   =   compact( 'base', 'unit' );
        } else {
            extract( $stored );
            $base_quantity  =   $this->unitService->computeBaseUnit( $unit, $base, $procurementProduct->quantity );
        }

        $itemData       =   [
            'product_id'        =>  $item->id,
            'unit_id'           =>  $item->purchase_unit_id,
            'base_quantity'     =>  $base_quantity,
            'quantity'          =>  $procurementProduct->quantity,
            'purchase_price'    =>  $this->currency->value( $procurementProduct->purchase_price )->get(),
            'total_price'       =>  $this->currency->value( $procurementProduct->purchase_price )->multiplyBy( $procurementProduct->quantity )->get(),
            'author'            =>  Auth::id(),
            'name'              =>  $item->name
        ];

        $itemsToSave[]  =   $itemData;

        return compact( 'itemsToSave', 'storedUnitReference' );
    }

    /**
     * save a defined procurement products
     * @param int procurement id
     * @param array items
     * @return array;
     */
    public function saveProcurementProducts( $procurement_id, $items )
    {
        $procuredItems  =   [];

        foreach( $items as $item ) {
            $product    =   new ProcurementProduct;
            
            foreach( $item as $field => $value ) {
                $product->$field    =   $value;
            }

            $product->author            =   Auth::id();
            $product->procurement_id    =   $procurement_id;
            $product->save();

            $procuredItems[]            =   $product->toArray();

            event( new ProcurementProductSavedEvent( $product ) );
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The operation has completed.' ),
            'data'      =>      [
                'success'     =>      $procuredItems
            ]
        ];
    }

    /**
     * refresh a procurement
     * by counting the total items & value
     * @param Procurement $provided procurement
     * @return array
     */
    public function refresh( Procurement $procurement )
    {
        /**
         * Let's loop all procured produt
         * and get unit quantity if that exists
         * otherwise we'll create a new one.
         */
        $purchases  =   $procurement
            ->products()
            ->get()
            ->map( function( $procurementProduct ) use ( $procurement ) {

            /**
             * If a previous unit stock doesn't exists
             * we'll need to create a new one.
             */
            $productUnitQuantity              =   ProductUnitQuantity::withProduct( $procurementProduct->product_id )
                ->withUnit( $procurementProduct->unit_id )
                ->first();

            if ( $productUnitQuantity instanceof ProductUnitQuantity ) {
                $productUnitQuantity  =   new ProductUnitQuantity();
                $productUnitQuantity->quantity  =   0;
            }

            /**
             * the stock get reflected on the system
             * only when the delivery status changes.
             * We'll update the status to "procured" to make
             * sure on update, that delivery doesn't reflect the stock twice.
             */
            if ( $procurement->delivery_status === 'delivered' ) {
                
                $totalQuantity      =   $productUnitQuantity->quantity + floatval( $procurementProduct->quantity );

                /**
                 * We'll keep an history of what has just happened.
                 * this will help to track how the stock evolve.
                 */
                $history                            =   new ProductHistory();
                $history->procurement_id            =   $procurementProduct->procurement_id;
                $history->product_id                =   $procurementProduct->product_id;
                $history->procurement_product_id    =   $procurementProduct->id;
                $history->operation_type            =   'procurement';
                $history->quantity                  =   $procurementProduct->quantity;
                $history->unit_price                =   $procurementProduct->purchase_price;
                $history->total_price               =   $procurementProduct->total_purchase_price;
                $history->before_quantity           =   $productUnitQuantity->quantity;
                $history->quantity                  =   $totalQuantity;
                $history->after_quantity            =   $procurementProduct->total_purchase_price;
                $history->unit_id                   =   $procurementProduct->unit_id;
                $history->save();

                /**
                 * let's update the product quantity with
                 * the unit attached to the object.
                 */
                $productUnitQuantity->quantity      =   $totalQuantity;
                $productUnitQuantity->save();
            }

            /**
             * We'll return the total purchase
             * price to update the procurement total fees.
             */
            return [
                'total_purchase_price'  =>  $procurementProduct->total_purchase_price,
                'tax_value'             =>  $procurementProduct->tax_value,
            ];
        });
        

        /**
         * the stock get reflected on the system
         * only when the delivery status changes.
         * We'll update the status to "procured" to make
         * sure on update, that delivery doesn't reflect the stock twice.
         */
        if ( $procurement->delivery_status === 'delivered' ) {
            /**
             * Let's make sure the delivery status change
             * to "stocked" to avoid twice procurement.
             */
            $procurement->delivery_status     =   'stocked';
        }
        
        $procurement->value               =   $purchases->sum( 'total_purchase_price' );
        $procurement->tax_value           =   $purchases->sum( 'tax_value' );
        $procurement->total_items         =   count( $purchases );
        $procurement->author              =   Auth::id();
        $procurement->save();

        event( new ProcurementRefreshedEvent( $procurement ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The procurement has been refreshed.' ),
            'data'      =>  compact( 'procurement' )
        ];
    }

    /**
     * delete all items recorded for a procurement
     * and reset all value including the computed owned money
     * @deprecated
     */
    public function resetProcurement( $id )
    {
        $procurement    =   Procurement::find( $id );

        $procurement->products->each( function( $product ) {
            $product->delete();
        });

        /**
         * trigger a specific event
         * to let other perform some action
         */
        event( new ProcurementCancelationEvent( $procurement ) );

        /**
         * @todo reset owned amount as well
         */
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The procurement has been resetted.' )
        ];
    }

    /**
     * delete procurement
     * products
     * @param Procurement 
     * @return array
     */
    public function deleteProducts( Procurement $procurement )
    {
        $procurement->products->each( function( $product ) {
            $product->delete();
        });

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The procurement products has been deleted.' )
        ];
    }

    /**
     * helps to determine if a procurement
     * includes a specific product using their id.
     * The ID of the product should be the one of the products of the procurements
     * @param int procurement id
     * @param int product id
     * 
     */
    public function hasProduct( int $procurement_id, int $product_id )
    {
        $procurement    =   $this->get( $procurement_id );

        return $procurement->products->filter( function( $product ) use ( $product_id ) {
            return ( int ) $product->id === ( int ) $product_id;
        })->count() > 0 ;
    }

    /**
     * @deprecated
     */
    public function updateProcurementProduct( $product_id, $fields )
    {
        $procurementProduct        =   $this->getProcurementProduct( $product_id );
        $item                   =   $this->productService->get( $procurementProduct->product_id );
        $storedUnitReference    =   [];
        $itemsToSave            =   [];

        event( new ProcurementBeforeUpdateProduct( $procurementProduct, $fields ) );

        /**
         * the idea here it to update the procurement
         * quantity, unit_id and purchase price, since that information
         * is used on __computeProcurementProductValues
         */
        foreach( $fields as $field => $value ) {
            $procurementProduct->$field    =   $value;
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
        foreach( $itemsToSave[0] as $field => $value ) {
            $procurementProduct->$field    =   $value;
        }

        $procurementProduct->author    =   Auth::id();
        $procurementProduct->save();

        event( new ProcurementAfterUpdateProduct( $procurementProduct, $fields ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The procurement product has been updated.' ),
            'data'      =>  [
                'product'   =>  $procurementProduct
            ]
        ];
    }

    public function getProcurementProduct( $product_id )
    {
        $product    =   ProcurementProduct::find( $product_id );
      
        if ( ! $product instanceof ProcurementProduct ) {
            throw new Exception( __( 'Unable to find the procurement product using the provided id.' ) );
        }

        return $product;
    }

    /**
     * Delete a procurement product
     * @param int procurement product id
     * @return array response
     */
    public function deleteProduct( $product_id )
    {
        /**
         * no we don't want to 
         * reload the product from the database again
         * That's where ORM could sucks.
         */
        $procurementProduct     =   $product_id instanceof ProcurementProduct ? $product_id : $this->getProcurementProduct( $product_id );
        $procurement_id         =   $procurementProduct->procurement_id;

        /**
         * this could be useful to prevent deletion for
         * product which might be in use by another resource
         */
        event( new ProcurementBeforeDeleteProduct( $procurementProduct ) );

        $procurementProduct->delete();

        /**
         * the product has been deleted, so we couldn't pass
         * the Model Object anymore
         */
        event( new ProcurementAfterDeleteProduct( $product_id, $procurement_id ) );
    }

    public function getProcurementProducts( $procurement_id )
    {
        return ProcurementProduct::getByProcurement( $procurement_id )
            ->get();
    }

    /**
     * Update a procurement products
     * using the provided product collection
     * @param int procurement id
     * @param array array
     * @return array status
     * @deprecated
     */
    public function bulkUpdateProducts( $procurement_id, $products )
    {
        $productsId        =   $this->getProcurementProducts( $procurement_id )
            ->pluck( 'id' );

        $result     =   collect( $products )
            ->map( function( $product ) use ( $productsId ) {
                if ( ! in_array( $product[ 'id' ], $productsId ) ) {
                    throw new Exception( sprintf( __( 'The product with the following ID "%s" is not initially included on the procurement' ), $product[ 'id' ] ) );
                }
                return $product;
            })
            ->map( function( $product ) {
                return $this->updateProcurementProduct( $product[ 'id' ], $product );
            });

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The procurement products has been updated.' ),
            'data'      =>  compact( 'result' )
        ];
    }

    /**
     * Get the procurements product
     * @param int procurement id
     * @return array
     */
    public function getProducts( $procurement_id )
    {
        $procurement    =   $this->get( $procurement_id );

        return $procurement->products;
    }
}