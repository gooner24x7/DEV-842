<template>
    <div style="overflow-x: scroll">
        <div v-if="quotes.framework.length || quotes.client.length">
            <div class="report-list-container">
                <h5>
                    Framework
                    <span class="report-target" v-if="targets.target_miles_framework">
                        {{ " (Target: " + targets.target_miles_framework + " Miles)" }}
                    </span>
                </h5>
                <table class="quotes-table">
                    <tr>
                        <th v-if="targets.target_miles_framework"></th>
                        <th>{{ getNameHeader() }}</th>
                        <th v-if="type === 'materials'">Sub-contractor Name</th>
                        <th>Postcode</th>
                        <th>Distance Within</th>
                        <th>Exact Distance</th>
                        <th v-if="showMaterialsPrices()">Price (ex vat)</th>
                        <th v-if="type === 'materials'">Local Material Spend</th>
                        <th>{{ getCategoryHeader() }}</th>
                        <th v-if="type === 'trades'">SME</th>
                        <th>Description of Goods</th>
                        <th v-if="showReviews()">Review Status</th>
                        <th v-if="showReviews()">Actions</th>
                    </tr>
                    <tr v-for="quote in quotes.framework">
                        <td v-if="targets.target_miles_framework">
                            <v-icon :color="getTargetIconColor(quote.distance, targets.target_miles_framework)">
                                {{ getTargetIcon(quote.distance, targets.target_miles_framework) }}
                            </v-icon>
                        </td>
                        <td><a href="#" @click.prevent="showUserSummary(quote.user_id)">{{ quote.first_name }}</a></td>
                        <td v-if="type === 'materials'">{{ quote.contractor_name }}</td>
                        <td>{{ quote.postcode }}</td>
                        <td>{{ quote.distance_within }}</td>
                        <td>{{ quote.distance.toFixed(2) }}</td>
                        <td v-if="showMaterialsPrices()">{{ quote.price | formatPrice }}</td>
                        <td v-if="type === 'materials'">{{ quote.local_material_spend | formatPrice }}</td>
                        <td>{{ getCategoryValue(quote) }}</td>
                        <td v-if="type === 'trades'">{{ quote.is_sme ? 'Yes' : 'No'}}</td>
                        <td>{{ quote.description }}</td>
                        <td v-if="showReviews()">{{ getQuoteReviewStatus(quote) }}</td>
                        <td v-if="showReviews()">
                            <v-btn
                                v-if="quote.review_status == null"
                                @click="sendReview(quote)"
                                color="primary"
                                small
                            >
                                Send Review
                            </v-btn>
                            <v-btn
                                v-if="quote.review_status === 1"
                                @click="startReview(quote)"
                                color="primary"
                                small
                            >
                                Start Review
                            </v-btn>
                            <v-btn
                                v-if="quote.review_status === 2"
                                @click="viewReview(quote)"
                                color="primary"
                                small
                            >
                                View Summary
                            </v-btn>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="report-list-container">
                <h5>
                    Client (Doncaster)
                    <span class="report-target" v-if="targets.target_miles_client">
                        {{ " (Target: " + targets.target_miles_client + " Miles)" }}
                    </span>
                </h5>
                <table class="quotes-table">
                    <tr>
                        <th v-if="targets.target_miles_client"></th>
                        <th>{{ getNameHeader() }}</th>
                        <th v-if="type === 'materials'">Sub-contractor Name</th>
                        <th>Postcode</th>
                        <th>Distance Within</th>
                        <th>Exact Distance</th>
                        <th v-if="showMaterialsPrices()">Price (ex vat)</th>
                        <th v-if="type === 'materials'">Local Material Spend</th>
                        <th>{{ getCategoryHeader() }}</th>
                        <th v-if="type === 'trades'">SME</th>
                        <th>Description of Goods</th>
                        <th v-if="showReviews()">Review Status</th>
                        <th v-if="showReviews()">Actions</th>
                    </tr>
                    <tr v-for="quote in quotes.client">
                        <td v-if="targets.target_miles_client">
                            <v-icon :color="getTargetIconColor(quote.distance, targets.target_miles_client)">
                                {{ getTargetIcon(quote.distance, targets.target_miles_client) }}
                            </v-icon>
                        </td>
                        <td><a href="#" @click.prevent="showUserSummary(quote.user_id)">{{ quote.first_name }}</a></td>
                        <td v-if="type === 'materials'">{{ quote.contractor_name }}</td>
                        <td>{{ quote.postcode }}</td>
                        <td>{{ quote.distance_within }}</td>
                        <td>{{ quote.distance.toFixed(2) }}</td>
                        <td v-if="showMaterialsPrices()">{{ quote.price | formatPrice }}</td>
                        <td v-if="type === 'materials'">{{ quote.local_material_spend | formatPrice }}</td>
                        <td>{{ getCategoryValue(quote) }}</td>
                        <td v-if="type === 'trades'">{{ quote.is_sme ? 'Yes' : 'No'}}</td>
                        <td>{{ quote.description }}</td>
                        <td v-if="showReviews()">{{ getQuoteReviewStatus(quote) }}</td>
                        <td v-if="showReviews()">
                            <v-btn
                                v-if="quote.review_status == null"
                                @click="sendReview(quote)"
                                color="primary"
                                small
                            >
                                Send Review
                            </v-btn>
                            <v-btn
                                v-if="quote.review_status === 1"
                                @click="startReview(quote)"
                                color="primary"
                                small
                            >
                                Start Review
                            </v-btn>
                            <v-btn
                                v-if="quote.review_status === 2"
                                @click="viewReview(quote)"
                                color="primary"
                                small
                            >
                                View Summary
                            </v-btn>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div v-else>No data available</div>
    </div>
</template>

<script>
import api from "../../common/api"
import permissions from "../../common/permissions";

export default {
    name: "AcceptedQuotesTable",
    props: ['quotes', 'targets', 'type'],
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

        getNameHeader() {
            if (this.type === 'materials') {
                return 'Merchant Name';
            }

            return 'Sub-contractor Name';
        },

        getCategoryHeader() {
            if (this.type === 'materials') {
                return 'Trade Linked To';
            }

            return 'Category';
        },

        getCategoryValue(quote) {
            if (this.type === 'materials') {
                return quote.supply_fit_product_name
            }

            return quote.product_name
        },

        formatPrice(val) {
            if (val === null) {
                return '';
            }

            return '£' + val.toLocaleString('en-GB', { minimumFractionDigits: 2 })
        },

        getTargetIcon(value, target) {
            return (value > target) ? 'mdi-close-circle' : 'mdi-check-circle';
        },

        getTargetIconColor(value, target) {
            return (value > target) ? 'red' : 'green';
        },

        showReviews() {
            return this.type === 'trades' && (permissions.hasRole(permissions.role_admin) || permissions.hasRole(permissions.role_contractor));
        },

        sendReview(quote) {
            this.$emit('sendReview', quote);
        },

        startReview(quote) {
            this.$emit('startReview', quote.review_id);
        },

        viewReview(quote) {
            this.$emit('viewReview', quote.review_id);
        },

        getQuoteReviewStatus(quote) {
            let status = 'Not Sent';

            if (quote.review_status === 1) {
                status = 'Review Required';
            } else if (quote.review_status === 2) {
                status = 'Completed';
            }

            return status;
        },

        showUserSummary(userId) {
            this.$emit('showUserSummary', userId);
        }
    }
}
</script>

<style scoped>

</style>
