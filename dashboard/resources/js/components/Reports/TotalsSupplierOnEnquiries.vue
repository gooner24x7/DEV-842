<template>
    <v-card>
        <v-card-title>Over all<span v-if="isAdmin()">for suppliers</span></v-card-title>

        <v-card-text>
            <v-container>
                <bar-chart :chart-data="chartdata" :options="options"></bar-chart>
            </v-container>
        </v-card-text>
    </v-card>
</template>

<script>
import BarChart from "./Charts/BarChart";
import api from "../../common/api";
import permissions from "../../common/permissions";

export default {
    name: "TotalsContractorOnEnquiries",
    components: {BarChart},
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
        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },
        loadChartData() {
            const self = this;

            api.getTotalsSupplierOnEnquiries().then((resp) => {
                self.chartdata = resp.data;
            });
        },
    }
}
</script>

<style scoped>

</style>
