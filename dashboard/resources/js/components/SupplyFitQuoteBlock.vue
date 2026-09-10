<template>
    <div>
        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="headers"
                :items="quoteItems"
                :single-expand="true"
                :expanded.sync="expanded"
                hide-default-footer
            >
                <template v-slot:header.esgPerc="{ header }">
                    {{ header.text.toUpperCase() }}

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-information
                                </v-icon>
                            </v-btn
                            >
                        </template>
                        <span>We take a 26 tonne truck and based on how close to the live site per mile work out the %<br>
                            savings of emissions from the furthest to closest supplier purchased from.</span>
                    </v-tooltip>
                </template>

                <template v-slot:item.quote_accepted_at="{ item }">
                    <v-checkbox v-if="isContractor()" :input-value="!!item.quote_accepted_at"
                                :label="item.quote_accepted_at | formatDateTime"
                                readonly
                                v-on:click="switchAccepted(item)"></v-checkbox>
                    <div v-else>{{ item.quote_accepted_at | formatDateTime }}</div>
                </template>

                <template v-slot:item.report_icon="{ item }">
                    <v-tooltip top v-if="isSubContractor() && item.quote_accepted_at !== null">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="showReport(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-chart-pie
                                </v-icon>
                            </v-btn
                            >
                        </template>
                        <span>Reports</span>
                    </v-tooltip>
                </template>

                <template v-slot:item.supplier_invoice_no="{ item }">
                    <span v-if="!isAnswerer()">{{ item.supplier_invoice_no }}</span>
                    <v-text-field v-if="isAnswerer()" v-model="item.supplier_invoice_no" dense hide-details outlined
                                  @change="(e) => updateSupplierInvoiceNo(e, item)"></v-text-field>
                </template>

                <template v-slot:item.first_name="{ item }">
                    <a href="#" @click="(e) => { e.preventDefault(); showSupplierDetails(item); }">{{
                            item.first_name
                        }}</a>
                </template>

                <template v-slot:item.comment="{ item }">
                    {{ item.comment | limit20Chars }}

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="viewComments(item)"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-format-list-bulleted-square
                                </v-icon>
                            </v-btn
                            >
                        </template>
                        <span>View Comments</span>
                    </v-tooltip>
                </template>
                <template v-slot:item.type="{item}">
                    <span v-if="item.type === 'full'">Full</span>
                    <span v-if="item.type === 'part'">Part</span>
                </template>

                <template v-slot:item.creditApplicationFormUrl="{item}">
                    <a v-if="item.creditApplicationFormUrl"
                       href="#"
                       target="_blank"
                       v-on:click="(e) => { openApplicationFormUrl(item) } ">
                        <v-icon>
                            mdi-note
                        </v-icon>
                    </a>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <div v-if="displayComments" class="expanded-comment">
                            <pre>{{ item.comment }}</pre>
                        </div>
                        <div v-if="displayUserInfo" class="expanded-user-info">
                            <h4 style="padding-left: 24px;">{{ userInfo.first_name }}</h4>
                            <v-row v-if="creditsafeInfo['Company Number']" class="creditsafe-info">
                                <v-col>
                                    <div style="padding-left: 24px; font-weight: bold;"><u>Creditsafe:</u></div>
                                    <ul class="creditsafe-info-list" style="list-style: none">
                                        <li v-for="(v, i) in creditsafeInfo" :key="i"><b>{{ i }}:</b> {{ v }}</li>
                                    </ul>
                                </v-col>
                            </v-row>
                        </div>
                    </td>
                </template>

                <template v-slot:item.esgPerc="{item}">
                    <div :title="'Distance: ' + (item.distance ?? 0).toFixed(2) + 'm, ' +
                    'Max distance: ' + (item.max_distance ?? 0).toFixed(2) + 'm'" style="white-space: nowrap;">
                        {{ (item.esgPerc ?? 0).toFixed(2) }}%
                    </div>
                </template>

                <template v-slot:item.price="{ item }">
                    <span>{{ item.price | formatPrice }}</span>
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="SupplyFitEnquiryQuote"
                title="Quote"
                @close="dialogUploads=false"
                :userCanEdit="user().id === dialogUploadItem?.user_id"
            ></attachments-dialog>
        </v-dialog>

    </div>
</template>

<script>
import AttachmentsDialog from "../components/AttachmentsDialog";
import api from "../common/api";
import permissions from "../common/permissions";
import ChatSupplyFitQuote from "./ChatSupplyFitQuote.vue";

export default {
    name: "SupplyFitQuoteBlock",

    props: [
        'quoteItems'
    ],

    components: {
        ChatSupplyFitQuote,
        AttachmentsDialog,
    },

    data() {
        return {
            expanded: [],
            dialogUploadItem: {},
            dialogUploads: false,
            displayComments: false,
            displayUserInfo: false,
            userInfo: {},
            creditsafeInfo: {},
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: false,
                },
                {
                    text: "Business Name",
                    value: "first_name",
                    sortable: false,
                },
                {
                    text: "Full / Part Quote",
                    value: "type",
                    sortable: false,
                },
                {
                    text: "Price",
                    value: "price",
                    sortable: false,
                },
                {
                    text: "Comments",
                    value: "comment",
                    sortable: false,
                },
                {
                    text: 'Documents',
                    value: 'attachments',
                    sortable: false,
                },
                {
                    text: 'Accepted At',
                    value: 'quote_accepted_at',
                    sortable: false,
                },
                {
                    text: '',
                    value: 'report_icon',
                    sortable: false,
                },
                {
                    text: "Supplier Invoice No.",
                    value: "supplier_invoice_no",
                    sortable: false,
                    width: '200px'
                },
                {
                    text: 'Account Form',
                    value: 'creditApplicationFormUrl',
                    sortable: false,
                },
                {
                    text: "ESG saving",
                    value: 'esgPerc',
                    sortable: false,
                },
            ],
        }
    },

    computed: {

    },

    methods: {
        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isSubContractor() {
            return permissions.hasRole(permissions.role_user);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        openApplicationFormUrl(item) {
            api.newAnalyticsEvent({
                'object_id': item.id,
                'object_type': 'supply-&-fit-inquiry-quote',
                'action': 'download',
                'target': 'application-form',
                'location': 'inquiry-quote-page',
                'url': window.location.pathname,
            });

            window.open(item.creditApplicationFormUrl, '__blank');
        },

        async showSupplierDetails(item) {
            const self = this;
            if (this.expanded.indexOf(item) > -1 && this.displayUserInfo) {
                this.expanded = [];
                return;
            }

            this.displayComments = false;
            this.displayUserInfo = true;

            const response = await api.getUser(item.user_id);
            const response2 = await api.getCreditsafeInfo(item.user_id);

            self.userInfo = response.data;
            self.creditsafeInfo = response2.data;

            this.expanded = [item];
        },

        viewComments: function (item) {
            if (this.expanded.indexOf(item) > -1 && this.displayComments) {
                this.expanded = [];
                return;
            }

            this.displayUserInfo = false;
            this.displayComments = true;

            this.expanded = [item];
        },

        async updateSupplierInvoiceNo(e, item) {
            const self = this;

            self.loader = true;

            try {
                await api.updateSupplierInvoiceNoSupplyFit(item.id, item.supplier_invoice_no)
                self.$store.commit("showSnackbar", {
                    message: "Supplier Invoice No has been successfully stored",
                    color: "success",
                });
            } catch (e) {
            }

            self.loader = false;
        },

        switchAccepted(item) {
            this.acceptItem(item);
        },

        async acceptItem(quote) {
            const self = this;

            if (!self.isContractor()) {
                return;
            }

            if (quote.quote_accepted_at) {
                if (confirm("Are you sure you want to mark this quote as not accepted?")) {
                    self.loader = true;
                    await api.unacceptSupplyFitEnquiryQuote(quote.id);
                    await self.load();
                    self.loader = false;
                }

                return;
            }

            if (confirm("Are you sure you want to accept this quote?")) {
                self.loader = true;
                await api.acceptSupplyFitEnquiryQuote(quote.id)
                await self.load();
                self.loader = false;
            }
        },

        showReport(item) {
            this.$router.push({
                name: "contractor-report",
                query: {
                    id: item.project_id
                }
            });
        },

        isAnswerer() {
            return permissions.hasRole(permissions.role_user);
        },

        user() {
            return this.$store.getters.getSession.user;
        },
    }
}
</script>

<style scoped>

</style>
