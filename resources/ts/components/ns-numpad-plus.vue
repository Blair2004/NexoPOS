<template>
    <div id="numpad-holder" class="border-t border-numpad-edge">
        <div v-for="(keys,index) of keyRows" :key="index">
            <div id="numpad" class="grid grid-flow-row grid-cols-3 grid-rows-1 text-lg border-r border-numpad-edge">
                    <div 
                    @click="inputValue( key )"
                    :key="index" 
                    :class="keys.length === 1 ? 'col-span-3' : ''"
                    v-for="(key,index) of keys" 
                    class="select-none ns-numpad-key border-l border-b h-24 font-bold flex items-center justify-center cursor-pointer">
                        <span v-if="key.value !== undefined">{{ key.value }}</span>
                        <i v-if="key.icon" class="las" :class="key.icon"></i>
                    </div>
                <slot name="numpad-footer"></slot>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-numpad-plus',
    props: [ 'value', 'currency', 'limit' ],
    data() {
        return {
            order: null,
            cursor: parseInt( ns.currency.ns_currency_precision ),
            orderSubscription: null,
            allSelected: true,
            keyRows: [
                ([7,8,9].map( key => ({ identifier: key, value: key }))),
                ([4,5,6].map( key => ({ identifier: key, value: key }))),
                ([1,2,3].map( key => ({ identifier: key, value: key }))),
                [{ identifier: '.', value: '.' },{ identifier: 0, value: 0 },{ identifier: 'backspace', icon : 'la-backspace' }],
                [{ identifier: 'next', value: __( 'Enter' ) }]
            ]
        }
    },
    mounted() {
        /**
         * will bind keyboard event listening
         */
        const numbers   =   ( new Array(10) ).fill('').map( ( v,i ) => i );

        nsHotPress
            .create( 'numpad-keys' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( numbers, ( event, value ) => {
                this.inputValue({ value });
            })

        nsHotPress
            .create( 'numpad-backspace' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( 'backspace', () => this.inputValue({ identifier: 'backspace' }))

        nsHotPress
            .create( 'numpad-increase' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( '+', () => this.increaseBy({ value: 1 }))

        nsHotPress
            .create( 'numpad-dot' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( '.', () => this.inputValue({ identifier: '.' }))

        nsHotPress
            .create( 'numpad-reduce' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( '-', () => this.increaseBy({ value: -1 }))

        nsHotPress
            .create( 'numpad-save' )
            .whenVisible([ '.is-popup' ])
            .whenPressed( 'enter', () => this.inputValue({ identifier: 'next' }))
    },
    beforeDestroy() {
        nsHotPress.destroy( 'numpad-backspace' );
        nsHotPress.destroy( 'numpad-increase' );
        nsHotPress.destroy( 'numpad-reduce' );
        nsHotPress.destroy( 'numpad-save' );
        nsHotPress.destroy( 'numpad-dot' );
    },
    methods: {
        increaseBy( key ) {
            this.$emit( 'changed', ( parseFloat( key.value ) + ( parseFloat( this.value ) || 0 ) ).toString() )
            this.allSelected    =   false;
        },

        inputValue( key ) {
            let value   =   this.value;
            
            if ( key.identifier === 'next' ) {
                this.$emit( 'next', this.value );
                return;
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    value    =   '0';
                    this.allSelected    =   false;
                } else {
                    value    =   this.value.toString().substr( 0, this.value.length - 1 );
                }
            } else if ( key.identifier === '.' ) {
                if ( this.allSelected ) {
                    value    =   '0.';
                    this.allSelected    =   false;
                } else {
                    if ( value.toString().match( /^[0-9][1-9]*\.[0-9]*$/) === null ) {
                        value    +=  '.';
                    }
                }
            } else if ( key.value.toString().match( /^\d+$/ ) ) {
                if ( this.limit > 0 && this.value.length >= this.limit ) {
                    return;
                }
                
                if ( this.allSelected ) {
                    value      =   key.value.toString();
                    this.allSelected    =   false;
                } else {
                    value      +=  '' + key.value.toString();

                    if ( this.mode === 'percentage' ) {
                        value = this.value > 100 ? 100 : this.value;
                    }
                }
            } 

            this.$emit( 'changed', value );
        }
    }
}
</script>