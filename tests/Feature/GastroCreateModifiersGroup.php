<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\UnitGroup;
use App\Services\TaxService;
use Faker\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;
use Modules\NsGastro\Models\ModifierGroup;
use Tests\TestCase;

class GastroCreateModifiersGroup extends TestCase
{
    public $faker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        $faker          =   \Faker\Factory::create();
        $taxService     =   app()->make( TaxService::class );
        $taxType        =   $faker->randomElement([ 'exclusive', 'inclusive' ]);
        $unitGroup      =   UnitGroup::first();
        $sale_price     =   $faker->numberBetween(25,30);
        $categories     =   ProductCategory::get()
            ->map( fn( $cat ) => $cat->id );
           
        $modifiersGroups    =   [ 
            [
                'name'          =>  __( 'Sauces' ),
                'general'       =>  [
                    'forced'        =>  true,
                    'countable'     =>  Arr::random([ true, false ]),
                    'multiselect'   =>  true,
                    'description'   =>  '',
                ],
                'modifiers' =>  [
                    [
                        'name'  =>  __( 'Bechamel' ),
                        'image' =>  asset( 'modules/nsgastro/images/bechamel.jpg' ),
                    ], [
                        'name' =>  __( 'Demi Glace' ),
                        'image' =>  asset( 'modules/nsgastro/images/demi-glace.jpg' ),
                    ], [
                        'name' =>  __( 'Espanol' ),
                        'image' =>  asset( 'modules/nsgastro/images/espanol.jpg' ),
                    ], [
                        'name' =>  __( 'Hollandaise' ),
                        'image' =>  asset( 'modules/nsgastro/images/hollandaise.jpg' ),
                    ], [
                        'name' =>  __( 'Tomato' ),
                        'image' =>  asset( 'modules/nsgastro/images/tomato.jpg' ),
                    ], [
                        'name' =>  __( 'Veloute' ),
                        'image' =>  asset( 'modules/nsgastro/images/veloute.jpg' ),
                    ]
                ]
            ]
        ];

        $this->faker    =   Factory::create();

        foreach( $modifiersGroups as $index => $group ) {
            $response       =   $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/crud/ns.gastro-modifiers-groups', [
                    'name'              =>  $group[ 'name' ],
                    'general'           =>  $group[ 'general' ]
                ]);
    
            $response->assertJsonPath( 'status', 'success' );

            $modifierGroup       =   ModifierGroup::orderBy( 'id', 'desc' )->first();

            foreach( $group[ 'modifiers' ] as $modifier ) {
                $response   = $this
                    ->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', '/api/nexopos/v4/products/', [
                        'name'          =>  $modifier[ 'name' ],
                        'variations'    =>  [
                            [
                                '$primary'  =>  true,
                                'expiracy'  =>  [
                                    'expires'       =>  0,
                                    'on_expiration' =>  'prevent_sales',
                                ],
                                'identification'    =>  [
                                    'barcode'           =>  $faker->ean13(),
                                    'barcode_type'      =>  'ean13',
                                    'category_id'       =>  $faker->randomElement( $categories ),
                                    'description'       =>  __( 'Created via tests' ),
                                    'product_type'      =>  'product',
                                    'type'              =>  'dematerialized',
                                    'sku'               =>  Str::random(15) . '-sku',
                                    'status'            =>  'available',
                                    'stock_management'  =>  'disabled',   
                                ],
                                'images'            =>  [
                                    [
                                        'primary'       =>  true,
                                        'image'         =>  $modifier[ 'image' ]
                                    ]
                                ],
                                'taxes'             =>  [
                                    'tax_group_id'  =>  1,
                                    'tax_type'      =>  $taxType,
                                ],
                                'units'             =>  [
                                    'selling_group' =>  $unitGroup->units->map( function( $unit ) use ( $faker, $sale_price ) {
                                        return [
                                            'sale_price_edit'       =>  $sale_price,
                                            'wholesale_price_edit'  =>  $faker->numberBetween(5,10),
                                            'unit_id'               =>  $unit->id
                                        ];
                                    }),
                                    'unit_group'    =>  $unitGroup->id
                                ],
                                'restaurant'            =>  [
                                    'modifiers_groups'      =>  '',
                                    'modifiers_group_id'    =>  $modifierGroup->id,
                                ]
                            ]
                        ]
                ]);

                $response->assertJsonPath( 'status', 'success' );
            }
        }
    }
}
