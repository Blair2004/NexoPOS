@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            @include( '../common/dashboard/title' )
        </div>
        <ns-best-products-report inline-template v-cloak>
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
                            <button @click="loadReport()" class="rounded flex justify-between border-box-background text-primary shadow py-1 items-center  px-2">
                                <i class="las la-sync-alt text-xl"></i>
                                <span class="pl-2">{{ __( 'Load' ) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-2">
                        <div class="ns-button">
                            <button @click="printSaleReport()" class="rounded flex justify-between border-box-background text-primary shadow py-1 items-center  px-2">
                                <i class="las la-print text-xl"></i>
                                <span class="pl-2">{{ __( 'Print' ) }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex -mx-2">
                    <div class="px-2">
                        <select v-model="sort" class="text-primary border-box-background shadow rounded p-2">
                            <option value="">{{ __( 'Sort Results' ) }}</option>
                            <option value="using_quantity_asc">{{ __( 'Using Quantity Ascending' ) }}</option>
                            <option value="using_quantity_desc">{{ __( 'Using Quantity Descending' ) }}</option>
                            <option value="using_sales_asc">{{ __( 'Using Sales Ascending' ) }}</option>
                            <option value="using_sales_desc">{{ __( 'Using Sales Descending' ) }}</option>
                            <option value="using_name_asc">{{ __( 'Using Name Ascending' ) }}</option>
                            <option value="using_name_desc">{{ __( 'Using Name Descending' ) }}</option>
                        </select>
                    </div>
                </div>
                <div id="best-products-report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-primary">
                                <ul>
                                    <li class="pb-1 border-b border-dashed ">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed ">{{ __( 'Document : Best Sales Report' ) }}</li>
                                    <li class="pb-1 border-b border-dashed ">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="shadow rounded my-4">
                        <div class="border-b ns-box">
                            <div class="ns-box-body p-2">
                                <table class="table ns-table w-full">
                                    <thead class="">
                                        <tr>
                                            <th class="p-2 text-left">{{ __( 'Product' ) }}</th>
                                            <th width="150" class="p-2 text-right">{{ __( 'Unit' ) }}</th>
                                            <th width="150" class="p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                            <th width="150" class="p-2 text-right">{{ __( 'Value' ) }}</th>
                                            <th width="150" class="p-2 text-right">{{ __( 'Progress' ) }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="" v-if="report">
                                        <tr :class="product.evolution === 'progress' ? 'bg-success-primary' : 'bg-error-primary'" v-for="product of report.current.products">
                                            <td class="p-2 border ">@{{ product.name }}</td>
                                            <td class="p-2 border text-right">@{{ product.unit_name }}</td>
                                            <td class="p-2 border text-right">
                                                <div class="flex flex-col">
                                                    <span>
                                                        <span>@{{ product.quantity }}</span>
                                                    </span>
                                                    <span :class="product.evolution === 'progress' ? 'text-success-light-secondary' : 'text-danger-light-secondary'" class="text-xs">
                                                        <span v-if="product.evolution === 'progress'">+</span>
                                                        @{{ product.quantity - product.old_quantity }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="p-2 border  text-right">
                                                <div class="flex flex-col">
                                                    <span>@{{ product.total_price | currency }}</span>
                                                    <span :class="product.evolution === 'progress' ? 'text-success-light-secondary' : 'text-danger-light-secondary'" class="text-xs">
                                                        <span v-if="product.evolution === 'progress'">+</span>
                                                        @{{ product.total_price - product.old_total_price | currency }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td :class="product.evolution === 'progress' ? 'text-success-light-secondary' : 'text-error-light-secondary'" class="p-2 border  text-right">
                                                <span v-if="product.evolution === 'progress'">
                                                @{{ product.difference.toFixed(2) }}% <i class="las la-arrow-up"></i>
                                                </span>
                                                <span v-if="product.evolution === 'regress'">
                                                @{{ product.difference.toFixed(2) }}% <i class="las la-arrow-down"></i>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="report.current.products.length === 0">
                                            <td colspan="5">
                                                {{ __( 'No results to show.' ) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center p-2">{{ __( 'Start by choosing a range and loading the report.' ) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot v-if="report" class="font-semibold">
                                        <tr>
                                            <td colspan="3" class="p-2 border"></td>
                                            <td class="p-2 border text-right">@{{ report.current.total_price | currency }}</td>
                                            <td class="p-2 border text-right"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ns-best-products-report>
    </div>
</div>
@endsection