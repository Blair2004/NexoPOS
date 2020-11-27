<template>
    <div id="report-section" class="px-4">
        <div class="flex -mx-2">
            <div class="px-2">
                <ns-datepicker></ns-datepicker>
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
        <div class="bg-white shadow rounded my-4">
            <div class="border-b border-gray-200">
                <table class="table w-full">
                    <thead class="text-gray-700">
                        <tr>
                            <th class="bg-gray-100 text-gray-800 border border-gray-300 p-2 text-left">Orders</th>
                            <th width="150" class="bg-gray-100 text-gray-800 border border-gray-300 p-2">Discounts</th>
                            <th width="150" class="bg-gray-100 text-gray-800 border border-gray-300 p-2">Taxes</th>
                            <th width="150" class="bg-gray-100 text-gray-800 border border-gray-300 p-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <tr>
                            <td class="p-2 border border-blue-200 bg-white">A</td>
                            <td class="p-2 border border-blue-200 bg-white">B</td>
                            <td class="p-2 border border-blue-200 bg-white">C</td>
                            <td class="p-2 border border-blue-200 bg-white">D</td>
                        </tr>
                    </tbody>
                    <tfoot class="text-gray-700 font-semibold">
                        <tr>
                            <td class="p-2 border border-gray-200 bg-gray-100 text-gray-700">A</td>
                            <td class="p-2 border border-gray-200 bg-gray-100 text-gray-700">B</td>
                            <td class="p-2 border border-gray-200 bg-gray-100 text-gray-700">C</td>
                            <td class="p-2 border border-gray-200 bg-gray-100 text-gray-700">D</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import nsDatepicker from "@/components/ns-datepicker";
export default {
    name: 'ns-sale-report',
    components: {
        nsDatepicker
    }
}
</script>