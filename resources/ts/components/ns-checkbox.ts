import Vue from 'vue';

const nsCheckbox    =   Vue.component( 'ns-checkbox', {
    data: () => {
        return {}
    },
    props: [ 'checked', 'field', 'label' ],
    template: `
    <div class="flex items-center justify-center cursor-pointer" @click="toggleIt()">
        <div class="w-6 h-6 flex bg-input-background border-input-edge border-2 items-center justify-center cursor-pointer">
            <i v-if="isChecked" class="las la-check"></i>   
        </div>
        <span v-if="label" class="mx-2">{{ label }}</span>
        <span v-if="field" class="mx-2">{{ field.label }}</span>
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