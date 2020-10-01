<template>
    <div class="h-full w-full py-2">
        <div class="px-2 pb-2">
            <div id="details" class="h-24 flex justify-between items-center bg-gray-300 text-gray-800 text-4xl p-2">
                <span>Total : </span>
                <span>{{ 145352 | currency }}</span>
            </div>
        </div>
        <div class="px-2 pb-2">
            <div id="details" class="h-24 flex justify-between items-center bg-blue-400 text-white text-4xl p-2">
                <span>Paid : </span>
                <span>{{ 0 | currency }}</span>
            </div>
        </div>
        <div class="px-2 pb-2">
            <div class="-mx-2 flex flex-wrap">
                <div class="px-2 flex-auto">
                    <div id="numpad" class="grid grid-flow-row grid-cols-3 grid-rows-3" style="padding: 1px">
                        <div 
                            @click="inputValue( key )"
                            :key="index" 
                            v-for="(key,index) of keys" 
                            style="margin:-1px;"
                            class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-3xl border h-24 flex items-center justify-center cursor-pointer">
                            <span v-if="key.value !== undefined">{{ key.value }}</span>
                            <i v-if="key.icon" class="las" :class="key.icon"></i>
                        </div>
                    </div>
                </div>
                <div class="px-2 w-72">
                    <div 
                        class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-3xl border h-24 flex items-center justify-center cursor-pointer">
                        <span>{{ 100 | currency }}</span>
                    </div>
                    <div 
                        class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-3xl border h-24 flex items-center justify-center cursor-pointer">
                        <span>{{ 500 | currency }}</span>
                    </div>
                    <div 
                        class="hover:bg-gray-400 hover:text-gray-800 bg-gray-300 text-3xl border h-24 flex items-center justify-center cursor-pointer">
                        <span>{{ 1000 | currency }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            keys: [
                ...([1,2,3].map( key => ({ identifier: key, value: key }))),
                ...([4,5,6].map( key => ({ identifier: key, value: key }))),
                ...([7,8,9].map( key => ({ identifier: key, value: key }))),
                ...[{ identifier: 'backspace', icon : 'la-backspace' },{ identifier: 0, value: 0 }, { identifier: 'next', icon: 'la-share' }],
            ]
        }
    }
}
</script>