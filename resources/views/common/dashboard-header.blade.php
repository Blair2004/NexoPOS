<div id="dashboard-header" class="w-full flex justify-between p-4">
    <div class="flex items-center">
        <div>
            <div @click="toggleSideMenu()" class="rounded-full h-10 w-10 cursor-pointer font-bold text-2xl justify-center items-center flex border ns-toggle-button">
                <i class="las la-bars"></i>
            </div>
        </div>
    </div>
    <div class="top-tools-side flex items-center -mx-2">
        <div clss="px-2">
            <ns-notifications></ns-notifications>
        </div>
        <div class="px-2">
            <div @click="toggleMenu()" :class="menuToggled ? 'toggled shadow-lg rounded-t-lg' : 'untoggled rounded-lg'" class="
                ns-avatar
                w-32 md:w-56 flex flex-col border py-2 justify-center hover:border-opacity-0 cursor-pointer hover:shadow-lg">
                <ns-avatar 
                    display-name="{{ Auth::user()->username }}"
                    url="{{ Auth::user()->attribute ? Auth::user()->attribute->avatar_link : asset( 'images/user.png' ) }}"></ns-avatar>
            </div>
            <div class="relative">
                <div v-cloak class="w-32 md:w-56 shadow-lg flex z-10 absolute -mb-2 rounded-br-lg rounded-bl-lg overflow-hidden" v-if="menuToggled">
                    <ul class="w-full ns-vertical-menu">
                        @if ( Gate::allows([ 'manage.profile' ]) )
                        <li><a class="block px-2 py-1" href="{{ ns()->route( 'ns.dashboard.users.profile' ) }}"><i class="las text-lg mr-2 la-user-tie"></i> {{ __( 'Profile' ) }}</a></li>
                        @endif
                        <li><a class="block px-2 py-1" href="{{ ns()->route( 'ns.logout' ) }}"><i class="las la-sign-out-alt mr-2"></i> {{ __( 'Logout' ) }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>