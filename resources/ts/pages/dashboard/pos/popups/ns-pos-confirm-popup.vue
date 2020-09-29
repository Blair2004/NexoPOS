<template>
    <div id="popup" class="flex flex-col h-full w-full">
        <div class="flex items-center justify-center flex-col flex-auto">
            <h2 class="text-4xl font-body text-gray-700">{{ title }}</h2>
            <p class="py-4 text-gray-600">{{ message }}</p>
        </div>
        <div class="flex border-t border-gray-200 text-gray-700">
            <button class="hover:bg-gray-100 flex-auto w-1/2 h-16 flex items-center justify-center uppercase" @click="emitAction( true )">Yes</button>
            <hr class="border-r border-gray-200">
            <button class="hover:bg-gray-100 flex-auto w-1/2 h-16 flex items-center justify-center uppercase" @click="emitAction( false )">No</button>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            title: '',
            message: ''
        }
    },
    mounted() {
        this.title          =   this.$popupParams.title;
        this.message        =   this.$popupParams.message;
    },
    methods: {
        emitAction( action ) {
            this.$popupParams.onAction( action );
            this.$popup.close();
        }
    }
}
</script>