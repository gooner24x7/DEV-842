<template>
    <div style="margin: 1em">
<!--        <h2>Materials & Hire</h2>-->

        <div style="position: relative;">
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
                    <v-dialog v-model="dialogVirtualExpo" max-width="600px">
                        <virtual-expo-random :item="dialogVirtualExpoItem"
                                             v-on:close="dialogVirtualExpo=false"></virtual-expo-random>
                    </v-dialog>

                    <v-dialog v-model="dialogMulti" max-width="600px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                v-if="canAskQuestion()"
                                class="m-2"
                                color="primary"
                                style="left: 0"
                                v-bind="attrs"
                                v-on="on"
                            >
                                New Enquiry
                            </v-btn>

                        </template>
                        <question-form-multi
                            :visible="dialogMulti"
                            v-on:cancel="closeMulti"
                            v-on:saved="updatedMulti"
                        />
                    </v-dialog>

                    <v-dialog v-model="dialog" max-width="900px">
                        <question-form
                            v-model="editedItem"
                            :title="formTitle"
                            :projects="projects"
                            :worksPackages="worksPackages"
                            v-on:cancel="close"
                            v-on:saved="updated"
                        />
                    </v-dialog>

                    <v-dialog v-model="dialogUploads" max-width="500px">
                        <attachments-dialog
                            :parentId="dialogUploadItem.id"
                            type="Question"
                            title="Enquiry"
                            @close="dialogUploads=false"
                            :userCanEdit="user().id === dialogUploadItem?.user_id"
                        ></attachments-dialog>
                    </v-dialog>

                    <v-dialog v-model="dialogOtherMerchants" max-width="900px">
                        <search-places :searchText="searchPlacesText"></search-places>
                    </v-dialog>

                    <v-row style="margin-top: 10px;">
                        <v-col>
                            <v-autocomplete
                                v-model="filters.is_quoted"
                                :items="['yes', 'no']"
                                :search-input.sync="searchQuoted"
                                clearable
                                dense
                                label="Already Quoted"
                                outlined
                            ></v-autocomplete>
                        </v-col>
                        <v-col>
                            <v-autocomplete
                                v-model="filters.is_ignored"
                                :items="['yes', 'no']"
                                :search-input.sync="searchIgnored"
                                clearable
                                dense
                                label="Skipped"
                                outlined
                            ></v-autocomplete>
                        </v-col>
                        <v-col>
                            <v-autocomplete
                                v-model="filters.productIds"
                                :items="products"
                                :loading="filtersLoader"
                                :search-input.sync="searchProduct"
                                chips
                                dense
                                item-text="name"
                                item-value="id"
                                label="Product"
                                multiple
                                outlined
                                small-chips
                            ></v-autocomplete>
                        </v-col>
                        <v-col>
                            <v-autocomplete
                                v-model="filters.projectIds"
                                :items="projects"
                                :loading="filtersLoader"
                                :search-input.sync="searchProject"
                                chips
                                dense
                                item-text="name"
                                item-value="id"
                                label="Project"
                                multiple
                                outlined
                                small-chips
                            ></v-autocomplete>
                        </v-col>
                        <v-col>
                            <v-autocomplete
                                v-model="filters.worksPackageIds"
                                :items="worksPackages"
                                :loading="filtersLoader"
                                :search-input.sync="searchWorksPackage"
                                chips
                                dense
                                item-text="name"
                                item-value="id"
                                label="Works Package"
                                multiple
                                outlined
                                small-chips
                            ></v-autocomplete>
                        </v-col>
                    </v-row>
                </template>

                <template v-slot:header.company_number="{ header }">
                    {{ header.text }}

                    <v-tooltip top>
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn
                                color="black"
                                icon
                                small
                                style="margin: 0"
                                v-bind="attrs"
                                v-on="on"
                            >
                                <v-icon>
                                    mdi-information
                                </v-icon>
                            </v-btn
                            >
                        </template>
                        <span>
                            We have added a quick link to credit safe to search for<br>
                            this company with the company number that we hold for them.<br>
                            Please check all the details on credit safe that the number<br>
                            represents the correct company first as we are helping with<br>
                            this link for efficiency and are not liable for the<br>
                            information / results shown or held by credit safe.]
                        </span>
                    </v-tooltip>
                </template>

                <template v-slot:expanded-item="{ headers, item }">
                    <td :colspan="headers.length">
                        <div class="expanded-comment">
                            <pre>{{ item.comment }}</pre>
                        </div>
                    </td>
                </template>

                <template v-slot:item.reference_id="{ item }">
                    {{ item.reference_id }}
                </template>

                <template v-slot:item.comment="{ item }">
                    {{ item.comment | limit20Chars }}
                </template>

                <template v-slot:item.created_at="{ item }">
                    {{ item.created_at | formatDate }}
                </template>

                <template v-slot:item.published_at="{ item }">
                    {{ item.published_at | formatDate }}
                </template>

                <template v-slot:item.attachments="{ item }">
                    <a
                        href="#"
                        v-if="item.attachments.length > 0"
                        v-on:click="(e) => { e.preventDefault(); dialogUploadItem = item; dialogUploads = true; }"
                    >
                        <v-icon>mdi-download</v-icon>
                    </a>
                </template>

                <template v-slot:item.is_quoted="{ item }">
                    <v-checkbox :input-value="!!item.is_quoted" readonly></v-checkbox>
                </template>

                <template v-slot:item.is_ignored="{ item }">
                    <v-checkbox :input-value="!!item.is_ignored" readonly></v-checkbox>
                </template>

                <template v-slot:item.product_type="{ item }">
                    {{ (item.product_type === 1) ? 'Hire Enquiry' : 'Purchase Enquiry' }}
                </template>

                <template v-slot:item.type="{ item }">
                    <div class="type-chip" :style="getChipStyle(item.type)">
                        {{ item.type }}
                    </div>
                </template>

                <template v-slot:item.id="{ item }">
                    <span>{{ item.id }}<span
                        v-if="item.ref_archived_enquiry_id"> (restored {{ item.ref_archived_enquiry_id }})</span></span>
                </template>

                <template v-slot:item.assigned="{ item }">
                    <ul>
                        <li v-for="user in (item.assigned || [])">{{
                                isCurrentUser(user.id) ? 'You' : user.username
                            }}
                        </li>
                    </ul>
                </template>

                <template v-if="(isCompany || isAdmin)" v-slot:item.company_number="{ item }">
                    <a v-if="item.company_number.length"
                       :href="'https://app.creditsafe.com/search?countries=GB&limit=24&name=' + item.company_number + '&page=1'"
                       target="_blank">Check Creditsafe</a>
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="text-center">
                        <v-btn
                            v-if="canEditQuestion(item)"
                            class="mr-2"
                            color="primary"
                            small
                            @click="editItem(item)"
                        >
                            {{ getEditButtonLabel(item) }}
                        </v-btn>

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

                        <v-tooltip top>
                            <template v-slot:activator="{ on, attrs }">
                                <v-btn
                                    v-if="canArchiveQuestion(item)"
                                    class="mr-2"
                                    color="black"
                                    icon
                                    small
                                    v-bind="attrs"
                                    @click="archiveItem(item)"
                                    v-on="on"
                                >
                                    <v-icon>
                                        mdi-folder-open
                                    </v-icon>
                                </v-btn
                                >
                            </template>
                            <span>Archive Enquiry</span>
                        </v-tooltip>
                    </div>
                    <v-badge
                        :content="item.newQuotesQty"
                        :overlap="true"
                        :value="showUnreadAnswersIndicator(item)"
                        class="large-right"
                        color="red"
                    >
                        <v-btn color="primary" small @click="showAnswers(item)">{{ showAnswersLabel() }}</v-btn>
                    </v-badge>

                    <v-badge
                        :content="item.seenUnquotedQty ?? 0"
                        :overlap="true"
                        :value="item.seenUnquotedQty > 0"
                        color="red"
                    >
                        <v-btn v-if="isAdmin() || isCustomerSuccessUser()" color="primary" small @click="showStatsReceivedQuoted(item)">
                            Merchants
                        </v-btn>
                    </v-badge>

                    <v-btn v-if="isAdmin() || isCustomerSuccessUser()" color="primary" small @click="showOtherMerchantsDialog(item)">
                        Other Merchants
                    </v-btn>

                    <v-btn v-if="userCanBeAssigned(item)" color="primary" small @click="toggleAssign(item)">
                        {{ assignToggleButtonLabel(item) }}
                    </v-btn>

                    <v-btn v-if="userCanIgnore(item)" color="primary" small @click="toggleIgnore(item)">
                        {{ ignoreToggleButtonLabel(item) }}
                    </v-btn>

                    <login-as-button :user-id="item.user_id" v-if="isAdmin()"></login-as-button>

                </template>
            </v-data-table>
        </div>

        <v-dialog v-model="chatDialog" max-width="500px">
            <chat-enquiry
                :enquiry="question"
                :interlocutorId="(!isSelectChat && question) ? question.user_id: undefined"
                :isSelectChat="isSelectChat"
                :visibility="chatDialog"
                v-on:close="chatDialog = false"
            />
        </v-dialog>
    </div>
</template>

<script>
import api from "../common/api.js";
import permissions from "../common/permissions.js";
import QuestionForm from "../components/QuestionForm";
import QuestionFormMulti from "../components/QuestionFormMulti";
import AttachmentsDialog from "../components/AttachmentsDialog";
import ChatEnquiry from "../components/ChatEnquiry";
import VirtualExpoRandom from "../components/VirtualExpoRandom";
import SearchPlaces from "../components/SearchPlaces.vue";
import LoginAsButton from "../components/LoginAsButton.vue";

export default {
    components: {
        LoginAsButton,
        SearchPlaces,
        AttachmentsDialog,
        QuestionForm, QuestionFormMulti,
        ChatEnquiry,
        VirtualExpoRandom
    },
    data() {
        return {
            refreshHandler: null,
            loader: false,
            filtersLoader: false,
            dialogUploadItem: {},
            searchProduct: '',
            searchProject: '',
            searchWorksPackage: '',
            searchQuoted: '',
            searchIgnored: '',
            isSelectChat: false,
            products: [],
            projects: [],
            worksPackages: [],
            filters: {
                productIds: [],
                projectIds: [],
                worksPackageIds: [],
                is_quoted: false,
                is_ignored: false,
            },
            headers: [
                {
                    text: "ID",
                    value: "id",
                    sortable: true,
                },
                {
                    text: "Reference ID",
                    value: "reference_id",
                    sortable: false,
                },
                {
                    text: "Date Created",
                    value: "created_at",
                    sortable: true,
                },
                {
                    text: "Date Published",
                    value: "published_at",
                    sortable: true,
                },
                {
                    text: "Creditsafe",
                    value: "company_number",
                    sortable: false,
                    align: 'center'
                },
                {
                    text: "Business Name",
                    value: "first_name",
                    sortable: true,
                },
                {
                    text: "Postcode",
                    value: "postcode",
                    sortable: true,
                },
                {
                    text: "Days",
                    value: "days",
                    sortable: true,
                },
                {
                    text: "Live/Tender",
                    value: "type",
                    sortable: true,
                },
                {
                    text: "Open/Closed",
                    value: "scope_str",
                    sortable: true,
                },
                {
                    text: "Product",
                    value: "name",
                    sortable: true,
                },
                {
                    text: "Product Type",
                    value: "product_type",
                    sortable: true,
                },
                {
                    text: "Project",
                    value: "project_name",
                    sortable: true,
                },
                {
                    text: "Works Package",
                    value: "works_package_name",
                    sortable: true,
                },
                {
                    text: 'Documents',
                    value: 'attachments',
                    sortable: false,
                },
                {
                    text: 'Assigned To',
                    value: 'assigned',
                    sortable: false,
                },
                {
                    text: 'Total Quotes',
                    value: 'total_quotes',
                    sortable: false,
                },
                {
                    text: 'Quoted',
                    value: 'is_quoted',
                    sortable: false,
                },
                {
                    text: 'Skipped',
                    value: 'is_ignored',
                    sortable: false,
                },
                {
                    text: "",
                    value: "actions",
                    sortable: false,
                },
            ],
            expanded: [],
            singleExpand: true,
            items: [],
            serverItemsLength: 0,
            options: {
                page: 1,
                itemsPerPage: 10,
                sortBy: ["published_at"],
                sortDesc: [true],
                groupBy: [],
                groupDesc: [],
                multiSort: false,
                mustSort: false,
            },
            dialog: false,
            dialogMulti: false,
            dialogVirtualExpo: false,
            dialogVirtualExpoItem: {},
            dialogUploads: false,
            dialogOtherMerchants: false,
            editedIndex: -1,
            editedItem: {
                postcode: "",
                comment: "",
                works_package_id: 0,
                project_id: 0,
                product_id: 0,
                manufacturer_product_selected: [],
                days: "",
                editId: null,
                type: "",
                product_type: null,
                status: null,
                scope: null,
            },
            defaultItem: {
                postcode: "",
                comment: "",
                works_package_id: 0,
                project_id: 0,
                product_id: 0,
                manufacturer_product_selected: [],
                days: "",
                editId: null,
                type: "",
                product_type: null,
                status: null,
                scope: null,
            },
            question: null,
            chatDialog: false,
            searchPlacesText: ""
        };
    },

    async mounted() {
        const self = this;

        await self.loadWorksPackageOptions();
        await self.loadProjectOptions();
        await self.loadProductOptions();

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
        filters: {
            handler(n, o) {
                this.load();
            },
            deep: true,
        },
        dialog(val) {
            val || this.close();
        },
        dialogMulti(val) {
            val || this.closeMulti();
        },
    },

    computed: {
        computedHeaders() {
            const self = this;
            return this.headers.filter(function (item) {
                if (item.value === 'total_quotes' && (self.isCompany() || self.isBranchManager())) {
                    return false;
                }

                if (item.value === 'attachments' && !self.isCompany()) {
                    return false;
                }

                if (item.value === 'company_number' && !(self.isCompany() || self.isAdmin())) {
                    return false;
                }

                if (item.value === 'is_quoted' && !permissions.can(permissions.create_quote_ph)) {
                    return false;
                }

                if (item.value === 'last_name' && !self.canAskQuestion()) {
                    return false;
                }

                if (item.value === 'id' && !self.isAdmin()) {
                    return false;
                }

                if (item.value === 'is_ignored' && !self.isCompany()) {
                    return false;
                }

                return true;
            });
        },

        formTitle() {
            return this.editedIndex === -1 ? "New Enquiry" : "Edit Enquiry";
        },
    },

    methods: {
        viewItem(question) {
            const self = this;

            question.newMsgQty = 0;
            self.question = question;
            self.question.product = {name: self.question.name}
            self.chatDialog = !self.chatDialog;
            self.isSelectChat = question.user_id === self.user().id;
        },

        isAdmin() {
            return permissions.hasRole(permissions.role_admin);
        },

        isCustomerSuccessUser() {
            return permissions.hasRole(permissions.role_customer_success_admin);
        },

        isCompany() {
            return permissions.hasRole(permissions.role_company);
        },

        isBranchManager() {
            return permissions.hasRole(permissions.role_branch_manager);
        },

        async toggleAssign(item) {
            const self = this;
            await api.toggleAssign(item.id)

            await self.load();
        },

        isCurrentUser(userId) {
            const self = this;

            return self.user().id === userId;
        },

        userHasBillingUser(item) {
            const self = this;

            let user = self.user()

            return user && user.billing_user_id > 0;
        },

        userCanBeAssigned(item) {
            const self = this;
            if (!self.userHasBillingUser(item)) {
                return false;
            }

            return self.user().can_assign_to_enquiries;
        },

        userCanIgnore(item) {
            return permissions.can(permissions.create_quote_ph);
        },

        async toggleIgnore(item) {
            const self = this;
            await api.togglePurchaseHireIgnore(item.id)
            await self.load();
        },

        assignToggleButtonLabel(item) {
            const self = this;

            let ids = item.assigned.map((item) => {
                return item.id;
            })

            return (ids.indexOf(self.user().id) < 0) ? "Assign" : "Remove assignment";
        },

        ignoreToggleButtonLabel(item) {
            const self = this;

            return (item.is_ignored === 1) ? 'Remove "Skipped"' : 'Skip';
        },

        showUnreadAnswersIndicator(item) {
            if (!this.canAskQuestion()) {
                return false;
            }

            return item.newQuotesQty > 0;
        },

        showAnswersLabel() {
            return (!permissions.can(permissions.create_quote_ph)) ? 'Quotes' : 'Quote';
        },

        itemRowBackground: function (item) {
            if (item.status === 1) {
                return "grey-row mnrow";
            }

            return item.is_preferred_supplier ? "highlight-row mnrow" : "mnrow";
        },

        viewComments: function (item) {
            if (this.expanded.indexOf(item) > -1) {
                this.expanded = [];
                return;
            }

            this.expanded = [item];
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
                worksPackageIds: self.filters.worksPackageIds,
                projectIds: self.filters.projectIds,
                productIds: self.filters.productIds,
            };

            if (self.filters.is_quoted) {
                params.isQuoted = self.filters.is_quoted
            }

            if (self.filters.is_ignored) {
                params.isIgnored = self.filters.is_ignored;
            }

            try {
                const response = await api.loadQuestions(params)

                self.items = response.data.data;
                self.serverItemsLength = response.data.total;
                self.hideNewEnquiriesIndicator();
            } catch (e) {
            }

            self.loader = false;
        },

        async loadWorksPackageOptions(search = '', projectIds = []) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.worksPackageOptions(search, projectIds);

            self.worksPackages = resp.data;
            self.filtersLoader = false;
        },

        async loadProjectOptions(searchVal = null) {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.projectOptions(searchVal ?? '');

            self.projects = resp.data
            self.filtersLoader = false;
        },

        async loadProductOptions() {
            const self = this;

            self.filtersLoader = true;

            const resp = await api.productOptions([1, 2]);
            self.products = resp.data;

            self.filtersLoader = false;
        },

        hideNewEnquiriesIndicator() {
            const self = this;
            setTimeout(() => self.$store.commit('setSession', {
                ...this.$store.getters.getSession,
                newEnquiries: 0,
            }), 3000);
        },

        editItem(item) {
            this.editedIndex = this.items.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        async deleteItem(question) {
            const self = this;

            if (confirm("Are you sure you want to delete this question?")) {
                self.loader = true;
                await api.deleteQuestion(question.id)
                await self.load();
                self.loader = false;
            }
        },

        async archiveItem(question) {
            const self = this;

            self.loader = true;
            await api.archiveQuestion(question.id)

            await self.load();
            self.loader = false;
        },

        showAnswers(inquiry) {
            this.$router.push({
                name: "answers",
                params: {question_id: inquiry.id},
            });
        },

        showStatsReceivedQuoted(inquiry) {
            this.$router.push({
                name: "seen-by-merchants",
                params: {inquiry_id: inquiry.id, lat: inquiry.lat, long: inquiry.long},
            });
        },

        showOtherMerchantsDialog(item) {
            if (item.postcode) {
                this.searchPlacesText = item.postcode + ' Local Builders Merchant';
            }

            this.dialogOtherMerchants = true;
        },

        user() {
            return this.$store.getters.getSession.user;
        },

        canAskQuestion() {
            return permissions.can(permissions.create_enquiry_ph);
        },

        canArchiveQuestion(question) {
            if (permissions.hasRole(permissions.role_admin)) {
                return true;
            }

            return question.user_id === this.user().id;
        },

        canEditQuestion(question) {
            const self = this;

            if (question.user_id === self.user().id && (question.status === 1)) {
                return true;
            }

            return permissions.hasRole(permissions.role_admin);
        },

        async updatedMulti(events) {
            const self = this;
            this.dialogMulti = false;
            await self.load();

            const productIds = events.items.map((item) => {
                return item.question.product_id
            })

            const virtualExpo = await api.getRandomVirtualExpo(productIds)
            if (virtualExpo.data) {
                self.dialogVirtualExpoItem = virtualExpo.data
                self.$nextTick(() => {
                    self.dialogVirtualExpo = true;

                    api.newAnalyticsEvent({
                        'object_id': self.dialogVirtualExpoItem.id,
                        'object_type': 'virtual-expo',
                        'action': 'click',
                        'location': 'expo-random',
                        'url': window.location.pathname,
                    });
                });
            }
        },

        closeMulti() {
            this.dialogMulti = false;
        },

        updated() {
            const self = this;

            self.dialog = false;
            this.$nextTick(() => {
                self.editedItem = Object.assign({}, self.defaultItem);
                self.editedIndex = -1;
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

        getEditButtonLabel(question) {
            return question.status === 1 ? "Edit Draft" : "Edit";
        },

        getChipStyle(type) {
            let style = 'background-color: #f0f0f0';

            if (type === 'live') {
                style = 'background-color: #75e673';
            } else if (type === 'tender') {
                style = 'background-color: #fffa86';
            } else if (type === 'pre tender') {
                style = 'background-color: #ffcc82';
            }

            return style;
        }
    },
};
</script>

<style>
</style>
