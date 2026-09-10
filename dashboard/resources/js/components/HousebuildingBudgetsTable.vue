<template>
    <div class="table-wrapper">
        <h5 v-if="category !== null">{{ category }}</h5>
        <table v-if="budgets" class="budgets-table">
            <tr>
                <th></th>
                <th v-for="name in houseNames">{{ name }}</th>
            </tr>
            <tr>
                <td>Total</td>
                <td v-for="value in budgets.total">{{ value | formatPrice }}</td>
            </tr>
            <tr>
                <td>Actual Price</td>
                <td v-for="value in budgets.actual_price">{{ value | formatPrice }}</td>
            </tr>
            <tr>
                <td>Difference</td>
                <td v-for="(value, index) in budgets.total">{{ getDiffValue(value, index) | formatPrice }}</td>
            </tr>
            <tr>
                <td>Budget</td>
                <td v-for="(value, index) in budgets.budget">
                    <v-text-field
                        v-model="budgets.budget[index]"
                        @change="(e) => updateBudgetValue(e, value, index)"
                        prefix="£"
                        dense
                        hide-details
                        outlined
                    ></v-text-field>
                </td>
            </tr>
        </table>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions";

export default {
    name: "HousebuildingBudgetsTable",
    props: ['category', 'houseNames', 'budgets'],
    components: {},

    data() {
        return {

        }
    },

    mounted() {
        const self = this;
    },

    methods: {
        async updateBudgetValue(e, value, index) {
            const self = this;

            let houseName = self.houseNames[index];
            let response = await api.updateHousebuildingBudget({category: self.category, house_name: houseName, budget: value});
        },
        getDiffValue(value, index) {
            const self = this;

            if (!value) {
                return null;
            }

            return (value - self.budgets.actual_price[index]).toFixed(2);
        }
    }
}
</script>

<style scoped>
    .table-wrapper {
        overflow-x: scroll;
        margin-top: 10px;
    }

    .budgets-table {
        border-collapse: collapse;
        border-spacing: 0 10px;
    }

    .budgets-table th {
        font-size: 0.75rem;
        color: rgba(0,0,0,.6);
        min-width: 140px;
    }

    .budgets-table th, .budgets-table td {
        padding: 10px 20px;
    }

    .budgets-table td {
        background: #ffffff;
        border: none;
        text-wrap: nowrap;
    }

    .budgets-table tr {
        border: 2px solid #f0f0f0;
    }
</style>
