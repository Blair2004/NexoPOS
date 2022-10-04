import Vue from "vue";

const nsCloseButton     =   Vue.component( 'ns-close-button', {
    template: `
    <button @click="clicked( $event )" class="outline-none ns-close-button border rounded-full h-8 w-8 items-center justify-center">
        <i class="las la-times"></i>
    </button>
    `,
    methods: {
        clicked( event ) {
            this.$emit( 'click', event );
        }
    }
});

export { nsCloseButton };