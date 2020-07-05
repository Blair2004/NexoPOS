window.Vue.component( 'ns-checkbox', {
    data: () => {
        return {
            
        }
    },
    props: [ 'checked' ],
    template: `
    <div class="justify-center items-center flex">
        <div @click="toggleChecked()" :class="checked ? 'border-2 border-blue-400 text-blue-400 bg-blue-200' : ''" class="w-6 h-6 border-gray-400 border bg-gray-200 flex items-center justify-center cursor-pointer">
            <i v-if="checked === 'checked'" class="las la-check"></i>
        </div>
    </div>
    `,
    mounted() {
        console.log( 'checkbox' );
    },
    methods: {
        toggleChecked() {
            console.log( this.checked );
            // if ( this.checked === 'checked' ) {
            //     this.checked    =   undefined;
            // } else {
            //     this.checked    =   'checked';
            // }
            // this.$emit( 'changed', this.checked );
        }
    }
})
