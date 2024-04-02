<template>
    <div id="report-section">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-field :field="startDateField"></ns-field>
            </div>
            <div class="px-2">
                <ns-field :field="endDateField"></ns-field>
            </div>
            <div class="px-2" v-if="selectedCustomer">
                <div class="ns-button">
                    <button @click="handleSelectedCustomer( selectedCustomer )" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">
                        <i class="las la-sync-alt text-xl"></i>
                        <span class="pl-2">{{ __( 'Load' ) }}</span>
                    </button>
                </div>
            </div>
            <div class="px-2">
                <div class="ns-button">
                    <button @click="printSaleReport()" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">
                        <i class="las la-print text-xl"></i>
                        <span class="pl-2">{{ __( 'Print' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
        <div>
            <ns-search
                :placeholder="__( 'Search Customer...' )"
                :label="[ 'first_name', 'last_name' ]"
                value="id"
                @select="handleSelectedCustomer( $event )"
                :url="searchUrl"
                ></ns-search>
        </div>
        <div id="report" class="anim-duration-500 fade-in-entrance">
            <div class="flex w-full">
                <div class="my-4 flex justify-between w-full">
                    <div class="text-primary">
                        <ul>
                            <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'Range : {date1} &mdash; {date2}' ).replace( '{date1}', this.startDateField.value ).replace( '{date2}', this.endDateField.value ) }}</li>
                            <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'Document : Customer Statement' ) }}</li>
                            <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'Customer : {selectedCustomerName}' ).replace( '{selectedCustomerName}', selectedCustomerName ) }}</li>
                            <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'By : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                    <div>
                        <img class="w-24" :src="storeLogo" :alt="storeName">
                    </div>
                </div>
            </div>
            <div class="shadow rounded">
                <div class="ns-box">
                    <div class="text-center ns-box-header p-2">
                        <h3 class="font-bold">{{ __( 'Summary' ) }}</h3>
                    </div>
                    <div class="border-b ns-box-body">
                        <table class="table ns-table w-full">
                            <tbody class="text-primary">
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-box-edge text-white print:text-black">{{ __( 'Total Purchases' ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( report.purchases_amount ) }}</td>
                                </tr>
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left bg-warning-secondary border-box-edge text-white print:text-black">{{ __( 'Due Amount' ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( report.owed_amount ) }}</td>
                                </tr>
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-box-edge text-white print:text-black">{{ __( 'Wallet Balance' ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( report.account_amount ) }}</td>
                                </tr>                                   
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left border-box-edge">{{ __( 'Credit Limit' ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ nsCurrency( report.credit_limit_amount ) }}</td>
                                </tr>                             
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left border-box-edge">{{ __( 'Total Orders' ) }}</td>
                                    <td class="p-2 border text-right border-box-edge">{{ report.total_orders }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <br><br>
            <div class="shadow rounded overflow-hidden" v-if="report.orders.length > 0">
                <div class="ns-box">
                    <div class="text-center ns-box-header p-2">
                        <h3 class="font-bold">{{ __( 'Orders' ) }}</h3>
                    </div>
                    <div class="border-b ns-box-body">
                        <table class="table ns-table w-full">
                            <thead>
                                <tr>
                                    <th class="p-2 border text-left">{{ __( 'Order' ) }}</th>
                                    <th class="p-2 border text-right">{{ __( 'Total' ) }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-primary">
                                <tr class="" v-for="order of report.orders" :key="order.id">
                                    <td width="200" class="font-semibold p-2 border text-left">{{ order.code }}</td>
                                    <td class="p-2 border text-right">{{ nsCurrency( order.total ) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsCurrency } from '~/filters/currency';
import { __ } from '~/libraries/lang';

export default {
    name: 'ns-customers-statement-report',
    props: [ 'storeLogo', 'storeName', 'search-url' ],
    data() {
        return {
            startDateField: {
                type: 'datetimepicker',
                name: 'startDate',
                value: moment( ns.date.current ).startOf( 'day' )
            },
            endDateField: {
                type: 'datetimepicker',
                name: 'endDate',
                value: moment( ns.date.current ).endOf( 'day' )
            },
            selectedCustomer: null,
            ns: window.ns,
            report: {
                total_purchases: 0,
                total_orders: 0,
                account_amount: 0,
                owed_amount:0,
                credit_limit_amount: 0,
                orders: [],
                wallet_transactions: [],
            }
        }
    },
    mounted() {
        // ...
    },
    computed: {
        selectedCustomerName() {
            if (this.selectedCustomer === null ) {
                return __( 'N/A' );
            }

            return `${this.selectedCustomer.first_name} ${this.selectedCustomer.last_name}` ;
        },
    },
    methods: {
        __,
        nsCurrency,
        printSaleReport() {
            this.$htmlToPaper( 'report' );
        },
        handleSelectedCustomer( customer ) {
            this.selectedCustomer   =   customer;

            nsHttpClient.post( `/api/reports/customers-statement/${customer.id}`, {
                rangeStarts: this.startDateField.value,
                rangeEnds: this.endDateField.value,
            }).subscribe({
                next: report => {
                    this.report     =   report;
                },
                error: error => {
                    nsSnackBar.error( error.message || __( 'An unexpected error occured' ) ).subscribe();
                }
            })
        }
    }
}
</script>