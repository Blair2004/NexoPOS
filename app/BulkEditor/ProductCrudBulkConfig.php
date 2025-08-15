<?php

namespace App\BulkEditor;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Crud\ProductCrud;
use App\Models\ProductCategory;
use App\Services\Helper;
use Modules\BulkEditor\Classes\BulkEditor;
use Modules\BulkEditor\Contracts\BulkEditorConfiguration;
use Modules\BulkEditor\Services\ConfigurationService;

class ProductCrudBulkConfig implements BulkEditorConfiguration
{
    public function setup( ConfigurationService $configuration ): void
    {
        $configuration->register(
            class: ProductCrud::class,
            configuration: BulkEditor::configuration(
                fields: CrudForm::fields(
                    FormInput::select(
                        label: __( 'Category' ),
                        name: 'category_id',
                        options: Helper::toJsOptions( ProductCategory::get(), [ 'id', 'name' ] ),
                        description: __( 'Select the category to which the product belongs.' ),
                    ),
                    FormInput::select(
                        label: __( 'Barcode Type' ),
                        name: 'barcode_type',
                        options: Helper::kvToJsOptions( [
                            'ean8' => __( 'EAN 8' ),
                            'ean13' => __( 'EAN 13' ),
                            'codabar' => __( 'Codabar' ),
                            'code128' => __( 'Code 128' ),
                            'code39' => __( 'Code 39' ),
                            'code11' => __( 'Code 11' ),
                            'upca' => __( 'UPC A' ),
                            'upce' => __( 'UPC E' ),
                        ] ),
                        description: __( 'Define the barcode type scanned.' ),
                    ),
                    FormInput::select(
                        label: __( 'Product Type' ),
                        name: 'type',
                        options: Helper::kvToJsOptions( [
                            'materialized' => __( 'Materialized Product' ),
                            'dematerialized' => __( 'Dematerialized Product' ),
                            'grouped' => __( 'Grouped Product' ),
                        ] ),
                        description: __( 'Define the product type. Applies to all variations.' ),
                    ),
                    FormInput::select(
                        label: __( 'Status' ),
                        name: 'status',
                        options: Helper::kvToJsOptions( [
                            'available' => __( 'On Sale' ),
                            'unavailable' => __( 'Hidden' ),
                        ] ),
                        description: __( 'Define whether the product is available for sale.' ),
                    ),
                    FormInput::switch(
                        label: __( 'Stock Management' ),
                        name: 'stock_management',
                        options: Helper::kvToJsOptions( [
                            'enabled' => __( 'Enabled' ),
                            'disabled' => __( 'Disabled' ),
                        ] ),
                        description: __( 'Enable the stock management on the product. Will not work for service or uncountable products.' ),
                    ),
                ),
                mapping: BulkEditor::mapping(
                    label: 'name',
                    value: 'id'
                )
            )
        );
    }
}
