<template>
    <div>
        <div class="flex ns-checkbox cursor-pointer mb-2" :class="(isChecked ? 'checked' : '') + ' ' + ( field?  (hasError ? 'has-error': 'is-pristine') : '' )" @click="toggleIt()">
            <div class="w-6 h-6 flex border items-center justify-center cursor-pointer">
                <i v-if="isChecked" class="las la-check"></i>   
            </div>
            <label v-if="label" class="mx-2">{{ label }}</label>
            <label v-if="field" class="mx-2">{{ field.label }}</label>
        </div>
        <ns-field-description v-if="field" :field="field"></ns-field-description>
    </div>
</template>
<script>
export default {
    data: () => {
        return {}
    },
    props: [ 'checked', 'field', 'label' ],
    computed: {
        isChecked() {
            return this.field ? this.field.value : this.checked;
        },
        hasError() {
            if ( this.field && this.field.errors !== undefined && this.field.errors.length > 0 ) {
                return true;
            }
            return false;
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
}
</script>