<div id="dashboard-header" class="w-full flex justify-between p-4">
    <div class="flex items-center">
        <div>
            <div class="hover:bg-gray-600 hover:text-white border border-gray-400 rounded-full h-10 w-10 cursor-pointer font-bold text-2xl justify-center items-center flex text-gray-800">
                <i class="las la-bell"></i>
            </div>
        </div>
    </div>
    <div class="top-tools-side flex items-center -mx-2">
        <div class="px-2">
            <div :class="menuToggled ? 'bg-white border-transparent shadow-lg rounded-t-lg' : 'border-gray-400 rounded-lg'" class="flex flex-col border py-2 justify-center hover:border-opacity-0 cursor-pointer hover:shadow-lg hover:bg-white">
                <div class="flex justify-between items-center flex-shrink-0" @click="menuToggled = ! menuToggled">
                    <span class="text-gray-600 px-2">Howdy, Blair</span>
                    <div class="px-2">
                        <div class="w-8 h-8 rounded-full bg-gray-800"></div>
                    </div>
                </div>
                <div class="w-full h-0 flex z-10 relative -mb-2 pt-2" v-if="menuToggled">
                    <ul class="text-gray-700 w-full bg-white shadow">
                        <li class="hover:bg-blue-400 bg-white hover:text-white px-2 py-1"><i class="las text-lg mr-2 la-user-tie"></i> Hello</li>
                        <li class="hover:bg-blue-400 bg-white hover:text-white px-2 py-1"><i class="las text-lg mr-2 la-user-tie"></i> World</li>
                        <li class="hover:bg-blue-400 bg-white hover:text-white px-2 py-1"><i class="las text-lg mr-2 la-user-tie"></i> Here</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>