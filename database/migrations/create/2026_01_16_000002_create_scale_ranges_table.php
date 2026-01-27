<?php

use App\Classes\Schema;
use App\Models\ScaleRange;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_scale_ranges' ) ) {
            Schema::create( 'nexopos_scale_ranges', function ( Blueprint $table ) {
                $table->id();
                $table->string( 'name' );
                $table->unsignedInteger( 'range_start' );
                $table->unsignedInteger( 'range_end' );
                $table->unsignedInteger( 'next_scale_plu' );
                $table->text( 'description' )->nullable();
                $table->integer( 'author' );
                $table->timestamps();
            } );
        }

        // Seed default ranges
        $this->seedDefaultRanges();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_scale_ranges' );
    }

    /**
     * Seed default PLU ranges
     */
    private function seedDefaultRanges(): void
    {
        $defaultRanges = [
            [
                'name' => __( 'Test Range' ),
                'range_start' => 1,
                'range_end' => 99,
                'next_scale_plu' => 1,
                'description' => __( 'Range for testing and development purposes' ),
            ],
            [
                'name' => __( 'Fruits & Vegetables' ),
                'range_start' => 100,
                'range_end' => 999,
                'next_scale_plu' => 100,
                'description' => __( 'Fresh produce that requires weighing' ),
            ],
            [
                'name' => __( 'Meat & Poultry' ),
                'range_start' => 1000,
                'range_end' => 1999,
                'next_scale_plu' => 1000,
                'description' => __( 'Fresh meat and poultry products' ),
            ],
            [
                'name' => __( 'Seafood' ),
                'range_start' => 2000,
                'range_end' => 2999,
                'next_scale_plu' => 2000,
                'description' => __( 'Fresh fish and seafood products' ),
            ],
            [
                'name' => __( 'Bakery' ),
                'range_start' => 3000,
                'range_end' => 3999,
                'next_scale_plu' => 3000,
                'description' => __( 'Bakery items sold by weight' ),
            ],
            [
                'name' => __( 'Deli & Cheese' ),
                'range_start' => 4000,
                'range_end' => 4999,
                'next_scale_plu' => 4000,
                'description' => __( 'Deli meats and cheese products' ),
            ],
            [
                'name' => __( 'Bulk Foods' ),
                'range_start' => 5000,
                'range_end' => 5999,
                'next_scale_plu' => 5000,
                'description' => __( 'Bulk food items like nuts, grains, and spices' ),
            ],
            [
                'name' => __( 'Prepared Foods' ),
                'range_start' => 6000,
                'range_end' => 6999,
                'next_scale_plu' => 6000,
                'description' => __( 'Ready-to-eat prepared foods' ),
            ],
            [
                'name' => __( 'Organic Products' ),
                'range_start' => 7000,
                'range_end' => 7999,
                'next_scale_plu' => 7000,
                'description' => __( 'Certified organic products' ),
            ],
            [
                'name' => __( 'Specialty Items' ),
                'range_start' => 8000,
                'range_end' => 8999,
                'next_scale_plu' => 8000,
                'description' => __( 'Specialty and gourmet products' ),
            ],
            [
                'name' => __( 'General Weighable' ),
                'range_start' => 9000,
                'range_end' => 9999,
                'next_scale_plu' => 9000,
                'description' => __( 'General category for weighable products' ),
            ],
        ];

        foreach ( $defaultRanges as $range ) {
            ScaleRange::create( array_merge( $range, [
                'author' => 0, // System created
            ] ) );
        }

        /**
         * Update the product code length setting to 4 to accommodate the 4-digit PLU codes
         */
        ns()->option->set( 'ns_scale_barcode_product_length', 4 );
    }
};
