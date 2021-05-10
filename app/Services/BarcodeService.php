<?php
namespace App\Services;

use App\Classes\Hook;
use Exception;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    /**
     * generate barcode using a code and a code type
     * @param string $barcode
     * @param string $type
     * @return void
     */
    public function generateBarcode( $barcode, $type )
    {
        $generator      =       new BarcodeGeneratorPNG;
        
        switch( $type ) {
            case 'ean8' : $realType     =   $generator::TYPE_EAN_8; break;
            case 'ean13' : $realType    =   $generator::TYPE_EAN_13; break;
            case 'codabar' : $realType  =   $generator::TYPE_CODABAR; break;
            case 'code128' : $realType  =   $generator::TYPE_CODE_128; break;
            case 'code39' : $realType   =   $generator::TYPE_CODE_39; break;
            case 'code11' : $realType   =   $generator::TYPE_CODE_11; break;
            case 'upca' : $realType     =   $generator::TYPE_UPC_A; break;
            case 'upce' : $realType     =   $generator::TYPE_UPC_E; break;
            default : $realType         =   $generator::TYPE_EAN_8; break;
        }

        try {
            Storage::disk( 'public' )->put(
                Hook::filter( 'ns-media-path', 'products/barcodes/' . $barcode . '.png' ),
                $generator->getBarcode( $barcode, $realType, 3, 30 )
            );
        } catch( Exception $exception ) {
            throw new Exception( __( 'An error has occured while creating a barcode for the product. Make sure the barcode value is correct for the barcode type selected.' ) );
        }
    }
}