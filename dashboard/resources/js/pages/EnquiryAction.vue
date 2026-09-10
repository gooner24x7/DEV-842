<template>

    <div style="padding: 10px">
        <div>Enquiry Action Page</div>

        <div v-if="params.action === 'accept_quote'">
            <h2>Your Enquiry</h2>

            <enquiry-block :enquiryItems="enquiryItems"></enquiry-block>

            <h2>Enquiry Quotes</h2>

            <div style="position: relative; padding-top: 1em">
                <v-data-table
                    :headers="headers"
                    :items="quoteItems"
                    :options.sync="options"
                    :server-items-length="serverItemsLength"
                    item-key="id"
                    loading-text="Loading... Please wait"
                    mobile-breakpoint="1000"
                >
                    <template v-slot:header.esgPerc="{ header }">
                        {{ header.text.toUpperCase() }}

                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    v-bind="attrs"
                                    v-on="on"
                                    class="mr-2"
                                    color="black"
                                    icon
                                    small
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

                    <template v-slot:item.actions="{ item }">
                        <div class="text-center">
                            <v-btn
                                class="mr-2"
                                color="primary"
                                small
                                @click="acceptQuote(item)"
                            >Accept Quote
                            </v-btn>
                        </div>
                    </template>

                </v-data-table>

                <v-dialog v-model="dialogUploads" max-width="500px">
                    <attachments-dialog
                        :parentId="dialogUploadItem.id"
                        type="Answer"
                        title="Quote"
                        @close="dialogUploads=false"
                        :userCanEdit="user().id === dialogUploadItem?.user_id"
                    ></attachments-dialog>
                </v-dialog>
            </div>
        </div>

        <div v-if="params.action === 'update_enquiry'">
            <h2>Your Enquiry</h2>

            <p>If you still wish to buy the materials, please update the due date. If you no longer wish to purchase, please archive the enquiry.</p>

            <enquiry-block :enquiryItems="enquiryItems"></enquiry-block>

            <v-card style="padding: 10px">
                <div v-if="isNaN(dueDate)">
                    <v-menu
                        v-model="menu"
                        :close-on-content-click="false"
                        max-width="290"
                    >
                        <template v-slot:activator="{ on, attrs }">
                            <v-text-field
                                label="Due Date"
                                v-model="dueDate"
                                type="text"
                                v-bind="attrs"
                                v-on="on"
                            ></v-text-field>
                        </template>
                        <v-date-picker
                            v-model="selectedDate"
                            no-title
                            @change="menu = false"
                        ></v-date-picker>
                    </v-menu>
                </div>
                <div v-else>
                    <v-text-field label="Days" v-model="dueDate"></v-text-field>
                </div>
                <div v-if="archivedAt">
                    Archived At: {{ archivedAt | formatDateTime }}
                </div>
                <v-checkbox label="Archive Enquiry" v-model="archiveEnquiry" :disabled="archivedAt !== null"></v-checkbox>
                <v-btn color="primary" @click="updateEnquiry()">Update Enquiry</v-btn>
            </v-card>
        </div>

        <div v-if="params.action === 'customer_response'">
            <p>Thankyou for letting us know</p>
        </div>

    </div>

</template>

<script>
import api from "../common/api.js";
import EnquiryBlock from "../components/EnquiryBlock.vue";
import moment from "moment/moment";
import AttachmentsDialog from "../components/AttachmentsDialog.vue";

export default {
    name: "EnquiryAction",
    components: {AttachmentsDialog, EnquiryBlock},
    props: [],

    data() {
        return {
            params: [],
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Business Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Type",
                    value: "type",
                    sortable: false,
                },
                {
                    text: "Price (ex VAT)",
                    value: "price",
                    sortable: true,
                },
                {
                    text: "Comments",
                    value: "comment",
                    sortable: true,
                },
                {
                    text: 'Viewed',
                    value: 'viewed_at',
                    sortable: true,
                },
                {
                    text: 'Documents',
                    value: 'attachments',
                    sortable: false,
                },
                {
                    text: 'Accepted At',
                    value: 'quote_accepted_at',
                    sortable: true,
                },
                {
                    text: "Supplier Invoice No.",
                    value: "supplier_invoice_no",
                    sortable: true,
                    width: '200px'
                },
                {
                    text: "ESG saving",
                    value: 'esgPerc',
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["id"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            enquiryItems: [],
            quoteItems: [],
            serverItemsLength: 0,
            archiveEnquiry: false,
            dueDate: null,
            selectedDate: '',
            menu: false,
            archivedAt: null,
            dialogUploads: false,
            dialogUploadItem: null,
        }
    },

    watch: {
        selectedDate:  function (n, o) {
            this.dueDate = moment(n, 'YYYY-MM-DD').format('DD-MM-YYYY');
        },
    },

    async mounted() {
        await this.load();
    },

    methods: {
        async load() {
            this.params = this.$route.query;

            if (this.params.action === 'update_enquiry' || this.params.action === 'accept_quote') {
                const response = await api.externalActionGetEnquiryData(this.params);

                this.enquiryItems = [response.data.question];
                this.quoteItems = response.data.answers;
                this.serverItemsLength = response.data.answers.length;
                this.dueDate = response.data.question.days;
                this.archivedAt = response.data.question.archived_at;

                if (isNaN(this.dueDate)) {
                    this.selectedDate = moment(this.dueDate, 'DD-MM-YYYY').format('YYYY-MM-DD');
                }

            } else if (this.params.action === 'customer_response') {
                const response = await api.externalActionCustomerResponse(this.params);
            }
        },

        async acceptQuote(item) {
            const response = await api.externalActionAcceptQuote({
                id: this.params.id,
                token: this.params.token,
                quoteId: item.id
            });

            if (response.status === 200) {
                this.$store.commit("showSnackbar", {
                    message: "Quote accepted",
                    color: "success",
                });
            } else {
                this.$store.commit("showSnackbar", {
                    message: "Error: " + response.statusText,
                    color: "error",
                });
            }
        },

        async updateEnquiry() {
            const response = await api.externalActionUpdateEnquiry({
                id: this.params.id,
                token: this.params.token,
                archive: this.archiveEnquiry,
                dueDate: this.dueDate
            });

            if (response.status === 200) {
                let message = "Your enquiry has been updated";

                if (this.archiveEnquiry) {
                    message = "We have archived your enquiry. If you wish to restore the enquiry, simply login to your account, navigate to 'Enquiries' > 'Purchase & Hire Archived', and click 'Restore' on the desired enquiry."
                }

                this.$store.commit("showSnackbar", {
                    message: message,
                    color: "success",
                });
            } else {
                this.$store.commit("showSnackbar", {
                    message: "Error: " + response.statusText,
                    color: "error",
                });
            }
        },

        user() {
            return this.$store.getters.getSession.user;
        },
    }
}
</script>

<style scoped>

</style>
