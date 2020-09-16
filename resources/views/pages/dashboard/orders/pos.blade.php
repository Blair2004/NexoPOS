@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="pos-container" class="w-full h-full bg-gray-200 flex flex-col">
    <div id="pos-header" class="h-12">
    </div>
    <div class="p-2 flex-auto flex">
        <div id="pos-sections" class="-m-2 bg-gray-200 flex flex-auto">
            <div class="w-1/2 flex pr-1 pl-3 py-2">
                <div id="pos-cart" class="rounded overflow-x-hidden shadow bg-white flex-auto">
                    <div class="cart-table">
                        <table class="table w-full">
                            <tr class="text-gray-700 font-semibold">
                                <td width="200" class="p-2 border-b border-gray-200 bg-gray-100">{{ __( 'Product' ) }}</td>
                                <td width="50" class="p-2 border-b border-gray-200 bg-gray-100">{{ __( 'Qty' ) }}</td>
                                <td width="50" class="p-2 border-b border-gray-200 bg-gray-100">{{ __( 'Total' ) }}</td>
                            </tr>
                        </table>
                        <table class="table w-full">
                            <tr class="hover:bg-gray-100 text-gray-700">
                                <td width="200" class="p-2 border-b border-gray-200">
                                    <h3 class="font-semibold">Some Product</h3>
                                    <div class="-mx-1 flex">
                                        <div class="px-1">
                                            <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Price : $25</a>
                                        </div>
                                        <div class="px-1"> 
                                            <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Discount 5% : $10</a>
                                        </div>
                                    </div>
                                </td>
                                <td width="50" class="p-2 border-b border-gray-200">2</td>
                                <td width="50" class="p-2 border-b border-gray-200">$20</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="w-1/2 flex pr-2 pl-1 py-2">
                <div id="pos-grid" class="rounded shadow bg-white flex-auto">
                    <div id="grid-header" class="p-2 border-b border-gray-200">
                        <div class="border rounded flex border-gray-400 overflow-hidden">
                            <button class="w-10 h-10 bg-gray-200 border-r border-gray-400">S</button>
                            <button class="w-10 h-10 shadow-inner bg-gray-300 border-r border-gray-400">S</button>
                            <input type="text" class="flex-auto outline-none px-2 bg-gray-100">
                        </div>
                    </div>
                    <div id="grid-breadscrumb" class="p-2 border-b border-gray-200">
                        <ul class="flex">
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Home > </a></li>
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Mens > </a></li>
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Shirts > </a></li>
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Sport > </a></li>
                        </ul>
                    </div>
                    <div id="grid-items" class="overflow-y-auto">
                        <div>
                            <div class="h-32 w-32 border border-gray-200"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection