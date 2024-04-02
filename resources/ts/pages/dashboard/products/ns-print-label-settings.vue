<script>
export default defineComponent({
    name: 'ns-print-label-settings',
    props: [ 'popup' ],
    template: `
    <div>
        <div class="shadow-lg ns-box w-95vw md:w-2/5-screen">
            <div class="border-b ns-box-body p-2 flex justify-between items-center">
                <h3>{{ __( 'Settings' ) }}</h3>
                <div>
                    <ns-close-button  @click="closePopup()"></ns-close-button>
                </div>
            </div>
            <div class="p-2">
                <ns-field :field="field" :key="index" :ref="field.name" v-for="(field, index) of fields"></ns-field>
            </div>
            <div class="border-t ns-box-footer p-2 flex justify-between">
                <div></div>
                <div>
                    <ns-button type="info" @click="saveSettings()">{{ __( 'Save' ) }}</ns-button>
                </div>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            fields: [],
            validation: new FormValidation,
        }
    },
    methods: {
        __,
        saveSettings() {
            this.popup.close();
            const form  =   this.validation.extractFields( this.fields );
            this.popup.params.resolve( form )
        },
        closePopup() {
            this.popup.close();
            this.popup.params.reject( false );
        }
    },
    mounted() {
        const product       =   this.popup.params.product;

        this.fields         =   this.validation.createFields([
            {
                label: 'Unit',
                type: 'select',
                name: 'selectedUnitQuantity',
                description: 'Choose the unit to apply for the item',
                options: product.unit_quantities.map( unit_quantity => {
                    return {
                        label: unit_quantity.unit.name,
                        value: unit_quantity
                    }
                }),
                value: product.selectedUnitQuantity || product.unit_quantities[0]
            }, {
                label: 'Unit',
                type: 'select',
                name: 'procurement_id',
                description: 'Choose quantity from procurement',
                options: product.unit_quantities.map( unit_quantity => {
                    return {
                        label: unit_quantity.unit.name,
                        value: unit_quantity
                    }
                }),
                value: product.procurement_id
            }, {
                label: 'Quantity',
                type: 'number',
                name: 'times',
                description: 'Define how many time the product will be printed',
                value: product.times || 1
            }
        ]);

        /**
         * we want that the quantity field always select
         * the entire quantity
         */
        setTimeout(() => {
            this.$refs.times[0].$el.parentNode.querySelector( 'input' ).addEventListener( 'focus', function( ) {
                this.select();
            });
        }, 100 );
    }
});
</script>