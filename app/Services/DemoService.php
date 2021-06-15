<?php
namespace App\Services;

use App\Models\Role;
use App\Models\Unit;
use App\Models\UnitGroup;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DemoService extends DemoCoreService
{
    protected $categoryService;
    protected $productService;
    protected $user;
    
    public function __construct(
        ProductCategoryService $categoryService,
        ProductService $productService
    ) {
        $this->categoryService  =   $categoryService;
        $this->productService   =   $productService;
    }

    /**
     * Will enable the basic grocery demo
     * @return void
     */
    public function run()
    {
        $this->prepareDefaultUnitSystem();
        $this->createCustomers();
        $this->createProviders();

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
                        'stock_management'  =>  'disabled', 
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