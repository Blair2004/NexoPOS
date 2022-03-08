<template>
    <div id="numpad" class="grid grid-flow-row grid-cols-3 gap-2 grid-rows-3" style="padding: 1px">
        <div 
            @click="inputValue( key )"
            :key="index" 
            v-for="(key,index) of keys" 
            style="margin:-1px;"
            class="select-none ns-numpad-key border h-16 flex items-center justify-center cursor-pointer">
            <span v-if="key.value !== undefined">{{ key.value }}</span>
            <i v-if="key.icon" class="las" :class="key.icon"></i>
        </div>
        <slot name="numpad-footer"></slot>
    </div>
</template>
<script>
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
            cursor: parseInt( ns.currency.ns_currency_precision ),
            orderSubscription: null,
            allSelected: true,
            keys: [
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    },
    mounted() {
        if( this.floating && this.value > 0 ) {
            this.screenValue    =   parseFloat( this.value / this.number );
        } else {
            this.screenValue    =   0;
        }
    },
    watch: {
        value() {        
            if ( this.value.toString().length > 0 ) {
                if ( this.floating ) {
                    this.screenValue    =   Math.round( this.value * this.number ).toString();
                } else {
                    this.screenValue    =   this.value;
                }
            }
        }
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
                    this.screenValue    =   this.screenValue.toString().substr( 0, this.screenValue.length - 1 );
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