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
            <div class="px-4">
                <ns-notice color="red" v-if="timezone === ''">
                    <template v-slot:title>{{ __( 'An Error Has Occured' ) }}</template>
                    <template v-slot:description>{{ __( 'Unable to load the report as the timezone is not set on the settings.' ) }}</template>
                </ns-notice>
                <div class="flex -mx-2" v-if="timezone !== ''">
                    <div class="px-2">
                        <input type="text" v-model="year" placeholder="{{ __( 'Year' ) }}" class="outline-none rounded border-gray-400 border-2 focus:border-blue-400 p-2">
                    </div>
                    <div class="px-2 flex">
                        <button @click="loadReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">{{ __( 'Load' ) }}</span>
                        </button>
                    </div>
                    <div class="px-2 flex">
                        <button @click="printSaleReport()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-print text-xl"></i>
                            <span class="pl-2">{{ __( 'Print' ) }}</span>
                        </button>
                    </div>
                    <div class="px-2 flex">
                        <button @click="recomputeForSpecificYear()" class="rounded flex justify-between bg-white shadow py-1 items-center text-gray-700 px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">{{ __( 'Recompute' ) }}</span>
                        </button>
                    </div>
                </div>
                <div id="annual-report" class="anim-duration-500 fade-in-entrance" v-if="timezone !== ''">
                    <div class="flex w-full">
                        <div class="my-4 flex justify-between w-full">
                            <div class="text-gray-600">
                                <ul>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ sprintf( __( 'Date : %s' ), ns()->date->getNowFormatted() ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ __( 'Document : Annual Report' ) }}</li>
                                    <li class="pb-1 border-b border-dashed border-gray-200">{{ sprintf( __( 'By : %s' ), Auth::user()->username ) }}</li>
                                </ul>
                            </div>
                            <div>
                                <img class="h-28" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded my-4 overflow-hidden">
                        <div class="border-b border-gray-200 overflow-auto">
                            <table class="table w-full">
                                <thead class="text-gray-700">
                                    <tr>
                                        <th width="100" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left"></th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">{{ __( 'Sales' ) }}</th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">{{ __( 'Taxes' ) }}</th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">{{ __( 'Expenses' ) }}</th>
                                        <th width="150" class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">{{ __( 'Income' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'January' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[1] ? report[1][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-left">{{ __( 'Febuary' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[2] ? report[2][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'March' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[3] ? report[3][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'April' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[4] ? report[4][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'May' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[5] ? report[5][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'June' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[6] ? report[6][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'July' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[7] ? report[7][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'August' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[8] ? report[8][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'September' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[9] ? report[9][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'October' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[10] ? report[10][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'November' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[11] ? report[11][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'December' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( report[12] ? report[12][ label ] : 0 ) | currency }}</td>
                                        </template>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="bg-gray-100 text-left text-gray-700 border border-gray-300 p-2">{{ __( 'Total' ) }}</td>
                                        <template v-for="label of labels">
                                            <td class="bg-gray-100 text-gray-700 border border-gray-300 p-2 text-right">@{{ ( sumOf( label ) ) | currency }}</td>
                                        </template>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </ns-yearly-report>
    </div>
</div>
@endsection