<?php

namespace App\Guides;

use App\Classes\Guide;
use App\Services\GuideService;

class ProcurementGuide
{
    public static function init( GuideService $guideService )
    {
        $guideService->register( 'procurement.guide', Guide::experience(
            id: 'procurement.guide',
            title: __( 'Procurement Guide' ),
            description: __( 'Take a quick tour of the procurement module and discover the main areas of NexoPOS.' ),
            permissions: ['nexopos.create.procurements'],
            required_routes: ['ns.procurement-create'],
            steps: Guide::steps(
                Guide::step(
                    id: 'procurement-name',
                    element: '[name="name"]',
                    popover: Guide::popover(
                        title: __( 'Procurement Name' ),
                        description: __( 'Define a procurement name to identify them. Note however that this is not mandatory.' ),
                        side: 'bottom',
                        align: 'center'
                    ),
                ),
                Guide::step(
                    id: 'automatic_approval',
                    element: '[name="automatic_approval"]',
                    popover: Guide::popover(
                        title: __( 'Automatic Approval' ),
                        description: __( 'If enabled, the procurement will be automatically approved when the delivery time will be reached.' ),
                        side: 'bottom',
                        align: 'center'
                    ),
                ),
                Guide::step(
                    id: 'delivery_time',
                    element: '#delivery_time',
                    popover: Guide::popover(
                        title: __( 'Delivery Time' ),
                        description: __( 'If the delivery is expected in a future date, set the expected delivery date here.' ),
                        side: 'bottom',
                        align: 'center'
                    ),
                ),
                Guide::step(
                    id: 'delivery_status',
                    element: '[name="delivery_status"]',
                    popover: Guide::popover(
                        title: __( 'Delivery Status' ),
                        description: __( 'Set the delivery status of a procurement. If the inventory hasn\'t yet reached the store, you can set it as pending.' ),
                        side: 'bottom',
                        align: 'center'
                    ),
                ),
                Guide::step(
                    id: 'procurement-overview',
                    element: '[name="provider_id"]',
                    popover: Guide::popover(
                        title: __( 'Assign a provider' ),
                        description: __( 'Select a provider for this procurement. You can choose an existing provider or create a new one.' ),
                        side: 'bottom',
                        align: 'center'
                    ),
                ),
            )
        ) );
    }
}
