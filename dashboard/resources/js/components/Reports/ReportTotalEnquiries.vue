<template>
    <v-card>
        <v-card-title>
            <span class="headline">Total Enquiries</span>
        </v-card-title>

        <v-card-text>
            <v-container>
                <line-chart :chart-data="chartdata" :options="options"></line-chart>
            </v-container>
        </v-card-text>
    </v-card>
</template>

<script>
import LineChart from './Charts/LineChart'
import api from "../../common/api"

export default {
    name: "ReportTotalEnquiries",
    props: ['branch_id'],
    components: {
        LineChart
    },
    data() {
        return {
            options: {
                legend: {
                    display: false
                },
            },
            chartdata: { labels:[], datasets: [] },
        }
    },
    mounted() {
        const self = this;

        self.loadChartData();
    },
    methods: {
        loadChartData() {
            const self = this;

            api.getTotalEnquiries().then((resp) => {
                self.chartdata = resp.data;
            });
        },
    }
}
</script>

<style scoped>

</style>
