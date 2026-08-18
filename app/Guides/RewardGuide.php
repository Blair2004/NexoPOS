<?php
namespace App\Guides;

use App\Classes\Guide;
use App\Services\GuideService;

class RewardGuide
{
    public static function init( GuideService $guide )
    {
        $guide->register( 'reward.guide', Guide::experience(
            id: 'reward.guide',
            title: __( 'Reward System Guide' ),
            permissions: [ 'nexopos.create.rewards' ],
            required_routes: [ 'ns.dashboard.rewards-create' ],
            description: __( 'This interactive guide will explain everything you need to know before creating a reward system.' ),
            steps: Guide::steps(
                Guide::step(
                    id: 'reward.name',
                    element: '[name="name"]',
                    popover: Guide::popover(
                        title: __( 'Reward Name' ),
                        description: __( 'This is the name of the reward system. It will be displayed to customers when they view their rewards.' ),
                    )
                ),
                Guide::step(
                    id: 'reward.coupon',
                    element: '[name="coupon_id"]',
                    popover: Guide::popover(
                        title: __( 'Coupon' ),
                        description: __( 'This is the coupon that will be generated when a customer reaches the target defined.' ),
                    )
                ),
                Guide::step(
                    id: 'reward.coupon.create',
                    element: '[name="coupon_id"] button',
                    popover: Guide::popover(
                        title: __( 'Create Coupon' ),
                        description: __( 'From this button you can directly create a coupon.' ),
                    )
                ),
                Guide::step(
                    id: 'reward.target',
                    element: '#target',
                    popover: Guide::popover(
                        title: __( 'Target' ),
                        description: __( 'This is the target in points, that a customer must reach to get the reward.' ),
                    )
                ),
                Guide::step(
                    id: 'reward.add.rule',
                    element: '#add-rule',
                    popover: Guide::popover(
                        title: __( 'Add Rule' ),
                        description: __( 'From this button you can add a new rule to the reward system.' ),
                    ),
                    nextAction: [
                        'type' => 'click',
                        'element' => '#add-rule',
                    ]
                ),
                Guide::step(
                    id: 'reward.rule.container',
                    element: '.rule-container',
                    popover: Guide::popover(
                        title: __( 'Rule Container' ),
                        description: __( 'This is the container that holds all the rules of the reward system. The "From" and "To" are amount spent ranges. The "From" will then always be less than the "To" and rules should not overlap.' ),
                    ),
                ),
                Guide::step(
                    id: 'reward.rule.from',
                    element: '#from',
                    waitForElement: 1000,
                    popover: Guide::popover(
                        title: __( 'From' ),
                        description: __( 'This is the starting point of the target range for the reward.' ),
                    )
                ),
                Guide::step(
                    id: 'reward.rule.to',
                    element: '#to',
                    waitForElement: 1000,
                    popover: Guide::popover(
                        title: __( 'To' ),
                        description: __( 'This is the ending point of the target range for the reward.' ),
                    )
                ),
                Guide::step(
                    id: 'reward.rule.points',
                    element: '[name="reward"]',
                    waitForElement: 1000,
                    popover: Guide::popover(
                        title: __( 'Points' ),
                        description: __( 'When the user reaches the target range, they will earn the specified points.' ),
                    )
                ),
            )
        ) );
    }
}