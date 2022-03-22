<template>
    
</template>
<script>
import moment from "moment";
import nsDatepicker from "@/components/ns-datepicker";
import { default as nsDateTimePicker } from '@/components/ns-date-time-picker';
import { nsHttpClient, nsSnackBar } from '@/bootstrap';
import { __ } from '@/libraries/lang';
import nsSelectPopupVue from '@/popups/ns-select-popup.vue';

export default {
    name: 'ns-sale-report',
    data() {
        return {
            startDate: moment(),
            endDate: moment(),
            result: [],
            users: [],
            summary: {},
            selectedUser: '',
            reportType: {
                label: __( 'Report Type' ),
                name: 'reportType',
                type: 'select',
                value: 'categories_report',
                options: [
                    {
                        label: __( 'Categories Detailed' ),
                        name: 'categories_report',
                    }, {
                        label: __( 'Categories Summary' ),
                        name: 'categories_summary',
                    }, {
                        label: __( 'Products' ),
                        name: 'products_report',
                    }
                ],
                description: __( 'Allow you to choose the report type.' ),
            },
            filterUser: {
                label: __( 'Filter User' ),
                name: 'filterUser',
                type: 'select',
                value: '',
                options: [
                    // ...
                ],
                description: __( 'Allow you to choose the report type.' ),
            },
            field: {
                type: 'datetimepicker',
                value: '2021-02-07',
                name: 'date'
            }
        }
    },
    components: {
        nsDatepicker,
        nsDateTimePicker,
    },
    computed: {
        // ..
    },
    methods: {
        printSaleReport() {
            this.$htmlToPaper( 'sale-report' );
        },
        setStartDate( moment ) {
            this.startDate  =   moment.format();
        },

        async openSettings() {
            try {
                const result    =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsSelectPopupVue, {
                        ...this.reportType,
                        resolve, 
                        reject
                    });
                });

                this.reportType.value   =   result[0].name;
                this.result             =   [];
                this.loadReport();
            } catch( exception ) {
                // ...
            }
        },

        async openUserFiltering() {
            try {
                /**
                 * let's try to pull the users first.
                 */
                const result    =   await new Promise( ( resolve, reject ) => {
                    nsHttpClient.get( `/api/nexopos/v4/users` )
                        .subscribe({
                            next: (users) => {
                                this.users      =   users;

                                this.filterUser.options     =   [
                                    {
                                        label: __( 'All Users' ),
                                        value: ''
                                    },
                                    ...this.users.map( user => {
                                        return {
                                            label: user.username,
                                            value: user.id
                                        }
                                    })
                                ];
                                
                                Popup.show( nsSelectPopupVue, {
                                    ...this.filterUser,
                                    resolve, 
                                    reject
                                });
                            },
                            error: error => {
                                nsSnackBar.error( __( 'No user was found for proceeding the filtering.' ) );
                                reject( error );
                            }
                        });
                });

                this.selectedUser       =   result[0].label;
                this.filterUser.value   =   result[0].value;
                this.result             =   [];
                this.loadReport();
            } catch( exception ) {
                // ...
            }
        },

        getType( type ) {
            const option    =   this.reportType.options.filter( option => {
                return option.name === type;
            });

            if ( option.length > 0 ) {
                return option[0].label;
            }

            return __( 'Unknown' );
        },

        loadReport() {
            if ( this.startDate === null || this.endDate ===null ) {
                return nsSnackBar.error( __( 'Unable to proceed. Select a correct time range.' ) ).subscribe();
            }

            const startMoment   =   moment( this.startDate );
            const endMoment     =   moment( this.endDate );

            if ( endMoment.isBefore( startMoment ) ) {
                return nsSnackBar.error( __( 'Unable to proceed. The current time range is not valid.' ) ).subscribe();
            }

            nsHttpClient.post( '/api/nexopos/v4/reports/sale-report', { 
                startDate: this.startDate,
                endDate: this.endDate,
                type: this.reportType.value,
                user_id: this.filterUser.value
            }).subscribe({
                next: response => {
                    this.result     =   response.result;
                    this.summary    =   response.summary;
                }, 
                error : ( error ) => {
                    nsSnackBar.error( error.message ).subscribe();
                }
            });
        },

        computeTotal( collection, attribute ) {
            if ( collection.length > 0 ) {
                return collection.map( entry => parseFloat( entry[ attribute ] ) )
                    .reduce( ( b, a ) => b + a );
            }

            return 0;
        },

        setEndDate( moment ) {
            this.endDate    =   moment.format();
        },
    }
}
</script>