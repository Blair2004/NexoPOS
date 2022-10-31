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
                    <ns-field v-for="field of tab.fields" :key="field.name" :field="field"></ns-field>
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
            validation: new FormValidation,
        }
    },
    computed: {
        // ...
    },
    mounted() {
        this.init();
    },
    methods: {
        __,
        nsCurrency,
        setActiveTab( tab ) {
            this.activeTab  =   tab;
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
                this.configurations = await this.loadConfiguration();

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

        loadConfiguration() {
            return new Promise( ( resolve, reject ) => {
                nsHttpClient.get( `/api/nexopos/v4/expenses/configurations` )
                    .subscribe({
                        next: configurations => {
                            resolve( configurations );
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