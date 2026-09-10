<template>
    <div>
        <div v-for="(quotes, worksPackageName) in quotesGroupedByWorksPackage" class="enquiry-item">
            <h5>{{ worksPackageName }}</h5>
            <table class="quotes-table">
                <tr>
                    <th v-if="showMaterialsPrices()">Price (ex vat)</th>
                    <th v-if="showNameColumn()">{{ getNameHeader() }}</th>
                    <th>Common Assessment Standard</th>
                    <th>Postcode</th>
                    <th>Exact Distance</th>
                    <th>ESG Saving</th>
                    <th>Accepted</th>
                    <th>SME</th>
                    <th>Time taken to quote</th>
                    <th v-if="showMaterialsPrices()">Total Savings</th>
                </tr>
                <tr v-for="quote in quotes">
                    <td v-if="showMaterialsPrices()">{{ quote.price | formatPrice }}</td>
                    <td v-if="showNameColumn()">{{ quote.first_name }}</td>
                    <td>{{ quote.cas_approved ? 'Yes' : 'No' }}</td>
                    <td>{{ quote.postcode }}</td>
                    <td>{{ quote.distance.toFixed(2) }}</td>
                    <td>{{ quote.esgPerc.toFixed(2) }}%</td>
                    <td>{{ quote.quote_accepted_at }}</td>
                    <td>{{ quote.is_sme ? 'Yes' : 'No' }}</td>
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
    name: "QuotesTableTender",
    props: ['enquiries', 'type'],
    components: {},

    data() {
        return {

        }
    },

    mounted() {
        const self = this;
    },

    computed: {
        quotesGroupedByWorksPackage() {
            const grouped = {};

            for (const enquiryId in this.enquiries) {
                const quotes = this.enquiries[enquiryId];

                for (let i = 0; i < quotes.length; i++) {
                    const quote = quotes[i];
                    const wpName = quote.works_package_name || 'Unknown';

                    if (!grouped[wpName]) {
                        grouped[wpName] = [];
                    }

                    grouped[wpName].push(quote);
                }
            }

            return grouped;
        }
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
