@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col" id="dashboard-content">
        <div class="px-4">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
                <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
            </div>
        </div>
        <ns-low-stock-report inline-template v-cloak> <!--  -->
            <div id="report-section" class="px-4">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <button @click="loadReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">{{ __( 'Load' ) }}</span>
                        </button>
                    </div>
                    <div class="px-2">
                        <button @click="printSaleReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-print text-xl"></i>
                            <span class="pl-2">{{ __( 'Print' ) }}</span>
                        </button>
                    </div>
                </div>
                <div id="low-stock-report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-gray-600">
                                <ul>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ __( 'Document : Low Stock' ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded my-4">
                        <div class="border-b border-gray-200">
                            <table class="table w-full">
                                <thead class="text-gray-700">
                                    <tr>
                                        <th class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Product' ) }}</th>
                                        <th class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Unit' ) }}</th>
                                        <th width="150" class="text-gray-700 border border-blue-200 bg-blue-100 p-2 text-right">{{ __( 'Quantity' ) }}</th>
                                        <th width="150" class="text-gray-700 border border-green-200 bg-green-100 p-2 text-right">{{ __( 'Price' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <tr v-if="products.length === 0" class="bg-gray-50">
                                        <td colspan="4" class="p-2 border border-gray-200 text-center">
                                            <span>{{ __( 'There is no product to display...' ) }}</span>
                                        </td>
                                    </tr>
                                    <tr v-for="unitQuantity of products" class="bg-gray-50 text-sm">
                                        <td class="p-2 border border-gray-200">@{{ unitQuantity.product.name }}</td>
                                        <td class="p-2 border border-gray-200">@{{ unitQuantity.unit.name }}</td>
                                        <td class="p-2 border border-blue-200 bg-blue-100 text-right">@{{ unitQuantity.quantity }}</td>
                                        <td class="p-2 border border-green-200 bg-green-100 text-right">@{{ unitQuantity.quantity * unitQuantity.sale_price | currency }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ns-low-stock-report>
    </div>
</div>
@endsection