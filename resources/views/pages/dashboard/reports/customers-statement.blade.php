@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <ns-customers-statement inline-template>
        <div id="report-section">
            <div class="flex -mx-2">
                <div class="px-2">
                    <ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>
                </div>
                <div class="px-2">
                    <ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>
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
                    placeholder="{{ __( 'Search Customer...' ) }}"
                    label="name"
                    value="id"
                    @select="handleSelectedCustomer( $event )"
                    url="{{ ns()->route( 'ns-api.customers.search' ) }}"
                    ></ns-search>
            </div>
            <div id="report" class="anim-duration-500 fade-in-entrance">
                <div class="flex w-full">
                    <div class="my-4 flex justify-between w-full">
                        <div class="text-primary">
                            <ul>
                                <li class="pb-1 border-b border-dashed border-box-edge">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'Document : Customer Statement' ) }}</li>
                                <li class="pb-1 border-b border-dashed border-box-edge">{{ sprintf( __( 'Customer : %s' ), '{' . '{selectedCustomerName}' . '}' ) }}</li>
                                <li class="pb-1 border-b border-dashed border-box-edge">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                            </ul>
                        </div>
                        <div>
                            <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
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
                                        <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-info-primary text-white print:text-black">{{ __( 'Total Purchases' ) }}</td>
                                        <td class="p-2 border text-right border-info-primary">@{{ report.purchases_amount | currency }}</td>
                                    </tr>
                                    <tr class="">
                                        <td width="200" class="font-semibold p-2 border text-left bg-warning-secondary border-info-primary text-white print:text-black">{{ __( 'Due Amount' ) }}</td>
                                        <td class="p-2 border text-right border-info-primary">@{{ report.owed_amount | currency }}</td>
                                    </tr>
                                    <tr class="">
                                        <td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white print:text-black">{{ __( 'Wallet Balance' ) }}</td>
                                        <td class="p-2 border text-right border-info-primary">@{{ report.account_amount | currency }}</td>
                                    </tr>
                                    <tr class="">
                                        <td width="200" class="font-semibold p-2 border text-left border-info-primary">{{ __( 'Credit Limit' ) }}</td>
                                        <td class="p-2 border text-right border-info-primary">@{{ report.credit_limit_amount | currency }}</td>
                                    </tr>
                                    <tr class="">
                                        <td width="200" class="font-semibold p-2 border text-left border-info-primary">{{ __( 'Total Orders' ) }}</td>
                                        <td class="p-2 border text-right border-info-primary">@{{ report.total_orders }}</td>
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
                                        <td width="200" class="font-semibold p-2 border text-left">@{{ order.code }}</td>
                                        <td class="p-2 border text-right">@{{ order.total | currency }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ns-customers-statement>
@endsection

@section( 'layout.dashboard.footer.inject' )
<script>
    Vue.component( 'ns-customers-statement', {
        data() {
            return {
                startDate: moment().startOf( 'day' ),
                endDate: moment().endOf( 'day' ),
                selectedCustomer: null,
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

                return this.selectedCustomer.name;
            },
        },
        methods: {
            printSaleReport() {
                this.$htmlToPaper( 'report' );
            },
            setStartDate( date ) {
                this.startDate  =   date;
            },
            setEndDate( date ) {
                this.endDate    =   date;
            },
            handleSelectedCustomer( customer ) {
                this.selectedCustomer   =   customer;

                nsHttpClient.post( `/api/nexopos/v4/reports/customers-statement/${customer.id}`, {
                    rangeStarts: this.startDate.format( 'YYYY-MM-DD HH:mm:ss' ),
                    rangeEnds: this.endDate.format( 'YYYY-MM-DD HH:mm:ss' ),
                }).subscribe({
                    next: report => {
                        this.report     =   report;
                    },
                    error: error => {
                        nsSnackBar.error( error.message || __( 'An unexpected error occurred' ) ).subscribe();
                    }
                })
            }
        }
    })
</script>
@endsection
