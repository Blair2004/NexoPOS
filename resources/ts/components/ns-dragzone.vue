<template>
    <div class="flex md:-mx-2 flex-wrap">
        <div class="w-full md:px-2 md:w-1/2 lg:w-1/3 xl:1/4" :column-name="column.name" v-for="(column,index) in columns" :key="column.name">
            <!-- v-for="widget in column.widets" -->
            <template v-for="(widget,key) in column.widgets">
                <div :data-index="key" class="intermediate-dropzone anim-duration-500">
                    <div></div>
                </div>
                <ns-dropzone>
                    <ns-draggable 
                        :component-name="widget[ 'component-name' ]"
                        @drag-start="handleStartDragging( $event )"
                        @drag-end="handleEndDragging( $event )"
                        :widget="widget">
                        <component @onRemove="handleRemoveWidget( widget, column )" :is="widget.component" :widget="widget"></component>
                    </ns-draggable>
                </ns-dropzone>
            </template>
            <div v-if="hasUnusedWidgets" @click="openWidgetAdded( column )" class="widget-placeholder cursor-pointer border-2 border-dashed h-16 flex items-center justify-center">
                <span class="text-sm text-font" type="info">{{ __( 'Drop widget / click to add' ) }}</span>
            </div>
            <div v-if="isDragging && ! hasUnusedWidgets !" class="widget-placeholder cursor-pointer border-2 border-dashed h-16 flex items-center justify-center">
                <span class="text-sm text-font" type="info">{{ __( 'Drop the widget here' ) }}</span>
            </div>
        </div>
    </div>
</template>
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
            isDragging: false,
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

        document.addEventListener( 'mousemove', ( event ) => {
            if ( this.isDragging === false ) {
                return;
            }
            
            const intermediateDropZones    =   document.querySelectorAll( '.intermediate-dropzone' );
           
            intermediateDropZones.forEach((dropZone,index) => {
                const position = dropZone.getBoundingClientRect();
                const { left, top, right, bottom } = position;
                const { clientX, clientY } = event;
    
                if (clientX >= left && clientX <= right && clientY >= top && clientY <= bottom) {
                    dropZone.setAttribute( 'hovered', 'true' );
                    dropZone.classList.add( 'slide-fade-entrance' );
                } else {
                    dropZone.setAttribute( 'hovered', 'false' );
                    dropZone.classList.remove( 'slide-fade-entrance' );
                }
            });

            const staticPlaceholder     =   document.querySelectorAll( '.widget-placeholder' );

            staticPlaceholder.forEach((placeholder) => {
                const position = placeholder.getBoundingClientRect();
                const { left, top, right, bottom } = position;
                const { clientX, clientY } = event;

                if (clientX >= left && clientX <= right && clientY >= top && clientY <= bottom) {
                    placeholder.setAttribute( 'hovered', 'true' );
                } else {
                    placeholder.setAttribute( 'hovered', 'false' );
                }
            });
        })
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
        handleStartDragging( event ) {
            this.isDragging     =   true;
        },
        
        handleEndDragging( widget ) {
            this.isDragging     =   false;
            const hasPlaceholderHovered     =   document.querySelector( '.widget-placeholder[hovered="true"]' );
            const hasIntermediateHovered    =   document.querySelector( '.intermediate-dropzone[hovered="true"]' );

            if ( hasPlaceholderHovered ) {
                this.processAppendingToColumn( widget );
            } else if ( hasIntermediateHovered ) {
                this.proceedMoveToSpecificLocation( widget, hasIntermediateHovered );
            } else {
                this.processRegularWidgetSwapping( widget );                
            }

            this.removeSpecialEffect();
        },

        removeSpecialEffect() {
            // we'll remove the attribute style to all .ns-draggable-item
            const draggableItems    =   document.querySelectorAll( '.ns-draggable-item' );
            draggableItems.forEach( item => {
                item.removeAttribute( 'style' );
            });
        },

        proceedMoveToSpecificLocation( widget, hoveredIntermediate ) {
            const hoveredIndex  =   hoveredIntermediate.getAttribute( 'data-index' );

            const previousColumn    =   this.columns.filter( column => column.name === widget.column )[0];
            const index             =   previousColumn.widgets.indexOf( widget );
            previousColumn.widgets.splice( index, 1 );

            const columnName    =   hoveredIntermediate.closest( '[column-name]' ).getAttribute( 'column-name' );
            const hoveredColumn     =   this.columns.filter( column => column.name === columnName )[0];
            
            widget.position     =   hoveredIndex;
            widget.column       =   hoveredColumn.name;

            hoveredColumn.widgets.splice( hoveredIndex, 0, widget );

            this.handleChange( hoveredColumn );
            this.handleChange( previousColumn );

            // we should close the overed intermediate dropzone
            hoveredIntermediate.setAttribute( 'hovered', 'false' );
        },

        processAppendingToColumn( widget ) {
            const hoveredZone   =   document.querySelector( '.widget-placeholder[hovered="true"]' );

            if ( hoveredZone ) {
                // let's remove the widget from the previous column
                const previousColumn    =   this.columns.filter( column => column.name === widget.column )[0];
                const index             =   previousColumn.widgets.indexOf( widget );
                previousColumn.widgets.splice( index, 1 );

                // let's add the widget to the new column
                const columnName    =   hoveredZone.closest( '[column-name]' ).getAttribute( 'column-name' );
                const column        =   this.columns.filter( column => column.name === columnName )[0];

                widget.position     =   column.widgets.length;
                widget.column       =   columnName;

                column.widgets.push( widget );

                this.handleChange( column );
                this.handleChange( previousColumn );
            }
        },

        processRegularWidgetSwapping( widget ) {
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

                hoveredZone.setAttribute( 'hovered', 'false' );

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
                        return nsSnackBar.error( error.message || __( 'An unpexpected error occured while using the widget.' ) );
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