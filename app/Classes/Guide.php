<?php

namespace App\Classes;

use InvalidArgumentException;

class Guide
{
    public static function experience(
        string $id,
        string $title,
        string $description,
        array $permissions = [],
        ?array $required_routes = null,
        ?string $required_path = '',
        array $steps = [],
        ?string $version = null
    ): array {
        if ( trim( $id ) === '' ) {
            throw new InvalidArgumentException(
                'A guide experience must have a valid ID.'
            );
        }

        return self::clean( [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'permissions' => $permissions,
            'required_routes' => $required_routes,
            'required_path' => $required_path,
            'steps' => $steps,
            'version' => $version ?? '1.0.0',
        ] );
    }

    /**
     * Creates a guide step.
     *
     * Most properties intentionally follow Driver.js DriveStep.
     *
     * NexoPOS additions:
     * - id
     * - route
     * - nextAction
     */
    public static function step(
        string $id,
        ?string $element = null,
        ?array $popover = null,
        ?string $route = null,
        bool $disableActiveInteraction = false,
        bool $advanceOnClick = false,
        bool $skipMissingElement = false,
        int $waitForElement = 0,
        array $data = [],
        ?array $nextAction = null
    ): array {
        if ( trim( $id ) === '' ) {
            throw new InvalidArgumentException(
                'A guide step must have a valid ID.'
            );
        }

        self::validateAction( $nextAction );

        return self::clean( [
            'id' => $id,
            'element' => $element,
            'popover' => $popover,
            'route' => $route,

            /**
             * Driver.js properties.
             */
            'disableActiveInteraction' => $disableActiveInteraction,
            'advanceOnClick' => $advanceOnClick,
            'skipMissingElement' => $skipMissingElement,
            'waitForElement' => max( 0, $waitForElement ),

            /**
             * Arbitrary module/frontend information.
             */
            'data' => $data,

            /**
             * NexoPOS declarative action that should run
             * when the Driver.js Next button is clicked.
             */
            'nextAction' => $nextAction,
        ] );
    }

    /**
     * Creates a Driver.js-compatible popover.
     */
    public static function popover(
        ?string $title = null,
        ?string $description = null,
        string $side = 'bottom',
        string $align = 'start',
        ?array $showButtons = null,
        ?array $disableButtons = null,
        ?string $nextBtnText = null,
        ?string $prevBtnText = null,
        ?string $doneBtnText = null,
        ?bool $showProgress = null,
        ?string $progressText = null,
        ?string $popoverClass = null
    ): array {
        if ( ! in_array( $side, [
            'top',
            'right',
            'bottom',
            'left',
        ], true ) ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid guide popover side [%s].',
                    $side
                )
            );
        }

        if ( ! in_array( $align, [
            'start',
            'center',
            'end',
        ], true ) ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid guide popover alignment [%s].',
                    $align
                )
            );
        }

        self::validateButtons( $showButtons );
        self::validateButtons( $disableButtons );

        return self::clean( [
            'title' => $title,
            'description' => $description,

            'side' => $side,
            'align' => $align,

            'showButtons' => $showButtons,
            'disableButtons' => $disableButtons,

            'nextBtnText' => $nextBtnText,
            'prevBtnText' => $prevBtnText,
            'doneBtnText' => $doneBtnText,

            'showProgress' => $showProgress,
            'progressText' => $progressText,

            'popoverClass' => $popoverClass,
        ] );
    }

    /**
     * Creates a collection of guide steps.
     */
    public static function steps( array ...$steps ): array
    {
        return $steps;
    }

    /**
     * Creates a click action.
     *
     * When element is null, the frontend may use the
     * currently highlighted element as the target.
     */
    public static function click(
        ?string $element = null
    ): array {
        return self::clean( [
            'type' => 'click',
            'element' => $element,
        ] );
    }

    /**
     * Creates an informational step without a highlighted element.
     */
    public static function modal(
        string $id,
        array $popover,
        ?string $route = null,
        array $data = [],
        ?array $nextAction = null
    ): array {
        return self::step(
            id: $id,
            element: null,
            popover: $popover,
            route: $route,
            data: $data,
            nextAction: $nextAction
        );
    }

    /**
     * Remove null values while preserving:
     *
     * false
     * 0
     * []
     */
    protected static function clean( array $data ): array
    {
        return array_filter(
            $data,
            fn ( $value ) => $value !== null
        );
    }

    /**
     * Validate Driver.js popover buttons.
     */
    protected static function validateButtons(
        ?array $buttons
    ): void {
        if ( $buttons === null ) {
            return;
        }

        $allowed = [
            'next',
            'previous',
            'close',
        ];

        foreach ( $buttons as $button ) {
            if ( ! in_array( $button, $allowed, true ) ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid Driver.js button [%s].',
                        $button
                    )
                );
            }
        }
    }

    /**
     * Validate a declarative NexoPOS guide action.
     */
    protected static function validateAction(
        ?array $action
    ): void {
        if ( $action === null ) {
            return;
        }

        if ( ! isset( $action['type'] ) ) {
            throw new InvalidArgumentException(
                'A guide action must define a type.'
            );
        }

        $allowed = [
            'click',
        ];

        if ( ! in_array(
            $action['type'],
            $allowed,
            true
        ) ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported guide action [%s].',
                    $action['type']
                )
            );
        }
    }
}
