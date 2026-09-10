<template>
    <div style="margin: 1em">
<!--        <h2>Enquiry</h2>-->

        <enquiry-block :enquiryItems="enquiryItems"></enquiry-block>

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
                    <v-btn
                        v-if="(canAnswer() && !isQuestionArchived())"
                        @click="dialog = true"
                        class="m-2"
                        color="primary"
                        style="left: 0 !important; top: 0 !important;"
                        dark
                    >
                        Quote
                    </v-btn>

                    <v-dialog v-model="dialog" max-width="500px">
                        <answer-form
                            v-model="editedItem"
                            :questionId="questionId"
                            :title="formTitle"
                            v-on:cancel="close"
                            v-on:saved="updated"
                        />
                    </v-dialog>

                    <v-btn
                        v-if="showCompareBtn()"
                        class="m-2"
                        color="primary"
                        dark
                        style="top: 0 !important; left: 100px !important"
                        @click="compareDisplay()"
                    >
                        Compare
                    </v-btn>

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

                    <v-btn class="m-2 float-right" color="primary" style="margin-right: 20px !important;"
                           @click="exportSpreadsheet()">Export
                    </v-btn>

                    <div style="clear:both"></div>
                </template>

                <template v-slot:item.compare_select="{ item, index }">
                    <v-checkbox @change="compareSelect(item, $event)"></v-checkbox>
                </template>

                <template v-slot:item.quote_accepted_at="{ item }">
                    <v-checkbox
                        :input-value="!!item.quote_accepted_at" :label="item.quote_accepted_at | formatDateTime"
                        v-on:click="switchAccepted(item)"
                        readonly
                    ></v-checkbox>
                </template>

                <template v-slot:item.checked_at="{ item }">
                    <v-checkbox
                        v-if="item.has_substitution"
                        :input-value="!!item.checked_at" :label="item.checked_at | formatDateTime"
                        v-on:click="switchChecked(item)"
                        readonly
                    ></v-checkbox>
                </template>

                <template v-slot:item.has_substitution="{ item }">
                    {{ item.has_substitution ? 'Yes' : 'No' }}
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
                            </v-btn>
                        </template>
                        <span>View Comments</span>
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
                                <img v-if="displayUserInfo" :src="displayInfoUser.logo_url"
                                     style="width: 100%; height: auto;">
                            </v-col>
                            <!--                            <v-col>-->
                            <!--                                <h4>How scoring system works?</h4>-->
                            <!--                                <p>Price is scored based on lowest price</p>-->
                            <!--                                <p>ESG is scored based on ESG percentage saving</p>-->
                            <!--                                <p>Quote Time is based on who quoted the fastest</p>-->
                            <!--                            </v-col>-->
                            <!--                            <v-col class="quote-scores">-->
                            <!--                                <div>Price Score: {{ item.price_score }}</div>-->
                            <!--                                <div>ESG Score: {{ item.esg_score }}</div>-->
                            <!--                                <div>Quote Time: {{ item.time_score }}</div><br>-->
                            <!--                                <div>Total Score: {{ item.total_score }}</div>-->
                            <!--                            </v-col>-->
                        </v-row>
                    </td>
                </template>

                <template v-slot:item.esgPerc="{item}">
                    <div :title="'Distance: ' + (item.distance ?? 0).toFixed(2) + 'm, ' +
                    'Max distance: ' + (item.max_distance ?? 0).toFixed(2) + 'm'" style="white-space: nowrap;">
                        {{ (item.esgPerc ?? 0).toFixed(2) }}%
                    </div>
                </template>

                <!--                <template v-slot:item.total_score="{ item }">-->
                <!--                    <a href="#" @click="(e) => { e.preventDefault(); showSupplierDetails(item); }">{{-->
                <!--                            item.total_score-->
                <!--                        }}</a>-->
                <!--                </template>-->

                <template v-slot:item.price="{ item }">
                    <span class="text-no-wrap">{{ item.price | formatPrice }}</span>
                </template>

                <template v-slot:item.local_material_spend="{ item }">
                    <v-text-field
                        v-if="isUser() && (item.quote_accepted_at !== null)"
                        v-model="item.local_material_spend"
                        type="number"
                        @change="(e) => updateLocalMaterialSpend(e, item)"
                        prefix="£"
                        dense
                        hide-details
                        outlined
                    ></v-text-field>
                    <span v-else class="text-no-wrap">{{ item.local_material_spend | formatPrice }}</span>
                </template>

                <template v-slot:item.viewed_at="{ item, index }">
                    <v-checkbox :input-value="!!item.viewed_at" readonly></v-checkbox>
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a href="#" @click="openAttachments($event, item)">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.attachments_comp="{ item }">
                    <a v-if="!!item.checked_at" href="#" @click="openAttachmentsComp($event, item)">
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.creditApplicationFormUrl="{item}">
                    <a
                        v-if="item.creditApplicationFormUrl"
                        href="#"
                        target="_blank"
                        v-on:click="(e) => { openApplicationFormUrl(item) } "
                    >
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
                    </div>
                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="dialogUploads" max-width="500px">
            <attachments-dialog
                type="Answer"
                title="Quote"
                :parentId="dialogUploadItem.id"
                :description="dialogUploadDesc"
                :userCanEdit="dialogUploadCanEdit"
                @close="dialogUploads=false"
            ></attachments-dialog>
        </v-dialog>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat
                :answer="answer"
                :question="question"
                :visibility="chatDialog"
                v-on:close="chatDialog = false"
            />
        </v-dialog>

        <v-dialog v-model="compareDialog">
            <v-card>
<!--                <v-btn-->
<!--                    v-if="compareFiles.length > 0"-->
<!--                    class="mr-2"-->
<!--                    color="primary"-->
<!--                    small-->
<!--                    @click="exportQuoteComparison()"-->
<!--                >Export to Excel-->
<!--                </v-btn>-->
                <v-row align="start" no-gutters>
                    <v-col>
                        <v-btn class="primary mb-2 mt-2" v-on:click="exportToExcel();">Export to Excel</v-btn>
                    </v-col>
                </v-row>
                <v-row align="start" no-gutters>
                    <v-col v-for="(item, index) in compareFiles" :key="index">
                        <v-sheet class="pa-2 ma-2">
                            <object
                                :data="item"
                                height="100%"
                                style="min-height: 700px"
                                type="application/pdf"
                                width="100%"
                            ></object>
                        </v-sheet>
                    </v-col>
                </v-row>
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
import * as XLSX from 'xlsx'
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default {
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
            dialogUploadDesc: '',
            dialogUploadCanEdit: false,
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
                    text: "Edit",
                    value: "compare_select",
                    sortable: false,
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
                    text: "Price (ex VAT)",
                    value: "price",
                    sortable: true,
                },
                {
                    text: "Local Material Spend",
                    value: "local_material_spend",
                    sortable: true,
                    width: "150px"
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
                    text: 'Competency Documents',
                    value: 'attachments_comp',
                    sortable: false,
                },
                {
                    text: 'Competency Checked At',
                    value: 'checked_at',
                    sortable: true,
                },
                {
                    text: 'Has Substitution',
                    value: 'has_substitution',
                    sortable: true,
                },
                {
                    text: 'Chat',
                    value: 'chat',
                    sortable: false,
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
                    text: 'Accepted At',
                    value: 'quote_accepted_at',
                    sortable: true,
                },
                // {
                //     text: "Score",
                //     value: 'total_score',
                //     sortable: true,
                // },
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
                offers: "",
                type: "",
                has_substitution: 0,
                supplier_invoice_no: "",
                editId: null,
            },
            defaultItem: {
                price: 0,
                comment: "",
                offers: "",
                type: "",
                has_substitution: 0,
                supplier_invoice_no: "",
                editId: null,
            },
            questionId: 0,
            answer: null,
            question: null,
            compareDialog: false,
            compareQuoteIds: [],
            compareItems: [],
            compareFiles: [],
        };
    },

    async mounted() {
        const self = this;

        self.questionId = self.$route.params.question_id;

        const response = await api.getQuestion(self.questionId);
        self.question = response.data;

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

            return this.headers.filter(function (item) {
                if (item.value === 'checked_at' && !self.enquiryItems[0]?.high_risk) {
                    return false;
                }

                if (item.value === 'attachments_comp' && !self.enquiryItems[0]?.high_risk) {
                    return false;
                }

                if (item.value === 'total_score' && self.isMerchant()) {
                    return false;
                }

                if (item.value === 'esgPerc' && !self.isContractorOrSubcontractor()) {
                    return false;
                }

                if (item.value === 'viewed_at' && !self.isMerchant()) {
                    return false;
                }

                return !(item.value === 'last_name' && self.canAskQuestion());
            });
        },
    },

    methods: {
        async exportToExcel() {
            const self = this;
            try {
                const response = await api.exportQuotesComparison(this.questionId, self.compareQuoteIds);

                let blob = new Blob([response.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                let link = document.createElement('a');

                link.href = window.URL.createObjectURL(blob);
                link.download = `compare_quotes.xlsx`;
                link.click();
            } catch (e) {
                console.log(e);
            }
        },

        openApplicationFormUrl(item) {
            api.newAnalyticsEvent({
                'object_id': item.id,
                'object_type': 'purchase-&-hire-inquiry-quote',
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
            self.displayInfoUser = response.data;

            this.expanded = [item]
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

        switchChecked(item) {
            this.checkItem(item);
        },

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_ph);
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

        isContractorOrSubcontractor() {
            return this.isUser() || this.isContractor() || this.isAdmin();
        },

        async updateSupplierInvoiceNo(e, item) {
            const self = this;

            self.loader = true;

            await api.updateAnswerPartial(item.id, {'supplier_invoice_no': item.supplier_invoice_no});

            self.$store.commit("showSnackbar", {
                message: "Supplier Invoice No has been successfully stored",
                color: "success",
            });

            self.loader = false;
        },

        async updateLocalMaterialSpend(e, item) {
            const self = this;

            self.loader = true;

            await api.updateAnswerPartial(item.id, {'local_material_spend': item.local_material_spend});

            self.$store.commit("showSnackbar", {
                message: "Local material spend has been successfully stored",
                color: "success",
            });

            self.loader = false;
        },

        viewEnquiryComments: function (item) {
            if (this.enquiryExpanded.indexOf(item) > -1) {
                this.enquiryExpanded = [];
                return;
            }
            this.enquiryExpanded = [item];
        },

        itemRowBackground: function (item) {
            return item.is_preferred_supplier ? "highlight-row" : "";
        },

        async loadEnquiry() {
            const self = this;
            self.loader = true;

            const response = await api.loadQuestions({'id': this.questionId});
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
                const response = await api.loadAnswers(this.questionId, params);
                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
            } catch (e) {
                console.log(e)
            }

            self.loader = false;
        },

        async exportSpreadsheet() {
            const self = this;

            let params = {
                ...self.options,
                page: self.options.page,
                itemsPerPage: self.options.itemsPerPage,
                sortBy: self.options.sortBy[0],
                sortDesc: self.options.sortDesc[0] ? 1 : 0,
                radius: self.radius,
            };

            try {
                const response = await api.exportAnswers(this.questionId, params);

                // download the file
                // this solution requires responseType: 'arraybuffer' in api.js
                let blob = new Blob([response.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                let link = document.createElement('a');

                link.href = window.URL.createObjectURL(blob);
                link.download = `enquiry_quotes_${this.questionId}.xlsx`;
                link.click();

            } catch (e) {
                console.log(e);
            }
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async acceptItem(quote) {
            const self = this;

            if (self.isMerchant()) {
                return;
            }

            if (quote.quote_accepted_at) {
                if (confirm("Are you sure you want to mark this quote as not accepted?")) {
                    self.loader = true;
                    await api.unacceptQuote(quote.id);
                    await self.load();

                    self.loader = false;
                }

                return;
            }

            if (self.enquiryItems[0]?.high_risk && !quote.has_substitution) {
                let warning = "⚠️This quotation will be accepted without a competency check. Are you sure?"
                let competencyUsersStr = self.user().competency_users.join(', ');

                if (competencyUsersStr.length > 0) {
                    warning += "\n\n" + "If in doubt please check with: " + competencyUsersStr;
                }

                if (confirm(warning)) {
                    self.loader = true;
                    await api.acceptQuote(quote.id);
                    await self.load();

                    self.loader = false;
                }

                return;
            }

            if (self.enquiryItems[0]?.high_risk && quote.has_substitution && !quote.checked_at) {
                alert("This quote is high risk and must be competency checked before it can be accepted.");
                return;
            }

            if (confirm("Are you sure you want to accept this quote?")) {
                self.loader = true;
                await api.acceptQuote(quote.id);
                await self.load();

                self.loader = false;
            }
        },

        async checkItem(quote) {
            const self = this;

            let message = "This package may contain products or systems that require technical review, competency validation, " +
                "compliance evidence or formal approval before use. By continuing, you confirm you are authorised to make " +
                "decisions on behalf of your organisation or will route approvals to the appropriate competent person where required.";

            let userCanCheckCompetency = self.user().can_check_competency ?? false;

            if (self.isMerchant() || !userCanCheckCompetency) {
                let competencyUsersStr = self.user().competency_users.join(', ');
                let message2 = 'You do not have permission to mark quotes as competency checked.';

                if (competencyUsersStr.length > 0) {
                    message2 += ' Users with permission: ' + competencyUsersStr;
                }

                alert(message2);
                return;
            }

            if (quote.checked_at) {
                if (quote.quote_accepted_at) {
                    alert('You must mark the quote as not accepted before marking it as not competency checked.')
                    return;
                }

                if (confirm("Are you sure you want to mark this quote as not competency checked?")) {
                    self.loader = true;
                    await api.setQuoteCheckedAt(quote.id, false);
                    await self.load();

                    self.loader = false;
                }

                return;
            }

            if (confirm(message)) {
                self.loader = true;
                await api.setQuoteCheckedAt(quote.id, true);
                await self.load();

                self.loader = false;
            }
        },

        async deleteItem(answer) {
            const self = this;

            if (confirm("Are you sure you want to delete this quote?")) {
                self.loader = true;
                await api.deleteAnswer(answer.id)

                await self.load();

                self.loader = false;
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

        canEditAnswer() {
            return permissions.hasRole(permissions.role_admin);
        },

        compareSelect(item, value) {
            const self = this;

            // if (value && !self.compareQuoteIds.includes(item.id)) {
            if (value) {
                self.compareQuoteIds.push(item.id);
            } else {
                self.compareQuoteIds = self.compareQuoteIds.filter(function (val) {
                    return val !== item.id;
                });
            }
        },

        async compareDisplay() {
            const self = this;

            self.compareItems = self.items.filter(function (item) {
                return self.compareQuoteIds.includes(item.id);
            });

            self.compareFiles = [];

            for (const item of self.compareItems) {
                let fileSrc = null;

                if (item.attachments && item.attachments[0]) {
                    let ext = item.attachments[0].name.split('.').pop();
                    let blob = await this.getFileBlob(item.attachments[0].url);

                    if (ext === 'xls' || ext === 'xlsx') {
                        fileSrc = await this.convertXLS(blob);
                    } else if (ext === 'pdf') {
                        fileSrc = URL.createObjectURL(blob);
                    }
                } else if (item.comment) {
                    fileSrc = this.convertText(item.comment, item.id);
                }

                self.compareFiles.push(fileSrc);
            }

            self.compareDialog = true;
        },

        showCompareBtn() {
            const self = this;

            if (self.isMerchant()) {
                return false;
            }

            return self.compareQuoteIds.length > 0 && self.compareQuoteIds.length < 4;
        },

        async getFileBlob(url) {
            const response = await fetch(url);
            return await response.blob();
        },

        async convertXLS(blob) {
            return new Promise((resolve, reject) => {
                if (blob) {
                    try {
                        const reader = new FileReader();

                        reader.readAsArrayBuffer(blob);

                        reader.onload = (e) => {
                            const bstr = e.target.result;
                            const wb = XLSX.read(bstr, {type: "binary"});

                            const wsname = wb.SheetNames[0];
                            const ws = wb.Sheets[wsname];

                            let data = XLSX.utils.sheet_to_json(ws, {header: 1, defval: ""});

                            data = data.filter((row) => {
                                let rowEmpty = true;

                                row.forEach((value) => {
                                    if (value !== "") {
                                        rowEmpty = false;
                                    }
                                });

                                return !rowEmpty;
                            });

                            const doc = new jsPDF();

                            autoTable(doc, {
                                head: [data[0]],
                                body: data.splice(1),
                            });

                            let pdfSrc = doc.output("bloburl");

                            resolve(pdfSrc);
                        };
                    } catch (error) {
                        reject(error);
                    }
                } else {
                    reject('invalid blob');
                }
            });
        },
        convertText(text, quoteId = 0) {
            const doc = new jsPDF();

            let x = 10;
            let y = 15;

            if (quoteId) {
                doc.setFontSize(12);
                doc.text('Quote ID: ' + quoteId, x, y);
                y = y + 8;
            }

            doc.setFontSize(12);
            doc.text(text, x, y, {maxWidth: 190});

            return doc.output("bloburl");
        },
        async exportQuoteComparison() {
             const self = this;

             const files = [];

            for (const item of self.compareItems) {
                if (item.attachments[0]) {
                    files.push(item.attachments[0]);
                }
            }

            try {
                const response = await api.exportQuoteComparison(files);

                // download the file
                // this solution requires responseType: 'arraybuffer' in api.js
                let blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                let link = document.createElement('a');

                link.href = window.URL.createObjectURL(blob);
                link.download = `quote_comparison_${this.questionId}.xlsx`;
                link.click();

            } catch (e) {
                console.log(e);
            }
        },
        isQuestionArchived() {
            const self = this;

            return self.question?.archived_at !== null;
        },
        openAttachments(e, item) {
            const self = this;
            e.preventDefault();

            self.dialogUploadItem = item;
            self.dialogUploadCanEdit = self.user().id === item?.user_id
            self.dialogUploadDesc = '';
            self.dialogUploads = true;
        },
        openAttachmentsComp(e, item) {
            const self = this;
            e.preventDefault();

            self.dialogUploadItem = item;
            self.dialogUploadCanEdit = self.user().id === self.question?.user_id;
            self.dialogUploadDesc = 'competency';
            self.dialogUploads = true;
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
