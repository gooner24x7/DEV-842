<template>
    <div style="margin: 1em">
<!--        <h2>Enquiry</h2>-->

        <logistics-enquiry-block :enquiryItems="enquiryItems"></logistics-enquiry-block>

        <v-btn
            class="m-2"
            color="primary"
            @click="toggleEnquiryDetails()"
            small
        >
            Show Enquiry Details
        </v-btn>

        <div class="enquiry-details" v-if="showEnquiryDetails">
            <v-row>
                <v-col>
                    <ul>
                        <li>ID: {{ enquiry.id }}</li>
                        <li>Type: {{ enquiry.type_str }}</li>
                        <li>Vehicle Type: {{ enquiry.vehicle_type }}</li>
                        <li>Load Details: {{ enquiry.load_details }}</li>
                        <li>Collect Date: {{ enquiry.collect_date | formatDate }}</li>
                        <li>Delivery Date: {{ enquiry.delivery_date | formatDate }}</li>
                        <li>Comments: {{ enquiry.comments }}</li>
                        <li>Notes: {{ enquiry.notes }}</li>
                    </ul>
                </v-col>
                <v-col>
                    <h5>Collection Address</h5>
                    <ul>
                        <li>Postcode: {{ enquiry.contacts[0]?.postcode }}</li>
                        <li>City: {{ enquiry.contacts[0]?.city }}</li>
                        <li>Address 1: {{ enquiry.contacts[0]?.address1 }}</li>
                        <li>Address 2: {{ enquiry.contacts[0]?.address2 }}</li>
                        <li>Contact Name: {{ enquiry.contacts[0]?.contact_name }}</li>
                        <li>Contact Phone: {{ enquiry.contacts[0]?.contact_phone }}</li>
                    </ul>
                </v-col>
                <v-col>
                    <h5>Delivery Address</h5>
                    <ul>
                        <li>Postcode: {{ enquiry.contacts[1]?.postcode }}</li>
                        <li>City: {{ enquiry.contacts[1]?.city }}</li>
                        <li>Address 1: {{ enquiry.contacts[1]?.address1 }}</li>
                        <li>Address 2: {{ enquiry.contacts[1]?.address2 }}</li>
                        <li>Contact Name: {{ enquiry.contacts[1]?.contact_name }}</li>
                        <li>Contact Phone: {{ enquiry.contacts[1]?.contact_phone }}</li>
                    </ul>
                </v-col>
            </v-row>
        </div>

        <h2>Enquiry Quotes</h2>

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                :expanded.sync="expanded"
                :headers="computedHeaders"
                :item-class="itemRowBackground"
                :items="items"
                :loading="loader"
                :options.sync="options"
                :server-items-length="serverItemsLength"
                :single-expand="singleExpand"
                item-key="id"
                loading-text="Loading... Please wait"
                mobile-breakpoint="1000"
            >

                <template v-slot:top>
                    <v-btn
                        v-if="canCreateQuote() && !isEnquiryArchived()"
                        @click="dialog = true"
                        class="m-2"
                        color="primary"
                        dark
                        style="left: 0 !important; top: 0 !important;"
                    >
                        New Quote
                    </v-btn>
                    <v-dialog v-model="dialog" max-width="500px">
                        <logistics-quote-form
                            v-model="editedItem"
                            :enquiryId="enquiryId"
                            :title="formTitle"
                            v-on:cancel="close"
                            v-on:saved="updated"
                        />
                    </v-dialog>

                    <div class="float-right" style="width: 150px">
                        <v-autocomplete
                            v-model="radius"
                            :items="radiusValues"
                            dense
                            item-text="label"
                            item-value="value"
                            label="Radius"
                            outlined
                        ></v-autocomplete>
                    </div>

                    <div style="clear:both"></div>
                </template>

                <template v-slot:item.accepted_at="{ item }">
                    <v-checkbox :input-value="!!item.accepted_at" :label="item.accepted_at | formatDateTime"
                                readonly
                                v-on:click="switchAccepted(item)"></v-checkbox>
                </template>

                <template v-slot:item.first_name="{ item }">
                    <a href="#" @click="(e) => { e.preventDefault(); showSupplierDetails(item); }">{{
                            item.first_name
                        }}</a>
                </template>

                <template v-slot:item.price="{ item }">
                    <span>{{ item.price | formatPrice }}</span>
                </template>

                <template v-slot:item.viewed_at="{ item, index }">
                    <v-checkbox :input-value="!!item.viewed_at" readonly></v-checkbox>
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.chat="{ item }">

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                class="mr-2"
                                color="black"
                                icon
                                small
                                v-bind="attrs"
                                @click="viewItem(item)"
                                v-on="on"
                            >
                                <v-badge
                                    :content="item.newMsgQty"
                                    :overlap="true"
                                    :value="item.newMsgQty > 0"
                                    color="red"
                                >
                                    <v-icon>
                                        mdi-forum
                                    </v-icon>
                                </v-badge>
                            </v-btn
                            >
                        </template>
                        <span>Live Chat</span>
                    </v-tooltip>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <v-row>
                            <v-col>
                                <div v-if="displayComments" class="expanded-comment">{{ item.comment }}</div>
                                <div v-if="displayUserInfo" class="expanded-user-info">
                                    <h4>{{ displayInfoUser.first_name }}</h4>
                                    <div>
                                        <b>Locations:</b> {{ displayInfoUser.locations }}<br>
                                        <b>Head Office:</b> {{ displayInfoUser.head_office_address }}<br>
                                        <b>Phone Number:</b> {{ displayInfoUser.phone }}<br>
                                        <b>Customer Service:</b> {{ displayInfoUser.customer_service }}<br>
                                        <b>Supplier Description:</b>
                                        <p>{{ displayInfoUser.description }}</p>
                                    </div>
                                </div>
                            </v-col>
                            <v-col>
                                <img v-if="displayUserInfo" :src="displayInfoUser.logo_url" style="width: 100%; height: auto;">
                            </v-col>
                        </v-row>
                    </td>
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn
                            v-if="canEditQuote(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="editItem(item)"
                        >Edit Quote
                        </v-btn>
                        <v-btn
                            v-if="canEditQuote(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="deleteItem(item)"
                        >Delete Quote
                        </v-btn>
                    </div>
                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="LogisticsQuote"
                title="Quote"
                @close="dialogUploads=false"
                :userCanEdit="user().id === dialogUploadItem?.user_id"
            ></attachments-dialog>
        </v-dialog>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat-logistics-quote
                :quote="quote"
                :enquiry="enquiry"
                :visibility="chatDialog"
                v-on:close="chatDialog = false"
            />
        </v-dialog>

    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import AttachmentsDialog from "../components/AttachmentsDialog";
import LogisticsQuoteForm from "../components/LogisticsQuoteForm.vue";
import LogisticsEnquiryBlock from "../components/LogisticsEnquiryBlock.vue";
import ChatLogisticsQuote from "../components/ChatLogisticsQuote.vue";

export default {
    components: {
        ChatLogisticsQuote,
        LogisticsEnquiryBlock,
        LogisticsQuoteForm,
        AttachmentsDialog,
    },
    data() {
        return {
            loader: false,
            expanded: [],
            singleExpand: true,
            refreshHandler: null,
            dialogUploads: false,
            dialogUploadItem: {},
            enquiryItems: [],
            displayComments: false,
            displayUserInfo: false,
            displayInfoUser: null,
            radius: 0,
            radiusValues: [
                {
                    value: 5,
                    label: '5 Miles',
                },
                {
                    value: 10,
                    label: '10 Miles',
                },
                {
                    value: 15,
                    label: '15 Miles',
                },
                {
                    value: 20,
                    label: '20 Miles',
                },
                {
                    value: 25,
                    label: '25 Miles',
                },
                {
                    value: 30,
                    label: '30 Miles',
                },
                {
                    value: 50,
                    label: '50 Miles',
                },
                {
                    value: 0,
                    label: 'All UK quotes',
                }
            ],
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
                    value: "type_str",
                    sortable: false,
                },
                {
                    text: "Price (ex VAT)",
                    value: "price",
                    sortable: true,
                },
                {
                    text: "Comments",
                    value: "comments",
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
                    text: 'Chat',
                    value: 'chat',
                    sortable: false,
                },
                {
                    text: 'Accepted At',
                    value: 'accepted_at',
                    sortable: true,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            items: [],
            serverItemsLength: 0,
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
            dialog: false,
            chatDialog: false,
            editedIndex: -1,
            editedItem: {
                price: 0,
                comment: "",
                supplier_invoice_no: "",
                editId: null,
            },
            defaultItem: {
                price: 0,
                comment: "",
                supplier_invoice_no: "",
                editId: null,
            },
            enquiryId: 0,
            quote: null,
            enquiry: null,
            enquiryFiltered: null,
            showEnquiryDetails: false
        };
    },

    async mounted() {
        const self = this;

        self.enquiryId = self.$route.params.enquiry_id;

        const response = await api.getLogisticsEnquiry(self.enquiryId);
        self.enquiry = response.data;

        self.enquiryFiltered = Object.filter(self.enquiry, ([key, value]) => key !== 'contacts');

        await self.loadEnquiry();

        self.refreshHandler = setTimeout(async () => {
            await self.load();
        }, 5 * 60 * 1000);
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        radius() {
            this.load();
        },
        dialog(val) {
            val || this.close();
        },
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New Quote" : "Edit Quote";
        },

        computedHeaders() {
            const self = this;

            return self.headers;
        },
    },

    methods: {
        async showSupplierDetails(item) {
            const self = this;

            if (this.expanded.indexOf(item) > -1 && this.displayUserInfo) {
                this.expanded = [];
                return;
            }

            this.displayComments = false;
            this.displayUserInfo = true;

            const response = await api.getUser(item.user_id);
            self.displayInfoUser = response.data;

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

        switchAccepted(item) {
            this.acceptItem(item);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isMerchant() {
            return permissions.hasRole(permissions.role_company);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        itemRowBackground: function (item) {
            return item.is_preferred_supplier ? "highlight-row" : "";
        },

        async loadEnquiry() {
            const self = this;
            self.loader = true;

            const response = await api.loadLogisticsEnquiries({'id': this.enquiryId})
            self.enquiryItems = response.data.data;
            self.loader = false;
        },

        async load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                radius: self.radius,
            };

            try {
                const response = await api.loadLogisticsQuotes(this.enquiryId, params)
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async acceptItem(quote) {
            const self = this;

            if (quote.accepted_at) {
                if (confirm("Are you sure you want to mark this quote as not accepted?")) {
                    self.loader = true;
                    await api.unacceptLogisticsQuote(quote.id);
                    await self.load();

                    self.loader = false;
                }

                return;
            }

            if (confirm("Are you sure you want to accept this quote?")) {
                self.loader = true;
                await api.acceptLogisticsQuote(quote.id);
                await self.load()

                self.loader = false
            }
        },

        async deleteItem(quote) {
            const self = this;

            if (confirm("Are you sure you want to delete this quote?")) {
                self.loader = true;
                await api.deleteLogisticsQuote(quote.id);
                await self.load();

                self.loader = false;
            }
        },

        viewItem(quote) {
            const self = this;

            quote.newMsgQty = 0;
            this.quote = quote;
            this.chatDialog = !this.chatDialog;
        },

        updated() {
            const self = this;

            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });

            self.load();
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        canCreateQuote() {
            return permissions.can(permissions.create_quote_logistics);
        },

        canEditQuote() {
            return permissions.hasRole(permissions.role_admin);
        },

        toggleEnquiryDetails() {
            const self = this;

            self.showEnquiryDetails = !self.showEnquiryDetails;
        },

        isEnquiryArchived() {
            const self = this;

            return self.enquiry?.archived_at !== null;
        }
    },
};
</script>

<style>
.quote-scores {
    font-size: 18px;
    font-weight: bold;
}
</style>
