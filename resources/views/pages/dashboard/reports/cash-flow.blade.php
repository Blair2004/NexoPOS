@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <ns-cash-flow-report inline-template v-cloak>
            <div id="report-section" class="px-4">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>
                    </div>
                    <div class="px-2">
                        <ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>
                    </div>
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="loadReport()" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">
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
                <div id="sale-report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-primary">
                                <ul>
                                    <li class="pb-1 border-b border-dashed border-box-edge">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'Document : Sale By Payment' ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-box-edge">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="shadow rounded my-4">
                        <div class="ns-box">
                            <div class="border-b ns-box-body">
                                <table class="ns-table table w-full">
                                    <thead class="">
                                        <tr>
                                            <th class="border p-2 text-left">{{ __( 'Account' ) }}</th>
                                            <th width="150" class="border border-error-secondary bg-error-primary p-2 text-right">{{ __( 'Debit' ) }}</th>
                                            <th width="150" class="text-right border-success-secondary bg-success-primary border p-2">{{ __( 'Credit' ) }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="">
                                        <tr v-for="expenseGroup of report.creditCashFlow">
                                            <td class="p-2 border"><i class="las la-arrow-right"></i> <strong>@{{ expenseGroup.account }}</strong> : @{{ expenseGroup.name }}</td>
                                            <td class="p-2 border border-error-secondary bg-error-primary text-right">@{{ 0 | currency }}</td>
                                            <td class="p-2 border text-right border-success-secondary bg-success-primary">@{{ expenseGroup.total | currency }}</td>
                                        </tr>
                                        <tr v-for="expenseGroup of report.debitCashFlow">
                                            <td class="p-2 border"><i class="las la-arrow-right"></i> <strong>@{{ expenseGroup.account }}</strong> : @{{ expenseGroup.name }}</td>
                                            <td class="p-2 border border-error-secondary bg-error-primary text-right">@{{ expenseGroup.total | currency }}</td>
                                            <td class="p-2 border text-right border-success-secondary bg-success-primary">@{{ 0 | currency }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class=" font-semibold">
                                        <tr>
                                            <td class="p-2 border">{{ __( 'Sub Total' ) }}</td>
                                            <td class="p-2 border border-error-secondary bg-error-primary text-right ">@{{ report.total_debit ? report.total_debit : 0 | currency }}</td>
                                            <td class="p-2 border text-right border-success-secondary bg-success-primary">@{{ report.total_credit ? report.total_credit : 0 | currency }}</td>
                                        </tr>
                                        <tr>
                                            <td class="p-2 border">{{ __( 'Balance' ) }}</td>
                                            <td colspan="2" class="p-2 border text-right border-info-secondary bg-info-primary">
                                                @{{ balance | currency }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ns-cash-flow-report>
    </div>
</div>
@endsection