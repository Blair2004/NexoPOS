<?php
namespace App\Services;

use App\Classes\Hook;
use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitGroup;
use Exception;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DemoService extends DemoCoreService
{
    protected $categoryService;
    protected $productService;
    protected $procurementService;
    protected $orderService;
    protected $user;
    protected $faker;
    
    public function __construct(
        ProductCategoryService $categoryService,
        ProductService $productService,
        ProcurementService $procurementService,
        OrdersService $ordersService
    ) {
        $this->categoryService      =   $categoryService;
        $this->productService       =   $productService;
        $this->procurementService   =   $procurementService;
        $this->orderService         =   $ordersService;
        $this->faker                =   (new Factory)->create();
    }

    public function extractProductFields( $fields )
    {
        $primary    =   collect( $fields[ 'variations' ] )
            ->filter( fn( $variation ) => isset( $variation[ '$primary' ] ) )
            ->first();

        $source                                 =   $primary;
        $units                                  =   $primary[ 'units' ];

        /**
         * this is made to ensure the array 
         * provided aren't flatten
         */
        unset( $primary[ 'units' ] );
        unset( $primary[ 'images' ] );

        $primary[ 'identification' ][ 'name' ]          =   $fields[ 'name' ];
        $primary                                        =    Helper::flatArrayWithKeys( $primary )->toArray();
        $primary[ 'product_type' ]                      =   'product';

        /**
         * let's restore the fields before
         * storing that.
         */
        $primary[ 'images' ]        =   $source[ 'images' ];
        $primary[ 'units' ]         =   $source[ 'units' ];
        
        unset( $primary[ '$primary' ] );

        /**
         * As foreign fields aren't handled with 
         * they are complex (array), this methods allow
         * external script to reinject those complex fields.
         */
        $primary        =   Hook::filter( 'ns-create-products-inputs', $primary, $source );

        /**
         * the method "create" is capable of 
         * creating either a product or a variable product
         */
        return $primary;
    }

    /**
     * Will enable the basic grocery demo
     * @return void
     */
    public function run( $data )
    {
        /**
         * @var string $mode
         * @var boolean $create_sales
         * @var boolean $create_procurements
         */
        extract( $data );

        $this->prepareDefaultUnitSystem();
        $this->createCustomers();
        $this->createAccountingAccounts();
        $this->createProviders();
        $this->createTaxes();
        $this->createProducts();

        if ( $create_procurements ) {
            $this->performProcurement();
        }
        
        if ( $create_sales && $create_procurements ) {
            $this->createSales();
        }
    }

    public function createProducts()
    {
        $categories     =   [
            json_decode( file_get_contents( base_path( 'database' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'bedding-n-bath.json' ) ) ),
            json_decode( file_get_contents( base_path( 'database' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'furniture.json' ) ) ),
            json_decode( file_get_contents( base_path( 'database' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'kitchen-dinning.json' ) ) ),
            json_decode( file_get_contents( base_path( 'database' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'decors.json' ) ) ),
        ];

        foreach( $categories as $category ) {
            $result             =   $this->categoryService->create([
                'name'          =>  $category->name,
                'preview_url'   =>  $category->image
            ]);

            $createdCategory   =   $result[ 'data' ][ 'category' ];

            foreach( $category->products as $product ) {
                $random     =   Str::random(8);
                $unitGroup  =   UnitGroup::with( 'units' )->where( 'name', __( 'Countable' ) )->first();

                try {
                    $result     =   $this->productService->create([
                        'product_type'      => 'product',
                        'name'              =>  $product->name,
                        'sku'               =>  $random,
                        'barcode'           =>  $random,
                        'barcode_type'      =>  'code128',
                        'category_id'       =>  $createdCategory[ 'id' ],
                        'description'       =>  __( 'generated' ),
                        'product_type'      =>  'product',
                        'type'              =>  'dematerialized',
                        'status'            =>  'available',
                        'stock_management'  =>  'enabled', // Arr::random([ 'disabled', 'enabled' ]), 
                        'tax_group_id'      =>  1,
                        'tax_type'          =>  'inclusive',
                        'images'            =>  [
                            [
                                'primary'       =>  true,
                                'image'         =>  asset( $product->image )
                            ]
                        ],
                        'units' =>  [
                            'selling_group' =>  $unitGroup
                                ->units->map( function( $unit ) use ( $product ) {
                                return [
                                    'sale_price_edit'       =>  $product->price,
                                    'wholesale_price_edit'  =>  ns()->currency->getPercentageValue( $product->price, 10, 'substract' ),
                                    'unit_id'               =>  $unit->id,
                                    'preview_url'           =>  asset( $product->image )
                                ];
                            }),
                            'unit_group'    =>  $unitGroup->id
                        ]
                    ]);
                } catch( Exception $exception ) {
                    dump( $exception->getMessage() );
                }
            }

        }
    }
}