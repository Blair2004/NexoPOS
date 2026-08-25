<?php

namespace App\Guides;

use App\Classes\Guide;
use App\Services\GuideService;

class ProductGuide
{
    public static function init( GuideService $guide )
    {
        $guide->register(
            'products.creation', Guide::experience(
                id: 'products.creation',
                permissions: [ 'nexopos.create.products' ],
                required_routes: [ 'ns.dashboard.products.create' ],
                title: __( 'Product Creation Guide' ),
                description: __( 'Let us walk you through the product creation process, so you start with only the necessary fields.' ),
                steps: Guide::steps(
                    Guide::step(
                        id: 'product.name',
                        element: '[name="name"]',
                        popover: Guide::popover(
                            title: __( 'Product Name' ),
                            description: __( 'Every product must have a unique name.' ),
                            side: 'bottom'
                        )
                    ),
                    Guide::step(
                        id: 'product.category',
                        element: '[name="category_id"]',
                        popover: Guide::popover(
                            title: __( 'Product Category' ),
                            description: __( 'Organize your product by assigning a category.' ),
                            side: 'bottom'
                        )
                    ),
                    Guide::step(
                        id: 'product.category.create',
                        element: '[name="category_id"] button',
                        popover: Guide::popover(
                            title: __( 'Create Category' ),
                            description: __( 'Click here to create a new category.' ),
                            side: 'bottom'
                        )
                    ),
                    Guide::step(
                        id: 'product.type',
                        element: '[name="type"]',
                        popover: Guide::popover(
                            title: __( 'Product Type' ),
                            description: __( 'Define if your product is a materialized or dematerialized product. You can also create a grouping product.' ),
                            side: 'bottom'
                        )
                    ),
                    Guide::step(
                        id: 'product.status',
                        element: '[name="status"]',
                        popover: Guide::popover(
                            title: __( 'Status' ),
                            description: __( 'Set wether the product should be visible on the POS. Hidden product can\'t be sold on the POS.' ),
                            side: 'bottom'
                        )
                    ),
                    Guide::step(
                        id: 'product.stock_management',
                        element: '[name="stock_management"]',
                        popover: Guide::popover(
                            title: __( 'Stock Management' ),
                            description: __( 'If the stock management is enabled, the product will need inventory via "Procurement" to provide stock.' ),
                            side: 'bottom'
                        )
                    ),
                    Guide::step(
                        id: 'product.pinned',
                        element: '[name="pinned"]',
                        popover: Guide::popover(
                            title: __( 'Pin Favorite Products' ),
                            description: __( 'If a product is likely to be frequently sold, you can pin that to the POS.' ),
                            side: 'bottom'
                        ),
                        nextAction: [
                            'type' => 'click',
                            'element' => '#tab-units',
                        ],
                    ),
                    Guide::step(
                        id: 'product.unit_group',
                        element: '[name="unit_group"]',
                        popover: Guide::popover(
                            title: __( 'Unit Group' ),
                            description: __( 'A product can be sold in various unit. Choose the unit group to apply to your product.' ),
                            side: 'bottom'
                        ),
                        nextAction: [
                            'type' => 'click',
                            'element' => '#tab-units',
                        ],
                        waitForElement: 1000
                    ),
                    Guide::step(
                        id: 'product.unit_group.create',
                        element: '[name="unit_group"] button',
                        popover: Guide::popover(
                            title: __( 'Create Unit Group' ),
                            description: __( 'You can create a unit group. For countable products, you can create a unit group named "Countable".' ),
                            side: 'bottom'
                        ),
                    ),
                    Guide::step(
                        id: 'product.accurate_tracking',
                        element: '[name="accurate_tracking"]',
                        popover: Guide::popover(
                            title: __( 'Accurate Tracking' ),
                            description: __( 'By enabling this options, NexoPOS will be able to the product sold. This will be useful to determine on which purchase order the procurement was provided.' ),
                            side: 'bottom'
                        ),
                    ),
                    Guide::step(
                        id: 'product.auto_cogs',
                        element: '[name="auto_cogs"]',
                        popover: Guide::popover(
                            title: __( 'Auto COGS' ),
                            description: __( 'If enabled, the Cost Of Good Sold will be automatically computed from the purchase price during the procurement. If set to no, you can manually define the COGS.' ),
                            side: 'bottom'
                        ),
                        nextAction: [
                            'type' => 'click',
                            'element' => '#tab-expiry',
                        ],
                    ),
                    Guide::step(
                        id: 'product.expires',
                        element: '[name="expires"]',
                        popover: Guide::popover(
                            title: __( 'Product Expiry' ),
                            description: __( 'If the product likely to expire, you can enable that here. Note that the expiry of each purchased product is determined during the procurement, so each batch product will have different expiration time.' ),
                            side: 'bottom'
                        ),
                    ),
                    Guide::step(
                        id: 'product.on_expiration',
                        element: '[name="on_expiration"]',
                        popover: Guide::popover(
                            title: __( 'On Expiration' ),
                            description: __( 'choose the action to execute when the product expires.' ),
                            side: 'bottom'
                        ),
                        nextAction: [
                            'type' => 'click',
                            'element' => '#tab-taxes',
                        ],
                    ),
                    Guide::step(
                        id: 'product.tax_group_id',
                        element: '[name="tax_group_id"]',
                        popover: Guide::popover(
                            title: __( 'Tax Group' ),
                            description: __( 'If you have already created a tax group, you can assign that group here. You might eventually create a tax group if that doesn\'t yet exist.' ),
                            side: 'bottom'
                        ),
                    ),
                    Guide::step(
                        id: 'product.tax_type',
                        element: '[name="tax_type"]',
                        popover: Guide::popover(
                            title: __( 'Tax Type' ),
                            description: __( 'Set if your tax should be inclusive or exclusive.' ),
                            side: 'bottom'
                        ),
                        nextAction: [
                            'type' => 'click',
                            'element' => '#tab-images',
                        ],
                    ),
                    Guide::step(
                        id: 'product.create-image',
                        element: '#create-image',
                        popover: Guide::popover(
                            title: __( 'Create Product' ),
                            description: __( 'A visual representation of a product helps cashier to identify product quickly if they are using the product grid.' ),
                            side: 'bottom'
                        ),
                        nextAction: [
                            'type' => 'click',
                            'element' => '#create-image',
                        ],
                    ),
                    Guide::step(
                        id: 'product.image-url',
                        element: '[name="url"]',
                        popover: Guide::popover(
                            title: __( 'Choose Your Image' ),
                            description: __( 'Use the media library to upload your product images.' ),
                            side: 'bottom'
                        ),
                        waitForElement: 500,
                        nextAction: [
                            'type' => 'click',
                            'element' => '#tab-identification',
                        ],
                    ),
                )
            )
        );
    }
}
