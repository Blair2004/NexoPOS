@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( '../common/dashboard-header' )
    <div id="dashboard-content" class="px-4">
        <div class="page-inner-header">

        </div>
        <div id="crud-table" class="w-full shadow rounded-lg bg-white">
            <div id="crud-table-header" class="p-2 border-b border-gray-200 flex justify-between flex-wrap">
                <div id="crud-search-box" class="w-full md:w-auto">
                    <div class="rounded-full p-1 bg-gray-200 flex">
                        <input type="text" class="bg-transparent outline-none px-2">
                        <button class="rounded-full w-8 h-8 bg-white outline-none hover:bg-blue-400 hover:text-white"><i class="las la-search"></i></button>
                    </div>
                </div>
                <div id="crud-buttons" class="-mx-1 flex flex-wrap w-full md:w-auto">
                    <div class="px-1 flex">
                        <button class="rounded-full text-sm h-10 bg-blue-400 px-4 outline-none text-white"><i class="las la-download"></i> {{ __( 'Export' ) }}</button>
                    </div>
                    <div class="px-1 flex">
                        <button class="rounded-full text-sm h-10 bg-green-400 px-4 outline-none text-white"><i class="las la-upload"></i> {{ __( 'Import' ) }}</button>
                    </div>
                    <div class="px-1 flex">
                        <button class="rounded-full text-sm h-10 hover:shadow-lg hover:border-transparent hover:bg-gray-100 outline-none border-gray-400 border text-gray-700 px-4"><i class="las la-filter"></i> {{ __( 'Bulk Options' )}}</button>
                    </div>
                </div>
            </div>
            <div>
                <ns-crud url="https://nexopos-v4.std/dashboard/crud/customers" id="crud-table-body"></ns-crud>
            </div>
        </div>
    </div>
</div>
@endsection