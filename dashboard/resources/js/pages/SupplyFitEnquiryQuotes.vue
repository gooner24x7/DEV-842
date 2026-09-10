<template>
    <div style="margin: 1em">
<!--        <h2>Enquiries > Marketplace</h2>-->

        <supply-fit-enquiry-block :enquiryItems="enquiryItems"></supply-fit-enquiry-block>

        <h2>Enquiry Quotes</h2>

        <v-row>
            <v-col v-if="isAdmin() || isContractor()">
                <v-btn class="m-2" color="primary" dark @click="editCompanies()">Edit Scores</v-btn>
            </v-col>
        </v-row>

        <div style="position: relative; padding-top: 1em">
            <v-btn
                v-if="canAnswer() && !isEnquiryArchived()"
                @click="dialog = true"
                class="m-2"
                color="primary"
                style="left: 0 !important; top: 0 !important;"
                dark
            >
                Quote
            </v-btn>

            <v-dialog v-model="dialog" max-width="500px">
                <supply-fit-quote-form
                    v-model="editedItem"
                    :enquiryId="enquiryId"
                    :title="formTitle"
                    v-on:cancel="close"
                    v-on:saved="updated"
                />
            </v-dialog>

            <v-dialog v-model="editDialog" max-width="2500px">
                <edit-scores :sessions="selectedQuestionnaireSessionIds" :visible="editDialog"></edit-scores>
            </v-dialog>

            <v-data-table
                v-model="selectedQuotes"
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
                show-select
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
                            </v-btn>
                        </template>
                        <span>We take a 26 tonne truck and based on how close to the live site per mile work out the %<br>
                            savings of emissions from the furthest to closest supplier purchased from.</span>
                    </v-tooltip>
                </template>

                <template v-slot:top>
                    <div v-if="!isAnswerer()" class="float-right" style="width: 150px">
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

                <template v-slot:item.is_sme="{ item }">
                    <div v-if="item.is_sme">
                        <div class="circle-icon">
                            <p>SME</p>
                        </div>
                    </div>
                </template>

                <template v-slot:item.quote_accepted_at="{ item }">
                    <v-checkbox
                        v-if="isContractor()"
                        :input-value="!!item.quote_accepted_at"
                        :label="item.quote_accepted_at | formatDateTime"
                        readonly
                        v-on:click="switchAccepted(item)"
                    ></v-checkbox>

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
                            </v-btn>
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
                            </v-btn>
                        </template>
                        <span>Live Chat</span>
                    </v-tooltip>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <div v-if="displayComments" class="expanded-comment">
                            <pre>{{ item.comment }}</pre>
                        </div>
                        <div v-if="displayUserInfo" class="expanded-user-info">
                            <h4 style="padding-left: 24px;">{{ displayInfoUser?.first_name }}</h4>
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
                    <span class="text-no-wrap">{{ item.price | formatPrice }}</span>
                </template>

                <template v-slot:item.total_score="{item}">
                    <v-btn v-if="isContractor()" color="primary" small v-on:click="showScores(item)">
                        {{ item.total_score }}
                    </v-btn>
                    <v-btn v-if="isContractor()" color="primary" small v-on:click="resendQuestionnaire(item)">
                        Resend Questionnaire
                    </v-btn>
                </template>

                <template v-slot:item.resources_available="{ item }">
                    {{ item.resources_available ? 'Yes' : 'No' }}
                </template>

                <template v-slot:item.is_competent="{ item }">
                    {{ item.is_competent ? 'Yes' : 'No' }}
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a v-if="item.attachments" href="#" v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn
                            v-if="canEditAnswer(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="editItem(item)"
                        >
                            Edit Quote
                        </v-btn>
                        <v-btn
                            v-if="canEditAnswer(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="deleteItem(item)"
                        >
                            Delete Quote
                        </v-btn>
                        <v-btn
                            v-if="showProgressBtn(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="viewQuoteProgress(item)"
                        >
                            {{ getProgressBtnLabel() }}
                        </v-btn>
                    </div>
                </template>
            </v-data-table>
        </div>

<!--        <div v-if="isContractor()">-->
<!--            <supply-fit-quotes-overview :enquiryId="enquiryId"></supply-fit-quotes-overview>-->
<!--        </div>-->

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                :parentId="dialogUploadItem.id"
                type="SupplyFitEnquiryQuote"
                title="Quote"
                @close="dialogUploads=false"
                :userCanEdit="user().id === dialogUploadItem?.user_id"
            ></attachments-dialog>
        </v-dialog>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat-supply-fit-quote
                :supplyFitEnquiry="enquiry"
                :supplyFitEnquiryQuote="quote"
                :visibility="chatDialog"
                v-on:close="chatDialog = false"
            />
        </v-dialog>

        <v-dialog v-model="dialogScores" max-width="500px">
            <v-card>
                <v-card-title>Questionnaire</v-card-title>
                <v-card-text>
                    <ul>
                        <li v-for="answer in questionnaireAnswers">
                            <b>{{ answer.question_text }}</b>:
                            {{
                                (answer.question_type === 'yes_no') ? (answer.is_yes ? 'yes' : 'no') : answer.answer_text
                            }}<br>
                            Score: {{ answer.score }}
                        </li>
                    </ul>
                </v-card-text>
            </v-card>
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
import SupplyFitQuoteForm from "../components/SupplyFitQuoteForm";
import SupplyFitEnquiryBlock from "../components/SupplyFitEnquiryBlock";
import ChatSupplyFitQuote from "../components/ChatSupplyFitQuote";
import SupplyFitQuotesOverview from '../components/SupplyFitQuotesOverview';
import EditScores from "../components/EditScores.vue";

export default {
    components: {
        EditScores,
        SupplyFitQuotesOverview,
        ChatSupplyFitQuote,
        SupplyFitEnquiryBlock,
        SupplyFitQuoteForm,
        EnquiryBlock,
        AttachmentsDialog,
        AnswerForm,
        Chat,
    },
    data() {
        return {
            selectedQuotes: [],
            loader: false,
            expanded: [],
            singleExpand: true,
            editDialog: false,
            refreshHandler: null,
            dialogUploads: false,
            dialogUploadItem: {},
            enquiryItems: [],
            displayComments: false,
            displayUserInfo: false,
            displayInfoUser: null,
            dialogScores: false,
            showScoreUserId: '',
            questionnaireAnswers: [],
            selectedQuestionnaireSessionIds: [],
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
                    text: "",
                    value: "is_sme",
                    sortable: false,
                },
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
                    text: 'Resources Available',
                    value: 'resources_available',
                    sortable: true,
                },
                {
                    text: 'Is Competent',
                    value: 'is_competent',
                    sortable: true,
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
                    text: '',
                    value: 'report_icon',
                    sortable: false,
                },
                {
                    text: "Supplier Invoice No.",
                    value: "supplier_invoice_no",
                    sortable: true,
                    width: '200px'
                },
                // {
                //     text: 'Account Form',
                //     value: 'creditApplicationFormUrl',
                //     sortable: false,
                // },
                {
                    text: "ESG saving",
                    value: 'esgPerc',
                    sortable: false,
                },
                {
                    text: 'Questionnaire Score',
                    value: 'total_score',
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
            creditsafeInfo: {}
        };
    },

    async mounted() {
        const self = this;

        self.enquiryId = self.$route.params.enquiry_id;
        await self.loadEnquiry();

        self.refreshHandler = setTimeout(() => {
            self.load();
        }, 5 * 60 * 1000);
    },

    watch: {
        selectedQuotes: {
            handler(n, o) {
                this.selectedQuestionnaireSessionIds = n.map((quote) => {
                    return {
                        'id': quote.questionnaire_session_id,
                        'quote_id': quote.id,
                        'user_id': quote.user_id,
                        'name': quote.first_name,
                        'score': quote.total_score,
                        'is_answered': quote.questionnaire_is_answered,
                        'is_preferred_subcontractor': quote.is_preferred_subcontractor,
                    };
                });
            },
            deep: true,
        },
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
            return this.headers.filter(function (item) {
                if (item.value === 'total_score' && !self.isContractor()) {
                    return false;
                }

                return !(item.value === 'last_name' && self.canAskQuestion());
            });
        },
    },

    methods: {
        editCompanies() {
            this.editDialog = true
        },

        async resendQuestionnaire(item) {
            try {
                await api.resendQuestionnaireForQuote(item.id);
                alert('Questionnaire has been sent');
            } catch (e) {
                console.log(e);
            }
        },

        isContractor() {
            return permissions.hasRole(permissions.role_contractor);
        },

        isSubContractor() {
            return permissions.hasRole(permissions.role_user);
        },

        isAdmin() {
            if (typeof this.$store.getters.getSession.user.role_name != 'object') {
                return false;
            }

            return this.$store.getters.getSession.user.role_name.join().toLowerCase().includes("admin");
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

            console.log(item);

            if (self.expanded.indexOf(item) > -1 && self.displayUserInfo) {
                self.expanded = [];
                return;
            }

            self.displayComments = false;
            self.displayUserInfo = true;

            const response = await api.getUser(item.user_id);
            const response2 = await api.getCreditsafeInfo({user_ids: [item.user_id]});

            self.displayInfoUser = response.data;
            self.creditsafeInfo = response2.data[item.user_id] ?? {};

            console.log(self.displayInfoUser);
            console.log(self.creditsafeInfo);

            self.expanded = [item];
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
            return permissions.can(permissions.create_enquiry_sf);
        },

        async updateSupplierInvoiceNo(e, item) {
            const self = this;

            self.loader = true;

            try {
                await api.updateSupplyFitQuotePartial(item.id, {'supplier_invoice_no': item.supplier_invoice_no});
                self.$store.commit("showSnackbar", {
                    message: "Supplier Invoice No has been successfully stored",
                    color: "success",
                });
            } catch (e) {
                console.log(e);
            }

            self.loader = false;
        },

        viewEnquiryComments: function (item) {
            if (this.enquiryExpanded.indexOf(item) > -1) {
                this.enquiryExpanded = [];
                return;
            }
            this.enquiryExpanded = [item];
        },

        async loadEnquiry() {
            const self = this;
            self.loader = true;

            try {
                const response = await api.loadSupplyFitEnquiries({'id': self.enquiryId});

                self.enquiryItems = response.data.data;
                self.enquiry = self.enquiryItems[0] ?? null;
            } catch (e) {
                console.log(e);
            }

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
                const response = await api.loadSupplyFitEnquiryQuotes(this.enquiryId, params)

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

        async deleteItem(quote) {
            const self = this;

            if (confirm("Are you sure you want to delete this quote?")) {
                self.loader = true;
                await api.deleteSupplyFitEnquiryQuote(quote.id)
                await self.load();

                self.loader = false;
            }
        },

        viewItem(quote) {
            const self = this;

            quote.newMsgQty = 0;
            self.quote = quote;
            self.chatDialog = !self.chatDialog;
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

        async showScores(item) {
            const self = this;
            if (!item.sessionId) {
                return;
            }
            const response = await api.loadAnswersWithScores([item.sessionId])

            self.questionnaireAnswers = (response.data[item.sessionId] ?? []);
            self.dialogScores = true;
            self.showScoreUserId = item.user_id;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        isAnswerer() {
            return permissions.hasRole(permissions.role_user);
        },

        canAnswer() {
            return permissions.hasRole(permissions.role_user) || permissions.hasRole(permissions.role_admin);
        },

        canEditAnswer() {
            return permissions.hasRole(permissions.role_admin);
        },

        showReport(item) {
            this.$router.push({
                name: "contractor-report",
                query: {
                    id: item.project_id
                }
            });
        },

        itemRowBackground: function (item) {
            if (item.is_preferred_subcontractor && item.is_client_preferred_subcontractor) {
                return "highlight-row outline-row";
            } else if (item.is_preferred_subcontractor) {
                return "highlight-row";
            } else if (item.is_client_preferred_subcontractor) {
                return "highlight-row-yellow";
            }

            return "";
        },

        showProgressBtn(item) {
            const self = this;

            if (item.quote_accepted_at === null) {
                return false;
            }

            return (self.isContractor() || self.isSubContractor() || self.isAdmin());
        },

        getProgressBtnLabel() {
            const self = this;

            return self.isSubContractor() ? 'Manage Progress' :  'View Progress';
        },

        viewQuoteProgress(item) {
            this.$router.push({
                name: "supply-fit-quote-progress",
                query: {
                    enquiry_id: this.enquiryId,
                    quote_id: item.id
                }
            });
        },

        isEnquiryArchived() {
            const self = this;

            return self.enquiry?.archived_at !== null;
        }
    },
};
</script>

<style>
.circle-icon {
    /* Step 1: Create a perfect square */
    width: 40px;
    height: 40px;

    /* Step 2: Turn the square into a circle */
    border-radius: 50%;

    /* Step 3: Use Flexbox to perfectly center the content */
    display: flex;
    flex-direction: column; /* Stack icon and text vertically */
    justify-content: center; /* Vertical alignment */
    align-items: center;    /* Horizontal alignment */

    /* Step 4: Styling & aesthetics */
    background-color: #264dec;
    color: #ffffff;
    font-family: sans-serif;

    /* Optional: Prevent text overflowing the circle boundaries */
    overflow: hidden;
    box-sizing: border-box;
}

/* Optional styling tweak for the inner text */
.circle-icon p {
    margin: 0;
    font-size: 13px;
    font-weight: bold;
}
</style>
