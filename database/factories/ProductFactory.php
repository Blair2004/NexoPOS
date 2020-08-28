<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use App\Models\Product;
use App\Models\TaxGroup;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define( Product::class, function (Faker $faker) {
    return [
        'name'                  =>  $faker->word,
        'product_type'          =>  'product',
        'sale_price'            =>  $salePrice = $faker->numberBetween( 20, 100 ),
        'gross_sale_price'      =>  $faker->numberBetween( 10, $salePrice ),
        'net_sale_price'        =>  $faker->numberBetween( 20, $salePrice ),
        'barcode'               =>  $faker->word,
        'stock_management'      =>  $faker->randomElement([ 'enabled', 'disabled' ]),
        'barcode_type'          =>  $faker->randomElement([ 'ean8', 'ean13' ]),
        'sku'                   =>  $faker->word . date( 's' ),
        'product_type'          =>  $faker->randomElement([ 'materialized', 'dematerialized']),
        'selling_unit_group'    =>  $faker->randomElement( TaxGroup::get()->map( fn( $group ) => $group->id ) ),
        'purchase_unit_group'   =>  $faker->randomElement( TaxGroup::get()->map( fn( $group ) => $group->id ) ),
        'transfer_unit_group'   =>  $faker->randomElement( TaxGroup::get()->map( fn( $group ) => $group->id ) ),
        'author'                =>  $faker->randomElement( User::get()->map( fn( $user ) => $user->id ) ),
    ];
});
