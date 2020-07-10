const { Vue }   =   require( './../bootstrap' );

const nsCheckbox    =   Vue.component( 'ns-checkbox', {
    data: () => {
        return {}
    },
    props: [ 'checked' ],
    template: `
    <div class="flex items-center justify-center">
        <div @click="toggleIt()" class="w-6 h-6 flex border-2 items-center justify-center cursor-pointer">
            <i v-if="checked" class="las la-check"></i>   
        </div>
    </div>
    `,
    methods: {
        toggleIt() {
            this.$emit( 'change', !this.checked );
        }
    }
});

module.exports   =   nsCheckbox;