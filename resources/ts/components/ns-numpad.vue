<template>
    <div id="numpad" class="grid grid-flow-row grid-cols-3 gap-2 grid-rows-3" style="padding: 1px">
        <div 
            @click="inputValue( key )"
            :key="index" 
            v-for="(key,index) of keys" 
            style="margin:-1px;"
            class="select-none hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
            <span v-if="key.value !== undefined">{{ key.value }}</span>
            <i v-if="key.icon" class="las" :class="key.icon"></i>
        </div>
        <slot name="numpad-footer"></slot>
    </div>
</template>
<script>
export default {
    name: 'ns-numpad',
    props: [ 'value', 'currency', 'floating', 'limit', 'syncValue' ],
    data() {
        return {
            number: parseInt( 
                1 + ( new Array( parseInt( ns.currency.ns_currency_precision ) ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            ),
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
        this.syncValue  =   this.value || 0;
    },
    methods: {
        increaseBy( key ) {
            let number    =   parseInt( 
                1 + ( new Array( this.cursor ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            );

            this.syncValue      =   (( parseFloat( key.value ) * number ) + ( parseFloat( this.syncValue ) || 0 ) ).toString();
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
                this.$emit( 'next', this.floating && this.syncValue.length > 0 ? parseFloat( this.syncValue / this.number ) : this.syncValue );
                return;
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.syncValue      =   '0';
                    this.allSelected    =   false;
                } else {
                    this.syncValue      =   this.syncValue.substr( 0, this.syncValue.length - 1 );
                }
            } else if ( key.value.toString().match( /^\d+$/ ) ) {
                if ( this.limit > 0 && this.syncValue.length >= this.limit ) {
                    return;
                }
                
                if ( this.allSelected ) {
                    this.syncValue      =   key.value.toString();
                    this.allSelected    =   false;
                } else {
                    this.syncValue      +=  key.value.toString();

                    if ( this.mode === 'percentage' ) {
                        this.syncValue = this.syncValue > 100 ? 100 : this.syncValue;
                    }
                }
            } 

            if ( ( this.syncValue ) === "0" ) {
                this.syncValue      =   '';
            }

            this.$emit( 'changed', this.floating && this.syncValue.length > 0 ? parseFloat( this.syncValue / this.number ) : this.syncValue );
        }
    }
}
</script>