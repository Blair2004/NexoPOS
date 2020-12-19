<template>
    <div class="bg-white rounded shadow my-2 overflow-hidden">
        <div class="welcome-box border-b border-gray-300 p-3 text-gray-700">
            <p class="text-gray-600 text-sm">Thank you for having choosed NexoPOS 4 for managing your store. This is the installation wizard that will guide you through the installation.</p>
            <div class="py-2">
                <h2 class="text-xl font-body text-gray-700">Remaining Steps</h2>
                <ul class="text-gray-600 text-sm">
                    <li><i class="las la-arrow-right"></i> Database Configuration</li>
                    <li><i class="las la-arrow-right"></i> Application Configuration</li>
                </ul>
            </div>
        </div>
        <div class="bg-gray-200 p-3 flex justify-end">
            <ns-link to="/database" type="info"><i class="las la-database"></i> Database Configuration</ns-link>
        </div>
    </div>
</template>

<script>
import { nsLink } from './../../components/components';

export default {
    components: {
        nsLink
    }
}
</script>

<style>

</style>