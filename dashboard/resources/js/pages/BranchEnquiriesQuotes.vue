<template>
    <div style="margin: 1em">
        <h2>Enquiry</h2>

        <enquiry-block :enquiryItems="enquiryItems"></enquiry-block>

        <h2>Enquiry Quotes</h2>

        <div style="position: relative; padding-top: 1em">
            <v-data-table
                item-key="id"
                :headers="computedHeaders"
                :items="items"
                :server-items-length="serverItemsLength"
                :loading="loader"
                :options.sync="options"
                loading-text="Loading... Please wait"
                :item-class="itemRowBackground"
                :single-expand="singleExpand"
                :expanded.sync="expanded"
                mobile-breakpoint="1000"
            >
                <template v-slot:header.esgPerc="{ header }">
                    {{ header.text.toUpperCase() }}

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                small
                                class="mr-2"
                                icon
                                color="black"
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

                <template v-slot:top>
                    <v-dialog v-model="dialog" max-width="500px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                v-if="canAnswer()"
                                color="primary"
                                dark
                                class="m-2"
                                v-bind="attrs"
                                v-on="on"
                                style="left: 0 !important; top: 0 !important;"
                            >Quote
                            </v-btn
                            >
                        </template>
                        <answer-form
                            :questionId="questionId"
                            v-model="editedItem"
                            v-on:saved="updated"
                            v-on:cancel="close"
                            :title="formTitle"
                        />
                    </v-dialog>

<!--                    <div style="width: 150px" class="float-right" v-if="!isAnswerer()">-->
<!--                        <v-autocomplete-->
<!--                            v-model="radius"-->
<!--                            :items="radiusValues"-->
<!--                            outlined-->
<!--                            dense-->
<!--                            label="Radius"-->
<!--                            item-value="value"-->
<!--                            item-text="label"-->
<!--                        ></v-autocomplete>-->
<!--                    </div>-->
                    <div style="clear:both"></div>
                </template>

                <template v-slot:item.quote_accepted_at="{ item }">
                    <v-checkbox :input-value="!!item.quote_accepted_at" readonly
                                v-on:click="switchAccepted(item)"
                                :label="item.quote_accepted_at | formatDateTime"></v-checkbox>
                </template>

                <template v-slot:item.supplier_invoice_no="{ item }">
                    <span v-if="!isAnswerer()">{{ item.supplier_invoice_no }}</span>
                    <v-text-field dense outlined hide-details v-if="isAnswerer()" v-model="item.supplier_invoice_no"
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
                                small
                                class="mr-2"
                                @click="viewComments(item)"
                                icon
                                color="black"
                                v-bind="attrs"
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

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <div class="expanded-comment" v-if="displayComments">{{ item.comment }}</div>
                        <div class="expanded-user-info" v-if="displayUserInfo">
                            <img :src="displayInfoUser.logo_url" class="float-right" height="150">
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
                    </td>
                </template>

                <template v-slot:item.esgPerc="{item}">
                    <div style="white-space: nowrap;" :title="'Distance: ' + (item.distance ?? 0).toFixed(2) + 'm, ' +
                    'Max distance: ' + (item.max_distance ?? 0).toFixed(2) + 'm'">
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

                <template v-slot:item.creditApplicationFormUrl="{item}">
                    <a v-if="item.creditApplicationFormUrl" :href="item.creditApplicationFormUrl" target="_blank">
                        <v-icon>
                            mdi-note
                        </v-icon>
                    </a>
                </template>

                <template v-slot:item.type="{item}">
                    <span v-if="item.type === 'full'">Full</span>
                    <span v-if="item.type === 'part'">Part</span>
                </template>

                <template v-slot:item.chat="{ item }">

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                small
                                class="mr-2"
                                @click="viewItem(item)"
                                icon
                                color="black"
                                v-bind="attrs"
                                v-on="on"
                            >
                                <v-badge
                                    :value="item.newMsgQty > 0"
                                    color="red"
                                    :content="item.newMsgQty"
                                    :overlap="true"
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


                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn
                            v-if="canEditAnswer(item)"
                            color="primary"
                            small
                            class="mr-2"
                            @click="editItem(item)"
                        >Edit Quote
                        </v-btn>
                        <v-btn
                            v-if="canEditAnswer(item)"
                            color="primary"
                            small
                            class="mr-2"
                            @click="deleteItem(item)"
                        >Delete Quote
                        </v-btn>

                        <!--                    <v-btn-->
                        <!--                        v-if="isInquirySender() && !item.quote_accepted_at"-->
                        <!--                        color="primary"-->
                        <!--                        small-->
                        <!--                        class="mr-2"-->
                        <!--                        @click="acceptItem(item)"-->
                        <!--                    >Accept Quote-->
                        <!--                    </v-btn>-->

                    </div>
                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="Answer"
                title="Quote"
                @close="dialogUploads=false"
                :userCanEdit="user().id === dialogUploadItem?.user_id"
            ></attachments-dialog>
        </v-dialog>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat
                :visibility="chatDialog"
                :answer="answer"
                :question="question"
                v-on:close="chatDialog = false"
            />
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import AnswerForm from "../components/AnswerForm";
import permissions from "../common/permissions.js";
import Chat from "../components/Chat";
import AttachmentsDialog from "../components/AttachmentsDialog";
import EnquiryBlock from "../components/EnquiryBlock";

export default {
    props: ['branchId'],
    components: {
        EnquiryBlock,
        AttachmentsDialog,
        AnswerForm,
        Chat,
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
            //
            // radius: 0,
            // radiusValues: [
            //     {
            //         value: 5,
            //         label: '5 Miles',
            //     },
            //     {
            //         value: 10,
            //         label: '10 Miles',
            //     },
            //     {
            //         value: 15,
            //         label: '15 Miles',
            //     },
            //     {
            //         value: 20,
            //         label: '20 Miles',
            //     },
            //     {
            //         value: 25,
            //         label: '25 Miles',
            //     },
            //     {
            //         value: 30,
            //         label: '30 Miles',
            //     },
            //     {
            //         value: 50,
            //         label: '50 Miles',
            //     },
            //     {
            //         value: 0,
            //         label: 'All UK quotes',
            //     }
            // ],

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
                    text: "Full / Part Quote",
                    value: "type",
                    sortable: false,
                },
                // {
                //     text: "Last Name",
                //     value: "last_name",
                //     sortable: true,
                // },
                {
                    text: "Price",
                    value: "price",
                    sortable: true,
                },
                {
                    text: "Comments",
                    value: "comment",
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
                    text: 'Account Form',
                    value: 'creditApplicationFormUrl',
                    sortable: false,
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
            questionId: 0,
            answer: null,
            question: null,
        };
    },

    mounted() {
        const self = this;

        self.questionId = self.$route.params.question_id;

        api.getQuestion(self.questionId).then((response) => {
            self.question = response.data;
        });

        self.loadEnquiry();

        self.refreshHandler = setTimeout(() => {
            self.load();
        }, 5 * 60 * 1000);
    },

    watch: {
        options: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        // radius() {
        //     this.load();
        // },
        dialog(val) {
            val || this.close();
        },
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New Answer" : "Edit Answer";
        },

        computedHeaders() {
            const self = this;
            return this.headers.filter(function (item) {
                if (item.value === 'esgPerc' && !self.isContractorOrSubcontractor()) {
                    return false;
                }

                return !(item.value === 'last_name' && self.canAskQuestion());
            });
        },
    },

    methods: {
        showSupplierDetails(item) {
            const self = this;
            if (this.expanded.indexOf(item) > -1 && this.displayUserInfo) {
                this.expanded = [];
                return;
            }

            this.displayComments = false;
            this.displayUserInfo = true;

            api.getUser(item.user_id).then((response) => {
                self.displayInfoUser = response.data;
            });

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

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_ph);
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isUser() {
            return permissions.hasRole(permissions.role_user);
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isContractorOrSubcontractor() {
            return this.isUser() || this.isContractor() || this.isAdmin();
        },

        updateSupplierInvoiceNo(e, item) {
            const self = this;

            self.loader = true;

            api
                .updateAnswerPartial(item.id, {'supplier_invoice_no': item.supplier_invoice_no})
                .then((response) => {
                    self.$store.commit("showSnackbar", {
                        message: "Supplier Invoice No has been successfully stored",
                        color: "success",
                    });
                })
                .catch((err) => {

                }).finally(() => {
                self.loader = false;
            })
        },

        viewEnquiryComments: function (item) {
            if (this.enquiryExpanded.indexOf(item) > -1) {
                this.enquiryExpanded = [];
                return;
            }
            this.enquiryExpanded = [item];
        },

        itemRowBackground: function (item) {
            if (item.quote_accepted_at) {
                return "highlight-row-accepted";
            }

            return item.is_preferred_supplier ? "highlight-row" : "";
        },

        loadEnquiry() {
            const self = this;
            self.loader = true;

            api.loadQuestions({'id': this.questionId}).then((response) => {
                self.enquiryItems = response.data.data;

                self.loader = false;
            })
                .catch((err) => {
                    self.loader = false;
                });
        },

        load() {
            const self = this;
            self.loader = true;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
              //  radius: self.radius,
                branchId: self.branchId
            };

            api
                .loadAnswers(this.questionId, params)
                .then((response) => {
                    self.items = response.data.data;

                    self.serverItemsLength = response.data.total;

                    self.loader = false;
                })
                .catch((err) => {
                    self.loader = false;
                });
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        acceptItem(quote) {
            const self = this;

            if (quote.quote_accepted_at) {
                if (confirm("Are you sure you want to mark this quote as not accepted?")) {
                    self.loader = true;
                    api.unacceptQuote(quote.id).then((response) => {
                        self.load();

                        self.loader = false;
                    });
                }

                return;
            }

            if (confirm("Are you sure you want to accept this quote?")) {
                self.loader = true;
                api.acceptQuote(quote.id).then((response) => {
                    self.load();

                    self.loader = false;
                });
            }
        },

        deleteItem(answer) {
            const self = this;

            if (confirm("Are you sure you want to delete this quote?")) {
                self.loader = true;
                api.deleteAnswer(answer.id).then((response) => {
                    self.load();

                    self.loader = false;
                });
            }
        },

        viewItem(answer) {
            const self = this;

            answer.newMsgQty = 0;
            this.answer = answer;
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

        isAnswerer() {
            return permissions.hasRole(permissions.role_company) || permissions.can(permissions.manage_branches);
        },

        canAnswer() {
            return permissions.can(permissions.create_quote_ph);
        },

        canEditAnswer(answer) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return answer.user_id === this.user().id;
        },
    },
};
</script>

<style>
</style>
