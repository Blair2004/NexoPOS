<template>
    <div class="picker">
        <div @click="visible = !visible" class="rounded cursor-pointer bg-white shadow px-1 py-1 flex items-center text-gray-700">
            <i class="las la-clock text-2xl"></i>
            <span class="mx-1 text-sm">Date : N/A</span>
        </div>
        <div class="relative h-0 w-0 -mb-2" v-if="visible">
            <div class="h-56 w-72 mt-2 shadow rounded bg-white anim-duration-300 zoom-in-entrance flex flex-col">
                <div class="flex-auto">
                    <div class="p-2 flex items-center">
                        <div>
                            <button class="w-8 h-8 bg-gray-400 rounded"><i class="las la-angle-left"></i></button>
                        </div>
                        <div class="flex flex-auto font-semibold text-gray-700 justify-center">May 2020</div>
                        <div>
                            <button class="w-8 h-8 bg-gray-400 rounded"><i class="las la-angle-right"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-flow-row grid-cols-7 grid-rows-5 gap-0 text-gray-700">
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Sun</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Mon</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Tue</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Wed</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Thr</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Fri</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Sat</div>
                    </div>
                </div>
                <div>

                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
export default {
    name: "ns-datepicker",
    data() {
        return {
            visible: false,
        }
    },
    mounted() {
    }
}
</script>