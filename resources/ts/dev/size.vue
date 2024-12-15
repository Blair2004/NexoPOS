<script>
import moment from 'moment';
import { nsDaterangePicker } from '~/components/components';

export default {
    name: 'datepicker',
    components: {
        nsDaterangePicker
    },
    data() {
        return {
            active: 'demo',
            field: {
                label: 'Date',
                name: 'range',
                description: 'a date range picker',
            }
        }
    }
}
</script>
<template>
    <ns-page-title>
        <template #title>Size</template>
        <template #description>set various div sizes.</template>
    </ns-page-title>
    <ns-tabs :active="active" @active="active = $event">
        <ns-tabs-item identifier="general" label="General">
            
        </ns-tabs-item>
        <ns-tabs-item identifier="demo" label="Demo">
            <div class="w-6/7-screen"></div>
            <div class="w-5/7-screen"></div>
            <div class="w-4/7-screen"></div>
            <div class="w-3/7-screen"></div>
            <div class="w-2/7-screen"></div>
            <div class="w-1/7-screen"></div>
            <div class="w-5/6-screen"></div>
            <div class="w-4/6-screen"></div>
            <div class="w-3/6-screen"></div>
            <div class="w-2/6-screen"></div>
            <div class="w-1/6-screen"></div>
            <div class="w-4/5-screen"></div>
            <div class="w-3/5-screen"></div>
            <div class="w-2/5-screen"></div>
            <div class="w-1/5-screen"></div>
            <div class="w-3/4-screen"></div>
            <div class="w-2/4-screen"></div>
            <div class="w-1/4-screen"></div>
            <div class="w-2/3-screen"></div>
            <div class="w-1/3-screen"></div>
            
            <div class="sm:w-6/7-screen"></div>
            <div class="sm:w-5/7-screen"></div>
            <div class="sm:w-4/7-screen"></div>
            <div class="sm:w-3/7-screen"></div>
            <div class="sm:w-2/7-screen"></div>
            <div class="sm:w-1/7-screen"></div>
            <div class="sm:w-5/6-screen"></div>
            <div class="sm:w-4/6-screen"></div>
            <div class="sm:w-3/6-screen"></div>
            <div class="sm:w-2/6-screen"></div>
            <div class="sm:w-1/6-screen"></div>
            <div class="sm:w-4/5-screen"></div>
            <div class="sm:w-3/5-screen"></div>
            <div class="sm:w-2/5-screen"></div>
            <div class="sm:w-1/5-screen"></div>
            <div class="sm:w-3/4-screen"></div>
            <div class="sm:w-2/4-screen"></div>
            <div class="sm:w-1/4-screen"></div>
            <div class="sm:w-2/3-screen"></div>
            <div class="sm:w-1/3-screen"></div>

            <div class="md:w-6/7-screen"></div>
            <div class="md:w-5/7-screen"></div>
            <div class="md:w-4/7-screen"></div>
            <div class="md:w-3/7-screen"></div>
            <div class="md:w-2/7-screen"></div>
            <div class="md:w-1/7-screen"></div>
            <div class="md:w-5/6-screen"></div>
            <div class="md:w-4/6-screen"></div>
            <div class="md:w-3/6-screen"></div>
            <div class="md:w-2/6-screen"></div>
            <div class="md:w-1/6-screen"></div>
            <div class="md:w-4/5-screen"></div>
            <div class="md:w-3/5-screen"></div>
            <div class="md:w-2/5-screen"></div>
            <div class="md:w-1/5-screen"></div>
            <div class="md:w-3/4-screen"></div>
            <div class="md:w-2/4-screen"></div>
            <div class="md:w-1/4-screen"></div>
            <div class="md:w-2/3-screen"></div>
            <div class="md:w-1/3-screen"></div>

            <div class="lg:w-6/7-screen"></div>
            <div class="lg:w-5/7-screen"></div>
            <div class="lg:w-4/7-screen"></div>
            <div class="lg:w-3/7-screen"></div>
            <div class="lg:w-2/7-screen"></div>
            <div class="lg:w-1/7-screen"></div>
            <div class="lg:w-5/6-screen"></div>
            <div class="lg:w-4/6-screen"></div>
            <div class="lg:w-3/6-screen"></div>
            <div class="lg:w-2/6-screen"></div>
            <div class="lg:w-1/6-screen"></div>
            <div class="lg:w-4/5-screen"></div>
            <div class="lg:w-3/5-screen"></div>
            <div class="lg:w-2/5-screen"></div>
            <div class="lg:w-1/5-screen"></div>
            <div class="lg:w-3/4-screen"></div>
            <div class="lg:w-2/4-screen"></div>
            <div class="lg:w-1/4-screen"></div>
            <div class="lg:w-2/3-screen"></div>
            <div class="lg:w-1/3-screen"></div>

            <div class="xl:w-6/7-screen"></div>
            <div class="xl:w-5/7-screen"></div>
            <div class="xl:w-4/7-screen"></div>
            <div class="xl:w-3/7-screen"></div>
            <div class="xl:w-2/7-screen"></div>
            <div class="xl:w-1/7-screen"></div>
            <div class="xl:w-5/6-screen"></div>
            <div class="xl:w-4/6-screen"></div>
            <div class="xl:w-3/6-screen"></div>
            <div class="xl:w-2/6-screen"></div>
            <div class="xl:w-1/6-screen"></div>
            <div class="xl:w-4/5-screen"></div>
            <div class="xl:w-3/5-screen"></div>
            <div class="xl:w-2/5-screen"></div>
            <div class="xl:w-1/5-screen"></div>
            <div class="xl:w-3/4-screen"></div>
            <div class="xl:w-2/4-screen"></div>
            <div class="xl:w-1/4-screen"></div>
            <div class="xl:w-2/3-screen"></div>
            <div class="xl:w-1/3-screen"></div>

            <!-- height -->

            <div class="h-6/7-screen"></div>
            <div class="h-5/7-screen"></div>
            <div class="h-4/7-screen"></div>
            <div class="h-3/7-screen"></div>
            <div class="h-2/7-screen"></div>
            <div class="h-1/7-screen"></div>
            <div class="h-5/6-screen"></div>
            <div class="h-4/6-screen"></div>
            <div class="h-3/6-screen"></div>
            <div class="h-2/6-screen"></div>
            <div class="h-1/6-screen"></div>
            <div class="h-4/5-screen"></div>
            <div class="h-3/5-screen"></div>
            <div class="h-2/5-screen"></div>
            <div class="h-1/5-screen"></div>
            <div class="h-3/4-screen"></div>
            <div class="h-2/4-screen"></div>
            <div class="h-1/4-screen"></div>
            <div class="h-2/3-screen"></div>
            <div class="h-1/3-screen"></div>
            
            <div class="sm:h-6/7-screen"></div>
            <div class="sm:h-5/7-screen"></div>
            <div class="sm:h-4/7-screen"></div>
            <div class="sm:h-3/7-screen"></div>
            <div class="sm:h-2/7-screen"></div>
            <div class="sm:h-1/7-screen"></div>
            <div class="sm:h-5/6-screen"></div>
            <div class="sm:h-4/6-screen"></div>
            <div class="sm:h-3/6-screen"></div>
            <div class="sm:h-2/6-screen"></div>
            <div class="sm:h-1/6-screen"></div>
            <div class="sm:h-4/5-screen"></div>
            <div class="sm:h-3/5-screen"></div>
            <div class="sm:h-2/5-screen"></div>
            <div class="sm:h-1/5-screen"></div>
            <div class="sm:h-3/4-screen"></div>
            <div class="sm:h-2/4-screen"></div>
            <div class="sm:h-1/4-screen"></div>
            <div class="sm:h-2/3-screen"></div>
            <div class="sm:h-1/3-screen"></div>

            <div class="md:h-6/7-screen"></div>
            <div class="md:h-5/7-screen"></div>
            <div class="md:h-4/7-screen"></div>
            <div class="md:h-3/7-screen"></div>
            <div class="md:h-2/7-screen"></div>
            <div class="md:h-1/7-screen"></div>
            <div class="md:h-5/6-screen"></div>
            <div class="md:h-4/6-screen"></div>
            <div class="md:h-3/6-screen"></div>
            <div class="md:h-2/6-screen"></div>
            <div class="md:h-1/6-screen"></div>
            <div class="md:h-4/5-screen"></div>
            <div class="md:h-3/5-screen"></div>
            <div class="md:h-2/5-screen"></div>
            <div class="md:h-1/5-screen"></div>
            <div class="md:h-3/4-screen"></div>
            <div class="md:h-2/4-screen"></div>
            <div class="md:h-1/4-screen"></div>
            <div class="md:h-2/3-screen"></div>
            <div class="md:h-1/3-screen"></div>

            <div class="lg:h-6/7-screen"></div>
            <div class="lg:h-5/7-screen"></div>
            <div class="lg:h-4/7-screen"></div>
            <div class="lg:h-3/7-screen"></div>
            <div class="lg:h-2/7-screen"></div>
            <div class="lg:h-1/7-screen"></div>
            <div class="lg:h-5/6-screen"></div>
            <div class="lg:h-4/6-screen"></div>
            <div class="lg:h-3/6-screen"></div>
            <div class="lg:h-2/6-screen"></div>
            <div class="lg:h-1/6-screen"></div>
            <div class="lg:h-4/5-screen"></div>
            <div class="lg:h-3/5-screen"></div>
            <div class="lg:h-2/5-screen"></div>
            <div class="lg:h-1/5-screen"></div>
            <div class="lg:h-3/4-screen"></div>
            <div class="lg:h-2/4-screen"></div>
            <div class="lg:h-1/4-screen"></div>
            <div class="lg:h-2/3-screen"></div>
            <div class="lg:h-1/3-screen"></div>

            <div class="xl:h-6/7-screen"></div>
            <div class="xl:h-5/7-screen"></div>
            <div class="xl:h-4/7-screen"></div>
            <div class="xl:h-3/7-screen"></div>
            <div class="xl:h-2/7-screen"></div>
            <div class="xl:h-1/7-screen"></div>
            <div class="xl:h-5/6-screen"></div>
            <div class="xl:h-4/6-screen"></div>
            <div class="xl:h-3/6-screen"></div>
            <div class="xl:h-2/6-screen"></div>
            <div class="xl:h-1/6-screen"></div>
            <div class="xl:h-4/5-screen"></div>
            <div class="xl:h-3/5-screen"></div>
            <div class="xl:h-2/5-screen"></div>
            <div class="xl:h-1/5-screen"></div>
            <div class="xl:h-3/4-screen"></div>
            <div class="xl:h-2/4-screen"></div>
            <div class="xl:h-1/4-screen"></div>
            <div class="xl:h-2/3-screen"></div>
            <div class="xl:h-1/3-screen"></div>

        </ns-tabs-item>
    </ns-tabs>
</template>