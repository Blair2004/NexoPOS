<?php 
namespace App\Services;

use App\Crud\ProductHistoryCrud;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Events\ProductResetEvent;
use App\Events\ProductAfterDeleteEvent;
use App\Events\ProductBeforeDeleteEvent;
use App\Models\ProductHistory;
use App\Models\Product;
use App\Models\ProcurementProduct;
use App\Models\ProductUnitQuantity;
use App\Services\TaxService;
use App\Services\UnitService;
use App\Services\CurrencyService;
use App\Services\ProductCategoryService;
use App\Exceptions\NotFoundException;
use App\Exceptions\NotAllowedException;
use App\Models\Procurement;
use App\ProductGallery;

class ProductService
{
    /** @param TaxService */
    protected $taxService;

    /** @param ProductCategoryService */
    protected $categoryService;

    /** @param CurrencyService */
    protected $currency;

    /** @param UnitService */
    protected $unitService;

    public function __construct( 
        ProductCategoryService $category,
        TaxService $tax,
        CurrencyService $currency,
        UnitService $unit
    )
    {
        $this->categoryService      =   $category;
        $this->taxService           =   $tax;
        $this->unitService          =   $unit;
        $this->currency             =   $currency;
    }

    /**
     * Get produt using 
     * the provided id
     * @param int product id
     * @return Product
     */
    public function get( $id )
    {
        $product    =   Product::find( $id );
        
        if ( ! $product instanceof Product ) {
            throw new Exception( __( 'Unable to find the product using the provided id.' ) );
        }

        return $product;
    }
    
    /**
     * Get Product using barcode
     * @param string barcode
     * @return Product|false
     */
    public function getProductUsingBarcode( $barcode ) 
    {
        /**
         * checks if a similar product already
         * exists and throw an error if it's the case
         */
        $product    =   Product::findUsingBarcode( $barcode )
            ->first();

        if ( $product instanceof Product ) {
            return $product;
        }

        return false;
    }
    
    /**
     * Get Product using barcode
     * @param string barcode
     * @return Product|false
     */
    public function getProductUsingBarcodeOrFail( $barcode ) 
    {
        /**
         * checks if a similar product already
         * exists and throw an error if it's the case
         */
        $product    =   Product::findUsingBarcode( $barcode )
            ->first();

        if ( $product instanceof Product ) {
            return $product;
        }

        return false;
    }

    /**
     * Get Product using sku
     * @param string barcode
     * @return Product|false
     */
    public function getProductUsingSKU( $barcode ) 
    {
        /**
         * checks if a similar product already
         * exists and throw an error if it's the case
         */
        $product    =   Product::findUsingSKU( $barcode )
            ->first();

        if ( $product instanceof Product ) {
            return $product;
        }

        return false;
    }

    /**
     * retrive a product using a SKU or fail
     * if the product is not found
     * @param string barcoe
     * @return Product
     */
    public function getProductUsingSKUOrFail( $barcode )
    {
        $product    =   $this->getProductUsingSKU( $barcode );

        if ( ! $product instanceof Product ) {
            throw new Exception( __( 'Unable to find the requested product using the provided SKU.' ) );
        }

        return $product;
    }

    /**
     * Create a product either it's a "product" 
     * or a "variable" product
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
         * check if it's a simple product or not
         */
        if ( $data[ 'product_type' ] === 'product' ) {
            return $this->createSimpleProduct( $data );
        } else if ( $data[ 'product_type' ] === 'variable' ) {
            return $this->createVariableProduct( $data );
        } else {
            throw new NotAllowedException([
                'status'    =>  'failed',
                'message'   =>  sprintf( __( 'Unable to create a product with an unknow type : %s' ), $data[ 'product_type' ] )
            ]);
        }
    }

    /**
     * create a variable product
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
        $result                 =   $this->createSimpleProduct( $data );
        $parent                 =   $result[ 'data' ][ 'product' ];
        $parent->product_type   =   'variable';
        $parent->save();

        /**
         * loop variations to 
         * see if they aren't using already in use SKU, Barcode
         */
        foreach( $data[ 'variations' ] as $variation ) {
            $this->createProductVariation( $parent, $variation );
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The variable product has been created.' ),
            'data'      =>  compact( 'parent' )
        ];
    }

    /**
     * Create a simple product
     * @param array data to handle
     * @return array response
     */
    public function createSimpleProduct( $data )
    {
        if ( $this->getProductUsingBarcode( $data[ 'barcode' ] ) ) {
            throw new Exception( sprintf( 
                __( 'The provided barcode "%s" is already in use.' ), 
                $data[ 'barcode' ] 
            ) );
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

        $product    =   new Product;
        $mode       =   'create';

        foreach( $data as $field => $value ) {
            if ( ! in_array( $field, [ 'variations' ] ) ) {
                $fields     =   $data;
                $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
            }
        }

        $product->author        =   Auth::id();
        $product->save();

        /**
         * compute product tax if it's provided
         * since it's case of variable product, the tax on
         * the parent product is not used.  
         */
        $this->taxService->computeTax( $product, $data[ 'tax_group_id' ]);

        /**
         * save product images
         */
        $this->saveGallery( $product, $fields[ 'images' ]);

        return [
            'status'    =>      'success',
            'message'   =>      __( 'The product has been saved.' ),
            'data'      =>      compact( 'product' )
        ];
    }

    /**
     * Update a product either is a "product" or a "variable"
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

        switch( $data[ 'product_type' ] ) {
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
     * @param Product 
     * @return void
     */
    public function releaseProductTaxes( $product ) 
    {
        $product->product_taxes()->delete();
    }

    /**
     * Update a simple product. This doesn't delete 
     * the variable within a product, if this latest has 
     * the type "product" before
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
        $product        =   $this->getProductUsingArgument( 'id', $id );
        $mode           =   'update';

        $this->releaseProductTaxes( $product );

        if ( $existingProduct = $this->getProductUsingBarcode( $fields[ 'barcode' ] ) ) {
            if ( $existingProduct->id !== $product->id ) {
                throw new Exception( __( 'The provided barcode is already in use.' ) );
            }
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

        foreach( $fields as $field => $value ) {
            $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
        }

        $product->author        =   Auth::id();
        $product->save();

        /**
         * compute product tax for either the wholesale_price
         * and the sale price
         */
        $this->taxService->computeTax( $product, $fields[ 'tax_group_id' ]);

        /**
         * save product images
         */
        $this->saveGallery( $product, $fields[ 'images' ]);

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product has been udpated' ),
            'data'      =>  compact( 'product' )
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
         * if there are many primary images
         * let's choose one for the user.
         * @todo should be tested
         */
        $manyPrimary    =   collect( $groups )->map( function( $fields ) {
            return isset( $fields[ 'primary' ] ) && $fields[ 'primary' ] === 1;
        })
            ->filter( fn( $result ) => $result === true )
            ->count() > 1;
        
        if ( $manyPrimary ) {
            $groups     =   collect( $groups )->map( function( $fields, $index ) {
                return collect( $fields )->map( function( $field, $fieldName ) use ( $index ) {
                    if ( $fieldName === 'primary' ) {
                        if ( $index === 0 ) {
                            $field      =   1;
                        } else {
                            $field      =   0;
                        }
                    }
    
                    return $field;
                });
            });
        }

        foreach( $groups as $group ) {
            $image              =   new ProductGallery;
            $image->featured    =   $group[ 'primary' ] ?? 0;
            $image->url         =   $group[ 'image' ];
            $image->author      =   Auth::id();
            $image->product_id  =   $product->id;
            $image->save();
        }
    }

    /**
     * Update a variable product
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
         * @var Illuminate\Support\Collection
         */
        $valid      =   collect( $data[ 'variations' ] )->filter( function( $product ) {
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
            if ( $foundProduct  = $this->getProductUsingBarcode( $variation[ 'barcode' ] ) ) {
                if ( $foundProduct->id !== $variation[ 'id' ] ) {
                    throw new Exception( sprintf( __( 'A variation within the product has a barcode which is already in use : %s.' ), $variation[ 'barcode' ] ) );
                }
            }
    
            /**
             * search a product using the provided SKU
             * and throw an error if it's the case
             */
            if ( $foundProduct = $this->getProductUsingSKU( $variation[ 'sku' ] ) ) {
                if( $foundProduct->id !== $variation[ 'id' ] ) {
                    throw new Exception( sprintf( __( 'A variation within the product has a SKU which is already in use : %s' ), $variation[ 'sku' ] ) );
                }
            }     
        });

        /**
         * let's update the product and recover the
         * parent product, which id will be reused.
         * @var array [
         *      'status': string,
         *      'message': string,
         *      'product': Product
         * ]
         */
        $result                 =   $this->updateSimpleProduct( $product, $data );
        $parent                 =   $result[ 'data' ][ 'product' ];
        $parent->product_type  =   'variable';
        $parent->save();

        /**
         * loop variations to see if they aren't 
         * using already used SKU or Barcode
         */
        foreach( $data[ 'variations' ] as $variation ) {
            $this->updateProductVariation( $parent, $variation[ 'id' ], $variation );
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The variable product has been updated.' ),
            'data'      =>  compact( 'parent' )
        ];
    }

    /**
     * Compute the tax and update the 
     * product according to the tax assigned
     * to that product
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

        if ( in_array( $field, [ 'sale_price', 'excl_tax_sale_price', 'incl_tax_sale_price', 'tax_value' ] ) ) {
            $product->$field    =   $this->currency->define( $value )
                ->get();
        } else if ( in_array( $field, [ 'selling_unit_ids', 'purchase_unit_ids', 'transfer_unit_ids' ]) ) {

            /**
             * we only verifiy the unit group
             * a valid value is provided. Note that for 
             * variable product, these fields aren't provided
             */
            if ( ! empty( $fields[ $field ] ) ) {
                /**
                 * try to get either a unit group or the unit itself
                 * according to the choice made on the item.
                 * @todo needs to be moved out from here
                 */
                $this->unitService->getGroups( $fields[ 'unit_group' ] );

                /**
                 * as we'll need to store that as a json.
                 */
                $product->$field    =   json_encode( array_values( $fields[ $field ] ) );
            } else {
                $product->$field    =   '[]';
            }

        } else if ( ! is_array( $value ) ) {
            $product->$field    =   $value;
        }
    }

    /**
     * refresh the price for a specific product
     * @param Product instance of the product
     * @return array response of the operation
     */
    public function refreshPrices( Product $product )
    {
        return $this->taxService->computeTax( $product, $product->tax_group_id );
    }

    /**
     * get product quantity according
     * to a specific unit id
     * @param int product id
     * @param int unit id
     */
    public function getQuantity( $product_id, $unit_id )
    {
        $unitQuantities     =   $this->get( $product_id )->unit_quantities;
        $filtredQuantities  =   $unitQuantities->filter( function( $quantity ) use ( $unit_id ) {
            return $quantity->unit_id === $unit_id;
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
     * @param string operation type
     * @param array history to save
     * @return array
     */
    public function saveHistory( $operationType, array $data )
    {
        switch( $operationType ) {
            case ProductHistory::ACTION_STOCKED :
                $this->__saveProcurementHistory( $data );
            break;
        }
    }

    /**
     * Record a procurement history for 
     * a specific set of product informations
     * @param array product informations to handle
     * @return array response of the process.
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
         */
        extract( $data );

        $currentQuantity                        =   $this->getQuantity( $product_id, $unit_id );
        $newQuantity                            =   $this->currency
            ->define( $currentQuantity )
            ->additionateBy( $quantity )
            ->get();
            
        $history                                =   new ProductHistory();
        $history->product_id                    =   $product_id;
        $history->procurement_id                =   $procurement_id;
        $history->procurement_product_id        =   $procurement_product_id;
        $history->unit_id                       =   $unit_id;
        $history->operation_type                =   ProductHistory::ACTION_STOCKED;
        $history->unit_price                    =   $unit_price;
        $history->total_price                   =   $total_price;
        $history->before_quantity               =   $currentQuantity;
        $history->quantity                      =   $quantity;
        $history->after_quantity                =   $newQuantity;
        $history->author                        =   Auth::id();
        $history->save();

        return $this->setQuantity( $product_id, $unit_id, $newQuantity );
    }

    /**
     * set quantity
     * this will update the quantity of 
     * a product using a unit as a reference
     * @param int product id
     * @param int unit id
     * @param float quantity
     * @return arrray response
     */
    public function setQuantity( $product_id, $unit_id, $quantity )
    {
        $unitQuantity   =   ProductUnitQuantity::where( 'product_id', $product_id )
            ->where( 'unit_id', $unit_id )
            ->first();

        if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
            $unitQuantity   =   new ProductUnitQuantity;
        }

        $unitQuantity->product_id   =   $product_id;
        $unitQuantity->unit_id      =   $unit_id;
        $unitQuantity->quantity     =   $quantity;
        $unitQuantity->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product\'s unit quantity has been updated.' ),
            'data'      =>  compact( 'unitQuantity' )
        ];
    }

    /**
     * Reset a product quantity
     * this will delete all quantity
     * @param int|Product product id
     * @return array response
     */
    public function resetProduct( $product_id )
    {
        /**
         * to avoid multiple call to the DB
         */
        if ( $product_id instanceof Product ) {
            $product        =   $product_id;
            $product_id     =   $product->id;
        } else {
            $product        =   $this->get( $product_id );
        }

        /**
         * let's check if the product is a variable 
         * product
         */
        if ( $product->product_type === 'variable' ) {

            $result         =   $product->variations->map( function( Product $product ) {
                return $this->__resetProductRelatives( $product );
            })->toArray();

            if ( count( $result ) === 0 ) {
                return [
                    'status'    =>  'info',
                    'message'   =>  sprintf( __( 'Unable to reset this variable product "%s", since it doens\'t seems to have any variations' ), $product->name )
                ];
            }

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The product variations has been reset' ),
                'data'      =>  compact( 'result' )
            ];

        } else {
            return $this->__resetProductRelatives( $product );
        }
    }

    private function __resetProductRelatives( Product $product )
    {
        $this->getProductHistory( $product->id )->each( function( $history ) {
            $history->delete();
        });

        $this->getUnitQuantities( $product->id )->each( function( $unitQuantity ) {
            $unitQuantity->delete();
        });

        /**
         * dispatch an event to let everyone knows
         * a product has been resetted
         */
        event( new ProductResetEvent( $product ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product has been resetted.' ),
            'data'      =>  compact( 'product' )
        ];
    }

    /**
     * delete a product using the
     * provided identifier
     * @param int product id
     * @return array operation status
     */
    public function deleteUsingID( $product_id )
    {
        $product    =   $this->get( $product_id );

        return $this->deleteProduct( $product );
    }

    /**
     * delete an instance of a product
     * @param Product instance to delete
     * @return array operation status
     */
    public function deleteProduct( Product $product )
    {
        $name   =   $product->name;

        event( new ProductBeforeDeleteEvent( $product ) );

        $product->delete();

        event( new ProductAfterDeleteEvent( $product ) );

        return [
            'status'    =>  'success',
            'message'   =>  sprintf( __( 'The product "%s" has been successfully deleted' ), $name )
        ];
    }

    /**
     * get product variation
     * @param int|Product
     * @return Collection<Product> variation
     */
    public function getProductVariations( $product = null )
    {
        if ( $product !== null ) {
            if ( is_numeric( $product ) ) {
                $product    =   $this->get( $product );
            }
    
            return $product->variations;
        } else {
            return Product::onlyVariations()->get();
        }
    }

    /**
     * get variations
     * @param int id to find
     * @return Product
     */
    public function getVariations()
    {
        return Product::onlyVariations()->get();
    }

    /**
     * get speciifc variation
     * @param int variation id
     * @return Product
     */
    public function getVariation( $id )
    {
        $variation  =   Product::where( 'product_type', 'variation' )
            ->where( 'id', $id )
            ->first();

        if ( ! $variation instanceof Product ) {
            throw new Exception( __( 'Unable to find the requested variation using the provided ID.' ) );
        }

        return $variation;
    }

    /**
     * get unit quantity for a specific product
     * @param int product id
     * @return Collection<ProductUnitQuantity>
     */
    public function getUnitQuantities( $product_id )
    {
        return ProductUnitQuantity::findProduct( $product_id )
            ->get()
            ->map( function( $productQuantity ) {
                $productQuantity->unit;
                return $productQuantity;
        });
    }

    public function getUnitQuantity( $product_id, $unit_id )
    {
        return ProductUnitQuantity::findProduct( $product_id )
            ->where( 'unit_id', $unit_id )
            ->first();
    }

    /**
     * get specific product quantity using the provided id
     * @param int id
     * @return Collection<ProductHistory>
     */
    public function getProductHistory( $product_id )
    {
        return ProductHistory::findProduct( $product_id )->orderBy( 'id' )->get()->map( function( $product ) {
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
        $history    =   $this->stockAdjustment( ProductHistory::ACTION_REMOVED, [
            'unit_id'                   =>      $oldProduct->unit_id,
            'product_id'                =>      $oldProduct->product_id,
            'unit_price'                =>      $oldProduct->purchase_price,
            'total_price'               =>      $oldProduct->total_price,
            'procurement_id'            =>      $oldProduct->procurement_id,
            'procurement_product_id'    =>      $oldProduct->id,
            'quantity'                  =>      $fields[ 'quantity' ]
        ]);

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product stock has been updated.' ),
            'compac'    =>  compact( 'history' )
        ];
    }

    /**
     * make an unit adjustment for 
     * a specific product
     * @param string operation : deducted, sold, procured, deleted, adjusted, damaged
     * @param array<$unit_id,$product_id,$unit_price,?$totalprice,?$procurement_id,?$procurement_product_id,?$sale_id,?$quantity> data to manage
     * @return ProductHistory
     */
    public function stockAdjustment( $action, $data )
    {
        extract( $data, EXTR_REFS );

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
            ProductHistory::ACTION_TRANSFER_OUT
        ]) ) {
            throw new NotAllowedException( __( 'The action is not an allowed operation.' ) );
        }

        /**
         * if the total_price is not provided
         * then we'll compute it
         */
        $total_price    =   ! empty( $data[ 'total_price' ] ) ? $this->currency
            ->define( $data[ 'unit_price' ] )
            ->multipliedBy( $data[ 'quantity' ] )
            ->get() : $data[ 'total_price' ];

        /**
         * we would like to verify if
         * by editing a procurement product
         * the remaining quantity will be greather than 0
         */
        $oldQuantity        =   $this->getQuantity( $product_id, $unit_id );
        $diffQuantity       =   $this->currency
            ->define( $oldQuantity )
            ->subtractBy( $quantity )
            ->get();

        if ( in_array( $action, [ 
            ProductHistory::ACTION_REMOVED,
            ProductHistory::ACTION_SOLD,
            ProductHistory::ACTION_DELETED,
            ProductHistory::ACTION_DEFECTIVE 
        ] ) ) {

            /**
             * this should prevent negative 
             * stock on the current item
             */
            if ( $diffQuantity < 0 ) {
                throw new NotAllowedException( __( 'Unable to proceed, this action will cause negative stock.' ) );
            }

            /**
             * @var string status
             * @var string message
             * @var array [ 'oldQuantity', 'newQuantity' ]
             */
            $result             =   $this->reduceUnitQuantities( $product_id, $unit_id, abs( $quantity ), $oldQuantity );
        } else {

            /**
             * @var string status
             * @var string message
             * @var array [ 'oldQuantity', 'newQuantity' ]
             */
            $result             =   $this->increaseUnitQuantities( $product_id, $unit_id, abs( $quantity ), $oldQuantity );
        }

        $history                                =   new ProductHistory;
        $history->product_id                    =   $product_id;
        $history->procurement_id                =   @$procurement_id;
        $history->procurement_product_id        =   @$procurement_product_id;
        $history->unit_id                       =   $unit_id;
        $history->order_id                      =   @$order_id;
        $history->operation_type                =   $action;
        $history->unit_price                    =   $unit_price;
        $history->total_price                   =   $total_price;
        $history->before_quantity               =   $result[ 'data' ][ 'oldQuantity' ];
        $history->quantity                      =   abs( $quantity );
        $history->after_quantity                =   $result[ 'data' ][ 'newQuantity' ];
        $history->author                        =   Auth::id();
        $history->save();

        return $history;
    }

    /**
     * reduce Product unit quantities and update
     * the available quantity for the unit provided
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
        $newQuantity        =   $this->currency
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
            'status'    =>  'success',
            'message'   =>  __( 'The product quantity has been updated.' ),
            'data'      =>  compact( 'newQuantity', 'oldQuantity', 'quantity' )
        ];
    }

    /**
     * Increase Product unit quantities and update
     * the available quantity for the unit provided
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
        $newQuantity        =   $this->currency
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
            'status'    =>  'success',
            'message'   =>  __( 'The product quantity has been updated.' ),
            'data'      =>  compact( 'newQuantity', 'oldQuantity', 'quantity' )
        ];
    }

    /**
     * add a stock entry to a product 
     * history using the provided informations
     * @param ProcurementProduct $product
     * @param array<$quantity,$unit_id,$purchase_price,$product_id>
     */
    public function procurementStockEntry( ProcurementProduct $product, $fields )
    {
        $history                        =   $this->stockAdjustment( ProductHistory::ACTION_ADDED, [
            'unit_id'                   =>      $product->unit_id,
            'product_id'                =>      $product->product_id,
            'unit_price'                =>      $product->purchase_price,
            'total_price'               =>      $product->total_price,
            'procurement_id'            =>      $product->procurement_id,
            'procurement_product_id'    =>      $product->id,
            'quantity'                  =>      $fields[ 'quantity' ]
        ]);

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product stock has been updated.' ),
            'data'      =>  compact( 'history' )
        ];
    }

    /**
     * returns only variable & product
     * @return array
     */
    public function getProducts()
    {
        return Product::excludeVariations()->get();
    }

    /**
     * Before delete a specific variations
     * @return array operation result
     */
    public function deleteVariations( $id = null )
    {
        $variations     =   $this->getVariations( $id );
        $count          =   $variations->count();

        $variations->map( function( $variation ) {
            event( new ProductBeforeDeleteEvent( $variation ) );

            $variation->delete();

            event( new ProductAfterDeleteEvent( $variation ) );
        });

        if ( $count === 0 ) {
            return [
                'status'    =>  'info',
                'message'   =>  __( 'There is no variations to delete.' )
            ];
        }

        return [
            'status'    =>  'success',
            'message'   =>  sprintf( __( '%s product(s) has been deleted.' ), $count )
        ];
    }

    /**
     * Delete all the available products
     * @return array result of the operation
     */
    public function deleteAllProducts()
    {
        $result     =   $this->getProducts()->map( function( $product ) {
            return $this->deleteProduct( $product );
        })->toArray();

        if ( ! $result ) {
            return [
                'status'    =>  'info',
                'message'   =>  __( 'There is no products to delete.' )
            ];
        }

        return [
            'status'    =>  'success',
            'message'   =>  sprintf( __( '%s products(s) has been deleted.' ), count( $result ) ),
            'data'      =>  compact( 'result' )
        ];
    }

    /**
     * Get a specific product using the 
     * provided argument & identifier
     * @param string argument
     * @param string|int identifier
     * @return Product
     */
    public function getProductUsingArgument( $argument = 'id', $identifier )
    {
        if ( $identifier instanceof Product ) {
            return $identifier;
        }

        try {
            switch( $argument ) {
                case 'id':
                    return $this->get( $identifier );
                case 'sku' :
                    return $this->getProductUsingSKUOrFail( $identifier );
                case 'barcode' :
                    return $this->getProductUsingBarcodeOrFail( $identifier );
            }
        } catch( Exception $exception ) {
            throw new Exception( sprintf( __( 'Unable to find the product, as the argument "%s" which value is "%s", doesn\'t have any match.' ), $argument, $identifier ) );
        }
    }

    /**
     * Create a variation for a 
     * specified parent product
     * @param Product parent
     * @param array fields
     * @return array
     */
    public function createProductVariation( Product $parent, $fields )
    {
        $product    =   new Product;
        $mode       =   'create';

        foreach( $fields as $field => $value ) {
            $fields         =   $fields;
            $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
        }

        $product->author        =   Auth::id();
        $product->parent_id     =   $parent->id;
        $product->type          =   $parent->type;
        $product->category_id   =   $parent->category_id;
        $product->product_type  =   'variation';
        $product->save();

        /**
         * compute product tax
         */
        $this->taxService->computeTax( $product, $fields[ 'tax_group_id' ]);

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product variation has been succesfully created.' ),
            'data'      =>  compact( 'product' )
        ];
    }

    public function updateProductVariation( $parent, $id, $fields )
    {
        $product    =   Product::find( $id );
        $mode       =   'update';

        foreach( $fields as $field => $value ) {
            /**
             * we'll update the data
             * since the variation don't need to
             * access the parent data informations.
             */
            $this->__fillProductFields( $product, compact( 'field', 'value', 'mode', 'fields' ) );
        }

        $product->author        =   Auth::id();
        $product->parent_id     =   $parent->id;
        $product->type          =   $parent->type;
        $product->product_type  =   'variation';
        $product->save();

        /**
         * compute product tax
         * for the meantime we assume the tax applies on the 
         * main product
         */
        $this->taxService->computeTax( $product, $fields[ 'tax_group_id' ]);

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The product variation has been updated.' ),
            'data'      =>  compact( 'product' )
        ];
    }
}