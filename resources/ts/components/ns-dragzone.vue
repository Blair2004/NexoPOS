<template>
    <div class="flex md:-mx-2 flex-wrap">
        <div class="w-full md:px-2 md:w-1/2 lg:w-1/3 xl:1/4" :column-name="column.name" v-for="(column,index) in columns" :key="column.name">
            <!-- v-for="widget in column.widets" -->
            <ns-dropzone v-for="widget in column.widgets">
                <ns-draggable 
                    :component-name="widget[ 'component-name' ]"
                    @drag-end="handleEndDragging( $event )"
                    :widget="widget">
                    <component @onRemove="handleRemoveWidget( widget, column )" :is="widget.component" :widget="widget"></component>
                </ns-draggable>
            </ns-dropzone>
            <div v-if="hasUnusedWidgets" @click="openWidgetAdded( column )" class="widget-placeholder cursor-pointer border-2 border-dashed h-16 flex items-center justify-center">
                <span class="text-sm text-primary" type="info">{{ __( 'Click here to add widgets' ) }}</span>
            </div>
        </div>
    </div>
</template>
<style scoped>
.light .widget-placeholder {
    @apply border-slate-600;
}
.dark .widget-placeholder {
    @apply border-slate-400;
}
</style>
<script lang="ts">
import { shallowRef } from '@vue/reactivity';
import { __ } from '~/libraries/lang';
import nsSelectPopupVue from '~/popups/ns-select-popup.vue';
import { nsSnackBar } from '~/bootstrap';
import nsDropzone from '~/components/ns-dropzone.vue';
import nsDraggable from '~/components/ns-draggable.vue';

declare const Popup;
declare const ns;

export default {
    name: 'ns-dragzone',
    props: [ 'raw-widgets', 'raw-columns' ],
    components: {
        nsDropzone,
        nsDraggable
    },
    data() {
        return {
            widgets: [],
            theme: ns.theme,
            dragged: null,
            columns: [],
        }
    },
    mounted() {
        this.widgets     =   this.rawWidgets.map( widget => {
            return { 
                name: widget.name, 
                'component-name': widget[ 'component-name' ], 
                'class-name' : widget[ 'class-name' ],
                component: shallowRef( window[ widget[ 'component-name' ] ])
            };
        });

        this.columns    =   this.rawColumns.map( column => {
            column.widgets.forEach( widget => {
                widget.component            =   shallowRef( window[ widget.identifier ] );
                widget[ 'class-name' ]      =   widget.class_name;
                widget[ 'component-name' ]  =   widget.identifier;
            });

            return column;
        });

        setTimeout( () => {
            // Select all elements whose parent has the class '.widget-placeholder'
            var elements = document.querySelectorAll('.widget-placeholder');

            document.addEventListener('mousemove', (event) => {
                // Loop over all elements
                for (var i = 0; i < elements.length; i++) {
                    // Get the bounding rectangle of the current element
                    var rect = elements[i].getBoundingClientRect();

                    // Check if the mouse coordinates are within the bounding rectangle
                    if (event.clientX >= rect.left && event.clientX <= rect.right &&
                        event.clientY >= rect.top && event.clientY <= rect.bottom) {
                        // The mouse is currently above the current element
                        elements[i].setAttribute( 'hovered', 'true' );
                        break;
                    } else {
                        elements[i].setAttribute( 'hovered', 'false' );
                    }
                }
            });
        }, 10 );
    },
    computed: {
        hasUnusedWidgets() {
            const alreadyUsedWidgets     =   this.columns.map( _column => _column.widgets ).flat();

            return this.widgets.filter( widget => {
                const namesOnly     =   alreadyUsedWidgets.map( widget => widget[ 'component-name' ] );
                return ! namesOnly.includes( widget[ 'component-name' ] );
            }).length > 0;
        }
    },
    methods: {
        __,
        
        handleEndDragging( widget ) {
            const hoveredZone   =   document.querySelector( '.ns-drop-zone[hovered="true"]' );

            if ( hoveredZone ) {
                const hoveredColumnElement      =   hoveredZone.closest( '[column-name]' );
                const hoveredColumnName         =   hoveredColumnElement.getAttribute( 'column-name' )
                const hoveredFilteredColumn     =   this.columns.filter( column => column.name === hoveredColumnName );
                const hoveredWidget             =   hoveredFilteredColumn[0].widgets.filter( __widget => {
                    return __widget[ 'component-name' ]  === hoveredZone.querySelector( '.ns-draggable-item' ).getAttribute( 'component-name' );
                });

                const previousWidgetElement     =   document.querySelector( `[component-name="${widget[ 'component-name' ]}"]`);
                const previousColumnElement     =   previousWidgetElement.closest( '[column-name]' );
                const previousColumnName        =   previousColumnElement.getAttribute( 'column-name' );
                const previousFilteredColumn    =   this.columns.filter( column => column.name === previousColumnName );
                const previousWidget            =   previousFilteredColumn[0].widgets.filter( __widget => {
                    return __widget[ 'component-name' ]  === previousWidgetElement.getAttribute( 'component-name' );
                });

                /**
                 * If the previous widget is the same as the hovered widget, we don't need to do anything.
                 */
                if ( previousWidget[0][ 'component-name' ] === hoveredWidget[0][ 'component-name' ] ) {
                    return;
                }

                const previousPosition          =   previousWidget[0].position;
                const hoveredPosition           =   hoveredWidget[0].position;

                previousWidget[0].column        =   hoveredColumnName;
                previousWidget[0].position      =   hoveredPosition;

                hoveredWidget[0].column         =   previousColumnName;
                hoveredWidget[0].position       =   previousPosition;

                hoveredFilteredColumn[0].widgets[hoveredPosition]       =   previousWidget[0];
                previousFilteredColumn[0].widgets[previousPosition]     =   hoveredWidget[0];

                this.handleChange( hoveredFilteredColumn[0] );
                this.handleChange( previousFilteredColumn[0] );

                hoveredZone.setAttribute( 'hovered', 'false' );
            }

            const hoveredPlaceHolderZone    =   document.querySelector( '.widget-placeholder[hovered="true"]' );

            if ( hoveredPlaceHolderZone ) {

                const columnName    =   hoveredPlaceHolderZone.closest( '[column-name]' ).getAttribute( 'column-name' );

                if ( widget === columnName ) {
                    console.log( 'The widget is already in the same column.' );
                    return;
                }

                // let's remove the widget from the previous column
                const previousColumn    =   this.columns.filter( column => column.name === widget.column )[0];
                const index             =   previousColumn.widgets.indexOf( widget );
                previousColumn.widgets.splice( index, 1 );

                const column        =   this.columns.filter( column => column.name === columnName )[0];
                widget.position     =   column.widgets.length;
                widget.column       =   columnName;

                column.widgets.push( widget );

                this.handleChange( previousColumn );
                this.handleChange( column );
            }
        },

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
                const otherColumnUsedWidgets     =   this.columns.filter( _column => {
                    if ( _column.name !== column.name ) {
                        console.log( _column.name );
                        return _column.widgets.length > 0;
                    }
                    return false;
                }).map( _column => _column.widgets ).flat();

                const columnUsedWidgetNames   =   column.widgets.map( widget => widget[ 'component-name' ] );

                const notUsedWidgets   =   this.widgets.filter( widget => {
                    const namesOnly     =   otherColumnUsedWidgets.map( widget => widget[ 'component-name' ] );
                    namesOnly.push( ...columnUsedWidgetNames );
                    return ! namesOnly.includes( widget[ 'component-name' ] );
                })
                .map( widget => {
                    return {
                        value: widget,
                        label: widget.name
                    }
                });

                const widgets    =   await new Promise<any[]>( ( resolve, reject ) => {
                    const value     =   notUsedWidgets.filter( widget => {
                        return columnUsedWidgetNames.includes( widget[ 'component-name' ] );
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

                this.columns[ index ].widgets  =   [ ...this.columns[ index ].widgets, ...widgets ].map( (widget, index) => {
                    widget.position     =   index;
                    widget.column       =   column.name;
                    return widget;
                });

                this.handleChange( this.columns[ index ] );
                
            } catch( exception ) {
                console.log( exception );
            }
        },
    }
}
</script>