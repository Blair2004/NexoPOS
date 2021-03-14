<?php
namespace App\Services;

use App\Classes\Hook;
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
            default : $realType         =   $generator::TYPE_EAN_8; break;
        }

        Storage::disk( 'public' )->put(
            Hook::filter( 'ns-media-path', 'products/barcodes/' . $barcode . '.png' ),
            $generator->getBarcode( $barcode, $realType, 3, 30 )
        );
    }
}