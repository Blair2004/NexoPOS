@extends( 'layout.base' )

@section( 'layout.base.header' )
    @parent
    <script>
    const POS               =   new Object;
    POS.order               =   new Object;
    POS.order.products      =   [];
    POS.order.customer      =   new Object;
    POS.breadcrumb          =   [];
    POS.grid                =   [];
    POS.header              =   new Object;
    POS.header.buttons      =   [];
    POS.activeCategory      =   new Object;
    </script>
    @yield( 'layout.base.header.pos' )
@endsection

@section( 'layout.base.body' )
<div class="h-full bg-gray-300 flex flex-col" id="pos-container">
    <div class="h-12 flex-shrink-0 px-2 pt-2 flex">
        <div class="-mx-2 flex">
            <div class="flex px-2">
                <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                    <i class="mr-1 text-xl las la-tachometer-alt"></i>
                    <span>Dashboard</span>
                </button>
            </div>
            <div class="flex px-2">
                <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                    <i class="mr-1 text-xl lar la-hand-pointer"></i>
                    <span>Pending Orders</span>
                </button>
            </div>
            <div class="flex px-2">
                <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                    <i class="mr-1 text-xl las la-truck"></i>
                    <span> Delivery</span>
                </button>
            </div>
            <div class="flex px-2">
                <button class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
                    <i class="mr-1 text-xl lar la-user-circle"></i>
                    <span>Customers</span>
                </button>
            </div>
        </div>
    </div>
    <div class="flex-auto overflow-hidden flex p-2">
        <div class="flex flex-auto overflow-hidden -m-2">
            <div class="w-1/2 flex overflow-hidden p-2">
                <div id="pos-cart" class="rounded shadow bg-white flex-auto flex overflow-hidden">
                    <div class="cart-table flex flex-auto flex-col overflow-hidden">
                        <div class="w-full text-gray-700 font-semibold flex">
                            <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200 bg-gray-100">{{ __( 'Product' ) }}</div>
                            <div class="w-1/6 p-2 border-b border-t-0 border-gray-200 bg-gray-100">{{ __( 'Qty' ) }}</div>
                            <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 bg-gray-100">{{ __( 'Total' ) }}</div>
                        </div>
                        <div class="flex flex-auto flex-col overflow-auto">
                            @for( $i = 0; $i < 20; $i++ )
                            <div class="text-gray-700 flex">
                                <div class="w-4/6 p-2 border border-l-0 border-t-0 border-gray-200">
                                    <h3 class="font-semibold">Some Product</h3>
                                    <div class="-mx-1 flex">
                                        <div class="px-1">
                                            <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Price : $25</a>
                                        </div>
                                        <div class="px-1"> 
                                            <a class="hover:text-blue-400 cursor-pointer outline-none border-dashed py-1 border-b border-blue-400 text-sm">Discount 5% : $10</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-1/6 p-2 border-b border-gray-200 flex items-center justify-center cursor-pointer hover:bg-blue-100">
                                    <span class="border-b border-dashed border-blue-400 p-2">12</span>
                                </div>
                                <div class="w-1/6 p-2 border border-r-0 border-t-0 border-gray-200 flex items-center justify-center">$20</div>
                            </div>
                            @endfor
                        </div>
                        <div class="flex">
                            <table class="table w-full text-sm text-gray-700">
                                <tr>
                                    <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                                    <td width="200" class="border border-gray-400 p-2">Sub Total</td>
                                    <td width="200" class="border border-gray-400 p-2"></td>
                                </tr>
                                <tr>
                                    <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                                    <td width="200" class="border border-gray-400 p-2">Discount</td>
                                    <td width="200" class="border border-gray-400 p-2"></td>
                                </tr>
                                <tr>
                                    <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                                    <td width="200" class="border border-gray-400 p-2">Shipping</td>
                                    <td width="200" class="border border-gray-400 p-2"></td>
                                </tr>
                                <tr class="bg-green-200">
                                    <td width="100" colspan="2" class="border border-gray-400 p-2"></td>
                                    <td width="200" class="border border-gray-400 p-2">Total</td>
                                    <td width="200" class="border border-gray-400 p-2"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="h-16 flex flex-shrink-0 border-t border-gray-200">
                            <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-green-500 text-white hover:bg-green-600 border-r border-green-600 flex-auto">
                                <i class="mr-2 text-3xl las la-cash-register"></i> 
                                <span class="text-2xl">Pay</span>
                            </div>
                            <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-blue-500 text-white border-r hover:bg-blue-600 border-blue-600 flex-auto">
                                <i class="mr-2 text-3xl las la-pause"></i> 
                                <span class="text-2xl">Hold</span>
                            </div>
                            <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-white border-r border-gray-200 hover:bg-indigo-100 flex-auto text-gray-700">
                                <i class="mr-2 text-3xl las la-percent"></i> 
                                <span class="text-2xl">Discount</span>
                            </div>
                            <div class="flex-shrink-0 w-1/4 flex items-center font-bold cursor-pointer justify-center bg-red-500 text-white border-gray-200 hover:bg-red-600 flex-auto">
                                <i class="mr-2 text-3xl las la-trash"></i> 
                                <span class="text-2xl">Void</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-1/2 p-2 flex overflow-hidden">
                <div id="pos-grid" class="rounded shadow bg-white overflow-hidden flex-auto">
                    <div id="grid-header" class="p-2 border-b border-gray-200">
                        <div class="border rounded flex border-gray-400 overflow-hidden">
                            <button class="w-10 h-10 bg-gray-200 border-r border-gray-400">S</button>
                            <button class="w-10 h-10 shadow-inner bg-gray-300 border-r border-gray-400">S</button>
                            <input type="text" class="flex-auto outline-none px-2 bg-gray-100">
                        </div>
                    </div>
                    <div id="grid-breadscrumb" class="p-2 border-gray-200">
                        <ul class="flex">
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Home > </a></li>
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Mens > </a></li>
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Shirts > </a></li>
                            <li><a href="javascript:void(0)" class="px-3 text-gray-700">Sport > </a></li>
                        </ul>
                    </div>
                    <div id="grid-items" class="overflow-hidden flex-auto">
                        <div class="grid grid-cols-6 gap-0 overflow-y-auto">
                            @for( $i = 0 ; $i < 100 ; $i++ )
                            <div class="border h-40 border-gray-200">{{ $i }}</div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section( 'layout.base.footer' )
    @parent
@verbatim
<script>
    const nsPopup   =   new Popup({
        primarySelector: '#pos-container',
        popupClass : `shadow-lg h-4/5-screen w-4/5 bg-white`
    });

    const sampleComponent   =   Vue.component( 'sample', {
        template: `
        <div class="h-full w-full">
            <h1>Hello World {{ count }}</h1>
            <button @click="increase()" class="px-3 py-2 bg-blue-400">Click</button>
        </div>
        `,
        data: () => {
            return {
                count: 0
            }
        },
        methods: {
            increase() {
                this.count++;
                if ( this.count === 4 ) {
                    this.close();
                }
            },
            close() {
                console.log( this.$popup );
                // this.$popup.close();
            }
        }
    });

    nsPopup.show( sampleComponent );

    setTimeout( () => {
        // nsPopup.close();
    }, 5000 );
</script>
@endverbatim
@endsection