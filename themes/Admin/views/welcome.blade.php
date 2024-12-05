@extends('layout.base')

@section('layout.base.body')
    <div class="flex flex-col justify-center w-full h-full align-middle bg-gray-2 dark:bg-dark">
        <!-- ====== Sticky bar Start -->
        <section class="px-6 py-3 bg-primary">
            <div class="relative pr-6">
                <div class="flex flex-wrap items-center justify-center gap-5 text-center">
                    <p class="inline-flex text-sm font-medium text-white lg:text-base">
                        Flat 70% Discount, Hurry Up to Grab the Deal! Sale ends in
                    </p>
                </div>
            </div>
        </section>
        <!-- ====== Sticky bar End -->
        <!-- ====== Cards Section Start -->
        <section class="py-3 d-flex">
            <div class="flex h-full">
                <div class="flex justify-center">
                    <div class="w-full px-4 md:w-1/2 xl:w-1/3">
                        <div
                            class="overflow-hidden duration-300 bg-white rounded-lg dark:bg-dark-2 shadow-1 hover:shadow-3 dark:shadow-card dark:hover:shadow-3">
                            <img src="https://cdn.tailgrids.com/2.0/image/application/images/cards/card-01/image-02.jpg"
                                alt="image" class="w-full" />
                            <div class="p-8 text-center sm:p-9 md:p-7 xl:p-9">
                                <div class="flex items-center justify-center mb-8 -mt-24">
                                    <img class="w-32 rounded-full" src="{{ asset('svg/camsoft-1.svg') }}" alt="NexoPOS">
                                </div>
                                <h3>
                                    <p
                                        class="text-dark dark:text-white hover:text-primary mb-4 block text-xl font-semibold sm:text-[22px] md:text-xl lg:text-[22px] xl:text-xl 2xl:text-[22px]">
                                        {{ __('Welcome to Camsoft Technology') }}
                                    </p>
                                </h3>
                                <p class="text-base leading-relaxed text-body-color dark:text-dark-6 mb-7">
                                    {{ __("If you see this page, this means NexoPOS is correctly installed on your system. As this page is meant to be the frontend, NexoPOS doesn't have a frontend for the meantime. This page shows useful links that will take you to the important resources.") }}
                                </p>

                                <div
                                    class="flex flex-col justify-between w-full text-center align-middle xl:flex-row gap-x-1 gap-y-1 d-flex">
                                    <a href="{{ ns()->route('ns.dashboard.home') }}"
                                        class="bg-primary border-primary border rounded-full inline-flex items-center justify-center py-2 px-7 text-center text-base text-white hover:bg-[#1B44C8] hover:border-[#1B44C8] disabled:bg-gray-3 disabled:border-gray-3 disabled:text-dark-5 active:bg-[#1B44C8] active:border-[#1B44C8]">
                                        {{ __('Dashboard') }}
                                    </a>
                                    <a href="{{ ns()->route('ns.login') }}"
                                        class="inline-block py-2 text-base font-medium transition border rounded-full text-body-color hover:border-primary hover:bg-primary border-gray-3 px-7 hover:text-white dark:border-dark-3 dark:text-dark-6">
                                        {{ __('Sign In') }}
                                    </a>
                                    <a href="{{ ns()->route('ns.register') }}"
                                        class="inline-block py-2 text-base font-medium transition border rounded-full text-body-color hover:border-primary hover:bg-primary border-gray-3 px-7 hover:text-white dark:border-dark-3 dark:text-dark-6">
                                        {{ __('Sign Up') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- ====== Cards Section End -->
    </div>
@endsection
