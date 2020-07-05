window.Vue.component( 'ns-checkbox', {
    data: () => {
        return {
        }
    },
    props: [ 'href', 'label' ],
    template: `
    <div>
        <div class="w-8 h-8 border-gray-400 border bg-gray-200">
            <i class="las la-check"></i>
        </div>
    </div>
    `,
    mounted() {
        console.log( 'checkbox' );
    }
})
