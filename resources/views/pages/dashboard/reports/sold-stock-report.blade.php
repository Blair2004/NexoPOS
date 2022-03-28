@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <ns-sold-stock-report inline-template v-cloak>
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
                            <button @click="loadReport()" class="rounded flex justify-between shadow py-1 items-center text-primary px-2">
                                <i class="las la-sync-alt text-xl"></i>
                                <span class="pl-2">{{ __( 'Load' ) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="printSaleReport()" class="rounded flex justify-between shadow py-1 items-center text-primary px-2">
                                <i class="las la-print text-xl"></i>
                                <span class="pl-2">{{ __( 'Print' ) }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="report-printable" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-primary">
                                <ul>
                                    <li class="pb-1 border-b border-dashed border-box-edge">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-box-edge">{{ __( 'Document : Sold Stock Report' ) }}</li>
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
                            <div class="border-b ns-box-body p-2">
                                <table class="table ns-table w-full">
                                    <thead class="">
                                        <tr>
                                            <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                            <th width="150" class="text-right border p-2">{{ __( 'Unit' ) }}</th>
                                            <th width="150" class="text-right border p-2">{{ __( 'Quantity' ) }}</th>
                                            <th width="150" class="text-right border p-2">{{ __( 'Tax Value' ) }}</th>
                                            <th width="150" class="text-right border p-2">{{ __( 'Total' ) }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="">
                                        <tr v-for="product of products" :key="product.id">
                                            <td class="p-2 border">@{{ product.name }}</td>
                                            <td class="p-2 border text-right">@{{ product.unit_name }}</td>
                                            <td class="p-2 border text-right">@{{ product.quantity }}</td>
                                            <td class="p-2 border text-right">@{{ product.tax_value | currency }}</td>
                                            <td class="p-2 border text-right">@{{ product.total_price | currency }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class=" font-semibold">
                                        <tr>
                                            <td colspan="2" class="p-2 border"></td>
                                            <td class="p-2 border text-right">@{{ totalQuantity }}</td>
                                            <td class="p-2 border text-right">@{{ totalTaxes | currency }}</td>
                                            <td class="p-2 border text-right">@{{ totalPrice | currency }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ns-sold-stock-report>
    </div>
</div>
@endsection