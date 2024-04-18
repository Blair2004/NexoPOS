<template>
    <div v-if="isLoading && ! unavailableType" class="h-half w-full flex items-center justify-center">
        <div class="flex flex-col">
            <ns-spinner></ns-spinner>
            <div class="py-4">
                <a @click="init()" class="text-info-tertiary hover:underline" href="javascript:void(0)">{{ __( 'Change Type' ) }}</a>
            </div>
        </div>
    </div>
    <div v-if="unavailableType && ! isLoading" class="flex items-center justify-center">
        <ns-notice color="warning">
            <template #title>{{ __( 'Unable to edit this transaction' ) }}</template>
            <template #description>
                {{ __( 'This transaction was created with a type that is no longer available. This type is no longer available because NexoPOS is unable to perform background requests.' ) }}
            </template>
            <template #controls>
                <ns-button target="_blank" href="https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled?utm_source=nexopos&utm_campaign=warning&utm_medium=app" type="warning">{{ __( 'Learn More' ) }}</ns-button>
            </template>
        </ns-notice>
    </div>
    <div v-if="tabs.length > 0 && ! isLoading">
        <ns-tabs :active="activeTab" @active="setActiveTab( $event )">
            <template #extra>
                <div class="md:flex hidden flex-col md:flex-row -mx-2">
                    <div class="px-2">
                        <div class="ns-button info"><button @click="confirmTypeChange()" class="py-1 px-2 text-sm rounded">{{ __( 'Change Type' ) }}</button></div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button success"><button @click="confirmBeforeSave()" class="py-1 px-2 text-sm rounded">{{ __( 'Save Transaction' ) }}</button></div>
                    </div>
                </div>
            </template>
            <ns-tabs-item v-for="tab of tabs" :key="tab.identifier" :identifier="tab.identifier" :label="tab.label">
                <template v-if="tab.fields">
                    <ns-notice class="mb-2" color="info" v-if="selectedConfiguration.identifier === 'ns.entity-transaction'">
                        <template #title>{{ __( 'Warning' ) }}</template>
                        <template #description>{{ __( 'While selecting entity transaction, the amount defined will be multiplied by the total user assigned to the User group selected.' ) }}</template>
                    </ns-notice>
                    <ns-field @saved="handleSavedField( $event, field )" v-for="field of tab.fields" :key="field.name" :field="field"></ns-field>
                </template>
                <template v-if="tab.identifier === 'recurrence'">
                    <template :key="field.name" v-for="field of recurrence">
                        <ns-field @saved="handleSavedField( $event, field )"  v-if="executeCondition( field, recurrence )" @change="updateSelectLabel()" :field="field"></ns-field>
                    </template>
                </template>
            </ns-tabs-item>
            <div class="my-3 md:hidden">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <div class="ns-button info"><button @click="confirmTypeChange()" class="py-1 px-2 text-sm rounded">{{ __( 'Change Type' ) }}</button></div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button success"><button @click="confirmBeforeSave()" class="py-1 px-2 text-sm rounded">{{ __( 'Save Expense' ) }}</button></div>
                    </div>
                </div>
            </div>
        </ns-tabs>
    </div>
</template>
<script>
import { nsSnackBar } from '~/bootstrap';
import { nsAlertPopup, nsConfirmPopup } from '~/components/components';
import { nsCurrency } from '~/filters/currency';
import FormValidation from '~/libraries/form-validation';
import { __ } from '~/libraries/lang';
import nsTransactionSelector from './ns-transaction-selector.vue';

export default {
    props: [],
    data() {
        return {
            configurations: [],
            activeTab: 'create-customers',
            selectedConfiguration: {},
            isLoading: false,
            tabs: [],
            unavailableType: false,
            transaction: {},
            originalRecurrence: [],
            validation: new FormValidation,
            recurrence: [],
            warningMessage: false,
        }
    },
    computed: {
        // ...
    },
    mounted() {
        if ( window.nsTransactionData !== undefined ) {
            this.transaction    =   window.nsTransactionData;
        }

        this.init();
    },
    watch: {
        // ...
    },
    methods: {
        __,
        nsCurrency,
        confirmBeforeSave() {
            Popup.show( nsConfirmPopup, {
                title: __( 'Confirm Your Action' ),
                message: __( 'The transaction is about to be saved. Would you like to confirm your action ?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        this.saveTransaction();
                    }
                }
            })
        },
        saveTransaction() {
            const verb              =   this.transaction.id !== undefined ? 'put' : 'post';
            const url               =   this.transaction.id !== undefined ? `/api/crud/ns.transactions/${this.transaction.id}` : `/api/crud/ns.transactions`;
            const correctConfig     =   this.configurations.filter( config => config.identifier === this.selectedConfiguration.identifier );

            /**
             * not likely to occurs, but if it does, that means we've clicked
             * on save button before choosing the configuration.
             */
            if ( correctConfig.length !== 1 ) {
                return nsSnackBar.error( __( 'No configuration were choosen. Unable to proceed.' ) ).subscribe();
            }

            /**
             * We'll proceed a form 
             * validation here.
             */
            if ( ! this.validation.validateFields( correctConfig[0].fields ) ) {
                return nsSnackBar.error( __( 'Unable to proceed the form is not valid.' ) ).subscribe();
            }

            this.validation.disableFields( correctConfig[0].fields );

            const firstTabFields    =   this.validation.extractFields( correctConfig[0].fields );
            const secondTabFields   =   this.validation.extractFields( this.recurrence );
            const merged            =   { ...firstTabFields, ...secondTabFields };
            const crudForm          =   {
                general: {}
            };

            for( let key in merged ) {
                if ( key === 'name' ) {
                    crudForm[ key ]     =   merged[ key ];
                } else {
                    crudForm.general[ key ]     =   merged[ key ];
                }
            }

            nsHttpClient[ verb ]( url, crudForm )
                .subscribe({
                    next: result => {
                        nsSnackBar.success( result.message ).subscribe();
                        setTimeout( ( ) => {
                            document.location   =   result.data.editUrl;
                        }, 1000 );
                    },
                    error: error => {
                        this.validation.enableFields( correctConfig[0].fields );
                        nsSnackBar.error( error.message || __( 'An unexpected error occured.' ) ).subscribe();
                    }
                });
        },
        updateSelectLabel() {
            if ( this.recurrence.length > 0 ) {
                this.recurrence[0].options  =   this.recurrence[0].options.map( (option, key) => {
                    const referenceOption   =   this.originalRecurrence[0].options[key];

                    if ([ 'x_after_month_starts', 'x_before_month_ends' ].includes( option.value ) ) {
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
            this.updateSelectLabel();
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
            if ( [ 'ns.recurring-transaction', 'ns.salary-transaction' ].includes( this.selectedConfiguration.identifier ) ) {
                tabs.push({
                    label: __( 'Conditions' ),
                    identifier: 'recurrence'
                })
            }

            this.tabs   =   tabs;
        },
        async init() {
            try {
                this.isLoading  =   true;
                const { configurations, recurrence, warningMessage } = await this.loadConfiguration();

                this.configurations         =   configurations;
                this.recurrence             =   recurrence;
                this.warningMessage         =   warningMessage;
                this.originalRecurrence     =   JSON.parse( JSON.stringify(recurrence) );

                if ( this.transaction.type === undefined ) {
                    await this.selectTransactionType();
                } else {
                    const expenseConfiguration  =   this.configurations.filter( configuration => configuration.identifier === this.transaction.type );

                    if ( expenseConfiguration.length == 0 ) {
                        this.unavailableType    =   true;
                        this.isLoading          =   false;

                        return Popup.show( nsAlertPopup, {
                            title: __( 'Unable to load the transaction' ),
                            message: __( 'You cannot edit this transaction if NexoPOS cannot perform background requests.' )
                        });
                    }

                    this.processSelectedConfiguration( expenseConfiguration[0] );
                }
                
                this.isLoading  =   false;
                this.setTabs();
            } catch( exception ) {
                console.log( exception );
            }
        },
        processSelectedConfiguration( result ) {
            /**
             * everytime, we'll rebuild the 
             * form validation for the selected type.
             */
            result.fields   =   this.validation.createFields( result.fields );

            /**
             * let's define if the transaction is recurring or not
             */
            result.fields.forEach( field => {
                if ( field.name === 'recurring' ) {
                    if ([ 'ns.recurring-transaction', 'ns.salary-transaction' ].includes( result.identifier ) ) {
                        field.value =   true;
                    } else {
                        field.value =   false;
                    }
                }

                if ( field.name === 'type' ) {
                    field.value     =   result.identifier;
                }
            });

            /**
             * we'll use the identifier of the configuration
             * as a type for the expense.
             */
            this.selectedConfiguration  =   result;
        },

        handleSavedField( event, field ) {
            try {
                field.options.push({
                    label: event.data.entry[ field.props.optionAttributes.label ],
                    value: event.data.entry[ field.props.optionAttributes.value ]
                });
                field.value     =   event.data.entry[ field.props.optionAttributes.value ];
            } catch ( exception ) {
                // something went wrong
            }
        },

        async selectTransactionType() {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsTransactionSelector, { resolve, reject, configurations: this.configurations, type: this.transaction.type, warningMessage: this.warningMessage });
                });

                this.processSelectedConfiguration( result );
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
                        delete this.transaction.type;
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
                nsHttpClient.get( `/api/transactions/configurations/${this.transaction.id ? this.transaction.id : ''}` )
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
