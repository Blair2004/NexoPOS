@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
            <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
        </div>
    </div>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <div class="picker">
                    <div class="rounded cursor-pointer bg-white shadow px-1 py-1 flex items-center text-gray-700">
                        <i class="las la-clock text-2xl"></i>
                        <span class="mx-1 text-sm">Date : N/A</span>
                    </div>
                    <!-- <div class="relative h-0 w-0">
                        <div class="h-56 w-64 mt-2 shadow rounded bg-white">
                        </div>
                    </div> -->
                </div>
            </div>
            <div class="px-2">
                <div class="picker">
                    <div class="rounded cursor-pointer bg-white shadow px-1 py-1 flex items-center text-gray-700">
                        <i class="las la-clock text-2xl"></i>
                        <span class="mx-1 text-sm">Date : N/A</span>
                    </div>
                    <!-- <div class="relative h-0 w-0">
                        <div class="h-56 w-64 mt-2 shadow rounded bg-white">
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
        <div class="bg-white rounded my-2">
            <div class="border-b border-gray-200">
                <table class="table w-full">
                    <thead class="text-gray-700">
                        <tr>
                            <th class="bg-blue-300 text-white border border-blue-400 p-2 text-left">Orders</th>
                            <th width="150" class="bg-blue-300 text-white border border-blue-400 p-2">Discounts</th>
                            <th width="150" class="bg-blue-300 text-white border border-blue-400 p-2">Taxes</th>
                            <th width="150" class="bg-blue-300 text-white border border-blue-400 p-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr>
                            <td class="p-2 border border-blue-200 bg-blue-100">A</td>
                            <td class="p-2 border border-blue-200 bg-blue-100">B</td>
                            <td class="p-2 border border-blue-200 bg-blue-100">C</td>
                            <td class="p-2 border border-blue-200 bg-blue-100">D</td>
                        </tr>
                    </tbody>
                    <tfoot class="text-gray-700 font-semibold">
                        <tr>
                            <td class="p-2 border border-blue-400 bg-blue-300 text-white">A</td>
                            <td class="p-2 border border-blue-400 bg-blue-300 text-white">B</td>
                            <td class="p-2 border border-blue-400 bg-blue-300 text-white">C</td>
                            <td class="p-2 border border-blue-400 bg-blue-300 text-white">D</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection