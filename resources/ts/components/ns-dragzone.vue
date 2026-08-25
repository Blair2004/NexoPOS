<template>
    <TransitionGroup
        tag="div"
        name="widget"
        move-class="ns-widget-move"
        ref="grid"
        class="ns-widget-grid"
        @pointerdown="handlePointerDown"
        @keydown="handleKeydown">
        <article
            v-for="widget in activeWidgets"
            :key="widget['component-name']"
            :data-widget-identifier="widget['component-name']"
            :data-widget-columns="widget.layout.columns"
            :class="{ 'is-dragging': draggedIdentifier === widget['component-name'] }"
            :style="widgetStyle(widget)"
            class="ns-widget-grid-item"
            tabindex="0"
            :aria-label="widget.name">
            <div class="ns-widget-grid-content">
                <component
                    :is="widget.component"
                    :widget="widget"
                    @onRemove="handleRemoveWidget(widget)">
                </component>
            </div>
        </article>

        <button
            v-if="hasUnusedWidgets"
            key="widget-add-button"
            type="button"
            class="widget-placeholder ns-widget-grid-add"
            @click="openWidgetAdded">
            <span class="text-sm text-fontcolor">{{ __( 'Click to add widgets' ) }}</span>
        </button>
    </TransitionGroup>
</template>

<script lang="ts">
import { shallowRef } from 'vue';
import { __ } from '~/libraries/lang';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import { nsSnackBar } from '~/bootstrap';
import { hasCrossedWidgetMidpoint, hasMovedBeyondReorderLock } from './widget-drag-intent.js';

declare const Popup;
declare const nsHttpClient;

type WidgetLayout = {
    name: string;
    columns: number;
    rows: number;
};

type DashboardWidget = {
    name: string;
    'component-name': string;
    'class-name': string;
    component: unknown;
    data: Record<string, unknown>;
    layout: WidgetLayout;
    suggestedLayout: WidgetLayout;
    supportedLayouts: WidgetLayout[];
    layoutPolicy: 'strict' | 'restricted' | 'unrestricted';
    requestLayoutChange: ( layoutName: string ) => void;
    position?: number;
};

type ReorderIntent = {
    identifier: string;
    clientX: number;
    clientY: number;
    timeoutId: number;
};

const reorderIntentDelay = 140;
const reorderUnlockDistance = 36;

const fallbackLayout = (): WidgetLayout => ({
    name: '1x1',
    columns: 1,
    rows: 1,
});

export default {
    name: 'ns-dragzone',
    props: [ 'raw-widgets', 'raw-columns' ],
    data() {
        return {
            widgets: [] as DashboardWidget[],
            activeWidgets: [] as DashboardWidget[],
            draggedIdentifier: null as string | null,
            layoutBeforeDrag: [] as DashboardWidget[],
            reorderIntent: null as ReorderIntent | null,
            reorderLock: null as { clientX: number, clientY: number } | null,
        };
    },
    mounted() {
        this.widgets = this.rawWidgets.map( widget => this.resolveWidget( widget ) );

        this.activeWidgets = this.rawColumns
            .flatMap( column => column.widgets )
            .map( storedWidget => {
                const registeredWidget = this.widgets.find( widget => widget['component-name'] === storedWidget.identifier );

                if ( ! registeredWidget ) {
                    return null;
                }

                const selectedLayout = registeredWidget.supportedLayouts.find( layout => layout.name === storedWidget.layout )
                    || registeredWidget.suggestedLayout;

                return {
                    ...registeredWidget,
                    layout: selectedLayout,
                    position: storedWidget.position,
                };
            })
            .filter( Boolean );
    },
    beforeUnmount() {
        this.cancelReorderIntent();
        this.removePointerListeners();
    },
    computed: {
        hasUnusedWidgets() {
            const activeIdentifiers = this.activeWidgets.map( widget => widget['component-name'] );

            return this.widgets.some( widget => ! activeIdentifiers.includes( widget['component-name'] ) );
        },
    },
    methods: {
        __,
        resolveWidget( widget ): DashboardWidget {
            const suggestedLayout = widget.layout || fallbackLayout();

            return {
                name: widget.name,
                'component-name': widget['component-name'],
                'class-name': widget['class-name'],
                component: shallowRef( window[widget['component-name']] ),
                data: widget.data || {},
                layout: suggestedLayout,
                suggestedLayout,
                supportedLayouts: widget['supported-layouts'] || [ suggestedLayout ],
                layoutPolicy: widget['layout-policy'] || 'strict',
                requestLayoutChange: ( layoutName: string ) => this.handleLayoutChange( widget['component-name'], layoutName ),
            };
        },
        widgetStyle( widget: DashboardWidget ) {
            return {
                '--widget-columns': widget.layout.columns,
                '--widget-rows': widget.layout.rows,
            };
        },
        handlePointerDown( event: PointerEvent ) {
            if ( event.button !== 0 || ! ( event.target as HTMLElement ).closest( '.widget-handle' ) ) {
                return;
            }

            const widgetElement = ( event.target as HTMLElement ).closest( '[data-widget-identifier]' ) as HTMLElement | null;

            if ( ! widgetElement ) {
                return;
            }

            event.preventDefault();
            this.layoutBeforeDrag = [ ...this.activeWidgets ];
            this.draggedIdentifier = widgetElement.dataset.widgetIdentifier || null;
            this.reorderLock = null;

            document.addEventListener( 'pointermove', this.handlePointerMove );
            document.addEventListener( 'pointerup', this.handlePointerUp, { once: true } );
            document.addEventListener( 'pointercancel', this.handlePointerCancel, { once: true } );
        },
        handlePointerMove( event: PointerEvent ) {
            if ( ! this.draggedIdentifier ) {
                return;
            }

            if ( this.reorderLock ) {
                if ( ! hasMovedBeyondReorderLock( event, this.reorderLock, reorderUnlockDistance ) ) {
                    this.cancelReorderIntent();

                    return;
                }

                this.reorderLock = null;
            }

            const hoveredElement = document
                .elementFromPoint( event.clientX, event.clientY )
                ?.closest( '[data-widget-identifier]' ) as HTMLElement | null;
            const hoveredIdentifier = hoveredElement?.dataset.widgetIdentifier;

            if ( ! hoveredIdentifier || hoveredIdentifier === this.draggedIdentifier ) {
                this.cancelReorderIntent();
                return;
            }

            if ( this.reorderIntent?.identifier === hoveredIdentifier ) {
                this.reorderIntent.clientX = event.clientX;
                this.reorderIntent.clientY = event.clientY;

                return;
            }

            this.cancelReorderIntent();

            const timeoutId = window.setTimeout( () => {
                this.commitReorderIntent();
            }, reorderIntentDelay );

            this.reorderIntent = {
                identifier: hoveredIdentifier,
                clientX: event.clientX,
                clientY: event.clientY,
                timeoutId,
            };
        },
        commitReorderIntent() {
            const intent = this.reorderIntent;
            this.reorderIntent = null;

            if ( ! intent || ! this.draggedIdentifier ) {
                return;
            }

            const hoveredElement = document
                .elementFromPoint( intent.clientX, intent.clientY )
                ?.closest( '[data-widget-identifier]' ) as HTMLElement | null;

            if ( hoveredElement?.dataset.widgetIdentifier !== intent.identifier ) {
                return;
            }

            const draggedElement = Array.from( this.$refs.grid.$el.querySelectorAll<HTMLElement>( '[data-widget-identifier]' ) )
                .find( element => element.dataset.widgetIdentifier === this.draggedIdentifier );
            const draggedIndex = this.activeWidgets.findIndex( widget => widget['component-name'] === this.draggedIdentifier );
            const hoveredIndex = this.activeWidgets.findIndex( widget => widget['component-name'] === intent.identifier );

            if ( ! draggedElement || draggedIndex < 0 || hoveredIndex < 0 ) {
                return;
            }

            if ( ! hasCrossedWidgetMidpoint( intent, draggedElement.getBoundingClientRect(), hoveredElement.getBoundingClientRect() ) ) {
                return;
            }

            const [ draggedWidget ] = this.activeWidgets.splice( draggedIndex, 1 );
            this.activeWidgets.splice( hoveredIndex, 0, draggedWidget );
            this.reorderLock = {
                clientX: intent.clientX,
                clientY: intent.clientY,
            };
        },
        cancelReorderIntent() {
            if ( this.reorderIntent ) {
                window.clearTimeout( this.reorderIntent.timeoutId );
                this.reorderIntent = null;
            }
        },
        handlePointerUp() {
            const hasChanged = this.identifiers( this.layoutBeforeDrag ) !== this.identifiers( this.activeWidgets );
            const previousLayout = [ ...this.layoutBeforeDrag ];

            this.finishDragging();

            if ( hasChanged ) {
                this.persistLayout( previousLayout );
            }
        },
        handlePointerCancel() {
            this.activeWidgets = [ ...this.layoutBeforeDrag ];
            this.finishDragging();
        },
        finishDragging() {
            this.cancelReorderIntent();
            this.draggedIdentifier = null;
            this.reorderLock = null;
            this.layoutBeforeDrag = [];
            this.removePointerListeners();
        },
        removePointerListeners() {
            document.removeEventListener( 'pointermove', this.handlePointerMove );
            document.removeEventListener( 'pointerup', this.handlePointerUp );
            document.removeEventListener( 'pointercancel', this.handlePointerCancel );
        },
        handleKeydown( event: KeyboardEvent ) {
            if ( ! event.altKey || ! [ 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown' ].includes( event.key ) ) {
                return;
            }

            const widgetElement = ( event.target as HTMLElement ).closest( '[data-widget-identifier]' ) as HTMLElement | null;
            const identifier = widgetElement?.dataset.widgetIdentifier;
            const currentIndex = this.activeWidgets.findIndex( widget => widget['component-name'] === identifier );
            const direction = [ 'ArrowLeft', 'ArrowUp' ].includes( event.key ) ? -1 : 1;
            const targetIndex = currentIndex + direction;

            if ( currentIndex < 0 || targetIndex < 0 || targetIndex >= this.activeWidgets.length ) {
                return;
            }

            event.preventDefault();
            const previousLayout = [ ...this.activeWidgets ];
            const [ widget ] = this.activeWidgets.splice( currentIndex, 1 );
            this.activeWidgets.splice( targetIndex, 0, widget );
            this.persistLayout( previousLayout );
        },
        identifiers( widgets: DashboardWidget[] ): string {
            return widgets.map( widget => widget['component-name'] ).join( ',' );
        },
        persistLayout( previousLayout: DashboardWidget[] ) {
            nsHttpClient.put( '/api/users/widgets', {
                widgets: this.activeWidgets.map( widget => ({
                    identifier: widget['component-name'],
                    layout: widget.layout.name === widget.suggestedLayout.name ? null : widget.layout.name,
                }) ),
            }).subscribe({
                error: error => {
                    this.activeWidgets = previousLayout;
                    nsSnackBar.error( error.message || __( 'An unexpected error occurred while saving the widget layout.' ) );
                },
            });
        },
        handleLayoutChange( identifier: string, selectedLayoutName: string ) {
            const widget = this.activeWidgets.find( activeWidget => activeWidget['component-name'] === identifier );

            if ( ! widget ) {
                return;
            }

            const selectedLayout = widget.supportedLayouts.find( layout => layout.name === selectedLayoutName );

            if ( ! selectedLayout || selectedLayout.name === widget.layout.name ) {
                return;
            }

            const previousLayout = [ ...this.activeWidgets ];
            const widgetIndex = this.activeWidgets.findIndex( activeWidget => activeWidget['component-name'] === widget['component-name'] );

            this.activeWidgets[widgetIndex] = {
                ...widget,
                layout: selectedLayout,
            };
            this.persistLayout( previousLayout );
        },
        handleRemoveWidget( widget: DashboardWidget ) {
            const previousLayout = [ ...this.activeWidgets ];
            this.activeWidgets = this.activeWidgets.filter( activeWidget => activeWidget !== widget );
            this.persistLayout( previousLayout );
        },
        async openWidgetAdded() {
            const activeIdentifiers = this.activeWidgets.map( widget => widget['component-name'] );
            const options = this.widgets
                .filter( widget => ! activeIdentifiers.includes( widget['component-name'] ) )
                .map( widget => ({ value: widget, label: widget.name }) );

            try {
                const selectedWidgets = await new Promise<DashboardWidget[]>( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        value: [],
                        resolve,
                        reject,
                        type: 'multiselect',
                        options,
                        label: __( 'Choose Widget' ),
                        description: __( 'Select the widgets you want to add to the dashboard.' ),
                    });
                });
                const previousLayout = [ ...this.activeWidgets ];

                this.activeWidgets = [ ...this.activeWidgets, ...selectedWidgets ];
                this.persistLayout( previousLayout );
            } catch ( exception ) {
                // Closing the selection popup does not change the dashboard.
            }
        },
    },
};
</script>

<style scoped>
.ns-widget-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-auto-flow: row dense;
    grid-auto-rows: 10rem;
    gap: 1rem;
}

.ns-widget-grid-item {
    display: flex;
    min-width: 0;
    grid-column: span var(--widget-columns);
    grid-row: span var(--widget-rows);
    transition: opacity 150ms ease;
}

.ns-widget-move {
    transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1);
}

:deep(.widget-handle) {
    touch-action: none;
}

.ns-widget-grid-item:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

.ns-widget-grid-item.is-dragging {
    opacity: 0.55;
}

.ns-widget-grid-content,
.ns-widget-grid-content > :first-child {
    display: flex;
    width: 100%;
    min-width: 0;
    height: 100%;
}

.ns-widget-grid-add {
    min-height: 4rem;
    grid-column: 1 / -1;
    border-width: 2px;
    border-style: dashed;
}

@media (max-width: 1023px) {
    .ns-widget-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ns-widget-grid-item[data-widget-columns="3"] {
        grid-column: span 2;
    }
}

@media (max-width: 767px) {
    .ns-widget-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .ns-widget-grid-item {
        grid-column: span 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .ns-widget-grid-item,
    .ns-widget-move {
        transition: none;
    }
}
</style>
