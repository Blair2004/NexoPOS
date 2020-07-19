const { Vue }       =   require( '../bootstrap' );
const nsField       =   Vue.component( 'ns-field', {
    data: () => {
        return {
        }
    },
    mounted() {
    },
    computed: {
        isInputField() {
            return [ 'text', 'password', 'email', 'number', 'datetime', 'tel' ].includes( this.field.type );
        },
        isSelectField() {
            return [ 'select' ].includes( this.field.type );
        },
        isTextarea() {
            return [ 'textarea' ].includes( this.field.type );
        },
        isCheckbox() {
            return [ 'checkbox' ].includes( this.field.type );
        },
    },
    props: [ 'field' ],
    template: `
    <div>
        <ns-input @blur="$emit( 'blur', this )" @change="$emit( 'change', this )"  :field="field" v-if="isInputField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description>{{ field.description || '' }}</template>
        </ns-input>
        <ns-select @blur="$emit( 'blur', this )" @change="$emit( 'change', this )"  :field="field" v-if="isSelectField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description>{{ field.description || '' }}</template>
        </ns-select>
        <ns-textarea @blur="$emit( 'blur', this )" @change="$emit( 'change', this )"  :field="field" v-if="isTextarea">
            <template>{{ field.label }}</template>
            <template v-slot:description>{{ field.description || '' }}</template>
        </ns-textarea v-slot>
        <ns-checkbox @blur="$emit( 'blur', this )" @change="$emit( 'change', this )"  :field="field" v-if="isCheckbox">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description>{{ field.description || '' }}</template>
        </ns-checkbox>
    </div>
    `,
});

module.exports     =   nsField;