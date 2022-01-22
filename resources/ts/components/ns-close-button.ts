import Vue from "vue";

const nsCloseButton     =   Vue.component( 'ns-close-button', {
    template: `
    <button @click="clicked( $event )" class="outline-none hover:bg-red-400 dark:border-slate-300 dark:text-slate-300 dark:hover:text-red-600 dark:hover:border-red-600 hover:text-white hover:border-red-400 rounded-full h-8 w-8 border items-center justify-center">
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