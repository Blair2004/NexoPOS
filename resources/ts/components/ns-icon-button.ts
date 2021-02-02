import Vue from "vue";

const nsIconButton     =   Vue.component( 'ns-icon-button', {
    template: `
    <button @click="clicked( $event )" class="hover:bg-blue-400 hover:text-white hover:border-blue-600 rounded-full h-8 w-8 border items-center justify-center">
        <i :class="className" class="las"></i>
    </button>
    `,
    props: [ 'className' ],
    methods: {
        clicked( event ) {
            this.$emit( 'click', event );
        }
    }
});

export { nsIconButton };