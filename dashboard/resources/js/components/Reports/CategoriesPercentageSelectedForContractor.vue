<template>
    <v-card>
        <v-card-title>By category <span v-if="isAdmin()">for contractors</span></v-card-title>

        <v-card-text>
            <v-container>
                <pie-chart :chart-data="chartdata" :options="options"></pie-chart>
            </v-container>
        </v-card-text>
    </v-card>
</template>

<script>
import PieChart from "./Charts/PieChart";
import {getRandomColor} from "./Charts/PieChart";
import api from "../../common/api";
import permissions from "../../common/permissions";

export default {
    name: "CategoriesPercentageSelectedForContractor",
    components: {PieChart},
    data() {
        return {
            options: {
                hoverBorderWidth: 20,
            },
            chartdata: {},
        }
    },
    mounted() {
        const self = this;

        api.getCategoriesPercentageSelectedForContractor().then((resp) => {
            self.chartdata = {
                hoverBackgroundColor: "red",
                hoverBorderWidth: 10,
                labels: Object.keys(resp.data),
                datasets: [
                    {
                        label: "Categories",
                        backgroundColor: Object.keys(resp.data).map(() => {
                            return getRandomColor()
                        }),
                        data: Object.values(resp.data)
                    }
                ]
            }

            console.log(self.chartdata)
        });
    },
    methods: {
        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },
    }
}
</script>

<style scoped>

</style>
