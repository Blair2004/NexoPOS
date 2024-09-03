<template>
    <div v-for="(rule, index) of rules" :key="index" class="rounded shadow text-primary overflow-hidden bg-box-background flex mb-2">
        <div class="flex">
            <div @click="setRuleAction( rule, 'on' )" class="hover:bg-numpad-hover cursor-pointer p-2 pr-4">
                {{ __( 'On : {action}' ).replace( '{action}', getActionName( rule.on ) ) }}
            </div>
            <div class="pt-2 relative w-0 border-l border-box-edge">
                <div class="absolute rounded-full w-6 h-6 flex -ml-3 items-center justify-center border border-box-edge p-1 bg-box-background">
                    <i class="las la-angle-double-right"></i>
                </div>
            </div>
            <div @click="changeTransactionType( rule, 'action' )" class="hover:bg-numpad-hover border-r border-box-edge cursor-pointer p-2 pl-4">
                {{ rule.action === 'increase' ? __( 'Increase' ) : __( 'Decrease' ) }}
            </div>
        </div>
        <div class="flex">
            <div @click="selectAccount( rule, 'account_id' )" class="hover:bg-numpad-hover cursor-pointer p-2 pr-4">
                {{ getAccountName( rule.account_id ) }}
            </div>
            <div class="pt-2 relative w-0 border-l border-box-edge">
                <div class="absolute rounded-full w-6 h-6 flex -ml-3 items-center justify-center border border-box-edge p-1 bg-box-background">
                    <i class="las la-angle-double-right"></i>
                </div>
            </div>
            <div @click="changeTransactionType( rule, 'do' )" class="border-r hover:bg-numpad-hover cursor-pointer border-box-edge p-2  pl-4">
                {{ rule.do === 'increase' ? __( 'Increase' ) : __( 'Decrease' ) }}
            </div>
            <div @click="selectAccount( rule, 'offset_account_id' )" class="hover:bg-numpad-hover cursor-pointer p-2">
                {{ getAccountName( rule.offset_account_id ) }}
            </div>
        </div>
        <div class="flex flex-auto justify-end">
            <div @click="saveRule( rule )" class="p-2 border-l-box-edge border-l hover:bg-info-secondary hover:border-info-secondary cursor-pointer text-primary">
                <i class="las la-save"></i> {{  __( 'Save' ) }}
            </div>
            <div @click="deleteRule( rule )" class="p-2 border-l-box-edge border-l hover:bg-error-secondary hover:border-error-secondary cursor-pointer text-primary">
                <i class="lar la-times-circle"></i>
            </div>
        </div>
    </div>
    <div @click="addNewRule()" class="rounded bg-box-background p-2 cursor-pointer text-primary flex text-center justify-center">
        <span><i class="las la-plus"></i> {{ __( 'Create a new rule' ) }}</span>
    </div>
</template>

<script lang="ts">
import { forkJoin, of } from 'rxjs';
import { Line } from 'vue-chartjs';
import { nsNotice, nsSnackBar } from '~/bootstrap';
import { nsConfirmPopup, nsSpinner } from '~/components/components';
import NsSelectPopup from '~/popups/ns-select-popup.vue';

declare const __;
declare const Popup;
declare const nsHttpClient;

export default {
    data() {
        return {
            rules: [],
            accounts: [],
            actions: [],
        }
    },
    mounted() {
        forkJoin([
            this.loadAccounts(), 
            this.loadActions(), 
            this.loadRules()
        ])
        .subscribe({
            next: (response:any) => {
                this.accounts = response[0];
                this.actions = response[1];
                this.rules  =   response[2];
            },
            error: error => {
                nsSnackBar.error( error.message ).subscribe();
            }
        })
    },
    methods: {
        __,
        loadRules() {
            return nsHttpClient.get( '/api/transactions/rules' );
        },
        addNewRule() {
            this.rules.push({
                on: '',
                action: 'increase',
                account_id: '',
                do: 'decrease',
                offset_account_id: ''
            })
        },
        getActionName( action ) {
            return this.actions[ action ] || __( 'Not Defined' );
        },
        async setRuleAction( rule, field ) {
            if ( this.actions.length === 0 ) {
                return nsSnackBar.error( __( 'No actions available' ).subscribe() );
            }

            try {
                rule[ field ] = await new Promise( ( resolve, reject ) => {
                    const actionKeys    =   Object.keys( this.actions );

                    Popup.show( NsSelectPopup, {
                        label: __( 'Choose Action' ),
                        description: __( 'Choose the action you want to perform.' ),
                        options: Object.values( this.actions ).map( (action, index) => ({
                            label: action,
                            value: actionKeys[ index ]
                        })),
                        resolve,
                        reject,
                    })
                })
            } catch( exception ) {

            }
        },
        saveRule( rule ) {
            nsHttpClient.post( '/api/transactions/rules', {rule} )
                .subscribe({
                    next: response => {
                        if ( response.status === 'success' ) {
                            rule.id = response.data.id;
                            nsNotice.success(
                                __( 'Save Completed' ),
                                __( 'The rule has been saved successfully' )
                            );
                        }
                    },
                    error: error => {
                        nsSnackBar.error( error.message ).subscribe();
                    }
                });
        },
        getAccountName( id ) {
            return this.accounts.find( account => account.id === id )?.name || __( 'Not Defined' );
        },
        loadActions() {
            return nsHttpClient.get( '/api/transactions-accounts/actions' );
        },
        loadAccounts() {
            return nsHttpClient.get( '/api/transactions-accounts' );
        },
        async changeTransactionType( line, field) {
            try {
                line[ field ] = await new Promise(  ( resolve, reject ) => {
                    Popup.show( NsSelectPopup, {
                        label: __( 'Transaction Type' ),
                        description: __( 'Choose the transaction type you want to perform' ),
                        options: [{
                            label: __( 'Increase' ),
                            value: 'increase'
                        }, {
                            label: __( 'Decrease' ),
                            value: 'decrease'
                        }],
                        resolve, 
                        reject,
                    })
                });
            } catch( exception ) {
                console.error( exception );
            }
        },
        selectAccount( rule, field ) {
            const spinner = Popup.show( nsSpinner );

            nsHttpClient.get( '/api/transactions-accounts' ).subscribe({
                next: async (accounts) => {
                    spinner.close();
                    try {
                        const accountId = await new Promise( ( resolve, reject ) => {
                            Popup.show( NsSelectPopup, {
                                label: __( 'Choose Account' ),
                                description: __( 'Choose the account you want to use' ),
                                options: accounts.map( account => ({
                                    label: account.name,
                                    value: account.id
                                })),
                                resolve,
                                reject
                            })
                        }) 

                        rule[ field ] = accountId;

                        console.log({ rule })
                    } catch( exception ) {
                        // ...
                    }
                },
            })
        },
        deleteRule( rule ) {
            if ( rule.id ) {
                return Popup.show( nsConfirmPopup, {
                    title: __( 'Delete Rule' ),
                    message: __( 'Are you sure you want to delete this rule?' ),
                    onAction: ( action ) => {
                        if ( action ) {
                            this.deleteRemoteRule( rule );
                        }
                    }
                })
            } else {
                this.rules = this.rules.filter( r => r !== rule );
            }
        },
        deleteRemoteRule( rule ) {
            nsHttpClient.delete( `/transactions/rules/${ rule.id }` )
                .then( response => {
                    if ( response.status === 'success' ) {
                        this.rules = this.rules.filter( r => r.id !== rule.id );
                    }
                })
                .catch( error => {
                    console.error( error );
                })
        } 
    }
}
</script>