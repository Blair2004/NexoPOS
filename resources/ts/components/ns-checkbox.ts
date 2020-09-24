import Vue from 'vue';

const nsCheckbox    =   Vue.component( 'ns-checkbox', {
    data: () => {
        return {}
    },
    props: [ 'checked', 'field', 'label' ],
    template: `
    <div class="flex items-center justify-center">
        <div @click="toggleIt()" class="w-6 h-6 flex bg-white border-2 items-center justify-center cursor-pointer">
            <i v-if="isChecked" class="las la-check"></i>   
        </div>
        <span v-if="label" class="mx-2">{{ label }}</span>
    </div>
    `,
    computed: {
        isChecked() {
            return this.field ? this.field.value : this.checked;
        },
    },
    methods: {
        toggleIt() {
            if ( this.field !== undefined ) {
                this.field.value    =   !this.field.value;
            }

            this.$emit( 'change', !this.checked );
        }
    }
});

export { nsCheckbox };