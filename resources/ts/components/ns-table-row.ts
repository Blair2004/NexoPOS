import Vue from 'vue';

import { nsEvent, nsHttpClient, nsSnackBar } from "@/bootstrap";
import { __ } from '@/libraries/lang';
import { Popup } from '@/libraries/popup';

const nsTableRow    =   Vue.component( 'ns-table-row', {
    props: [
        'options', 'row', 'columns', 'prependOptions', 'showOptions'
    ],
    data: () => {
        return {
            optionsToggled: false
        }
    },
    mounted() {
        
    },
    methods: {
        __,

        sanitizeHTML(s) {
            var div         = document.createElement('div');
            div.innerHTML   = s;
            var scripts     = div.getElementsByTagName('script');
            var i           = scripts.length;
            while (i--) {
              scripts[i].parentNode.removeChild(scripts[i]);
            }
            return div.innerHTML;
        },
        getElementOffset(el) {
            const rect = el.getBoundingClientRect();
            
            return {
                top: rect.top + window.pageYOffset,
                left: rect.left + window.pageXOffset,
            };
        },
        toggleMenu( element ) {
            this.row.$toggled   =   !this.row.$toggled;
            this.$emit( 'toggled', this.row );

            if ( this.row.$toggled ) {
                setTimeout(() => {
                    const dropdown                  =   this.$el.querySelectorAll( '.relative > .absolute' )[0];
                    const parent                    =   this.$el.querySelectorAll( '.relative' )[0];
                    const offset                    =   this.getElementOffset( parent );
                    dropdown.style.top              =   offset.top + 'px';
                    dropdown.style.left             =   offset.left + 'px';
                    
                    if ( parent !== undefined ) {
                        parent.classList.remove( 'relative' );
                        parent.classList.add( 'dropdown-holder' );
                    }
                }, 100 );
            } else {
                const parent                    =   this.$el.querySelectorAll( '.dropdown-holder' )[0];
                parent.classList.remove( 'dropdown-holder' );
                parent.classList.add( 'relative' );
            }
        },
        handleChanged( event ) {
            this.row.$checked   =   event;
            this.$emit( 'updated', this.row );
        },
        triggerAsync( action ) {
            if ( action.confirm ) {
                if ( confirm( action.confirm.message ) ) {
                    nsHttpClient[ action.type.toLowerCase() ]( action.url )
                        .subscribe( response => {
                            nsSnackBar.success( response.message )
                                .subscribe();
                            this.$emit( 'reload', this.row );
                        }, ( response ) => {
                            this.toggleMenu();
                            nsSnackBar.error( response.message ).subscribe();
                        })
                }
            } else {
                nsEvent.emit({
                    identifier: 'ns-table-row-action',
                    value: { action, row: this.row, component : this }
                });
                this.toggleMenu();
            }
        },

        /**
         * Will catch custom popup opening that define a component.
         * @param action an object that defined the action
         * @param row the object that has the actual row
         * @returns {mixed}
         */
        triggerPopup( action, row ) {
            const component     =   (<any>window).nsExtraComponents[ action.component ];

            /**
             * it might be relaying on manual popups.
             */
            if ( action.component ) {
                if ( component ) {
                    return new Promise( ( resolve, reject ) => {
                        Popup.show( component, { resolve, reject, row, action });
                    });
                } else {
                    return nsSnackBar.error( __( `Unable to load the component "${action.component}". Make sure the component is registered to "nsExtraComponents".` ) ).subscribe();
                }
            } else {
                this.triggerAsync( action );
            }
        }
    },
    template: `
    <tr class="ns-table-row" :class="row.$cssClass ? row.$cssClass : 'border text-sm'">
        <td class="font-sans p-2">
            <ns-checkbox @change="handleChanged( $event )" :checked="row.$checked"> </ns-checkbox>
        </td>
        <td v-if="prependOptions && showOptions" class="font-sans p-2">
            <div class=""> <!-- flex items-center justify-center -->
                <button @click="toggleMenu( $event )" :class="row.$toggled ? 'active': ''" class="ns-inset-button outline-none rounded-full w-24 text-sm p-1 border"><i class="las la-ellipsis-h"></i> {{ __( 'Options' ) }}</button>
                <div @click="toggleMenu( $event )" v-if="row.$toggled" class="absolute w-full h-full z-10 top-0 left-0"></div>
                <div class="relative">
                    <div v-if="row.$toggled" class="zoom-in-entrance border border-box-edge anim-duration-300 z-50 origin-bottom-right w-56 mt-2 absolute rounded-md shadow-lg ns-menu-wrapper">
                        <div class="rounded-md shadow-xs">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <template v-for="action of row.$actions">
                                    <a :href="action.url" v-if="action.type === 'GOTO'" class="ns-action-button block px-4 py-2 text-sm leading-5" role="menuitem" v-html="sanitizeHTML( action.label )"></a>
                                    <a href="javascript:void(0)" @click="triggerAsync( action )" v-if="[ 'GET', 'DELETE' ].includes( action.type )" class="ns-action-button block px-4 py-2 text-sm leading-5" role="menuitem" v-html="sanitizeHTML( action.label )"></a>
                                    <a href="javascript:void(0)" @click="triggerPopup( action, row )" v-if="[ 'POPUP' ].includes( action.type )" class="ns-action-button block px-4 py-2 text-sm leading-5" role="menuitem" v-html="sanitizeHTML( action.label )"></a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>
        <td v-for="(column, identifier) of columns" class="font-sans p-2">
            <template v-if="row[ identifier ] && row[ identifier ].type && row[ identifier ].type === 'link'">
                <a target="_blank" :href="row[ identifier ].href" v-html="sanitizeHTML( row[ identifier ].label )"></a>
            </template>
            <template v-if="typeof row[ identifier ] === 'string' || typeof row[ identifier ] === 'number'">
                <div v-html="sanitizeHTML( row[ identifier ] )"></div>
            </template>
            <template v-if="row[ identifier ] === null">
                <div>{{ __( 'Undefined' ) }}</div>
            </template>
        </td>
        <td v-if="!prependOptions && showOptions" class="font-sans p-2 flex flex-col items-center justify-center">
            <div class=""> <!-- flex items-center justify-center -->
                <button @click="toggleMenu( $event )" :class="row.$toggled ? 'active': ''" class="ns-inset-button outline-none rounded-full w-24 text-sm p-1 border"><i class="las la-ellipsis-h"></i> {{ __( 'Options' ) }}</button>
                <div @click="toggleMenu( $event )" v-if="row.$toggled" class="absolute w-full h-full z-10 top-0 left-0"></div>
                <div class="relative">
                    <div v-if="row.$toggled" class="zoom-in-entrance border border-box-edge anim-duration-300 z-50 origin-bottom-right -ml-28 w-56 mt-2 absolute rounded-md shadow-lg ns-menu-wrapper">
                        <div class="rounded-md shadow-xs">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                <template v-for="action of row.$actions">
                                    <a :href="action.url" :target="action.target || ''" v-if="action.type === 'GOTO'" class="ns-action-button block px-4 py-2 text-sm leading-5" role="menuitem" v-html="sanitizeHTML( action.label )"></a>
                                    <a href="javascript:void(0)" @click="triggerAsync( action )" v-if="[ 'GET', 'DELETE' ].includes( action.type )" class="ns-action-button block px-4 py-2 text-sm leading-5" role="menuitem" v-html="sanitizeHTML( action.label )"></a>
                                    <a href="javascript:void(0)" @click="triggerPopup( action, row )" v-if="[ 'POPUP' ].includes( action.type )" class="ns-action-button block px-4 py-2 text-sm leading-5" role="menuitem" v-html="sanitizeHTML( action.label )"></a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    `
})

export { nsTableRow }