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
        <ns-yearly-report inline-template v-cloak>
            <div id="yearly-report" class="px-4">
                <div class="flex -mx-2">
                    <div class="px-2">
                        <ns-datepicker :date="startDate" @change="setStartDate( $event )"></ns-datepicker>
                    </div>
                    <div class="px-2">
                        <ns-datepicker :date="endDate" @change="setEndDate( $event )"></ns-datepicker>
                    </div>
                    <div class="px-2">
                        <button @click="loadReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">Load</span>
                        </button>
                    </div>
                    <div class="px-2">
                        <button @click="printSaleReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-print text-xl"></i>
                            <span class="pl-2">Print</span>
                        </button>
                    </div>
                </div>
                <div id="sale-report" class="anim-duration-500 fade-in-entrance">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-gray-600">
                                <ul>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ __( 'Document : Sale Report' ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded my-4 overflow-hidden">
                        <div class="border-b border-gray-200 overflow-auto">
                            <table class="table w-full">
                                <thead class="text-gray-700">
                                    <tr>
                                        <th width="100" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left"></th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Sales' ) }}</th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Taxes' ) }}</th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Expenses' ) }}</th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Income' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'January' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Febuary' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'March' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'April' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'May' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'June' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'July' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'August' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'September' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'October' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'November' ) }}</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'December' ) }}</th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ns-yearly-report>
    </div>
</div>
@endsection