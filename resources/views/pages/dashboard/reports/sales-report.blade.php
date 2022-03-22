@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <ns-sale-report inline-template v-cloak>
            <div id="report-section" class="px-4">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>
                    </div>
                    <div class="px-2">
                        <ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>
                    </div>
                    <div class="px-2">
                        <button @click="loadReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">{{ __( 'Load' ) }}</span>
                        </button>
                    </div>
                </div>
                <div class="flex -mx-2">
                    <div class="px-2">
                        <button @click="printSaleReport()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                            <i class="las la-print text-xl"></i>
                            <span class="pl-2">{{ __( 'Print' ) }}</span>
                        </button>
                    </div>
                    <div class="px-2">
                        <button @click="openSettings()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                            <i class="las la-cogs text-xl"></i>
                            <span class="pl-2">{{ __( 'Type' ) }} : @{{ getType( reportType.value ) }}</span>
                        </button>
                    </div>
                    <div class="px-2">
                        <button @click="openUserFiltering()" class="rounded flex justify-between bg-input-button shadow py-1 items-center text-primary px-2">
                            <i class="las la-user text-xl"></i>
                            <span class="pl-2">{{ __( 'Filter By User' ) }} : @{{ selectedUser || __( 'All Users' ) }}</span>
                        </button>
                    </div>
                </div>
                <div id="sale-report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-secondary">
                                <ul>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed">{{ __( 'Document : Sale Report' ) }}</li>
                                    <li class="pb-1 border-b border-dashed">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="-mx-4 flex md:flex-row flex-col">
                            <div class="w-full md:w-1/2 px-4">
                                <div class="shadow rounded my-4 ns-box">
                                    <div class="border-b ns-box-body">
                                        <table class="table ns-table w-full">
                                            <tbody class="text-primary">
                                                <tr class="">
                                                    <td width="200" class="font-semibold p-2 border text-left bg-info-secondary border-info-primary text-white">{{ __( 'Sub Total' ) }}</td>
                                                    <td class="p-2 border text-right border-info-primary">@{{ summary.subtotal | currency }}</td>
                                                </tr>
                                                <tr class="">
                                                    <td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Sales Discounts' ) }}</td>
                                                    <td class="p-2 border text-right border-error-primary">@{{ summary.sales_discounts | currency }}</td>
                                                </tr>
                                                <tr class="">
                                                    <td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Sales Taxes' ) }}</td>
                                                    <td class="p-2 border text-right border-error-primary">@{{ summary.sales_taxes | currency }}</td>
                                                </tr>
                                                <tr class="">
                                                    <td width="200" class="font-semibold p-2 border text-left bg-error-secondary border-error-primary text-white">{{ __( 'Products Taxes' ) }}</td>
                                                    <td class="p-2 border text-right border-error-primary">@{{ summary.producs_taxes | currency }}</td>
                                                </tr>
                                                <tr class="">
                                                    <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-success-secondary text-white">{{ __( 'Total' ) }}</td>
                                                    <td class="p-2 border text-right border-success-primary">@{{ summary.total | currency }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 px-4">
                            </div>
                        </div>
                    </div>
                    <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'products_report'">
                        <div class="border-b border-box-edge">
                            <table class="table ns-table w-full">
                                <thead class="text-primary">
                                    <tr>
                                        <th class="border p-2 text-left">{{ __( 'Products' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-primary">
                                    <tr v-for="product of result" :key="product.id">
                                        <td class="p-2 border">@{{ product.name }}</td>
                                        <td class="p-2 border text-right">@{{ product.quantity }}</td>
                                        <td class="p-2 border text-right">@{{ product.discount | currency }}</td>
                                        <td class="p-2 border text-right">@{{ product.tax_value | currency }}</td>
                                        <td class="p-2 border text-right">@{{ product.total_price | currency }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="text-primary font-semibold">
                                    <tr>
                                        <td class="p-2 border text-primary"></td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'quantity' ) }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'discount' ) | currency }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'tax_value' ) | currency }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_price' ) | currency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'categories_report'">
                        <div class="border-b border-box-edge">
                            <table class="table ns-table w-full">
                                <thead class="text-primary">
                                    <tr>
                                        <th class="border p-2 text-left">{{ __( 'Category' ) }}</th>
                                        <th class="border p-2 text-left">{{ __( 'Product' ) }}</th>
                                        <th width="100" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-primary">
                                    <template v-for="(category, categoryIndex) of result">
                                        <template v-if="category.products.length > 0">
                                            <tr v-for="(product,productIndex) of category.products" :key="parseInt( category.id + '' + product.id )">
                                                <td class="p-2 border">@{{ category.name }}</td>
                                                <td class="p-2 border">@{{ product.name }}</td>
                                                <td class="p-2 border text-right">@{{ product.quantity }}</td>
                                                <td class="p-2 border text-right">@{{ product.discount | currency }}</td>
                                                <td class="p-2 border text-right">@{{ product.tax_value | currency }}</td>
                                                <td class="p-2 border text-right">@{{ product.total_price | currency }}</td>
                                            </tr>
                                        </template>
                                        <tr :key="categoryIndex"  class="bg-info-primary">
                                            <td colspan="2" class="p-2 border border-info-primary">@{{ category.name }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'quantity' ) }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'discount' ) | currency }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'tax_value' ) | currency }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'total_price' ) | currency }}</td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="text-primary font-semibold">
                                    <tr>
                                        <td colspan="2" class="p-2 border text-primary"></td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_sold_items' ) }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_discount' ) | currency }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_tax_value' ) | currency }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_price' ) | currency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="bg-box-background shadow rounded my-4" v-if="reportType.value === 'categories_summary'">
                        <div class="border-b border-box-edge">
                            <table class="table ns-table w-full">
                                <thead class="text-primary">
                                    <tr>
                                        <th class="border p-2 text-left">{{ __( 'Category' ) }}</th>
                                        <th width="100" class="border p-2">{{ __( 'Quantity' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Discounts' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Taxes' ) }}</th>
                                        <th width="150" class="border p-2">{{ __( 'Total' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-primary">
                                    <template v-for="(category, categoryIndex) of result">
                                        <tr :key="categoryIndex"  class="">
                                            <td class="p-2 border text-left border-info-primary">@{{ category.name }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'quantity' ) }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'discount' ) | currency }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'tax_value' ) | currency }}</td>
                                            <td class="p-2 border text-right border-info-primary">@{{ computeTotal( category.products, 'total_price' ) | currency }}</td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="text-primary font-semibold">
                                    <tr>
                                        <td class="p-2 border text-primary"></td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_sold_items' ) }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_discount' ) | currency }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_tax_value' ) | currency }}</td>
                                        <td class="p-2 border text-right text-primary">@{{ computeTotal( result, 'total_price' ) | currency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ns-sale-report>
    </div>
</div>
@endsection