<template>
    <div v-if="isLoading" class="h-half w-full flex items-center justify-center">
        <ns-spinner></ns-spinner>
    </div>
    <div v-if="tabs.length > 0 && ! isLoading">
        <ns-tabs :active="activeTab" @active="setActiveTab( $event )">
            <template #extra>
                <div class="ns-button"><button @click="confirmTypeChange()" class="py-1 px-2 text-sm rounded">{{ __( 'Change Type' ) }}</button></div>
            </template>
            <ns-tabs-item v-for="tab of tabs" :key="tab.identifier" :identifier="tab.identifier" :label="tab.label">
                <template v-if="tab.fields">
                    <ns-notice class="mb-2" color="info" v-if="selectedConfiguration.identifier === 'ns.salary-expenses'">
                        <template #title>{{ __( 'Warning' ) }}</template>
                        <template #description>{{ __( 'While selecting salary expense, the amount defined will be multiplied by the total user assigned to the User group selected.' ) }}</template>
                    </ns-notice>
                    <br>
                    <ns-field v-for="field of tab.fields" :key="field.name" :field="field"></ns-field>
                </template>
                <template v-if="tab.identifier === 'recurrence'">
                    <template :key="field.name" v-for="field of recurrence">
                        <ns-field v-if="executeCondition( field, recurrence )" @change="updateSelectLabel()" :field="field"></ns-field>
                    </template>
                </template>
            </ns-tabs-item>
        </ns-tabs>
    </div>
</template>
<script>
import { nsConfirmPopup } from '~/components/components';
import { nsCurrency } from '~/filters/currency';
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
import nsExpenseSelectorVue from './ns-expense-selector.vue';

export default {
    props: [ 'expense' ],
    data() {
        return {
            handledExpense: { type: undefined },
            configurations: [],
            activeTab: 'create-customers',
            selectedConfiguration: {},
            isLoading: false,
            tabs: [],
            originalRecurrence: [],
            validation: new FormValidation,
            recurrence: []
        }
    },
    computed: {
        // ...
    },
    mounted() {
        this.init();
    },
    watch: {
        // ...
    },
    methods: {
        __,
        nsCurrency,
        updateSelectLabel() {
            if ( this.recurrence.length > 0 ) {
                this.recurrence[0].options  =   this.recurrence[0].options.map( (option, key) => {
                    const referenceOption   =   this.originalRecurrence[0].options[key];

                    if ([ 'x_days_after_month_starts', 'x_days_before_month_ends' ].includes( option.value ) ) {
                        option.label            =   referenceOption.label.replace( '{day}', this.recurrence[1].value >= 0 && this.recurrence[1].value <= 1 ? `${this.recurrence[1].value} day` : `${this.recurrence[1].value} days` );
                        option.label            =   referenceOption.label.replace( '{day}', this.recurrence[1].value >= 0 && this.recurrence[1].value <= 1 ? `${this.recurrence[1].value} day` : `${this.recurrence[1].value} days` );
                    } else if ([ 'on_specific_day' ].includes( option.value ) ) {
                        option.label            =   referenceOption.label.replace( '{day}', this.ordinalSuffix( this.recurrence[1].value ) );
                    }

                    return option;
                });
            }
        },
        setActiveTab( tab ) {
            this.activeTab  =   tab;
        },
        executeCondition( field, fields ) {
            if ( field.shows ) {
                const requestedFields   =   fields.filter( _field => Object.keys( field.shows ).includes( _field.name ) );

                const validatedFields   =   requestedFields.filter( requestedField => {
                    return field.shows[ requestedField.name ].includes( requestedField.value );
                });

                return validatedFields.length === requestedFields.length;
            }

            return true;
        },
        setTabs() {
            const tabs  =   [
                {
                    label: this.selectedConfiguration.label || __( 'N/A' ),
                    identifier: 'general',                    
                    active: true,
                    fields: this.selectedConfiguration.fields
                }
            ];

            /**
             * for the expense we know supports
             * recurring behavior
             */
            if ( [ 'ns.recurring-expenses', 'ns.salary-expenses' ].includes( this.selectedConfiguration.identifier ) ) {
                tabs.push({
                    label: __( 'Recurrence' ),
                    identifier: 'recurrence'
                })
            }

            this.tabs   =   tabs;
        },
        async init() {
            try {
                this.handledExpense     =   { type: undefined };
                this.isLoading  =   true;
                const { configurations, recurrence } = await this.loadConfiguration();

                this.configurations         =   configurations;
                this.recurrence             =   recurrence;
                this.originalRecurrence     =   JSON.parse( JSON.stringify(recurrence) );

                // we're probably submiting an expense to edit
                if ( this.expense !== undefined ) {
                    this.handledExpense     =   this.expense;
                }
        
                if ( this.handledExpense.type === undefined ) {
                    await this.selectExpenseType();
                }
                
                this.isLoading  =   false;
                this.setTabs();
            } catch( exception ) {

            }
        },
        async selectExpenseType() {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsExpenseSelectorVue, { resolve, reject, configurations: this.configurations, type: this.handledExpense.type });
                });

                /**
                 * everytime, we'll rebuild the 
                 * form validation for the selected type.
                 */
                result.fields   =   this.validation.createFields( result.fields );

                /**
                 * we'll use the identifier of the configuration
                 * as a type for the expense.
                 */
                this.selectedConfiguration  =   result;
            } catch( exception ) {
                console.log( exception );
            }
        },

        confirmTypeChange() {
            Popup.show( nsConfirmPopup, {
                title: __( 'Change Type' ),
                message: __( 'By proceeding the current for and all your entries will be cleared. Would you like to proceed?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.init();
                    }
                }
            })
        },

        ordinalSuffix(i) {
            var j = i % 10,
                k = i % 100;
            if (j == 1 && k != 11) {
                return i + "st";
            }
            if (j == 2 && k != 12) {
                return i + "nd";
            }
            if (j == 3 && k != 13) {
                return i + "rd";
            }
            return i + "th";
        },

        loadConfiguration() {
            return new Promise( ( resolve, reject ) => {
                nsHttpClient.get( `/api/nexopos/v4/expenses/configurations` )
                    .subscribe({
                        next: result => {
                            resolve( result );
                        },
                        error: error => {
                            reject( error );
                        }
                    })
            });
        }
    }

}
</script>