<template>
    <div class="-mx-4 mt-4 flex flex-col md:flex-row-reverse flex-wrap">
        <template v-for="column in columns" :key="column.name">
            <draggable @change="handleChange( column, $event )" item-key="componentName" :group="column.parent" :list="column.widgets" class="mb-4 px-4 w-full lg:w-1/2 xl:w-1/3">
                <template #item="{element}">
                    <div class="mb-4">
                        <component @onRemove="handleRemoveWidget( element, column )" :is="element.component"></component>
                    </div>
                </template>
                <!-- v-if="column.widgets.length === 0" -->
                <template #footer>
                    <div @click="openWidgetAdded( column )" class="cursor-pointer border-2 border-dashed h-16 flex items-center justify-center">
                        <span class="text-sm text-primary" type="info">{{ __( 'Click here to add widgets' ) }}</span>
                    </div>
                </template>
            </draggable>
        </template>
    </div>
</template>
<script lang="ts">
import { shallowRef } from '@vue/reactivity';
import draggable from 'vuedraggable';
import { __ } from '~/libraries/lang';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import { nsSnackBar } from '~/bootstrap';

declare const Popup;

export default {
    name: 'ns-dragzone',
    props: [ 'raw-widgets', 'raw-columns' ],
    components: {
        draggable
    },
    data() {
        return {
            widgets: [],
            dragged: null,
            columns: [],
        }
    },
    mounted() {
        this.widgets     =   this.rawWidgets.map( widget => {
            return { 
                name: widget.name, 
                componentName: widget.component, 
                className: widget.className,
                component: shallowRef( window[ widget.component ])
            };
        });

        this.columns    =   this.rawColumns.map( column => {
            column.widgets.forEach( widget => {
                widget.component        =   shallowRef( window[ widget.identifier ] );
                widget.componentName    =   widget.identifier;
                widget.className        =   widget.class_name;
            });

            return column;
        });
    },
    methods: {
        __,
        handleChange( column, event ) {
            setTimeout( () => {
                nsHttpClient.post( '/api/users/widgets', { column })
                    .subscribe( result => {
                        // ...
                    }, error => {
                        return nsSnackBar.error( error.message || __( 'An unpexpected error occured while using the widget.' ) ).subscribe();
                    })
            }, 100 );
        },
        handleRemoveWidget( widget, column ) {
            const index     =   column.widgets.indexOf( widget );
            column.widgets.splice( index, 1 );

            this.handleChange( column );
        },
        async openWidgetAdded( column ) {
            try {
                /**
                 * We want to get the widgets that are already used
                 * and make sure not to allow a new usage.
                 */
                const alreadyUsedWidgets     =   this.columns.filter( _column => {
                    if ( _column.name !== column.name ) {
                        return _column.widgets.length > 0;
                    }
                    return false;
                }).map( _column => _column.widgets ).flat();

                const currentlyUsedWidgetNames   =   column.widgets.map( widget => widget.componentName );

                const notUsedWidgets   =   this.widgets.filter( widget => {
                    const namesOnly     =   alreadyUsedWidgets.map( widget => widget.componentName );
                    return ! namesOnly.includes( widget.componentName );
                })
                .map( widget => {
                    return {
                        value: widget,
                        label: widget.name
                    }
                });

                const widgets    =   await new Promise( ( resolve, reject ) => {
                    const value     =   notUsedWidgets.filter( widget => {
                        return currentlyUsedWidgetNames.includes( widget.componentName );
                    });

                    Popup.show( nsSelectPopupVue, {
                        value,
                        resolve, 
                        reject,
                        type: 'multiselect',
                        options: notUsedWidgets,
                        label: __( 'Choose Widget' ),
                        description: __( 'Select with widget you want to add to the column.' )
                    });
                }); 
                
                const index     =   this.columns.indexOf( column );
                this.columns[ index ].widgets  =   widgets;
                this.handleChange( this.columns[ index ] );
                
            } catch( exception ) {
                console.log( exception );
            }
        },
    }
}
</script>