import Vue from "vue";

const nsIconButton     =   Vue.component( 'ns-icon-button', {
    template: `
    <button @click="clicked( $event )" :class="type ? type : buttonClass " class="ns-inset-button rounded-full h-8 w-8 border items-center justify-center">
        <i :class="className" class="las"></i>
    </button>
    `,
    props: [ 'className', 'buttonClass', 'type' ],
    methods: {
        clicked( event ) {
            this.$emit( 'click', event );
        }
    }
});

export { nsIconButton };