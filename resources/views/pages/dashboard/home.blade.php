@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( '../common/dashboard-header' )
        <div id="dashboard-content" class="px-4">
            <ns-dashboard-cards></ns-dashboard-cards>
            <div class="-m-4 flex flex-wrap">
                <div class="p-4 w-full lg:w-1/2">
                    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
                        <div class="head bg-indigo-400 flex-auto flex h-56">
                        </div>
                        <div class="p-2 bg-white -mx-4 flex flex-wrap">
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Gross Income' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 725</h2>
                            </div>
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Week Taxes' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 125</h2>
                            </div>
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Net Income' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 600</h2>
                            </div>
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Week Expenses' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 200</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full lg:w-1/2">
                    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
                        <div class="head bg-gray-200 flex-auto flex h-56">
                            <table class="table flex-auto">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 bg-white border-t-0 border-l-0 border-gray-300 border">{{ __( 'Order' ) }}</th>
                                        <th class="px-3 py-2 bg-white border-t-0 border-gray-300 border">{{ __( 'Due' ) }}</th>
                                        <th class="px-3 py-2 bg-white border-t-0 border-r-0 border-gray-300 border">{{ __( 'Since' ) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">001-0620</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">$5</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">1 day ago</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">002-0620</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">$8</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">1 day ago</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">002-0420</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">$20</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">2 days ago</td>
                                    </tr>
                                    <tr>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">021-0320</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">$12</td>
                                        <td class="text-center text-sm text-gray-800 bg-white border-t-0 border-gray-300 border">3 days ago</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-2 bg-white -mx-4 flex flex-wrap">
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Week Due' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 35</h2>
                            </div>
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Partially' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 354</h2>
                            </div>
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Net Income' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 600</h2>
                            </div>
                            <div class="px-4 w-1/2 lg:w-1/4 flex flex-col items-center justify-center">
                                <span class="text-xs text-gray-600">{{ __( 'Week Expenses' ) }}</span>
                                <h2 class="text-3xl text-gray-700 font-bold">$ 200</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full lg:w-1/4">
                    <ns-best-customers></ns-best-customers>
                </div>
                <div class="p-4 w-full lg:w-1/4">
                    <ns-best-cashiers></ns-best-cashiers>
                </div>
            </div>
        </div>
    </div>
@endsection