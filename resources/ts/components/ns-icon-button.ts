import Vue from "vue";

const nsIconButton     =   Vue.component( 'ns-icon-button', {
    template: `
    <button @click="clicked( $event )" :class="buttonClass ? buttonClass : 'hover:bg-blue-400 hover:text-white hover:border-blue-600'" class="rounded-full h-8 w-8 border items-center justify-center">
        <i :class="className" class="las"></i>
    </button>
    `,
    props: [ 'className', 'buttonClass' ],
    methods: {
        clicked( event ) {
            this.$emit( 'click', event );
        }
    }
});

export { nsIconButton };