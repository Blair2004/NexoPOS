<template>
    <div :class="field ? 'flex flex-col' : ''">
        <div class="ns-checkbox cursor-pointer flex" :class="(isChecked ? 'checked' : '') + ' ' + ( field?  (hasError ? 'has-error': 'is-pristine') : 'justify-center items-center' )" @click="toggleIt()">
            <div class="w-5 h-5 flex border items-center justify-center cursor-pointer">
                <i :class="isChecked ? 'visible' : 'invisible'" class="las la-check"></i>   
            </div>
            <label v-if="label" class="mx-2">{{ label }}</label>
            <label v-if="field && field.label" class="mx-2">{{ field.label }}</label>
        </div>
        <ns-field-description v-if="field && field.description" :field="field"></ns-field-description>
    </div>
</template>
<script>
export default {
    data: () => {
        return {}
    },
    emits: [ 'change' ],
    props: [ 'checked', 'field', 'label', 'class' ],
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