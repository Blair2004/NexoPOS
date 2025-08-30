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
            <div class="w-[85.71vw]"></div>
            <div class="w-[71.43vw]"></div>
            <div class="w-[57.14vw]"></div>
            <div class="w-[42.86vw]"></div>
            <div class="w-[28.57vw]"></div>
            <div class="w-[14.29vw]"></div>
            <div class="w-[83.33vw]"></div>
            <div class="w-[66.67vw]"></div>
            <div class="w-[50vw]"></div>
            <div class="w-[33.33vw]"></div>
            <div class="w-[16.67vw]"></div>
            <div class="w-[80vw]"></div>
            <div class="w-[60vw]"></div>
            <div class="w-[40vw]"></div>
            <div class="w-[20vw]"></div>
            <div class="w-[75vw]"></div>
            <div class="w-half"></div>
            <div class="w-[25vw]"></div>
            <div class="w-[66.67vw]"></div>
            <div class="w-[33.33vw]"></div>
            
            <div class="sm:w-[85.71vw]"></div>
            <div class="sm:w-[71.43vw]"></div>
            <div class="sm:w-[57.14vw]"></div>
            <div class="sm:w-[42.86vw]"></div>
            <div class="sm:w-[28.57vw]"></div>
            <div class="sm:w-[14.29vw]"></div>
            <div class="sm:w-[83.33vw]"></div>
            <div class="sm:w-[66.67vw]"></div>
            <div class="sm:w-[50vw]"></div>
            <div class="sm:w-[33.33vw]"></div>
            <div class="sm:w-[16.67vw]"></div>
            <div class="sm:w-[80vw]"></div>
            <div class="sm:w-[60vw]"></div>
            <div class="sm:w-[40vw]"></div>
            <div class="sm:w-[20vw]"></div>
            <div class="sm:w-[75vw]"></div>
            <div class="sm:w-half"></div>
            <div class="sm:w-[25vw]"></div>
            <div class="sm:w-[66.67vw]"></div>
            <div class="sm:w-[33.33vw]"></div>

            <div class="md:w-[85.71vw]"></div>
            <div class="md:w-[71.43vw]"></div>
            <div class="md:w-[57.14vw]"></div>
            <div class="md:w-[42.86vw]"></div>
            <div class="md:w-[28.57vw]"></div>
            <div class="md:w-[14.29vw]"></div>
            <div class="md:w-[83.33vw]"></div>
            <div class="md:w-[66.67vw]"></div>
            <div class="md:w-[50vw]"></div>
            <div class="md:w-[33.33vw]"></div>
            <div class="md:w-[16.67vw]"></div>
            <div class="md:w-[80vw]"></div>
            <div class="md:w-[60vw]"></div>
            <div class="md:w-[40vw]"></div>
            <div class="md:w-[20vw]"></div>
            <div class="md:w-[75vw]"></div>
            <div class="md:w-half"></div>
            <div class="md:w-[25vw]"></div>
            <div class="md:w-[66.67vw]"></div>
            <div class="md:w-[33.33vw]"></div>

            <div class="lg:w-[85.71vw]"></div>
            <div class="lg:w-[71.43vw]"></div>
            <div class="lg:w-[57.14vw]"></div>
            <div class="lg:w-[42.86vw]"></div>
            <div class="lg:w-[28.57vw]"></div>
            <div class="lg:w-[14.29vw]"></div>
            <div class="lg:w-[83.33vw]"></div>
            <div class="lg:w-[66.67vw]"></div>
            <div class="lg:w-[50vw]"></div>
            <div class="lg:w-[33.33vw]"></div>
            <div class="lg:w-[16.67vw]"></div>
            <div class="lg:w-[80vw]"></div>
            <div class="lg:w-[60vw]"></div>
            <div class="lg:w-[40vw]"></div>
            <div class="lg:w-[20vw]"></div>
            <div class="lg:w-[75vw]"></div>
            <div class="lg:w-half"></div>
            <div class="lg:w-[25vw]"></div>
            <div class="lg:w-[66.67vw]"></div>
            <div class="lg:w-[33.33vw]"></div>

            <div class="xl:w-[85.71vw]"></div>
            <div class="xl:w-[71.43vw]"></div>
            <div class="xl:w-[57.14vw]"></div>
            <div class="xl:w-[42.86vw]"></div>
            <div class="xl:w-[28.57vw]"></div>
            <div class="xl:w-[14.29vw]"></div>
            <div class="xl:w-[83.33vw]"></div>
            <div class="xl:w-[66.67vw]"></div>
            <div class="xl:w-[50vw]"></div>
            <div class="xl:w-[33.33vw]"></div>
            <div class="xl:w-[16.67vw]"></div>
            <div class="xl:w-[80vw]"></div>
            <div class="xl:w-[60vw]"></div>
            <div class="xl:w-[40vw]"></div>
            <div class="xl:w-[20vw]"></div>
            <div class="xl:w-[75vw]"></div>
            <div class="xl:w-half"></div>
            <div class="xl:w-[25vw]"></div>
            <div class="xl:w-[66.67vw]"></div>
            <div class="xl:w-[33.33vw]"></div>

            <!-- height -->

            <div class="h-[85.71vh]"></div>
            <div class="h-[71.43vh]"></div>
            <div class="h-[57.14vh]"></div>
            <div class="h-[42.86vh]"></div>
            <div class="h-[28.57vh]"></div>
            <div class="h-[14.29vh]"></div>
            <div class="h-[83.33vh]"></div>
            <div class="h-[66.67vh]"></div>
            <div class="h-[50vh]"></div>
            <div class="h-[33.33vh]"></div>
            <div class="h-[16.67vh]"></div>
            <div class="h-[80vh]"></div>
            <div class="h-[60vh]"></div>
            <div class="h-[40vh]"></div>
            <div class="h-[20vh]"></div>
            <div class="h-[75vh]"></div>
            <div class="h-half"></div>
            <div class="h-[25vh]"></div>
            <div class="h-[66.67vh]"></div>
            <div class="h-[33.33vh]"></div>
            
            <div class="sm:h-[85.71vh]"></div>
            <div class="sm:h-[71.43vh]"></div>
            <div class="sm:h-[57.14vh]"></div>
            <div class="sm:h-[42.86vh]"></div>
            <div class="sm:h-[28.57vh]"></div>
            <div class="sm:h-[14.29vh]"></div>
            <div class="sm:h-[83.33vh]"></div>
            <div class="sm:h-[66.67vh]"></div>
            <div class="sm:h-[50vh]"></div>
            <div class="sm:h-[33.33vh]"></div>
            <div class="sm:h-[16.67vh]"></div>
            <div class="sm:h-[80vh]"></div>
            <div class="sm:h-[60vh]"></div>
            <div class="sm:h-[40vh]"></div>
            <div class="sm:h-[20vh]"></div>
            <div class="sm:h-[75vh]"></div>
            <div class="sm:h-half"></div>
            <div class="sm:h-[25vh]"></div>
            <div class="sm:h-[66.67vh]"></div>
            <div class="sm:h-[33.33vh]"></div>

            <div class="md:h-[85.71vh]"></div>
            <div class="md:h-[71.43vh]"></div>
            <div class="md:h-[57.14vh]"></div>
            <div class="md:h-[42.86vh]"></div>
            <div class="md:h-[28.57vh]"></div>
            <div class="md:h-[14.29vh]"></div>
            <div class="md:h-[83.33vh]"></div>
            <div class="md:h-[66.67vh]"></div>
            <div class="md:h-[50vh]"></div>
            <div class="md:h-[33.33vh]"></div>
            <div class="md:h-[16.67vh]"></div>
            <div class="md:h-[80vh]"></div>
            <div class="md:h-[60vh]"></div>
            <div class="md:h-[40vh]"></div>
            <div class="md:h-[20vh]"></div>
            <div class="md:h-[75vh]"></div>
            <div class="md:h-half"></div>
            <div class="md:h-[25vh]"></div>
            <div class="md:h-[66.67vh]"></div>
            <div class="md:h-[33.33vh]"></div>

            <div class="lg:h-[85.71vh]"></div>
            <div class="lg:h-[71.43vh]"></div>
            <div class="lg:h-[57.14vh]"></div>
            <div class="lg:h-[42.86vh]"></div>
            <div class="lg:h-[28.57vh]"></div>
            <div class="lg:h-[14.29vh]"></div>
            <div class="lg:h-[83.33vh]"></div>
            <div class="lg:h-[66.67vh]"></div>
            <div class="lg:h-[50vh]"></div>
            <div class="lg:h-[33.33vh]"></div>
            <div class="lg:h-[16.67vh]"></div>
            <div class="lg:h-[80vh]"></div>
            <div class="lg:h-[60vh]"></div>
            <div class="lg:h-[40vh]"></div>
            <div class="lg:h-[20vh]"></div>
            <div class="lg:h-[75vh]"></div>
            <div class="lg:h-half"></div>
            <div class="lg:h-[25vh]"></div>
            <div class="lg:h-[66.67vh]"></div>
            <div class="lg:h-[33.33vh]"></div>

            <div class="xl:h-[85.71vh]"></div>
            <div class="xl:h-[71.43vh]"></div>
            <div class="xl:h-[57.14vh]"></div>
            <div class="xl:h-[42.86vh]"></div>
            <div class="xl:h-[28.57vh]"></div>
            <div class="xl:h-[14.29vh]"></div>
            <div class="xl:h-[83.33vh]"></div>
            <div class="xl:h-[66.67vh]"></div>
            <div class="xl:h-[50vh]"></div>
            <div class="xl:h-[33.33vh]"></div>
            <div class="xl:h-[16.67vh]"></div>
            <div class="xl:h-[80vh]"></div>
            <div class="xl:h-[60vh]"></div>
            <div class="xl:h-[40vh]"></div>
            <div class="xl:h-[20vh]"></div>
            <div class="xl:h-[75vh]"></div>
            <div class="xl:h-half"></div>
            <div class="xl:h-[25vh]"></div>
            <div class="xl:h-[66.67vh]"></div>
            <div class="xl:h-[33.33vh]"></div>

        </ns-tabs-item>
    </ns-tabs>
</template>