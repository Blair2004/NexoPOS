<script lang="ts">
import { default as nsDateRangePicker } from './ns-date-range-picker.vue';
import { default as nsDateTimePicker } from './ns-date-time-picker.vue';
import { default as nsSwitch } from './ns-switch.vue';
export default {
    emits: [ 'blur', 'change', 'saved', 'keypress' ],
    data: () => {
        return {
        }
    },
    mounted() {
        // ...
    },
    components: {
        nsDateRangePicker,
        nsDateTimePicker,
        nsSwitch,
    },
    computed: {
        isInputField() {
            return [ 'text', 'password', 'email', 'number', 'tel' ].includes( this.field.type );
        },
        isHiddenField() {
            return [ 'hidden' ].includes( this.field.type );
        },
        isDateField() {
            return [ 'date' ].includes( this.field.type );
        },
        isSelectField() {
            return [ 'select' ].includes( this.field.type );
        },
        isSearchField() {
            return [ 'search-select' ].includes( this.field.type );
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
        isInlineMultiselect() {
            return [ 'inline-multiselect' ].includes( this.field.type );
        },
        isSelectAudio() {
            return [ 'select-audio' ].includes( this.field.type );
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
        isDateRangePicker() {
            return [ 'daterangepicker' ].includes( this.field.type );
        },
        isCustom() {
            return [ 'custom' ].includes( this.field.type );
        },
    },
    props: [ 'field' ],
    methods: {
        handleSaved( field, event ) {
            this.$emit( 'saved', event );
        },
        addOption( option ) {
            if( this.field.type === 'select' ) {
                this.field.options.forEach( option => option.selected = false );
            }

            option.selected     =   true;

            const index         =   this.field.options.indexOf( option );
            
            this.field.options.splice( index, 1 );

            this.field.options.unshift( option );

            this.refreshMultiselect();

            this.$emit( 'change', { action: 'addOption', option })
        },
        changeTouchedState( field, $event ){
            if ( $event.stopPropagation ) {
                $event.stopPropagation();
            }
            field.touched    =    true;
            this.$emit( 'change', field );
        },
        refreshMultiselect() {
            this.field.value    =   this.field.options
                .filter( option => option.selected )
                .map( option => option.value );
        },
        removeOption( option ) {
            option.selected     =   false;
            this.refreshMultiselect();                
            this.$emit( 'change', { action: 'removeOption', option });
        },
    },
}
</script>
<template>
    <template v-if="isHiddenField">
        <input type="hidden" :name="field.name" :value="field.value"/>
    </template>
    <div class="flex flex-auto mb-2" v-if="! isHiddenField">
        <ns-input @keypress="changeTouchedState( field, $event )" @change="changeTouchedState( field, $event )" :field="field" v-if="isInputField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-input>
        <ns-date-time-picker @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isDateTimePicker">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-date-time-picker>
        <ns-date @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isDateField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-date>
        <ns-media-input @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isMedia">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-media-input>
        <ns-select @change="changeTouchedState( field, $event )" :field="field" v-if="isSelectField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-select>
        <ns-search-select :field="field" @saved="handleSaved( field, $event)" @change="changeTouchedState( field, $event )" v-if="isSearchField">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-search-select>
        <ns-daterange-picker @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isDateRangePicker">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-daterange-picker>
        <ns-select-audio @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isSelectAudio">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-select-audio>
        <ns-textarea @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isTextarea">
            <template>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-textarea>
        <ns-checkbox @blur="$emit( 'blur', field )" @change="changeTouchedState( field, $event )"  :field="field" v-if="isCheckbox">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-checkbox>
        <ns-inline-multiselect @blur="$emit( 'blur', field )" @update="changeTouchedState( field, $event )"  :field="field" v-if="isInlineMultiselect">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-inline-multiselect>
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
            @change="changeTouchedState( field, $event )"
            v-if="isSwitch">
            <template v-slot>{{ field.label }}</template>
            <template v-slot:description><span v-html="field.description || ''"></span></template>
        </ns-switch>
        <template v-if="isCustom">
            <keep-alive>
                <component 
                    :field="field"
                    @blur="$emit( 'blur', field )" 
                    @change="changeTouchedState( field, $event )" 
                        v-bind:is="field.component"></component>
            </keep-alive>
        </template>
    </div>
</template>