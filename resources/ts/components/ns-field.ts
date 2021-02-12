import Vue from 'vue';
const nsField       =   Vue.component( 'ns-field', {
    data: () => {
        return {
        }
    },
    mounted() {
    },
    computed: {
        isInputField() {
            return [ 'text', 'password', 'email', 'number', 'tel' ].includes( this.field.type );
        },
        isDateField() {
            return [ 'date' ].includes( this.field.type );
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
        isMultiselect() {
            return [ 'multiselect' ].includes( this.field.type );
        },
        isSwitch() {
            return [ 'switch' ].includes( this.field.type );
        },
        isMedia() {
            return [ 'media' ].includes( this.field.type );
        },
        isCkEditor() {
            return [ 'ckeditor' ].includes( this.field.type );
        },
        isDateTimePicker() {
            return [ 'datetimepicker' ].includes( this.field.type );
        },
        isCustom() {
            return [ 'custom' ].includes( this.field.type );
        },
    },
    props: [ 'field' ],
    methods: {
        addOption( option ) {
            if( this.field.type === 'select' ) {
                this.field.options.forEach( option => option.selected = false );
            }

            option.selected     =   true;

            this.refreshMultiselect();

            this.$emit( 'change', { action: 'addOption', option })
        },
        refreshMultiselect() {
            this.field.value    =   this.field.options
                .filter( option => option.selected )
                .map( option => option.value );

            console.log( this.field.options );
        },
        removeOption( option ) {
            option.selected     =   false;
            this.refreshMultiselect();                
            this.$emit( 'change', { action: 'removeOption', option });
        },
    },
    template: `
    <div class="flex flex-auto">
        <ns-input @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isInputField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-input>
        <ns-date-time-picker @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isDateTimePicker">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-date-time-picker>
        <ns-date @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isDateField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-date>
        <ns-media-input @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isMedia">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-media-input>
        <ns-select @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isSelectField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-select>
        <ns-textarea @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isTextarea">
            <template>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-textarea v-slot>
        <ns-checkbox @blur="$emit( 'blur', field )" @change="$emit( 'change', field )"  :field="field" v-if="isCheckbox">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-checkbox>
        <ns-multiselect 
            @addOption="addOption( $event )" 
            @removeOption="removeOption( $event )" 
            :field="field" 
            v-if="isMultiselect">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-multiselect>
        <ns-ckeditor 
            :field="field" 
            v-if="isCkEditor">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-ckeditor>
        <ns-switch 
            :field="field" 
            @change="$emit( 'change', field )"
            v-if="isSwitch">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-switch>
        <template v-if="isCustom">
            <keep-alive>
                <component 
                    :field="field"
                    @blur="$emit( 'blur', field )" 
                    @change="$emit( 'change', field )" 
                    v-bind:is="field.component"></component>
            </keep-alive>
        </template>
    </div>
    `,
});

export { nsField }