<template>
    <div class="ns-box shadow min-h-2/5-screen w-3/4-screen md:w-3/5-screen lg:w-3/5-screen xl:w-2/5-screen relative">
        <div class="flex-shrink-0 flex p-2 border-b ns-box-header justify-between items-center">
            <h1 class="text-xl font-bold text-primary text-center">{{ __( 'Define Quantity' ) }}</h1>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div id="screen" class="h-16 ns-box-body flex items-center justify-center">
            <h1 class="font-bold text-3xl">{{ finalValue }}</h1>
        </div>
        <div id="numpad" class="grid grid-flow-row grid-cols-3 grid-rows-3">
            <div 
                @click="inputValue( key )"
                :key="index" 
                v-for="(key,index) of keys" 
                class="text-xl font-bold border ns-numpad-key h-24 flex items-center justify-center cursor-pointer">
                <span v-if="key.value !== undefined">{{ key.value }}</span>
                <i v-if="key.icon" class="las" :class="key.icon"></i>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-procurement-quantity',
    props: [ 'popup' ],
    data() {
        return {
            finalValue: 1,
            virtualStock: null,
            allSelected: true,
            isLoading: false,
            keys: [
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    },
    mounted() {
        /**
         * if the quantity is defined, then probably
         * we're already trying to edit an existing product
         */
        if ( this.popup.params.quantity ) {
            this.finalValue     =   this.popup.params.quantity;
        }

        document.addEventListener( 'keyup', this.handleKeyPress );
    },
    unmounted() {
        document.removeEventListener( 'keypress', this.handleKeyPress );
    },
    methods: {
        __,
        handleKeyPress( event ) {
            if ( event.keyCode === 13 ) {
                this.inputValue({ identifier : 'next' });
            }
        },

        closePopup() {
            /**
             * as this runs under a Promise
             * we need to make sure that
             * it resolve false using the "resolve" function
             * provided as "popup.params".
             * Here we resolve "false" as the user has broken the Promise
             */
            this.popup.params.reject( false );

            /**
             * we can safely close the popup.
             */
            this.popup.close();
        },
        
        inputValue( key ) {
            if ( key.identifier === 'next' ) {
                /**
                 * resolve is provided only on the addProductQueue
                 */
                const { product, data }         =   this.popup.params;
                const quantity                  =   parseFloat( this.finalValue );

                if ( quantity === 0 ) {
                    return nsSnackBar.error( __( 'Please provide a quantity' ) )
                        .subscribe();
                }

                this.resolve({ quantity });

            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.finalValue     =   0;
                    this.allSelected    =   false;
                } else {
                    this.finalValue     =   this.finalValue.toString();
                    this.finalValue     =   this.finalValue.substr(0, this.finalValue.length - 1 ) || 0;
                }
            } else {
                if ( this.allSelected ) {
                    this.finalValue     =   key.value;
                    this.finalValue     =   parseFloat( this.finalValue );
                    this.allSelected    =   false;
                } else {
                    this.finalValue     +=  '' + key.value;
                    this.finalValue     =   parseFloat( this.finalValue );
                }
            } 
        },

        resolve( params ) {
            this.popup.params.resolve( params );
            this.popup.close();
        }
    }
}
</script>