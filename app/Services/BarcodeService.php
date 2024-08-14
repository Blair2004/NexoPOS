<?php

namespace App\Services;

use App\Classes\Hook;
use App\Models\Product;
use Exception;
use Faker\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    const TYPE_EAN8 = 'ean8';

    const TYPE_EAN13 = 'ean13';

    const TYPE_CODABAR = 'codabar';

    const TYPE_CODE128 = 'code128';

    const TYPE_CODE39 = 'code39';

    const TYPE_CODE11 = 'code11';

    const TYPE_UPCA = 'upca';

    const TYPE_UPCE = 'upce';

    /**
     * Will generate code
     * for provided barcode type
     */
    public function generate( string $type ): string
    {
        return $this->generateRandomBarcode( $type );
    }

    /**
     * Will generate a EAN8 code
     *
     * @return string generated code.
     */
    public function generateRandomBarcode( $code ): string
    {
        $factory = Factory::create();

        do {
            switch ( $code ) {
                case self::TYPE_EAN8:
                    $barcode = $factory->ean8();
                    break;
                case self::TYPE_EAN13:
                    $barcode = $factory->ean13();
                    break;
                case self::TYPE_CODABAR:
                case self::TYPE_CODE128:
                case self::TYPE_CODE39:
                    $barcode = Str::random( 10 );
                    break;
                case self::TYPE_CODE11:
                    $barcode = rand( 1000000000, 999999999 );
                    break;
                case self::TYPE_UPCA:
                    $barcode = rand( 10000000000, 99999999999 );
                    break;
                case self::TYPE_UPCA:
                    $barcode = rand( 1000000, 9999999 );
                    break;
                default:
                    $barcode = $factory->isbn10();
                    break;
            }

            $product = Product::where( 'barcode', $barcode )->first();
        } while ( $product instanceof Product );

        return $barcode;
    }

    public function getBarcodeInBase64( $barcode, $type, $height = 30 )
    {
        $generator = new BarcodeGeneratorPNG;

        switch ( $type ) {
            case 'ean8': $realType = $generator::TYPE_EAN_8;
                break;
            case 'ean13': $realType = $generator::TYPE_EAN_13;
                break;
            case 'codabar': $realType = $generator::TYPE_CODABAR;
                break;
            case 'code128': $realType = $generator::TYPE_CODE_128;
                break;
            case 'code39': $realType = $generator::TYPE_CODE_39;
                break;
            case 'code11': $realType = $generator::TYPE_CODE_11;
                break;
            case 'upca': $realType = $generator::TYPE_UPC_A;
                break;
            case 'upce': $realType = $generator::TYPE_UPC_E;
                break;
            default: $realType = $generator::TYPE_EAN_8;
                break;
        }

        return base64_encode( $generator->getBarcode( $barcode, $realType, 3, $height ) );
    }

    /**
     * generate barcode using a code and a code type
     *
     * @param  string $barcode
     * @param  string $type
     * @return void
     */
    public function generateBarcode( $barcode, $type )
    {
        $generator = new BarcodeGeneratorPNG;

        switch ( $type ) {
            case 'ean8': $realType = $generator::TYPE_EAN_8;
                break;
            case 'ean13': $realType = $generator::TYPE_EAN_13;
                break;
            case 'codabar': $realType = $generator::TYPE_CODABAR;
                break;
            case 'code128': $realType = $generator::TYPE_CODE_128;
                break;
            case 'code39': $realType = $generator::TYPE_CODE_39;
                break;
            case 'code11': $realType = $generator::TYPE_CODE_11;
                break;
            case 'upca': $realType = $generator::TYPE_UPC_A;
                break;
            case 'upce': $realType = $generator::TYPE_UPC_E;
                break;
            default: $realType = $generator::TYPE_EAN_8;
                break;
        }

        try {
            Storage::disk( 'public' )->put(
                Hook::filter( 'ns-media-path', 'products/barcodes/' . $barcode . '.png' ),
                $generator->getBarcode( $barcode, $realType, 3, 30 )
            );
        } catch ( Exception $exception ) {
            $insight = ( $exception->getMessage() ?: __( 'N/A' ) );

            throw new Exception(
                sprintf(
                    __( 'An error has occurred while creating a barcode "%s" using the type "%s" for the product. Make sure the barcode value is correct for the barcode type selected. Additional insight : %s' ),
                    $barcode,
                    $realType,
                    $insight
                )
            );
        }
    }

    /**
     * @deprecated
     */
    public function generateBarcodeValue( $type )
    {
        $faker = ( new Factory )->create();

        switch ( $type ) {
            case self::TYPE_CODE39: return strtoupper( Str::random( 10 ) );
            case self::TYPE_CODE128: return strtoupper( Str::random( 10 ) );
            case self::TYPE_EAN8: return $faker->randomNumber( 8, true );
            case self::TYPE_EAN13: return $faker->randomNumber( 6, true ) . $faker->randomNumber( 6, true );
            case self::TYPE_UPCA: return $faker->randomNumber( 5, true ) . $faker->randomNumber( 6, true );
            case self::TYPE_UPCE: return $faker->randomNumber( 6, true );
            case self::TYPE_CODABAR: return $faker->randomNumber( 8, true ) . $faker->randomNumber( 8, true );
            case self::TYPE_CODE11: return $faker->randomNumber( 5, true ) . '-' . $faker->randomNumber( 4, true );
        }
    }
}
