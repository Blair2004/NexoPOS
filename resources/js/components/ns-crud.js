window.Vue.component( 'ns-crud', {
    data: () => {
        return {

        }
    }, 
    template: `
    <table class="table w-full">
        <thead>
            <tr class="text-gray-700 border-b border-gray-200">
                <th class="text-center px-2 border-gray-200 w-16 py-2">ID</th>
                <th class="text-left px-2 border-gray-200 py-2">Product</th>
                <th class="text-left px-2 border-gray-200 py-2">Name</th>
                <th class="text-left px-2 border-gray-200 py-2">Price</th>
                <th class="text-left px-2 border-gray-200 py-2">Quantity</th>
                <th class="text-left px-2 border-gray-200 py-2 w-16"></th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-gray-200 border">
                <td class="text-gray-700 font-sans border-gray-200 p-2">
                    <ns-checkbox checked="checked"></ns-checkbox>
                </td>
                <td class="text-gray-700 font-sans border-gray-200 p-2">Product-21</td>
                <td class="text-gray-700 font-sans border-gray-200 p-2">Product-31</td>
                <td class="text-gray-700 font-sans border-gray-200 p-2">Product-41</td>
                <td class="text-gray-700 font-sans border-gray-200 p-2">Product-51</td>
                <td class="text-gray-700 font-sans border-gray-200 p-2">
                    <button class="outline-none rounded-full w-24 text-sm p-1 border border-gray-400 hover:bg-blue-400 hover:text-white hover:border-transparent"><i class="las la-ellipsis-h"></i> Options</button>
                </td>
            </tr>
        </tbody>
    </table>
    `,
    methods: {
        
    },
    mounted() {
        console.log( 'mounted' );
    }
})