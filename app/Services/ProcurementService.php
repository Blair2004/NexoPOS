<?php
namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Provider;
use App\Models\Procurement;
use App\Services\ProductService;
use Tendoo\Core\Exceptions\NotFoundException;
use App\Models\ProcurementProduct;
use Tendoo\Core\Exceptions\NotAllowedException;
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
        $provider           =   $this->providerService->get( @$provider_id );
        if ( ! $provider instanceof Provider ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the assigned provider.' )
            ]);
        }

        // mustFind( Provider::class, $provider_id )->handleResponse( function( $instance ) {
        // })->orThrow([
        //     'status'    =>  'failed',
        //     'message'   =>  __( 'Unable to find the assigned Provider' )
        // ]);

        $procurement    =       new Procurement;

        foreach( $data as $field => $value ) {
            $procurement->$field        =   $value;
        }

        $procurement->author            =   Auth::id();
        $procurement->value             =   0;
        $procurement->status            =   'unpaid'; // or default value while creating procurement
        $procurement->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The provider has been created.' ),
            'data'      =>  compact( 'procurement' )
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
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the requested procurement using the provided id.' )
            ]);
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
            } catch( NotFoundException $exception ) {
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
         * we want to retreive the 
         * base unit only once if a similar id
         * is already provided.
         */
        $storedUnitReference            =   [];
        $itemsToSave                    =   [];
        $errors                         =   [];

        $products->each( function( $procurementProduct ) use ( &$storedUnitReference, &$itemsToSave, &$errors ) {
            $procurementProduct    =   ( object ) $procurementProduct;
            
            if ( isset( $procurementProduct->id ) ) {
                $argument   =   'id';
            } else if ( isset( $procurementProduct->sku ) ) {
                $argument   =   'sku';
            } else if ( isset( $procurementProduct->barcode ) ) {
                $argument   =   'barcode';
            }

            try {
                $item               =   $this->productService->getProductUsingArgument( $argument, $procurementProduct->$argument );
                extract( $this->__computeProcurementProductValues( compact( 'item', 'procurementProduct', 'storeUnitReference', 'itemsToSave', 'errors' ) ) );
            } catch( NotFoundException $exception ) {
                $errors[]           =   [
                    'status'        =>  'failed',
                    'message'       =>  $exception->getMessage()
                ];
            }
        });

        $result     =   $this->saveProcurementProducts( $procurement->id, $itemsToSave );

        /**
         * trigger a specific event
         * to let other perform some action
         */
        event( new ProcurementDeliveryEvent( $procurement ) );

        $result[ 'data' ][ 'errors' ]    =   $errors;

        return $result;
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
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  sprintf( __( 'The unit used for the product %s doesn\'t belongs to the Unit Group assigned to the item' ), $item->name )
            ]);
        }

        $itemData       =   [
            'product_id'        =>  $item->id,
            'unit_id'           =>  $procurementProduct->unit_id,
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
        $totalCost      =   0;
        $procurement    =   Procurement::find( $procurement->id );
        $totalEntries   =   $procurement->products->count();

        $procurement->products->each( function( $product ) use ( &$totalCost ) {
            $totalCost  =  $this->currency->value( $totalCost )->additionateBy( $product->total_price )
                ->get();
        });

        $procurement->total_items   =   $totalEntries;
        $procurement->value         =   $totalCost;
        $procurement->author        =   Auth::id();
        $procurement->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The procurement has been refreshed.' ),
            'data'      =>  compact( 'procurement' )
        ];
    }

    /**
     * delete all items recorded for a procurement
     * and reset all value including the computed owned money
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
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the procurement product using the provided id.' )
            ]);
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
     */
    public function bulkUpdateProducts( $procurement_id, $products )
    {
        $productsId        =   $this->getProcurementProducts( $procurement_id )
            ->pluck( 'id' );

        $result     =   collect( $products )
            ->map( function( $product ) use ( $productsId ) {
                if ( ! in_array( $product[ 'id' ], $productsId ) ) {
                    throw new NotFoundException([
                        'status'    =>  'failed',
                        'message'   =>  sprintf( __( 'The product with the following ID "%s" is not initially included on the procurement' ), $product[ 'id' ] )
                    ]);
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