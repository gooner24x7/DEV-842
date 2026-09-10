<template>
    <div style="overflow-x: scroll">
        <div v-if="quotes.length">
            <div class="report-list-container">
                <table class="quotes-table">
                    <tr>
                        <th v-if="showNameColumn()">{{ getNameHeader() }}</th>
                        <th v-if="type === 'materials'">Sub-contractor Name</th>
                        <th>Works Package</th>
                        <th>Evidence Documents</th>
                        <th>Documents</th>
                        <th>Postcode</th>
                        <th>Distance Within</th>
                        <th>Exact Distance</th>
                        <th v-if="showMaterialsPrices()">Price (ex vat)</th>
                        <th v-if="type === 'materials'">Local Material Spend</th>
                        <th>{{ getCategoryHeader() }}</th>
                        <th>Description of Goods</th>
                    </tr>
                    <tr v-for="quote in quotes">
                        <td v-if="showNameColumn()">{{ quote.first_name }}</td>
                        <td v-if="type === 'materials'">{{ quote.contractor_name }}</td>
                        <td>{{ quote.works_package_name }}</td>
                        <td>{{ quote.attachments.length ? 'Yes' : 'No'}}</td>
                        <td>
                            <a href="#" @click="openAttachments($event, quote)">
                                <v-icon>mdi-download</v-icon>
                            </a>
                        </td>
                        <td>{{ quote.postcode }}</td>
                        <td>{{ quote.distance_within }}</td>
                        <td>{{ quote.distance.toFixed(2) }}</td>
                        <td v-if="showMaterialsPrices()">{{ quote.price | formatPrice }}</td>
                        <td v-if="type === 'materials'">{{ quote.local_material_spend | formatPrice }}</td>
                        <td>{{ getCategoryValue(quote) }}</td>
                        <td>{{ quote.description }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div v-else>No data available</div>

        <v-dialog v-model="dialog" max-width="500px">
            <attachments-dialog
                type="Answer"
                title="Quote"
                :parentId="dialogParentId"
                :userCanEdit="dialogCanEdit"
                @close="dialog=false"
            ></attachments-dialog>
        </v-dialog>
    </div>
</template>

<script>
import api from "../../common/api"
import permissions from "../../common/permissions";
import AttachmentsDialog from "../AttachmentsDialog.vue";

export default {
    name: "QuotesWithSubstitutionsTable",
    props: ['quotes', 'type'],
    components: {AttachmentsDialog},

    data() {
        return {
            dialog: false,
            dialogParentId: null,
            dialogCanEdit: false,
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

        openAttachments(e, item) {
            const self = this;
            e.preventDefault();

            self.dialogParentId = item.id;
            self.dialogCanEdit = false;
            self.dialog = true;
        }
    }
}
</script>

<style scoped>

</style>
