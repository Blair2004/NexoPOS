<template>
    <div id="numpad" class="grid grid-flow-row grid-cols-3 gap-2 grid-rows-3" style="padding: 1px">
        <div 
            @click="inputValue( key )"
            :key="index" 
            v-for="(key,index) of keys" 
            style="margin:-1px;"
            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-2xl text-gray-700 border h-16 flex items-center justify-center cursor-pointer">
            <span v-if="key.value !== undefined">{{ key.value }}</span>
            <i v-if="key.icon" class="las" :class="key.icon"></i>
        </div>
        <slot name="numpad-footer"></slot>
    </div>
</template>
<script>
export default {
    props: [ 'value', 'currency' ],
    data() {
        return {
            backValue: '0',
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
        this.backValue  =   this.value || this.backValue;
    },
    methods: {
        increaseBy( key ) {
            let number    =   parseInt( 
                1 + ( new Array( this.cursor ) )
                .fill('')
                .map( _ => 0 )
                .join('') 
            );

            this.backValue      =   (( parseFloat( key.value ) * number ) + ( parseFloat( this.backValue ) || 0 ) ).toString();
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
                this.$emit( 'next', this.backValue );
                this.backValue     =   '0';
                this.$emit( 'changed', this.backValue );
                return;
            } else if ( key.identifier === 'backspace' ) {
                if ( this.allSelected ) {
                    this.backValue      =   '0';
                    this.allSelected    =   false;
                } else {
                    this.backValue      =   this.backValue.substr( 0, this.backValue.length - 1 );
                }
            } else if ( key.value.toString().match( /^\d+$/ ) ) {
                if ( this.allSelected ) {
                    this.backValue      =   key.value.toString();
                    this.allSelected    =   false;
                } else {
                    this.backValue      +=  key.value.toString();

                    if ( this.mode === 'percentage' ) {
                        this.backValue = this.backValue > 100 ? 100 : this.backValue;
                    }
                }
            } 

            if ( ( this.backValue ) === "0" ) {
                this.backValue      =   '';
            }

            this.$emit( 'changed', this.backValue );
        }
    }
}
</script>