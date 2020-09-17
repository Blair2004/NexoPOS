@extends( 'layout.base' )

@section( 'layout.base.body' )
<div id="pos-container" class="w-full h-full bg-gray-200 flex flex-col">
    <div id="pos-header" class="h-12 flex-shrink-0">
    </div>
    <div class="p-2 flex-auto flex">
        <div id="pos-sections" class="-m-2 bg-gray-200 flex flex-auto">
            <div class="w-1/2 flex pr-1 pl-3 py-2">
                <div id="pos-cart" class="rounded overflow-x-hidden shadow bg-white flex-auto flex">
                    <div class="cart-table flex flex-col flex-auto">
                        <div class="w-full text-gray-700 font-semibold flex">
                            <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200 bg-gray-100">{{ __( 'Product' ) }}</div>
                            <div class="w-1/6 p-2 border-b border-t-0 border-gray-200 bg-gray-100">{{ __( 'Qty' ) }}</div>
                            <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 bg-gray-100">{{ __( 'Total' ) }}</div>
                        </div>
                        <div class="flex-auto bg-white">
                            
                        </div>
                        <div class="h-16 flex border-t border-gray-200">
                            <div class="flex items-center font-bold text-gray-700 cursor-pointer justify-center hover:bg-green-100 border-r border-gray-200 flex-auto">Pay</div>
                            <div class="flex items-center font-bold text-gray-700 cursor-pointer justify-center border-r border-gray-200 hover:bg-teal-100 flex-auto">Hold</div>
                            <div class="flex items-center font-bold text-gray-700 cursor-pointer justify-center border-gray-200 hover:bg-red-100 flex-auto">Void</div>
                        </div>
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