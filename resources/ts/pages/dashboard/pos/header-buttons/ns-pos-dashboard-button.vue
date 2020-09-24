<script>
export default {
    name: 'ns-pos-dashboard-button',
    methods: {
        goToDashboard() {
            return document.location = `/dashboard`;
        }
    }
}
</script>
<template>
    <button @click="goToDashboard()" class="flex items-center shadow rounded px-2 py-1 text-sm bg-white text-gray-700">
        <i class="mr-1 text-xl las la-tachometer-alt"></i>
        <span>Dashboard</span>
    </button>
</template>