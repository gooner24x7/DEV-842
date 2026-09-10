<template>
    <v-card>
        <v-card-title>
            <span class="headline">Total Quotes</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <bar-chart :chart-data="chartdata" :options="options"></bar-chart>
            </v-container>
        </v-card-text>
    </v-card>
</template>

<script>
import BarChart from './Charts/BarChart'
import api from "../../common/api";

export default {
    name: "ReportTotalQuotes",
    props: ['branch_id'],
    components: {
        BarChart
    },
    data() {
        return {
            options: {
                legend: {
                    display: false
                },
            },
            chartdata: {labels: [], datasets: []},
        }
    },
    mounted() {
        const self = this;

        self.loadChartData();
    },
    methods: {
        loadChartData() {
            const self = this;

            api.getTotalQuotes().then((resp) => {
                self.chartdata = resp.data;
            });
        },
    }
}
</script>

<style scoped>

</style>
