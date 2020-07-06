window.Vue.component( 'ns-checkbox', {
    data: () => {
        return {
            do: false
        }
    },
    props: [ 'checked' ],
    template: `
    <div>
        <div @click="toggleIt()" class="w-6 h-6 flex items-center justify-center cursor-pointer">
            <i v-if="checked === 'checked'" class="las la-check"></i>
        </div>
    </div>
    `,
    mounted() {
        console.log( 'checkbox' );
    },
    methods: {
        toggleIt() {
            alert( 'ok' );
            // console.log( this.checked );
            // if ( this.checked === 'checked' ) {
            //     this.checked    =   undefined;
            // } else {
            //     this.checked    =   'checked';
            // }
            // this.$emit( 'changed', this.checked );
        }
    }
});
// class="justify-center items-center flex"
// :class="checked ? 'border-2 border-blue-400 text-blue-400 bg-blue-100' : 'border-gray-400 border bg-gray-200'"