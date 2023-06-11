@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <ns-low-stock-report inline-template v-cloak> <!--  -->
            <div id="report-section" class="px-4">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="loadReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                                <i class="las la-sync-alt text-xl"></i>
                                <span class="pl-2">{{ __( 'Load' ) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="printSaleReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                                <i class="las la-print text-xl"></i>
                                <span class="pl-2">{{ __( 'Print' ) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="selectReport()" class="rounded flex justify-between shadow py-1 items-center px-2">
                                <i class="las la-print text-xl"></i>
                                <span class="pl-2">{{ __( 'Report Type' ) }} : @{{ reportTypeName }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="low-stock-report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <?php
                            $documentTitle  =   '{{ reportTypeName }}';
                            ?>
                            <div class="text-primary">
                                <ul>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'Document : %s' ), $documentTitle ) }}</li>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="text-primary shadow rounded my-4">
                        <div class="ns-box">
                            <div class="ns-box-body" v-if="reportType === 'low_stock'">
                                <table class="table ns-table w-full">
                                    <thead>
                                        <tr>
                                            <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                            <th class="border p-2 text-left">{{ __( 'Unit' ) }}</th>
                                            <th width="150" class="border border-info-secondary bg-info-primary p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                            <th width="150" class="border border-success-secondary bg-success-primary p-2 text-right">{{ __( 'Price' ) }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="products.length === 0">
                                            <td colspan="4" class="p-2 border text-center">
                                                <span>{{ __( 'There is no product to display...' ) }}</span>
                                            </td>
                                        </tr>
                                        <tr v-for="unitQuantity of products" class="text-sm">
                                            <td class="p-2 border">@{{ unitQuantity.product.name }}</td>
                                            <td class="p-2 border">@{{ unitQuantity.unit.name }}</td>
                                            <td class="p-2 border text-right">@{{ unitQuantity.quantity }}</td>
                                            <td class="p-2 border border-success-secondary bg-success-primary text-right">@{{ unitQuantity.quantity * unitQuantity.sale_price | currency }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="ns-box-body" v-if="reportType === 'stock_report'">
                                <table class="table ns-table w-full">
                                    <thead>
                                        <tr>
                                            <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                            <th class="border p-2 text-left">{{ __( 'Unit' ) }}</th>
                                            <th width="150" class="border p-2 text-right">{{ __( 'Price' ) }}</th>
                                            <th width="150" class="border p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                            <th width="150" class="border p-2 text-right">{{ __( 'Total Price' ) }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="stockReportResult.data === undefined || stockReportResult.data.length === 0">
                                            <td colspan="5" class="p-2 border text-center">
                                                <span>{{ __( 'There is no product to display...' ) }}</span>
                                            </td>
                                        </tr>
                                        <template v-if="stockReportResult.data !== undefined">
                                            <template v-for="product of stockReportResult.data">
                                                <tr v-for="unitQuantity of product.unit_quantities" class="text-sm">
                                                    <td class="p-2 border">@{{ product.name }} (@{{ product.sku }})</td>
                                                    <td class="p-2 border">@{{ unitQuantity.unit.name }}</td>
                                                    <td class="p-2 border text-right">@{{ unitQuantity.sale_price | currency }}</td>
                                                    <td class="p-2 border text-right">@{{ unitQuantity.quantity }}</td>
                                                    <td class="p-2 border text-right">@{{ unitQuantity.quantity * unitQuantity.sale_price | currency }}</td>
                                                </tr>
                                            </template>
                                        </template>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="p-2 border"></td>
                                            <td class="p-2 border"></td>
                                            <td class="p-2 border"></td>
                                            <td class="p-2 border text-right">@{{ sum( stockReportResult, 'quantity' ) }}</td>
                                            <td class="p-2 border text-right">@{{ totalSum( stockReportResult, 'sale_price', 'quantity' ) | currency }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div class="flex justify-end p-2" v-if="stockReportResult.data">
                                    <ns-paginate @load="loadStockReport( $event )" :pagination="stockReportResult"></ns-pagination>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ns-low-stock-report>
    </div>
</div>
@endsection