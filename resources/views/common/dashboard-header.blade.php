<div id="dashboard-header" class="w-full flex justify-between p-4">
    <div class="flex items-center">
        <div>
            <div @click="toggleSideMenu()" class="hover:bg-white hover:text-gray-700 hover:shadow-lg hover:border-opacity-0 border border-gray-400 rounded-full h-10 w-10 cursor-pointer font-bold text-2xl justify-center items-center flex text-gray-800">
                <i class="las la-bars"></i>
            </div>
        </div>
    </div>
    <div class="top-tools-side flex items-center -mx-2">
        <div clss="px-2">
            <ns-notifications></ns-notifications>
        </div>
        <div class="px-2">
            <div @click="toggleMenu()" :class="menuToggled ? 'bg-white border-transparent shadow-lg rounded-t-lg' : 'border-gray-400 rounded-lg'" class="w-32 md:w-56 flex flex-col border py-2 justify-center hover:border-opacity-0 cursor-pointer hover:shadow-lg hover:bg-white">
                <ns-avatar 
                    display-name="{{ Auth::user()->username }}"
                    url="{{ Auth::user()->attribute ? Auth::user()->attribute->avatar_link : asset( 'images/user.png' ) }}"></ns-avatar>
            </div>
            <div v-cloak class="w-32 md:w-56 shadow-lg flex z-10 absolute -mb-2 rounded-br-lg rounded-bl-lg overflow-hidden" v-if="menuToggled">
                <ul class="text-gray-700 w-full bg-white">
                    @if ( Auth::user()->allowedTo([ 'manage.profile' ]) )
                    <li class="hover:bg-blue-400 bg-white hover:text-white"><a class="block px-2 py-1" href="{{ ns()->route( 'ns.dashboard.users.profile' ) }}"><i class="las text-lg mr-2 la-user-tie"></i> {{ __( 'Profile' ) }}</a></li>
                    @endif
                    <li class="hover:bg-blue-400 bg-white hover:text-white"><a class="block px-2 py-1" href="{{ ns()->route( 'ns.logout' ) }}"><i class="las la-sign-out-alt mr-2"></i> {{ __( 'Logout' ) }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>