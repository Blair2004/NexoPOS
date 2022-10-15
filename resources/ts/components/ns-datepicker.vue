<template>
    <div class="picker">
        <div @click="visible = !visible" class="rounded cursor-pointer bg-white shadow px-1 py-1 flex items-center text-gray-700">
            <i class="las la-clock text-2xl"></i>
            <span class="mx-1 text-sm">
                <span>{{ label || __( 'Date' ) }} : </span>
                <span v-if="currentDay">{{ currentDay.format( 'YYYY/MM/DD' ) }}</span>
                <span v-if="currentDay === null">{{ __( 'N/A' ) }}</span>
            </span>
        </div>
        <div class="relative h-0 w-0 -mb-2" v-if="visible">
            <div class="w-72 mt-2 shadow rounded bg-white anim-duration-300 zoom-in-entrance flex flex-col">
                <div class="flex-auto">
                    <div class="p-2 flex items-center">
                        <div>
                            <button @click="subMonth()" class="w-8 h-8 bg-gray-400 rounded"><i class="las la-angle-left"></i></button>
                        </div>
                        <div class="flex flex-auto font-semibold text-gray-700 justify-center">{{ currentDay.format( 'MMM' ) }} {{ currentDay.format( 'YYYY' ) }}</div>
                        <div>
                            <button @click="addMonth()" class="w-8 h-8 bg-gray-400 rounded"><i class="las la-angle-right"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-gray-700">
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Sun' ) }}</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Mon' ) }}</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Tue' ) }}</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Wed' ) }}</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Thr' ) }}</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Fri' ) }}</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">{{ __( 'Sat' ) }}</div>
                    </div>
                    <div v-for="(week, index) of calendar" :key="index" class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-gray-700">
                        <div :key="_index" v-for="( dayOfWeek, _index) in daysOfWeek" class="h-8 flex justify-center items-center text-sm">
                            <template v-for="(day,_dayIndex) of week" class="h-full w-full">
                                <div :key="_dayIndex" v-if="day.dayOfWeek === dayOfWeek" :class="day.date.format( 'DD' ) === currentDay.format( 'DD' ) ? 'bg-blue-400 text-white border border-blue-500' : 'hover:bg-gray-100 border border-gray-200'" class="h-full w-full flex items-center justify-center cursor-pointer" @click="selectDate( day.date )">
                                    {{ day.date.format( 'DD' ) }}
                                </div>
                            </template>
                        </div>
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
import { __ } from '@/libraries/lang';
export default {
    name: "ns-datepicker",
    props: [ 'label', 'date' ],
    data() {
        return {
            visible: false,
            currentDay: null,
            daysOfWeek: (new Array(7)).fill('').map( ( _, i ) => i ),
            calendar: [
                [], // first week
            ],
        }
    },
    mounted() {
    document.addEventListener( 'click', this.checkClickedItem );
        this.currentDay     =   [ undefined, null ].includes( this.date ) ? moment() : moment( this.date );
        this.build();
    },
    methods: {
        __,
        checkClickedItem( event ) {
            let clickChildrens;

            clickChildrens        =   this.$el.contains( event.srcElement );
            
            if ( ! clickChildrens && this.visible ) {
                this.visible    =   false;
            }
        },
        selectDate( date ) {
            this.currentDay     =   date;
            this.visible        =   false;
            this.$emit( 'change', date );
        },
        subMonth() {
            this.currentDay.subtract( 1, 'month' );
            this.build();
        },
        addMonth() {
            this.currentDay.add( 1, 'month' );
            this.build();
        },
        resetCalendar() {
            this.calendar   =   [[]];
        },
        build() {

            this.resetCalendar();

            const startOfMonth      =   this.currentDay.clone().startOf( 'month' );
            const currentCursor     =   this.currentDay.clone().startOf( 'month' );
            const endOfMonth        =   this.currentDay.clone().endOf( 'month' );
            
            while( true ) {
                if ( currentCursor.day() === 0 ) {
                    if ( this.calendar[0].length > 0 ) {
                        this.calendar.push([]);
                    }
                }

                let week    =   this.calendar.length - 1;

                this.calendar[ week ].push({
                    date: currentCursor.clone(),
                    dayOfWeek: currentCursor.day(),
                    isToday: currentCursor.isSame( moment.now(), 'day' )
                });

                if ( currentCursor.isSame( endOfMonth, 'day' ) ) {
                    break;
                }

                currentCursor.add( 1, 'day' );
            }
        }
    }
}
</script>