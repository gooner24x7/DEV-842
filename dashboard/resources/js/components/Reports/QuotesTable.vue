<template>
    <div>
        <div v-for="(quotes, id) in enquiries" class="enquiry-item">
            <h5>Enquiry ID {{ id }}</h5>
            <table class="quotes-table">
                <tr>
                    <th v-if="showMaterialsPrices()">Price (ex vat)</th>
                    <th v-if="showNameColumn()">{{ getNameHeader() }}</th>
                    <th>Postcode</th>
                    <th>ESG Saving</th>
                    <th>Accepted</th>
                    <th>Time taken to quote</th>
                    <th v-if="showMaterialsPrices()">Total Savings</th>
                </tr>
                <tr v-for="quote in quotes">
                    <td v-if="showMaterialsPrices()">{{ quote.price | formatPrice }}</td>
                    <td v-if="showNameColumn()">{{ quote.first_name }}</td>
                    <td>{{ quote.postcode }}</td>
                    <td>{{ quote.esgPerc.toFixed(2) }}%</td>
                    <td>{{ quote.quote_accepted_at }}</td>
                    <td>{{ quote.time_diff }}</td>
                    <td v-if="showMaterialsPrices()">{{ quote.total_savings }}</td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
import api from "../../common/api"
import permissions from "../../common/permissions";

export default {
    name: "QuotesTable",
    props: ['enquiries', 'type'],
    components: {},

    data() {
        return {

        }
    },

    mounted() {
        const self = this;
    },

    methods: {
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isSubContractor() {
            return permissions.hasRole(permissions.role_user);
        },

        isFramework() {
            return permissions.hasRole(permissions.role_framework);
        },

        isClient() {
            return permissions.hasRole(permissions.role_client);
        },

        showMaterialsPrices() {
            if (this.type === 'materials') {
                return !this.isContractor() && !this.isFramework() && !this.isClient();
            }

            return true;
        },

        showNameColumn() {
            return !(this.type === 'materials' && (this.isFramework() || this.isClient()));
        },

        getNameHeader() {
            if (this.type === 'materials') {
                return 'Merchant Name';
            }

            return 'Sub-contractor Name';
        }
    }
}
</script>

<style scoped>

</style>
