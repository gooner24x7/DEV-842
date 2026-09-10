<template>
    <v-card>
        <v-card-title>Accepted enquiry based on time from quote <span v-if="isAdmin()">for contractors</span></v-card-title>

        <v-card-text>
            <v-container>
                <line-chart :chart-data="chartdata" :options="options"></line-chart>
            </v-container>
        </v-card-text>
    </v-card>
</template>

<script>
import LineChart from "./Charts/LineChart";
import api from "../../common/api";
import permissions from "../../common/permissions";

export default {
    name: "EnquiriesToTimeContractor",
    components: {LineChart},
    data() {
        return {
            options: {
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Number of quotes'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Number of enquiries'
                        }
                    }]
                }
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

            api.getEnquiriesToTimeContractor().then((resp) => {
                self.chartdata = resp.data;
            });
        },
        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },
    }
}
</script>

<style scoped>

</style>
