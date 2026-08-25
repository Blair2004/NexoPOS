<template>
    <article id="ns-orders-chart" class="ns-box flex h-full min-h-0 flex-auto flex-col overflow-hidden rounded-lg shadow">
        <header class="ns-box-header flex shrink-0 items-center justify-between gap-3 border-b border-box-edge p-2">
            <div class="min-w-0">
                <h3 class="truncate font-semibold text-fontcolor">{{ __( 'Sales Overview' ) }}</h3>
                <p class="truncate text-xs text-fontcolor-soft">{{ __( 'This week compared with last week' ) }}</p>
            </div>
            <div class="flex shrink-0 items-center">
                <div class="px-1">
                    <ns-widget-layout-selector :widget="widget"></ns-widget-layout-selector>
                </div>
                <div class="px-1">
                    <ns-icon-button class="widget-handle" className="la-expand-arrows-alt"></ns-icon-button>
                </div>
                <div class="px-1">
                    <ns-close-button @click="$emit( 'onRemove' )"></ns-close-button>
                </div>
            </div>
        </header>

        <div v-if="isLoading" class="flex flex-auto flex-col items-center justify-center gap-2 p-6 text-fontcolor-soft">
            <ns-spinner size="8" border="4"></ns-spinner>
            <span class="text-sm">{{ __( 'Loading weekly sales...' ) }}</span>
        </div>

        <div v-else-if="errorMessage" class="flex flex-auto flex-col items-center justify-center gap-3 p-6 text-center">
            <p class="text-sm text-error-primary">{{ errorMessage }}</p>
            <ns-button type="default" @click="loadReport">{{ __( 'Try Again' ) }}</ns-button>
        </div>

        <div v-else class="flex min-h-0 flex-auto flex-col gap-3 bg-box-background p-3 text-fontcolor">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-fontcolor-soft">{{ metric === 'amount' ? __( 'Total sales' ) : __( 'Total orders' ) }}</p>
                    <div class="flex items-center gap-2">
                        <strong class="text-2xl leading-tight">{{ formatMetricValue( currentTotal ) }}</strong>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="comparisonClasses( overallComparison )">
                            {{ formatComparison( overallComparison ) }}
                        </span>
                    </div>
                </div>

                <div class="flex rounded-lg border border-box-edge bg-input-background p-1" :aria-label="__( 'Chart metric' )">
                    <button
                        v-for="option in metricOptions"
                        :key="option.value"
                        type="button"
                        class="rounded-md px-3 py-1 text-xs font-medium outline-none"
                        :class="metric === option.value ? 'bg-info-secondary text-white' : 'text-fontcolor-soft hover:bg-input-button-hover hover:text-fontcolor'"
                        :aria-pressed="metric === option.value"
                        @click="metric = option.value">
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-fontcolor-soft">
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-success-primary"></span>
                    {{ __( 'Current Week' ) }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-0 w-4 border-t-2 border-dashed border-fontcolor-soft"></span>
                    {{ __( 'Previous Week' ) }}
                </span>
                <span>{{ __( 'Select a day to compare it.' ) }}</span>
            </div>

            <div class="relative min-h-52 flex-auto" @mouseleave="hoveredIndex = null">
                <svg
                    class="h-full min-h-52 w-full overflow-visible"
                    :viewBox="`0 0 ${chart.width} ${chart.height}`"
                    preserveAspectRatio="none"
                    role="img"
                    :aria-label="__( 'Current and previous week sales chart' )">
                    <g aria-hidden="true">
                        <line
                            v-for="tick in yTicks"
                            :key="tick.value"
                            :x1="chart.padding.left"
                            :x2="chart.width - chart.padding.right"
                            :y1="tick.y"
                            :y2="tick.y"
                            class="stroke-box-edge"
                            stroke-dasharray="4 5"
                            vector-effect="non-scaling-stroke">
                        </line>
                        <text
                            v-for="tick in yTicks"
                            :key="`label-${tick.value}`"
                            :x="chart.padding.left - 8"
                            :y="tick.y + 4"
                            text-anchor="end"
                            class="fill-fontcolor-soft text-[11px]">
                            {{ formatAxisValue( tick.value ) }}
                        </text>
                    </g>

                    <path :d="currentAreaPath" class="fill-success-tertiary opacity-30"></path>
                    <path
                        :d="previousLinePath"
                        fill="none"
                        class="stroke-fontcolor-soft opacity-60"
                        stroke-width="2"
                        stroke-dasharray="6 6"
                        stroke-linecap="round"
                        vector-effect="non-scaling-stroke">
                    </path>
                    <path
                        :d="currentLinePath"
                        fill="none"
                        class="stroke-success-primary"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        vector-effect="non-scaling-stroke">
                    </path>

                    <line
                        v-if="activePoint"
                        :x1="activePoint.x"
                        :x2="activePoint.x"
                        :y1="chart.padding.top"
                        :y2="chartBaseline"
                        class="stroke-primary opacity-50"
                        stroke-dasharray="3 4"
                        vector-effect="non-scaling-stroke">
                    </line>

                    <g
                        v-for="( point, index ) in currentPoints"
                        :key="points[index].label"
                        role="button"
                        tabindex="0"
                        class="cursor-pointer outline-none"
                        :aria-label="dayAriaLabel( index )"
                        @mouseenter="hoveredIndex = index"
                        @focus="hoveredIndex = index"
                        @blur="hoveredIndex = null"
                        @click="selectedIndex = index"
                        @keydown.enter.prevent="selectedIndex = index"
                        @keydown.space.prevent="selectedIndex = index">
                        <rect
                            :x="point.x - pointHitWidth / 2"
                            :y="chart.padding.top"
                            :width="pointHitWidth"
                            :height="chartBaseline - chart.padding.top"
                            fill="transparent">
                        </rect>
                        <circle
                            :cx="point.x"
                            :cy="point.y"
                            :r="activeIndex === index ? 5 : 3"
                            class="fill-box-background stroke-success-primary"
                            stroke-width="3"
                            vector-effect="non-scaling-stroke">
                        </circle>
                        <text
                            :x="point.x"
                            :y="chart.height - 12"
                            text-anchor="middle"
                            class="fill-fontcolor-soft text-[11px] font-medium">
                            {{ points[index].shortLabel }}
                        </text>
                    </g>
                </svg>

                <div
                    v-if="activePoint && activeDay"
                    class="pointer-events-none absolute z-10 min-w-36 rounded-lg border border-box-edge bg-floating-menu px-3 py-2 text-xs text-fontcolor shadow-lg"
                    :style="tooltipStyle">
                    <p class="font-semibold">{{ activeDay.label }}</p>
                    <p class="mt-1 text-sm font-bold">{{ formatMetricValue( activeDay.current[metric] ) }}</p>
                    <p class="text-fontcolor-soft">
                        {{ formatComparison( selectedComparison ) }} {{ __( 'vs previous week' ) }}
                    </p>
                </div>
            </div>
        </div>
    </article>
</template>

<script>
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import {
    areaPath,
    chartPoints,
    normalizeWeeklyReport,
    percentageChange,
    smoothLinePath,
} from './orders-chart-data.js';

export default {
    name: 'ns-orders-chart',
    props: [ 'widget' ],
    emits: [ 'onRemove' ],
    data() {
        return {
            chart: {
                width: 700,
                height: 260,
                padding: { top: 18, right: 18, bottom: 38, left: 58 },
            },
            errorMessage: null,
            hoveredIndex: null,
            isLoading: true,
            metric: 'amount',
            points: [],
            reportSubscription: null,
            selectedIndex: 0,
        };
    },
    computed: {
        metricOptions() {
            return [
                { label: __( 'Amount' ), value: 'amount' },
                { label: __( 'Orders' ), value: 'count' },
            ];
        },
        currentValues() {
            return this.points.map( point => point.current[this.metric] );
        },
        previousValues() {
            return this.points.map( point => point.previous[this.metric] );
        },
        currentTotal() {
            return this.currentValues.reduce( ( total, value ) => total + value, 0 );
        },
        previousTotal() {
            return this.previousValues.reduce( ( total, value ) => total + value, 0 );
        },
        overallComparison() {
            return percentageChange( this.currentTotal, this.previousTotal );
        },
        maximumValue() {
            const largestValue = Math.max( 0, ...this.currentValues, ...this.previousValues );

            if ( largestValue === 0 ) {
                return 1;
            }

            const magnitude = 10 ** Math.floor( Math.log10( largestValue ) );
            const normalizedValue = largestValue / magnitude;
            const roundedValue = normalizedValue <= 2 ? 2 : normalizedValue <= 5 ? 5 : 10;

            return roundedValue * magnitude;
        },
        currentPoints() {
            return chartPoints( this.currentValues, this.chart.width, this.chart.height, this.chart.padding, this.maximumValue );
        },
        previousPoints() {
            return chartPoints( this.previousValues, this.chart.width, this.chart.height, this.chart.padding, this.maximumValue );
        },
        currentLinePath() {
            return smoothLinePath( this.currentPoints );
        },
        previousLinePath() {
            return smoothLinePath( this.previousPoints );
        },
        currentAreaPath() {
            return areaPath( this.currentPoints, this.chartBaseline );
        },
        chartBaseline() {
            return this.chart.height - this.chart.padding.bottom;
        },
        yTicks() {
            return [ 0, 0.25, 0.5, 0.75, 1 ].map( ratio => ({
                value: this.maximumValue * ratio,
                y: this.chartBaseline - ( ( this.chartBaseline - this.chart.padding.top ) * ratio ),
            }) ).reverse();
        },
        pointHitWidth() {
            const plotWidth = this.chart.width - this.chart.padding.left - this.chart.padding.right;

            return this.points.length > 1 ? plotWidth / ( this.points.length - 1 ) : plotWidth;
        },
        activeIndex() {
            return this.hoveredIndex ?? this.selectedIndex;
        },
        activePoint() {
            return this.currentPoints[this.activeIndex] || null;
        },
        activeDay() {
            return this.points[this.activeIndex] || null;
        },
        selectedComparison() {
            if ( ! this.activeDay ) {
                return 0;
            }

            return percentageChange( this.activeDay.current[this.metric], this.activeDay.previous[this.metric] );
        },
        tooltipStyle() {
            const horizontalPosition = this.activePoint.x / this.chart.width * 100;
            const verticalPosition = Math.max( 2, this.activePoint.y / this.chart.height * 100 - 26 );
            const translateX = this.activeIndex === 0 ? '0' : this.activeIndex === this.points.length - 1 ? '-100%' : '-50%';

            return {
                left: `${horizontalPosition}%`,
                top: `${verticalPosition}%`,
                transform: `translateX(${translateX})`,
            };
        },
    },
    mounted() {
        this.loadReport();
    },
    beforeUnmount() {
        this.reportSubscription?.unsubscribe();
    },
    methods: {
        __,
        comparisonClasses( comparison ) {
            if ( comparison === null ) {
                return 'bg-info-tertiary text-info-primary';
            }

            if ( comparison > 0 ) {
                return 'bg-success-tertiary text-success-primary';
            }

            if ( comparison < 0 ) {
                return 'bg-error-tertiary text-error-primary';
            }

            return 'bg-box-elevation-background text-fontcolor-soft';
        },
        dayAriaLabel( index ) {
            const day = this.points[index];
            const comparison = percentageChange( day.current[this.metric], day.previous[this.metric] );

            return `${day.label}: ${this.formatMetricValue( day.current[this.metric] )}, ${this.formatComparison( comparison )} ${__( 'versus previous week' )}`;
        },
        formatAxisValue( value ) {
            if ( this.metric === 'amount' ) {
                return nsCurrency( value, 'abbreviate' );
            }

            return Intl.NumberFormat().format( Math.round( value ) );
        },
        formatComparison( comparison ) {
            if ( comparison === null ) {
                return __( 'New' );
            }

            const roundedComparison = Math.round( comparison );

            return `${roundedComparison > 0 ? '+' : ''}${roundedComparison}%`;
        },
        formatMetricValue( value ) {
            return this.metric === 'amount'
                ? nsCurrency( value, 'abbreviate' )
                : Intl.NumberFormat().format( Math.round( value ) );
        },
        loadReport() {
            this.reportSubscription?.unsubscribe();
            this.isLoading = true;
            this.errorMessage = null;

            this.reportSubscription = nsHttpClient.get( '/api/dashboard/weeks' )
                .subscribe( {
                    next: data => {
                        this.points = normalizeWeeklyReport( data.result );
                        this.selectedIndex = Math.min( new Date().getDay(), Math.max( this.points.length - 1, 0 ) );
                        this.isLoading = false;
                    },
                    error: () => {
                        this.points = [];
                        this.errorMessage = __( 'The weekly sales report could not be loaded.' );
                        this.isLoading = false;
                    },
                } );
        },
    },
};
</script>
