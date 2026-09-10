<template>
    <v-card>
        <v-card-title>
            <span class="headline">Branch Performance</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <horizontal-bar-chart :chart-data="chartdata" :options="options"></horizontal-bar-chart>
            </v-container>
        </v-card-text>
    </v-card>
</template>

<script>
import HorizontalBarChart from './Charts/HorizontalBarChart'
import api from "../../common/api";

export default {
    name: "ReportTotalQuotes",
    props: ['branch_id'],
    components: {
        HorizontalBarChart
    },
    data() {
        return {
            chartdata: { labels:[], datasets: [] },
            options: {
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.tooltips[tooltipItem.index];
                        }
                    }
                },
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        display: true,
                        ticks: {
                            beginAtZero: true,
                            steps: 10,
                            stepValue: 5,
                            max: 100
                        }
                    }]
                }
            },
        }
    },
    mounted() {
        const self = this;

        self.loadChartData();
    },
    methods: {
        loadChartData() {
            const self = this;

            api.getBranchPerformance().then((resp) => {
                self.chartdata = resp.data;
            });
        },
    }
}
</script>

<style scoped>

</style>
