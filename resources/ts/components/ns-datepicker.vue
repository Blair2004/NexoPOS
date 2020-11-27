<template>
    <div class="picker">
        <div @click="visible = !visible" class="rounded cursor-pointer bg-white shadow px-1 py-1 flex items-center text-gray-700">
            <i class="las la-clock text-2xl"></i>
            <span class="mx-1 text-sm">Date : N/A</span>
        </div>
        <div class="relative h-0 w-0 -mb-2" v-if="visible">
            <div class="h-56 w-72 mt-2 shadow rounded bg-white anim-duration-300 zoom-in-entrance flex flex-col">
                <div class="flex-auto">
                    <div class="grid grid-flow-row grid-cols-7 grid-rows-5 gap-0 text-gray-700">
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Sun</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Mon</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Tue</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Wed</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Thr</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Fri</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center">Sat</div>
                    </div>
                </div>
                <div>

                </div>
            </div>
        </div>
    </div>
</template>
<script>
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