@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div>
        @include( '../common/dashboard-header' )
        <div id="dashboard-content" class="px-4">
            <div class="-m-4 flex flex-wrap">
                <div class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-red-400 to-red-600 text-white px-3 py-5">
                        <div class="text-4xl font-black w-1/2 flex items-center justify-center">1542</div>
                        <div class="flex flex-col px-2 w-1/2 justify-center">
                            <h3 class="font-bold">Unpaid Orders</h3>
                            <h4 class="text-xs font-semibold">+4 Today</h4>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-green-400 to-green-600 text-white px-3 py-5">
                        <div class="text-4xl font-black w-1/2 flex items-center justify-center">$10k</div>
                        <div class="flex flex-col px-2 w-1/2 justify-center">
                            <h3 class="font-bold">Total Incomes</h3>
                            <h4 class="text-xs font-semibold">+$600 Today</h4>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-teal-400 to-teal-600 text-white px-3 py-5">
                        <div class="text-4xl font-black w-1/2 flex items-center justify-center">$435</div>
                        <div class="flex flex-col px-2 w-1/2 justify-center">
                            <h3 class="font-bold">Wasted Goods</h3>
                            <h4 class="text-xs font-semibold">+$10 Today</h4>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full md:w-1/2 lg:w-1/4">
                    <div class="flex flex-auto rounded-lg shadow-lg bg-gradient-to-br from-indigo-400 to-indigo-600 text-white px-3 py-5">
                        <div class="text-4xl font-black w-1/2 flex items-center justify-center">$600</div>
                        <div class="flex flex-col px-2 w-1/2 justify-center">
                            <h3 class="font-bold">Expenses</h3>
                            <h4 class="text-xs font-semibold">$580 Last Month</h4>
                        </div>
                    </div>
                </div>
            </div>
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
                    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
                        <div class="head bg-white flex-auto">
                            <div class="head text-center border-b border-gray-400 text-gray-700 w-full py-2">
                                <h2>{{ __( 'Best Customers' ) }}</h2>
                            </div>
                            <div class="body">
                                <table class="table w-full">
                                    <thead>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">John Doe</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$1412,55</th>
                                        </tr>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Nicolas Doe</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$1212,30</th>
                                        </tr>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Anna Doe</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$1000,45</th>
                                        </tr>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Paul Doe</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$900,70</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4 w-full lg:w-1/4">
                    <div class="flex flex-auto flex-col shadow rounded-lg overflow-hidden">
                        <div class="head bg-white flex-auto">
                            <div class="head text-center border-b border-gray-400 text-gray-700 w-full py-2">
                                <h2>{{ __( 'Best Cashiers' ) }}</h2>
                            </div>
                            <div class="body">
                                <table class="table w-full">
                                    <thead>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Sarah Cash</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$10814,74</th>
                                        </tr>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Cash Nico</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$9985,30</th>
                                        </tr>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Forest Gum</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$8000,20</th>
                                        </tr>
                                        <tr class="border-gray-300 border-b text-sm">
                                            <th class="p-2">
                                                <div class="-mx-1 flex justify-start items-center">
                                                    <div class="px-1">
                                                        <div class="rounded-full bg-gray-600 h-6 w-6 "></div>
                                                    </div>
                                                    <div class="px-1 justify-center">
                                                        <h3 class="font-semibold text-gray-600 items-center">Deluchy Doe</h3>
                                                    </div>
                                                </div>
                                            </th>
                                            <th class="flex justify-end text-green-700 p-2">$7750,60</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection