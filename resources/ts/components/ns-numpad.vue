<template>
    <div id="numpad" class="grid grid-flow-row divide-x divide-y border-r border-b border-input-edge grid-cols-3 grid-rows-3">
        <div 
            @click="inputValue( key )"
            :key="index"
            v-for="(key,index) of keys" 
            :class="index === 0 ? 'border-l border-t' : ''"
            class="select-none ns-numpad-key h-24 font-bold flex items-center justify-center cursor-pointer">
            <span v-if="key.value !== undefined">{{ key.value }}</span>
            <i v-if="key.icon" class="las" :class="key.icon"></i>
        </div>
        <slot name="numpad-footer"></slot>
    </div>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';

declare const ns, nsHotPress, nsState;

export default {
    name: 'ns-numpad',
    props: [ 'value', 'currency', 'floating', 'limit' ],
    data() {
        return {
            number: parseInt( 
                1 + ( new Array( parseInt( ns.currency.ns_currency_precision ) ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            ),
            screenValue: 0,
            order: null,
            popupSubscription: null,
            cursor: parseInt( ns.currency.ns_currency_precision ),
            orderSubscription: null,
            allSelected: true,
            keys: [
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 },{ identifier: 'next', value: __( 'Enter' ) }],
            ]
        }
    },
    unmounted() {
        if ( this.popupSubscription ) {
            this.popupSubscription.unsubscribe();
        }
    },
    mounted() {
        if( this.floating && this.value > 0 ) {
            this.screenValue    =   parseFloat( this.value * this.number );
        } else {
            this.screenValue    =   this.value || 0;
        }

        /**
         * will bind keyboard event listening
         */
        const numbers   =   ( new Array(10) ).fill('').map( ( v,i ) => i );

        // will trigger if any state changes occurs on nsState
        this.popupSubscription  =   nsState.subscribe( state => {
            setTimeout( () => {
                const isInPopup     =   this.$el.closest('.is-popup');
                this.numpadKeyboardWorking = ( isInPopup && isInPopup.getAttribute('focused') === 'true' );
            }, 100 );
        });


        /**
         * We'll no check if the popup is focused 
         * or if it's active
         */        
        nsHotPress
            .create( 'numpad-keys' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( numbers, ( event, value ) => {
                if ( this.numpadKeyboardWorking ) {
                    this.inputValue({ value });
                }
            })

        nsHotPress
            .create( 'numpad-backspace' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( 'backspace', () => {
                if ( this.numpadKeyboardWorking ) {
                    this.inputValue({ identifier: 'backspace' });
                }
            })

        nsHotPress
            .create( 'numpad-increase' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( '+', () => {
                if ( this.numpadKeyboardWorking ) {
                    this.increaseBy({ value: 1 });
                }
            })

        nsHotPress
            .create( 'numpad-reduce' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( '-', () => {
                if ( this.numpadKeyboardWorking ) {
                    this.increaseBy({ value: -1 });
                }
            })

        nsHotPress
            .create( 'numpad-save' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( 'enter', () => {
                if ( this.numpadKeyboardWorking ) {
                    this.inputValue({ identifier: 'next' });
                }
            });
    },
    watch: {
        value() {
            if ( this.value.toString().length > 0 ) {
                if ( this.floating ) {
                    this.screenValue    =   Math.round( this.value * this.number ).toString();
                } else {
                    this.screenValue    =   this.value;
                }
            } else {
                this.screenValue    =   '';
            }
        }
    },
    beforeDestroy() {
        nsHotPress.destroy( 'numpad-backspace' );
        nsHotPress.destroy( 'numpad-increase' );
        nsHotPress.destroy( 'numpad-reduce' );
        nsHotPress.destroy( 'numpad-save' );
    },
    methods: {
        increaseBy( key ) {
            let number    =   parseInt( 
                1 + ( new Array( this.cursor ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            );

            this.screenValue      =   (( parseFloat( key.value ) * number ) + ( parseFloat( this.screenValue ) || 0 ) ).toString();
            this.allSelected    =   false;
        },

        inputValue( key ) {
            // count a screenValue numbers if it's a string, if it's not, let's convert it to string first
            if ( typeof this.screenValue !== 'string' ) {
                this.screenValue    =   this.screenValue.toString();
            }

            let number    =   parseInt( 
                1 + ( new Array( this.cursor ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            );

            if ( key.identifier === 'next' ) {
                this.$emit( 'next', this.floating && this.screenValue.length > 0 ? parseFloat( this.screenValue / number ) : this.screenValue );
                return;
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.screenValue    =   '0';
                    this.allSelected    =   false;
                } else {
                    this.screenValue    =   this.screenValue.substr( 0, this.screenValue.length - 1 );
                }
            } else if ( key.value.toString().match( /^\d+$/ ) ) {
                if ( this.limit > 0 && this.screenValue.length >= this.limit ) {
                    return;
                }
                
                if ( this.allSelected ) {
                    this.screenValue      =   key.value.toString();
                    this.allSelected    =   false;
                } else {
                    this.screenValue      +=  '' + key.value.toString();

                    if ( this.mode === 'percentage' ) {
                        this.screenValue = this.screenValue > 100 ? 100 : this.screenValue;
                    }
                }
            } 

            const emitted   =   this.floating && this.screenValue.length > 0 && this.screenValue !== '0' ? parseFloat( this.screenValue / this.number ) : this.screenValue;

            this.$emit( 'changed', emitted );
        }
    }
}
</script>