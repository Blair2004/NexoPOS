<div id="dashboard-content" class="px-4">
    <div class="flex -mx-4">
        <div class="px-4 w-full md:w-1/2 lg:w-1/3 xl:w-1/4">
            <div class="rounded-lg shadow bg-white flex items-center justify-center flex-col overflow-hidden">
                <div class="my-4 rounded-full h-32 w-32 border-4 border-white flex items-center justify-center">
                    <img src="{{ Auth::user()->attribute ? Auth::user()->attribute->avatar_link : asset( 'images/user.png' ) }}" alt="profile">
                </div>
                <div class="flex flex-col p-3 items-center">
                    <h2 class="text-2xl font-bold text-gray-800">{{ Auth::user()->username }}</h2>
                    <span class="text-xs text-gray-600">{{ Auth::user()->email }}</span>
                </div>
                <ul class="w-full">
                    <li class="cursor-pointer text-center">
                        <a href="{{ route( 'ns.dashboard.users.profile' ) }}" class="text-gray-700 text-sm block py-2 w-full hover:bg-gray-100 border-t border-gray-100">{{ __( 'Profile' ) }}</a>
                    </li>
                    <li class="cursor-pointer text-center">
                        <a href="{{ route( 'ns.logout' ) }}" class="text-gray-700 text-sm block py-2 w-full hover:bg-gray-100 border-t border-gray-100">{{ __( 'Log out' ) }}</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="px-4"></div>
    </div>
</div>