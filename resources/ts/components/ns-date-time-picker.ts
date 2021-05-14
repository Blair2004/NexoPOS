import Vue from "vue";
import moment from "moment";

const nsDateTimePicker  =   Vue.component( 'ns-date-time-picker', {
    template: `
    <div class="picker mb-2">
        <label class="my-1 block leading-5 font-medium text-gray-700">{{ field.label }}</label>
        <div @click="visible = !visible" class="rounded cursor-pointer bg-white shadow px-1 py-1 flex items-center text-gray-700">
            <i class="las la-clock text-2xl"></i>
            <span class="mx-1 text-sm">
                <span v-if="currentDay">{{ currentDay.format( 'YYYY/MM/DD HH:mm' ) }}</span>
                <span v-if="currentDay === null">N/A</span>
            </span>
        </div>
        <p class="text-sm text-gray-500 py-1">{{ field.description }}</p>
        <div class="relative z-10 h-0 w-0 -mb-2" v-if="visible">
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
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Sun</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Mon</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Tue</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Wed</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Thr</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Fri</div>
                        <div class="border border-gray-200 h-8 flex justify-center items-center text-sm">Sat</div>
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
                <div class="border-t border-gray-200 w-full p-2">
                    <div class="-mx-2 flex justify-between">
                        <div class="px-2">
                            <div class="flex rounded overflow-hidden">
                            </div>
                        </div>
                        <div class="px-2">
                            <div class="rounded flex">
                                <input placeholder="HH" ref="hours" @change="detectHoursChange( $event )" class="w-16 p-1 border border-gray-200 active:border-blue-400" v-model="hours" type="number">
                                <span>:</span>
                                <input placeholder="mm" ref="minutes" @change="detectMinuteChange( $event )" class="w-16 p-1 border border-gray-200 active:border-blue-400" v-model="minutes" type="number">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
    props: [ 'field' ],
    data() {
        return {
            visible: false,
            hours: 0,
            minutes: 0,
            currentDay: null,
            daysOfWeek: (new Array(7)).fill('').map( ( _, i ) => i ),
            calendar: [
                [], // first week
            ],
        }
    },
    watch: {
        visible() {
            if ( this.visible ) {
                setTimeout( () => {
                    this.$refs[ 'hours' ].addEventListener( 'focus', function() {
                        this.select();
                    });
                    this.$refs[ 'minutes' ].addEventListener( 'focus', function() {
                        this.select();
                    });
                }, 100 );
            }
        }
    },
    mounted() {
        document.addEventListener( 'mousedown', ( e ) => this.checkClickedItem( e ) );
        this.currentDay     =   [ undefined, null, '' ].includes( this.field.value ) ? moment() : moment( this.field.value );
        this.hours          =   this.currentDay.format( 'HH' );
        this.minutes        =   this.currentDay.format( 'mm' );
        this.build();        
    },
    methods: {
        detectHoursChange() {
            if ( parseFloat( this.hours ) < 0 ) {
                this.hours  =   0;
            }
            if ( parseFloat( this.hours ) > 23 ) {
                this.hours  =   23;
            }
            this.updateDateTime();
        },
        detectMinuteChange() {
            if ( parseFloat( this.minutes ) < 0 ) {
                this.minutes  =   0;
            }
            if ( parseFloat( this.minutes ) > 59 ) {
                this.minutes  =   59;
            }
            this.updateDateTime();
        },
        updateDateTime() {
            this.currentDay.hours( this.hours );
            this.currentDay.minutes( this.minutes );
            this.selectDate( this.currentDay );
        },
        checkClickedItem( event ) {
            let clickChildrens;

            clickChildrens        =   this.$el.contains( event.srcElement );
            
            if ( ! clickChildrens && this.visible ) {
                this.visible    =   false;
            }
        },
        selectDate( date ) {
            this.currentDay     =   date;
            this.currentDay.hours( this.hours );
            this.currentDay.minutes( this.minutes );
            this.field.value    =   this.currentDay.format( 'YYYY/MM/DD HH:mm' );
            this.$emit( 'change', this.field );
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
})

export { nsDateTimePicker };