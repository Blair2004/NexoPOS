<template>
    <p v-if="! field.errors || field.errors.length === 0" class="text-xs ns-description">{{ field.description }}</p>
    <p :key="index" v-for="(error,index) of field.errors" class="text-xs ns-error">
        <slot v-if="error.identifier === 'required'" :name="error.identifier">{{ __( 'This field is required.' ) }}</slot>
        <slot v-if="error.identifier === 'email'" :name="error.identifier">{{ __( 'This field must contain a valid email address.' ) }}</slot>
        <slot v-if="error.identifier === 'invalid'" :name="error.identifier">{{ error.message }}</slot>
        <slot v-if="error.identifier === 'same'" :name="error.identifier">{{ __( 'This field must be similar to "{other}"' ).replace( '{other}', this.validation.getValueByDotNotation( error.labels, error.rule.value ) ) }}</slot>
        <slot v-if="error.identifier === 'min'" :name="error.identifier">{{ __( 'This field must have at least "{length}" characters' ).replace( '{length}', error.rule.value ) }}</slot>
        <slot v-if="error.identifier === 'max'" :name="error.identifier">{{ __( 'This field must have at most "{length}" characters' ).replace( '{length}', error.rule.value ) }}</slot>
        <slot v-if="error.identifier === 'different'" :name="error.identifier">{{ __( 'This field must be different from "{other}"' ).replace( '{other}', this.validation.getValueByDotNotation( error.labels, error.rule.value ) ) }}</slot>
    </p>
</template>
<script lang="ts">
import { __ } from '~/libraries/lang';
import FormValidation from '~/libraries/form-validation';

export default {
    name: 'ns-field-detail',
    props: [ 'field' ],
    data() {
        return { 
            validation: new FormValidation
        }
    },
    methods: {
        __,
    }
}
</script>